<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Devolve o `asaas_wallet_id` de um recebedor que TEM subconta mas perdeu a
 * coluna — lendo o id do próprio Asaas, nunca de anotação.
 *
 * Nasceu de um caso concreto e de um impasse real. Em 19/10/2026 o
 * `asaas_wallet_id` da personal #7 foi APAGADO em produção, de propósito: a
 * wallet dela estava bloqueada por compliance e, no código da época, recebedor
 * sem wallet era cobrado SEM split (o dinheiro caía na plataforma e o repasse
 * virava manual). Era feio, mas vendia.
 *
 * Em 24/10 subiu a trava que faz `AsaasService::montarSplit()` ABORTAR sem
 * wallet, porque receita de terceiro na conta da plataforma é problema fiscal.
 * A partir daí o paliativo inverteu de sinal: a coluna vazia deixou de
 * destravar a venda e passou a ser a única coisa que a impede.
 *
 * E o estado é PIOR do que ter a wallet bloqueada, por um motivo que não é
 * óbvio: a trava de bloqueio é um TIMESTAMP que expira em
 * `AsaasService::HORAS_RETENTAR_SPLIT` e se retesta sozinha, então uma wallet
 * bloqueada volta a vender no instante em que o Asaas liberar, sem ninguém
 * fazer nada. A coluna VAZIA não expira: ela barra para sempre, e
 * `asaas:sincronizar-subcontas` filtra `whereNotNull('asaas_wallet_id')` — ou
 * seja, quem está assim saiu até da sincronização e ficou invisível.
 *
 * Por isso devolver o id é estritamente melhor que deixar vazio, mesmo sem
 * saber se o Asaas já liberou:
 *
 *   wallet liberada  → volta a vender imediatamente;
 *   ainda bloqueada  → UMA cobrança falha, o carimbo entra, e a trava passa a
 *                      retestar a cada 6h até liberar (e aí o aluno lê o texto
 *                      certo, "bloqueada pelo banco", não "finalizando o
 *                      cadastro").
 *
 * **NÃO use `asaas:criar-subcontas-faltantes` para isto.** Aquele comando casa
 * com `whereNull('asaas_wallet_id')` e criaria uma subconta NOVA: no melhor
 * caso o Asaas recusa por CPF duplicado, no pior ele aceita e a gravação
 * sobrescreve `asaas_account_id` e `asaas_api_key`, orfanando a conta real do
 * profissional — com o saldo dele dentro.
 *
 * ──────────────────────────────────────────────────────────────────────
 * POR QUE O ID VEM DA API E NÃO DE UM PARÂMETRO
 *
 * O walletId é o DESTINO do dinheiro. Um id errado aqui não falha: ele
 * funciona, e paga outra pessoa — silenciosamente, em toda venda dela. É o
 * único campo deste projeto em que um erro de transcrição move dinheiro para
 * um terceiro sem gerar nenhum erro. Então ele é lido de `GET /accounts`
 * filtrado pelo documento do recebedor, e só é gravado quando a identidade
 * confere (documento idêntico, um único resultado, e `asaas_account_id`
 * batendo quando já o temos).
 *
 * Uso:
 *   php artisan asaas:recuperar-wallet --personal=7               (só mostra)
 *   php artisan asaas:recuperar-wallet --personal=7 --confirmar   (grava)
 */
class RecuperarWalletAsaas extends Command
{
    protected $signature = 'asaas:recuperar-wallet
        {--personal= : id do personal}
        {--academia= : id da academia}
        {--studio= : id do studio}
        {--loja= : id da loja}
        {--confirmar : grava de fato (sem isto só mostra o que faria)}';

    protected $description = 'Recupera no Asaas o asaas_wallet_id de um recebedor que tem subconta mas está com a coluna vazia';

    /** Papel => [classe, coluna do documento]. */
    private const PAPEIS = [
        'personal' => [\App\Models\Cadastro\Personal::class, 'cpf'],
        'academia' => [\App\Models\Cadastro\Academia::class, 'cnpj'],
        'studio' => [\App\Models\Studio::class, 'cnpj'],
        'loja' => [\App\Models\Loja::class, 'cnpj'],
    ];

    public function handle(): int
    {
        [$papel, $conta, $colunaDoc] = $this->resolverAlvo() ?: [null, null, null];

        if (! $conta) {
            return self::FAILURE;
        }

        $this->line("Recebedor: {$papel} #{$conta->id} — {$conta->nome}");

        /*
         * Já tem wallet: não há o que recuperar e sobrescrever seria o risco
         * que este comando existe para evitar. Quem quer trocar uma wallet
         * existente está fazendo outra coisa, e deve fazê-la deliberadamente.
         */
        if (! empty($conta->asaas_wallet_id)) {
            $this->error('Este recebedor JÁ tem asaas_wallet_id: '.$conta->asaas_wallet_id);
            $this->line('Nada a recuperar. Este comando nunca sobrescreve uma wallet existente.');

            return self::FAILURE;
        }

        $doc = preg_replace('/\D/', '', (string) $conta->{$colunaDoc});

        if ($doc === '') {
            $this->error("Recebedor sem {$colunaDoc} — não há como identificar a subconta no Asaas.");

            return self::FAILURE;
        }

        $this->line('Documento ....... '.$this->mascarar($doc));
        $this->line('account_id local  '.($conta->asaas_account_id ?: '(vazio)'));
        $this->line('api_key local ... '.(! empty($conta->asaas_api_key) ? 'presente' : '(vazia)'));
        $this->newLine();

        $res = Http::withHeaders([
            'access_token' => config('services.asaas.key'),
            'Content-Type' => 'application/json',
        ])->get(config('services.asaas.url').'/accounts', ['cpfCnpj' => $doc]);

        /*
         * Consulta que falhou é "não sei", nunca "não existe". Seguir aqui com
         * a lista vazia levaria alguém a concluir que a subconta não existe e a
         * criar uma nova — exatamente o estrago descrito no docblock.
         */
        if ($res->failed()) {
            $this->error('A consulta ao Asaas falhou (HTTP '.$res->status().'). NÃO conclua nada daqui.');
            $this->line(mb_substr((string) $res->body(), 0, 300));

            return self::FAILURE;
        }

        $encontradas = collect($res->json('data') ?? [])
            ->filter(fn ($c) => preg_replace('/\D/', '', (string) ($c['cpfCnpj'] ?? '')) === $doc)
            ->values();

        if ($encontradas->isEmpty()) {
            $this->error('Nenhuma subconta no Asaas com este documento.');
            $this->line('Aí sim o caso é CRIAR: php artisan asaas:criar-subcontas-faltantes --papel='.$papel);

            return self::FAILURE;
        }

        /*
         * Mais de uma subconta com o mesmo documento é ambiguidade, e escolher
         * uma por conta própria é escolher para onde vai o dinheiro dela. Para
         * uma pessoa decidir.
         */
        if ($encontradas->count() > 1) {
            $this->error('Há '.$encontradas->count().' subcontas com este documento — ambíguo, não vou escolher.');
            foreach ($encontradas as $c) {
                $this->line('  id='.($c['id'] ?? '?').'  wallet='.($c['walletId'] ?? '?').'  email='.($c['email'] ?? '?'));
            }

            return self::FAILURE;
        }

        $sub = $encontradas->first();
        $wallet = (string) ($sub['walletId'] ?? '');

        if ($wallet === '') {
            $this->error('A subconta existe mas o Asaas não devolveu walletId. Nada a gravar.');

            return self::FAILURE;
        }

        $this->info('Subconta encontrada no Asaas:');
        $this->line('  id ........ '.($sub['id'] ?? '?'));
        $this->line('  nome ...... '.($sub['name'] ?? '?'));
        $this->line('  email ..... '.($sub['email'] ?? '?'));
        $this->line('  walletId .. '.$wallet);
        $this->newLine();

        /*
         * A verificação que impede o pior caso: se já guardamos um
         * `asaas_account_id` e ele NÃO é o desta subconta, então o documento
         * casa mas a conta é outra. Gravar o walletId dela mandaria o dinheiro
         * para a conta errada, e funcionaria — é a falha silenciosa.
         */
        if (! empty($conta->asaas_account_id) && ($sub['id'] ?? null) !== $conta->asaas_account_id) {
            $this->error('PARE: o account_id local não é o desta subconta.');
            $this->line('  local: '.$conta->asaas_account_id);
            $this->line('  Asaas: '.($sub['id'] ?? '?'));
            $this->line('Mesmo documento, conta diferente. Gravar mandaria o repasse para a conta errada.');

            return self::FAILURE;
        }

        if (($sub['email'] ?? null) && $sub['email'] !== $conta->email) {
            $this->warn('Atenção: e-mail do Asaas ('.$sub['email'].') difere do local ('.$conta->email.').');
            $this->line('Não é impedimento (o e-mail pode ter sido trocado), mas confira que é a pessoa certa.');
        }

        if (! $this->option('confirmar')) {
            $this->newLine();
            $this->warn('MODO SECO: nada foi gravado.');
            $this->line('Para gravar:  php artisan asaas:recuperar-wallet --'.$papel.'='.$conta->id.' --confirmar');

            return self::SUCCESS;
        }

        $carimboAnterior = $conta->asaas_split_bloqueado_em;

        $conta->forceFill([
            'asaas_wallet_id' => $wallet,
            // Preenche o account_id se estava vazio; nunca o altera (o caso em
            // que ele difere já abortou acima).
            'asaas_account_id' => $conta->asaas_account_id ?: ($sub['id'] ?? null),
            /*
             * Limpa o carimbo de bloqueio de propósito: devolver a wallet É o
             * pedido de reteste. Mantê-lo deixaria o recebedor barrado por até
             * HORAS_RETENTAR_SPLIT por causa de uma recusa anterior, referente
             * ao mesmo estado que estamos justamente mandando testar de novo.
             */
            'asaas_split_bloqueado_em' => null,
            'asaas_split_bloqueio_motivo' => null,
        ])->save();

        Log::channel('security')->info('Asaas: walletId recuperado da API e regravado', [
            'papel' => $papel,
            'id' => $conta->id,
            'wallet_id' => $wallet,
            'account_id' => $sub['id'] ?? null,
            'carimbo_de_bloqueio_limpo' => $carimboAnterior,
        ]);

        $this->newLine();
        $this->info('✓ Gravado. '.$conta->nome.' pode voltar a vender.');

        if ($carimboAnterior) {
            $this->line('O carimbo de bloqueio anterior ('.$carimboAnterior.') foi limpo para permitir o reteste.');
        }

        $this->newLine();
        $this->line('A PRÓXIMA COBRANÇA é o teste. Dois desfechos, e nenhum exige voltar aqui:');
        $this->line('  • passa  → a wallet foi liberada pelo Asaas e ela voltou a vender;');
        $this->line('  • falha  → segue bloqueada, o carimbo entra e a trava reteste a cada '
            .\App\Services\AsaasService::HORAS_RETENTAR_SPLIT.'h sozinha.');
        $this->line('Para ver qual foi:  php artisan asaas:diagnosticar-split --ultimos=1');

        return self::SUCCESS;
    }

    /** @return array{0:string, 1:\Illuminate\Database\Eloquent\Model, 2:string}|null */
    private function resolverAlvo(): ?array
    {
        foreach (self::PAPEIS as $papel => [$classe, $colunaDoc]) {
            if ($id = $this->option($papel)) {
                if ($conta = $classe::find($id)) {
                    return [$papel, $conta, $colunaDoc];
                }

                $this->error(ucfirst($papel)." #{$id} não encontrado.");

                return null;
            }
        }

        $this->error('Informe um recebedor: --personal=, --academia=, --studio= ou --loja=');

        return null;
    }

    /** Documento só para confirmação visual — nunca inteiro no terminal/log. */
    private function mascarar(string $doc): string
    {
        if (mb_strlen($doc) <= 4) {
            return str_repeat('*', mb_strlen($doc));
        }

        return str_repeat('*', mb_strlen($doc) - 4).mb_substr($doc, -4);
    }
}
