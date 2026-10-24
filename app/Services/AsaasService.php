<?php

namespace App\Services;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Pacote;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\StudioPlano;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lógica de cobrança via Asaas compartilhada entre o PaymentController web
 * (AJAX/Blade, autenticado por sessão) e os controllers da API mobile
 * (App\Http\Controllers\Api, autenticados por token Sanctum).
 *
 * Os métodos seguem o idioma já usado no controller web: retornam arrays de
 * dados prontos para virar JSON e, em erro, abortam com response()->json
 * (422 para erro de negócio, 500 para falha de infraestrutura) — assim o
 * comportamento HTTP é idêntico nos dois pontos de entrada.
 */
class AsaasService
{
    // ─────────────────────────────────────────────
    // PREÇO — a comissão é ACRÉSCIMO, não desconto
    // ─────────────────────────────────────────────

    /*
     * O profissional recebe o preço que ele pediu, CHEIO. A comissão da
     * plataforma é somada por cima e cobrada do cliente:
     *
     *     personal pede R$ 100  →  cliente paga R$ 110  →  personal recebe R$ 100
     *
     * Era o contrário até aqui (`SPLIT_RATE = 0.90`: cobrava-se R$ 100 e
     * repassava-se R$ 90). A constante antiga foi REMOVIDA em vez de
     * reaproveitada, de propósito — "taxa de split" e "markup" produzem valores
     * diferentes a partir do mesmo 0.10, e um `SPLIT_RATE` sobrevivente seria
     * lido como o antigo por quem não acompanhou a mudança.
     *
     * Consequências que precisam andar juntas e estão todas abaixo:
     *  - `montarSplit()` manda o preço INTEGRAL do profissional (fixedValue);
     *  - a taxa do Asaas no cartão é embutida no valor cobrado do cliente
     *    (gross-up), porque é ele que escolhe o meio caro;
     *  - a taxa do Pix sai da nossa comissão — e é ela que define o valor
     *    mínimo de uma cobrança (ver valorMinimo()).
     *
     * Métodos de preço são STATIC por necessidade: as vitrines (Blade), os
     * resources da API e o app precisam do preço final sem instanciar um
     * serviço que fala HTTP.
     */

    /**
     * A comissão da plataforma, em fração do preço do profissional.
     *
     * É o ÚNICO lugar onde esse número existe (`config('taxas.comissao')`).
     * Nunca escreva 0.10 em outro arquivo: `company_fee` é a base do bônus de
     * indicação, e uma cópia velha paga sobre uma comissão que não foi cobrada.
     */
    public static function feeRate(): float
    {
        return max(0.0, (float) config('taxas.comissao', 0.10));
    }

    /** Quanto a plataforma ganha num serviço de R$ `$base`. */
    public static function comissao(float $base): float
    {
        return round($base * self::feeRate(), 2);
    }

    /**
     * O inverso de comissao(): a comissão EMBUTIDA num valor já cobrado.
     *
     * Serve para reconstituir a comissão de uma cobrança sem `company_fee`
     * registrado (ver CupomService::comissao). Não é `bruto * feeRate()` — o
     * bruto já contém o acréscimo, então dividir é diferente de multiplicar:
     * em R$ 110 a comissão é R$ 10 (110 × 1/11), não R$ 11.
     */
    public static function comissaoEmbutida(float $bruto): float
    {
        $taxa = self::feeRate();

        return round($bruto * $taxa / (1 + $taxa), 2);
    }

    /**
     * O que o cliente paga por um serviço de R$ `$base`: preço do profissional
     * + comissão + (no cartão) a taxa do Asaas.
     */
    public static function valorCobrado(float $base, string $billingType = 'PIX'): float
    {
        return self::detalhar($base, $billingType)['total'];
    }

    /**
     * A conta inteira, para exibir no checkout e para gravar em payments.
     *
     * `gateway` só é diferente de zero no cartão, e é um GROSS-UP: o valor
     * cobrado tem de ser grande o bastante para que, DEPOIS de o Asaas
     * descontar a taxa dele, ainda sobrem o preço do profissional + a nossa
     * comissão. Resolver `bruto = base + comissão + (bruto × p + f)` em `bruto`
     * dá a divisão abaixo — somar a taxa "por fora" (base + comissão + p×base)
     * erra, porque a taxa incide sobre o total já acrescido.
     *
     * @return array{base:float, comissao:float, gateway:float, total:float}
     */
    public static function detalhar(float $base, string $billingType = 'PIX'): array
    {
        $base = round($base, 2);
        $comissao = self::comissao($base);
        $liquidoNecessario = $base + $comissao;

        if (! self::ehCartao($billingType)) {
            // Pix: a taxa fixa do Asaas sai da nossa comissão, não do cliente.
            return [
                'base' => $base,
                'comissao' => $comissao,
                'gateway' => 0.0,
                'total' => round($liquidoNecessario, 2),
            ];
        }

        // Clamp defensivo: um percentual mal configurado (≥ 1) faria a divisão
        // estourar ou virar negativa, e isso aqui decide quanto se cobra de uma
        // pessoa. Acima de 50% é config errado, não contrato ruim.
        $percentual = max(0.0, min(0.5, (float) config('taxas.asaas.cartao_percentual', 0.0499)));
        $fixa = max(0.0, (float) config('taxas.asaas.cartao_fixa', 0.49));

        $total = round(($liquidoNecessario + $fixa) / (1 - $percentual), 2);

        return [
            'base' => $base,
            'comissao' => $comissao,
            'gateway' => round($total - $liquidoNecessario, 2),
            'total' => $total,
        ];
    }

    /**
     * `UNDEFINED` (checkout hospedado do Asaas) conta como CARTÃO aqui, e a
     * escolha é deliberada: nesse modo quem decide o meio é o cliente, já na
     * tela do Asaas, depois de a cobrança existir. Precificar pelo Pix e
     * receber no cartão deixaria o `fixedValue` do split acima do líquido — a
     * Asaas recusa, e a falha aparece na cara do cliente no fim do checkout.
     * Então cobramos o teto. Quem quer o preço do Pix escolhe Pix (o app e o
     * site oferecem os dois); nesse caminho o billingType é `PIX` de verdade.
     */
    private static function ehCartao(string $billingType): bool
    {
        return in_array($billingType, ['CREDIT_CARD', 'DEBIT_CARD', 'UNDEFINED'], true);
    }

    /** O que o Asaas cobra de nós para processar uma cobrança de R$ `$bruto`. */
    public static function taxaAsaas(float $bruto, string $billingType = 'PIX'): float
    {
        if (! self::ehCartao($billingType)) {
            return round((float) config('taxas.asaas.pix_fixa', 1.99), 2);
        }

        $percentual = max(0.0, min(0.5, (float) config('taxas.asaas.cartao_percentual', 0.0499)));
        $fixa = max(0.0, (float) config('taxas.asaas.cartao_fixa', 0.49));

        return round($bruto * $percentual + $fixa, 2);
    }

    /**
     * Preço mínimo de um serviço, DERIVADO — não um número escolhido.
     *
     * No Pix a taxa do Asaas sai da nossa comissão, então abaixo do ponto em
     * que a comissão cobre essa taxa o repasse integral não cabe no líquido e a
     * Asaas recusa o split. Com comissão de 10% e Pix de R$ 1,99 isso dá
     * R$ 19,90 — e o antigo mínimo de R$ 10 ficaria furado (em R$ 10 a comissão
     * é R$ 1 e a taxa é R$ 1,99: a plataforma pagaria para trabalhar).
     */
    public static function valorMinimo(): float
    {
        $taxa = self::feeRate();
        $pix = (float) config('taxas.asaas.pix_fixa', 1.99);

        if ($taxa <= 0) {
            // Plataforma sem comissão: não há de onde tirar a taxa do Pix. O
            // split cai no fallback percentual (ver montarSplit) e o mínimo
            // deixa de ter função.
            return self::PISO_ABSOLUTO;
        }

        return max(self::PISO_ABSOLUTO, round(ceil($pix / $taxa * 100) / 100, 2));
    }

    /** Piso de sanidade para o mínimo derivado (cobrança de centavos não existe). */
    private const PISO_ABSOLUTO = 5.0;

    private function asaas(): string
    {
        return config('services.asaas.url');
    }

    private function asaasHeaders(): array
    {
        return [
            'access_token' => config('services.asaas.key'),
            'Content-Type' => 'application/json',
        ];
    }

    // ─────────────────────────────────────────────
    // SITUAÇÃO CADASTRAL DA SUBCONTA
    // ─────────────────────────────────────────────

    /**
     * Veredito do Asaas para subconta apta a receber (`general` do
     * /myAccount/status). É o único valor que libera split.
     *
     * Mora aqui, e não na trait `TemSubcontaAsaas`, por uma limitação da
     * versão: traits só aceitam constantes a partir do PHP 8.2 e o projeto
     * roda 8.1. Este também é o lugar mais defensável — a constante é
     * vocabulário do Asaas, e `AsaasService` é quem fala com ele.
     */
    public const STATUS_APROVADA = 'APPROVED';

    /**
     * Não foi possível verificar: a chave da subconta que guardamos é inválida.
     *
     * NÃO é o mesmo que reprovada, e a diferença tem consequência prática
     * oposta à intuição:
     *
     *  - a COBRANÇA usa a chave da PLATAFORMA e só referencia o `walletId`, de
     *    modo que o split continua funcionando normalmente — esse profissional
     *    segue vendendo e recebendo;
     *  - SALDO e SAQUE usam a chave da SUBCONTA, então esses dois estão
     *    quebrados para ele agora.
     *
     * Por isso este status não bloqueia pagamento (bloquear pararia a venda de
     * quem recebe bem) mas também não pode ficar invisível: é defeito real,
     * com dinheiro parado que o profissional não consegue sacar.
     *
     * Causa mais provável: a chave foi cifrada com outro APP_KEY (base copiada
     * entre ambientes, ou APP_KEY rotacionada). `getAsaasApiKeyDecrypted()`
     * engole o DecryptException e devolve o TEXTO CIFRADO como se fosse a
     * chave — que o Asaas recusa com 401. Ver `classificarChaveSubconta()`.
     */
    public const STATUS_CHAVE_INVALIDA = 'INVALID_KEY';

    /**
     * Por quanto tempo uma wallet recusada pelo Asaas fica barrada aqui.
     *
     * A trava precisa expirar porque o Asaas **não avisa quando desbloqueia** e
     * `/myAccount/status` responde APPROVED antes e depois — não existe
     * endpoint que responda "a wallet voltou". Sem prazo, o profissional
     * ficaria barrado para sempre por um bloqueio já resolvido.
     *
     * Seis horas equilibra os dois erros possíveis: curto o bastante para o
     * profissional voltar a vender no mesmo dia em que o Asaas liberar, longo
     * o bastante para que no máximo um aluno por janela passe pelo checkout
     * que vai falhar. Essa cobrança falha é o preço de detectar o desbloqueio,
     * e é o único jeito de detectá-lo.
     */
    public const HORAS_RETENTAR_SPLIT = 6;

    /**
     * Mensagem de erro do Asaas traduzida para quem está comprando.
     *
     * O Asaas devolve texto escrito para quem INTEGRA, não para quem contrata
     * um treino. O caso que originou isto:
     *
     *   "Não é possível fazer split para wallet
     *    [82947cc6-d275-4ceb-b55c-7df09867066b], pois a mesma está bloqueada.
     *    Favor entrar em contato com o Suporte Técnico."
     *
     * Esse texto ia inteiro para o modal do app. Três problemas de uma vez: o
     * aluno não entende, não tem o que fazer a respeito, e a mensagem manda ele
     * procurar o suporte do ASAAS — com quem ele não tem relação nenhuma. De
     * quebra expõe um identificador interno do recebedor.
     *
     * Aqui as falhas conhecidas viram frases acionáveis. O que não for
     * reconhecido NÃO é repassado cru: cai no `$padrao` de quem chamou, porque
     * um texto de API de terceiro na cara do usuário é sempre pior do que uma
     * frase genérica nossa. O detalhe técnico vive no log, que é onde serve.
     */
    public static function mensagemDeErro(?array $corpo, string $padrao): string
    {
        $bruto = $corpo['errors'][0]['description'] ?? null;

        if (! $bruto) {
            return $padrao;
        }

        if (self::ehBloqueioDeRecebedor($corpo)) {
            return 'Este profissional ainda está finalizando o cadastro de recebimentos '
                .'e não pode receber pagamentos no momento. Tente novamente mais tarde '
                .'ou escolha outro profissional.';
        }

        $texto = mb_strtolower($bruto);

        // Falhas de cartão: o texto do Asaas aqui já é dirigido ao portador e
        // é acionável ("saldo insuficiente"), então vale repassar o sentido.
        if (str_contains($texto, 'cartão') || str_contains($texto, 'cartao') || str_contains($texto, 'credit')) {
            return $bruto;
        }

        if (str_contains($texto, 'customer') || str_contains($texto, 'cpf') || str_contains($texto, 'cnpj')) {
            return 'Não foi possível validar os seus dados de cobrança. '
                .'Confira CPF e e-mail no seu perfil e tente de novo.';
        }

        return $padrao;
    }

    /**
     * O Asaas recusou por conta bloqueada APESAR da trava: avisa quem pode agir.
     *
     * Acontece na corrida entre a última sincronização e a cobrança — o status
     * local dizia APPROVED e o Asaas bloqueou no meio. É venda perdida, não
     * erro do comprador, e ninguém ficaria sabendo: o aluno desiste e o
     * profissional nunca descobre por que parou de vender.
     *
     * Faz duas coisas: marca a subconta como suspeita, para a próxima
     * sincronização confirmar e para a trava passar a valer já na próxima
     * tentativa; e avisa o admin. Best-effort — é reação a uma cobrança que já
     * falhou e não pode falhar por cima.
     */
    public static function reportarBloqueioDeRecebedor($recebedor, ?array $corpo, array $ctx = []): void
    {
        if (! self::ehBloqueioDeRecebedor($corpo)) {
            return;
        }

        try {
            Log::channel('security')->error('Asaas: split recusado por conta bloqueada', array_merge($ctx, [
                'recebedor' => $recebedor ? class_basename($recebedor) : null,
                'id' => $recebedor->id ?? null,
                'status_local' => $recebedor->asaas_account_status ?? null,
                'asaas' => $corpo['errors'][0]['description'] ?? null,
            ]));

            if ($recebedor && method_exists($recebedor, 'registrarBloqueioDeSplit')) {
                /*
                 * Carimba a COLUNA DO BLOQUEIO DE WALLET, não o status
                 * cadastral.
                 *
                 * Isto já escreveu `asaas_account_status = 'BLOCKED'`, e foi um
                 * erro: `asaas:sincronizar-subcontas` é dono dessa coluna e
                 * grava APPROVED a partir de `/myAccount/status` duas vezes ao
                 * dia. Os dois brigavam pelo mesmo campo e o estado oscilava em
                 * círculo — e, no meio do ciclo, o aluno lia que o profissional
                 * estava "finalizando o cadastro" com o cadastro aprovado nos
                 * quatro campos.
                 *
                 * Cada fonte escreve na sua coluna: `/myAccount/status` não
                 * enxerga bloqueio de wallet, e a cobrança é a única que vê.
                 */
                $recebedor->registrarBloqueioDeSplit($corpo['errors'][0]['description'] ?? null);
            }

            if ($admin = config('services.admin.email')) {
                \App\Services\NotificacaoService::enviar(
                    $admin,
                    'Admin',
                    'Venda bloqueada: subconta Asaas recusou o split',
                    'O Asaas recusou uma cobrança porque a conta de recebimentos do profissional '
                    ."está bloqueada.\n\n"
                    .'Profissional: '.($recebedor->nome ?? '?').' ('.($recebedor->id ?? '?').")\n"
                    .'Mensagem do Asaas: '.($corpo['errors'][0]['description'] ?? '?')."\n\n"
                    .'Para ver o motivo e o link de regularização, rode no servidor:'
                    ."\nphp artisan asaas:diagnosticar-subconta --personal=".($recebedor->id ?? '')
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Asaas: falha ao reportar bloqueio de recebedor', ['erro' => $e->getMessage()]);
        }
    }

    /**
     * O recebedor por trás dos campos de uma assinatura.
     *
     * `criarAssinaturaPix` recebe o split já montado, não o modelo — então
     * quando o Asaas recusa lá dentro, é por `$subFields` que se descobre de
     * quem era a conta. As chaves são as mesmas gravadas em `subscriptions`.
     */
    private function recebedorDosCampos(array $subFields)
    {
        return match (true) {
            ! empty($subFields['trainer_id']) => Personal::find($subFields['trainer_id']),
            ! empty($subFields['academia_id']) => Academia::find($subFields['academia_id']),
            ! empty($subFields['studio_id']) => Studio::find($subFields['studio_id']),
            default => null,
        };
    }

    public static function ehBloqueioDeRecebedor(?array $corpo): bool
    {
        $bruto = mb_strtolower($corpo['errors'][0]['description'] ?? '');

        if ($bruto === '') {
            return false;
        }

        return (str_contains($bruto, 'split') && str_contains($bruto, 'bloquead'))
            || str_contains($bruto, 'wallet')
                && (str_contains($bruto, 'bloquead') || str_contains($bruto, 'inativ'));
    }

    /**
     * Situação cadastral da subconta de um recebedor, direto no Asaas.
     *
     * Existe porque o erro de split não diz o motivo. Quando a subconta não
     * está aprovada, o Asaas recusa a cobrança com "Não é possível fazer split
     * para wallet [...], pois a mesma está bloqueada" — que informa QUE está
     * bloqueada e nunca POR QUE. Sem isto, descobrir a causa exigia abrir o
     * painel do Asaas conta por conta.
     *
     * **Usa a chave da PRÓPRIA SUBCONTA, não a da plataforma.** `/myAccount/*`
     * responde sobre a conta dona do token: com a chave da plataforma ele
     * devolveria a situação da conta-mãe, que está sempre aprovada — um
     * diagnóstico que erra em silêncio e sempre diz que está tudo bem.
     *
     * Nunca lança: é consulta de diagnóstico e de rotina, chamada por comando
     * agendado e por tela de painel. Falha de rede devolve `ok => false` com o
     * motivo, e quem chama decide o que fazer.
     *
     * @return array{ok:bool, erro:?string, geral:?string, status:array, pendencias:array, onboarding_url:?string}
     */
    public function situacaoSubconta($conta): array
    {
        $vazio = [
            'ok' => false, 'erro' => null, 'geral' => null,
            'status' => [], 'pendencias' => [], 'onboarding_url' => null,
            'auth_falhou' => false, 'chave' => null,
        ];

        $chave = $this->chaveDaSubconta($conta);
        if (! $chave) {
            return array_merge($vazio, ['erro' => 'recebedor sem chave de subconta gravada']);
        }

        $headers = ['access_token' => $chave, 'Content-Type' => 'application/json'];

        try {
            $res = Http::withHeaders($headers)->timeout(15)->get($this->asaas().'/myAccount/status');

            if ($res->failed()) {
                /*
                 * 401 é diferente de "não deu": é a NOSSA chave que não serve.
                 *
                 * Falha de rede é transitória e a próxima varredura resolve;
                 * chave inválida é permanente e nunca vai se resolver sozinha,
                 * então precisa de rótulo próprio — senão fica como "nunca
                 * sincronizado" para sempre, indistinguível de uma conta nova.
                 */
                $auth = $res->status() === 401;

                return array_merge($vazio, [
                    'erro' => 'HTTP '.$res->status().' — '.substr($res->body(), 0, 200),
                    'auth_falhou' => $auth,
                    'geral' => $auth ? self::STATUS_CHAVE_INVALIDA : null,
                    'chave' => $auth ? self::classificarChaveSubconta($conta) : null,
                ]);
            }

            $status = $res->json() ?: [];

            /*
             * `general` é o veredito que o split obedece. Os outros campos
             * (commercialInfo, bankAccountInfo, documentation) são as partes
             * que o compõem — e são eles que dizem ONDE está a pendência, que é
             * a informação acionável para o profissional.
             */
            $geral = $status['general'] ?? null;

            // Documentos pendentes e link de regularização. Falha aqui não
            // invalida o diagnóstico: o status já é a resposta principal.
            $pendencias = [];
            $onboarding = null;
            try {
                $docs = Http::withHeaders($headers)->timeout(15)->get($this->asaas().'/myAccount/documents');
                if ($docs->successful()) {
                    $corpo = $docs->json() ?: [];

                    /*
                     * `onboardingUrl` fica DENTRO de cada item de `data`, não na
                     * raiz da resposta — conferido na referência do Asaas
                     * (`GET /v3/myAccount/documents`: a raiz tem `rejectReasons`
                     * e `data`). Lido da raiz, como estava, dava sempre null: o
                     * painel do profissional caía no texto "fale com o suporte"
                     * mesmo quando havia link de regularização disponível.
                     */
                    foreach (($corpo['data'] ?? []) as $doc) {
                        $situacao = $doc['status'] ?? null;

                        if ($onboarding === null && ! empty($doc['onboardingUrl'])) {
                            $onboarding = $doc['onboardingUrl'];
                        }

                        // APPROVED está resolvido; IGNORED é documento que o
                        // Asaas dispensou para este perfil — listar qualquer um
                        // dos dois manda o profissional atrás do que não falta.
                        if (in_array($situacao, ['APPROVED', 'IGNORED'], true)) {
                            continue;
                        }

                        // `title` é a descrição que o Asaas escreve para humanos
                        // ("Documento de identificação"); `type` é o enum
                        // (`IDENTIFICATION`). Quem lê isto é o profissional.
                        $pendencias[] = trim(sprintf(
                            '%s — %s',
                            $doc['title'] ?? $doc['type'] ?? 'documento',
                            $situacao ?? 'pendente'
                        ));
                    }

                    // Motivo da reprovação, quando houver: é a informação mais
                    // acionável da resposta inteira e estava sendo ignorada.
                    if (! empty($corpo['rejectReasons'])) {
                        $pendencias[] = 'Motivo da reprovação: '.$corpo['rejectReasons'];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Asaas: falha ao listar documentos da subconta', [
                    'conta' => class_basename($conta),
                    'id' => $conta->id,
                    'erro' => $e->getMessage(),
                ]);
            }

            return [
                'ok' => true,
                'erro' => null,
                'geral' => $geral,
                'status' => [
                    'general' => $geral,
                    'commercialInfo' => $status['commercialInfo'] ?? null,
                    'bankAccountInfo' => $status['bankAccountInfo'] ?? null,
                    'documentation' => $status['documentation'] ?? null,
                ],
                'pendencias' => $pendencias,
                'onboarding_url' => $onboarding,
            ];
        } catch (\Throwable $e) {
            return array_merge($vazio, ['erro' => $e->getMessage()]);
        }
    }

    /**
     * Chave da subconta, decifrada quando houver cifra.
     *
     * `Personal` tem helper próprio (e tolera chave legada em texto puro);
     * academia, studio e loja gravam em texto puro desde sempre
     * (`AdminController`), então a tentativa de decifrar falha e o valor cru é
     * o correto. Tratar os dois casos aqui evita espalhar try/catch de Crypt
     * por todo chamador.
     */
    /**
     * Diz POR QUE a chave da subconta não serve, sem nunca imprimir a chave.
     *
     * Existe porque "401 invalid_access_token" sozinho não distingue três
     * causas com consertos completamente diferentes:
     *
     *  - `cifrada_ilegivel` — a chave foi cifrada com OUTRO APP_KEY (base
     *    copiada entre ambientes, ou APP_KEY rotacionada).
     *    `getAsaasApiKeyDecrypted()` engole o DecryptException e devolve o
     *    TEXTO CIFRADO como se fosse a chave, e é esse blob que vai no header.
     *    É a causa mais provável, e o conserto é recriar a subconta — a chave
     *    original não é recuperável.
     *  - `ambiente_errado` — chave de sandbox contra produção (ou o inverso).
     *    O Asaas prefixa as chaves com `$aact_`; sandbox e produção têm
     *    prefixos distintos. Conserto: apontar o ambiente certo.
     *  - `texto_puro` — chave legível, formato plausível, mas recusada. Aí foi
     *    revogada no painel do Asaas e precisa ser regerada.
     *
     * @return array{forma:string, prefixo:string, tamanho:int}
     */
    public static function classificarChaveSubconta($conta): array
    {
        $bruta = (string) ($conta->asaas_api_key ?? '');

        if ($bruta === '') {
            return ['forma' => 'ausente', 'prefixo' => '', 'tamanho' => 0];
        }

        // `eyJpdiI6` é o começo do payload base64 do Crypt do Laravel.
        $pareceCifrada = str_starts_with($bruta, 'eyJpdiI6');

        $decifrada = null;
        try {
            $decifrada = Crypt::decryptString($bruta);
        } catch (\Throwable $e) {
            $decifrada = null;
        }

        if ($pareceCifrada && $decifrada === null) {
            // Cifrada mas ilegível com o APP_KEY atual: o blob cifrado é o que
            // está sendo enviado ao Asaas como token.
            return ['forma' => 'cifrada_ilegivel', 'prefixo' => substr($bruta, 0, 8), 'tamanho' => strlen($bruta)];
        }

        $efetiva = $decifrada ?? $bruta;
        $forma = str_starts_with($efetiva, '$aact_') ? 'texto_puro' : 'formato_inesperado';

        return [
            'forma' => $forma,
            // Só o prefixo: identifica o ambiente sem revelar a credencial.
            'prefixo' => substr($efetiva, 0, 12),
            'tamanho' => strlen($efetiva),
        ];
    }

    private function chaveDaSubconta($conta): ?string
    {
        if (empty($conta->asaas_api_key)) {
            return null;
        }

        if (method_exists($conta, 'getAsaasApiKeyDecrypted')) {
            return $conta->getAsaasApiKeyDecrypted();
        }

        try {
            return Crypt::decryptString($conta->asaas_api_key);
        } catch (\Throwable $e) {
            return $conta->asaas_api_key;
        }
    }

    // ─────────────────────────────────────────────
    // SPLIT
    // ─────────────────────────────────────────────

    /**
     * Monta o split do marketplace: o recebedor fica com o preço INTEGRAL que
     * ele pediu (`fixedValue` = o próprio preço), e a plataforma — dona da
     * cobrança — fica com a comissão que foi acrescentada por cima e absorve a
     * taxa Asaas do Pix.
     *
     * `$base` é o preço do PROFISSIONAL, não o valor cobrado do cliente. Quem
     * chama nunca precisa saber o valor final: ele é derivado aqui e em
     * detalhar(), a partir do mesmo lugar.
     *
     * O fallback para `percentualValue: 100` é defensivo e, com o mínimo de
     * valorMinimo() respeitado, não deve acontecer: por construção o líquido é
     * `preço + comissão − taxa`, sempre acima do preço. Se acontecer (config de
     * taxa editada para um valor que inverte a conta), ele erra em favor do
     * PROFISSIONAL — ele recebe todo o líquido e a plataforma, nada — porque o
     * contrário seria entregar menos do que foi combinado com ele.
     */
    public function montarSplit($recebedor, float $base, array $ctx = [], string $billingType = 'PIX'): ?array
    {
        $walletId = $recebedor->asaas_wallet_id ?? null;

        /*
         * SEM walletId A COBRANÇA NÃO ACONTECE. Nunca devolva null daqui.
         *
         * Até 20/10/2026 isto devolvia `null`, e null significa "siga sem
         * split": a cobrança ia ao Asaas inteira, o dinheiro do profissional
         * caía na conta da PLATAFORMA e o repasse ficava manual. Estava
         * documentado como gap deliberado, e é insustentável por três motivos
         * que só aparecem depois do fato:
         *
         * 1. FISCAL. Entra na conta da plataforma, como receita dela, dinheiro
         *    que é de terceiro — os R$ 20 do profissional contabilizados junto
         *    dos R$ 2 que realmente são nossos.
         * 2. O saldo do profissional passa a MENTIR. `payments.trainer_amount`
         *    é gravado com ou sem split, então o registro diz que ele tem R$ 20
         *    a receber enquanto o dinheiro está em outra conta.
         * 3. Ninguém percebe. A cobrança é bem-sucedida, o aluno paga, o aviso
         *    sai — e a única pista era um Log::warning que nada lê.
         *
         * Abortar 422 é o mesmo tratamento da wallet bloqueada, logo abaixo, e
         * pelo mesmo motivo: melhor a venda falhar na frente do aluno, com texto
         * que explica, do que acontecer errado em silêncio.
         *
         * CONSEQUÊNCIA ACEITA: profissional sem subconta não vende até ela
         * existir. Rode `asaas:conferir-repasses` para ver quem está nessa
         * situação ANTES de subir isto.
         */
        if (! $walletId) {
            Log::error('Asaas: cobrança barrada — recebedor sem walletId (o repasse não teria destino)', $ctx);

            abort(response()->json([
                'error' => method_exists($recebedor, 'motivoNaoRecebe') && $recebedor->motivoNaoRecebe()
                    ? $recebedor->motivoNaoRecebe()
                    : 'Este profissional ainda está finalizando o cadastro de recebimentos '
                      .'e não pode receber pagamentos no momento.',
            ], 422));
        }

        /*
         * TRAVA: subconta não aprovada não recebe split.
         *
         * Aqui, e não nos dez pontos de entrada de cobrança, por um motivo
         * prático: todos eles terminam nesta função, e uma guarda espalhada em
         * dez lugares é uma guarda que o décimo primeiro ponto vai esquecer.
         * Foi por isso que a assinatura passou a receber o RECEBEDOR em vez do
         * walletId solto — com a string não havia como consultar nada.
         *
         * Aborta em vez de devolver null. Null significa "siga sem split", e o
         * dinheiro cairia inteiro na conta da plataforma, criando uma dívida
         * manual com alguém cuja conta está bloqueada — pior que falhar, porque
         * o aluno paga e ninguém percebe que o repasse não tem para onde ir.
         *
         * Sem esta trava a cobrança seguia até o Asaas, que recusava com
         * "Não é possível fazer split para wallet [...], pois a mesma está
         * bloqueada" — texto que ia cru para o modal do app.
         */
        if (method_exists($recebedor, 'podeReceberPagamento') && ! $recebedor->podeReceberPagamento()) {
            Log::warning('Asaas: cobrança barrada — subconta do recebedor não está apta', array_merge($ctx, [
                'status' => $recebedor->asaas_account_status,
                'verificado_em' => $recebedor->asaas_status_em,
            ]));

            abort(response()->json([
                'error' => $recebedor->motivoNaoRecebe(),
            ], 422));
        }

        $base = round($base, 2);
        $bruto = self::valorCobrado($base, $billingType);
        $liquido = round($bruto - self::taxaAsaas($bruto, $billingType), 2);

        if ($base > $liquido) {
            Log::warning('Asaas: repasse integral não cabe no líquido — split caiu para percentual', array_merge($ctx, [
                'preco' => $base,
                'bruto' => $bruto,
                'liquido' => $liquido,
                'billing_type' => $billingType,
            ]));

            return [['walletId' => $walletId, 'percentualValue' => 100.0]];
        }

        return [['walletId' => $walletId, 'fixedValue' => $base]];
    }

    public function splitPersonal(Personal $personal, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($personal, $amount, ['personal_id' => $personal->id], $billingType);
    }

    public function splitStudio(Studio $studio, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($studio, $amount, ['studio_id' => $studio->id], $billingType);
    }

    public function splitAcademia(Academia $academia, float $amount, string $billingType = 'PIX'): ?array
    {
        return $this->montarSplit($academia, $amount, ['academia_id' => $academia->id], $billingType);
    }

    /**
     * Os valores gravados localmente (payments/subscriptions), a partir do
     * preço do profissional.
     *
     * As quatro colunas fecham a conta e cada uma responde uma pergunta
     * diferente — por isso `gateway_fee` existe em vez de ser somada na
     * comissão:
     *
     *     amount_total   o que o cliente pagou
     *     trainer_amount o que o profissional recebeu (o preço dele, cheio)
     *     company_fee    o que a plataforma GANHOU  ← base do bônus de indicação
     *     gateway_fee    o que a Asaas levou no cartão (passou por nós, não é nosso)
     *
     * Jogar a taxa do cartão em `company_fee` inflaria o bônus de indicação
     * sobre dinheiro que nunca foi receita nossa.
     */
    public function calculateSplit(float $base, string $billingType = 'PIX'): array
    {
        $conta = self::detalhar($base, $billingType);

        return [
            'amount_total' => $conta['total'],
            'company_fee' => $conta['comissao'],
            'trainer_amount' => $conta['base'],
            'gateway_fee' => $conta['gateway'],
        ];
    }

    /**
     * Barra serviços abaixo do mínimo. O limite não é estético: abaixo dele a
     * comissão não cobre a taxa do Pix, o repasse integral não cabe no líquido
     * e a Asaas recusaria o split. Ver valorMinimo().
     */
    public function garantirValorMinimo(float $value): void
    {
        $minimo = self::valorMinimo();

        if ($value < $minimo) {
            abort(response()->json([
                'error' => 'O valor mínimo para pagamento é R$ '.number_format($minimo, 2, ',', '.').'.',
            ], 422));
        }
    }

    // ─────────────────────────────────────────────
    // PRECIFICAÇÃO (regras de valor/descrição por item)
    // ─────────────────────────────────────────────

    /**
     * Resolve valor, descrição e vínculos de avaliação de um item do personal
     * (aula avulsa, pacote, ficha ou avaliação), a partir do request validado.
     *
     * @return array{0: float, 1: string, 2: int|null, 3: array|null} [valor, descrição, pacote_avaliacao_id, tipos de avaliação]
     */
    public function resolverItemPersonal(Personal $personal, array $validated): array
    {
        $avaliacaoPacoteId = null;
        $avaliacaoTipos = null;

        if ($validated['tipo'] === 'pacote') {
            $pacote = Pacote::findOrFail($validated['pacote_id']);
            $amount = (float) $pacote->valor_mensal;
            $description = "Pacote {$pacote->frequencia}x/semana — {$personal->nome}";
        } elseif ($validated['tipo'] === 'ficha') {
            $amount = (float) ($personal->valor_ficha ?? 0);
            if ($amount <= 0) {
                abort(response()->json(['error' => 'Este personal não trabalha com ficha personalizada.'], 422));
            }
            $description = "Ficha Personalizada — {$personal->nome}";
        } elseif ($validated['tipo'] === 'avaliacao') {
            if (! empty($validated['pacote_avaliacao_id'])) {
                $pacoteAv = \App\Models\PacoteAvaliacao::where('personal_id', $personal->id)
                    ->findOrFail($validated['pacote_avaliacao_id']);
                $amount = (float) $pacoteAv->valor;
                $description = "Pacote de Avaliação: {$pacoteAv->nome} — {$personal->nome}";
                $avaliacaoPacoteId = $pacoteAv->id;
                $avaliacaoTipos = $pacoteAv->tipos;
            } elseif (! empty($validated['avaliacao_tipo'])) {
                $precos = $personal->precos_avaliacao ?? [];
                $tipoAv = $validated['avaliacao_tipo'];
                if (! isset($precos[$tipoAv]) || (float) $precos[$tipoAv] <= 0) {
                    abort(response()->json(['error' => 'Este personal não trabalha com essa avaliação.'], 422));
                }
                $label = \App\Models\AvaliacaoFisica::META[$tipoAv]['label'] ?? $tipoAv;
                $amount = (float) $precos[$tipoAv];
                $description = "Avaliação: {$label} — {$personal->nome}";
                $avaliacaoTipos = [$tipoAv];
            } else {
                $amount = (float) ($personal->valor_avaliacao ?? 0);
                if ($amount <= 0) {
                    abort(response()->json(['error' => 'Este personal não trabalha com avaliação física.'], 422));
                }
                $description = "Avaliação Física — {$personal->nome}";
            }
        } else {
            $amount = (float) ($personal->valor_secao ?? 0);
            $description = "Aula Avulsa — {$personal->nome}";
        }

        return [$amount, $description, $avaliacaoPacoteId, $avaliacaoTipos];
    }

    /**
     * Resolve o item a ser cobrado na academia: um plano específico ou,
     * quando não houver plano_id, a mensalidade base da academia.
     *
     * @return array{0: float, 1: string, 2: int|null} [valor, descrição, plano_id]
     */
    public function resolverItemAcademia(Academia $academia, $planoId): array
    {
        if (! empty($planoId)) {
            $plano = \App\Models\Cadastro\Plano::findOrFail($planoId);
            if ((int) $plano->academia_id !== (int) $academia->id) {
                abort(response()->json(['error' => 'Plano inválido para esta academia.'], 422));
            }

            return [(float) $plano->valor, "Plano {$plano->nome} — {$academia->nome}", $plano->id];
        }

        $amount = (float) ($academia->valor_mensalidade ?? 0);
        if ($amount <= 0) {
            abort(response()->json(['error' => 'Esta academia ainda não definiu o valor da mensalidade.'], 422));
        }

        return [$amount, "Mensalidade — {$academia->nome}", null];
    }

    /** @return array{0: Studio, 1: StudioPlano} */
    public function validarStudioPlano(int $studioId, int $planoId): array
    {
        $studio = Studio::where('id', $studioId)->where('status', 'aprovado')->first();
        if (! $studio) {
            abort(response()->json(['error' => 'Studio não encontrado.'], 404));
        }

        $plano = StudioPlano::find($planoId);
        if (! $plano || (int) $plano->studio_id !== (int) $studioId || ! $plano->ativo) {
            abort(response()->json(['error' => 'Plano inválido para este studio.'], 422));
        }

        return [$studio, $plano];
    }

    // ─────────────────────────────────────────────
    // CLIENTE ASAAS
    // ─────────────────────────────────────────────

    /**
     * O cliente no Asaas: acha pelo e-mail ou cria.
     *
     * ─── A LINHA QUE EXPLODIA EM SILÊNCIO ───────────────────────────────────
     *
     * Era `return $create->json()['id'];` sem NENHUMA verificação. Quando o
     * Asaas recusava a criação (HTTP 400 + `errors[]`, sem `id`), o acesso à
     * chave inexistente virava `ErrorException` — o Laravel converte warning em
     * exceção — e o `catch (\Exception)` do checkout a transformava em
     * **"Erro interno. Tente novamente."**, sem log nenhum.
     *
     * Resultado: a causa real (o que o Asaas respondeu) era descartada antes de
     * qualquer um poder lê-la. O corpo da resposta agora vai para o log sempre,
     * e o usuário recebe 422 com uma frase que diz o que fazer.
     *
     * ─── `cpfCnpj` É OBRIGATÓRIO NA API DO ASAAS ────────────────────────────
     *
     * `POST /v3/customers` declara `"required": ["name", "cpfCnpj"]`, e a
     * tabela `clientes` não tinha o campo. O CPF é pedido no CHECKOUT — ver
     * `garantirCpfDoCliente()` logo abaixo, que é quem resolve de onde ele vem.
     *
     * A busca por e-mail roda ANTES e é o que mascarava o defeito: quem já
     * existia no Asaas passava direto, e só aluno novo quebrava.
     */
    public function obterOuCriarClienteAsaas(Cliente $cliente, ?string $cpfInformado = null): string
    {
        $search = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas().'/customers', ['email' => $cliente->email]);

        if ($search->successful() && ! empty($search->json()['data'])) {
            return $search->json()['data'][0]['id'];
        }

        $payload = [
            'name' => $cliente->nome,
            'email' => $cliente->email,
            'cpfCnpj' => $this->garantirCpfDoCliente($cliente, $cpfInformado),
            // Deixa o cliente rastreável no painel do Asaas a partir do nosso
            // id — a busca acima é por e-mail, que o aluno pode trocar.
            'externalReference' => 'cliente_'.$cliente->id,
        ];

        $create = Http::withHeaders($this->asaasHeaders())
            ->post($this->asaas().'/customers', $payload);

        $id = $create->json()['id'] ?? null;

        if (! $id) {
            $corpo = $create->json();

            Log::error('Asaas: falha ao criar cliente', [
                'cliente_id' => $cliente->id,
                'http' => $create->status(),
                'tem_cpf' => isset($payload['cpfCnpj']),
                'body' => $corpo,
            ]);

            abort(response()->json([
                'error' => self::mensagemDeErro(
                    $corpo,
                    'Não foi possível validar os seus dados de cobrança. '
                    .'Confira nome e e-mail no seu perfil e tente de novo.'
                ),
            ], 422));
        }

        return (string) $id;
    }

    /**
     * O CPF do aluno, exigido pelo Asaas — guardado na primeira vez que ele paga.
     *
     * Por que no checkout e não no cadastro: quem só usa a parte gratuita
     * nunca precisa entregar documento (minimização de dado, LGPD art. 6º,
     * III), e exigir no cadastro quebraria o app já publicado na loja, que não
     * manda o campo. O fluxo de cartão já pedia o CPF do portador na hora de
     * pagar; aqui o valor passa a ser GUARDADO, então é pedido uma vez só.
     *
     * Quando não há CPF nenhum, responde 422 com `precisa_cpf: true`. Essa
     * flag é o contrato com o front: é ela que diz "abra o campo de CPF e
     * repita a chamada", em vez de obrigar toda tela de pagamento a exibir um
     * campo que a maioria dos alunos não precisa preencher duas vezes.
     *
     * Só é chamado DEPOIS da busca por e-mail, ou seja, só quando o cliente
     * precisa mesmo ser criado. Essa ordem é o que impede a exigência de
     * alcançar quem já paga hoje: quem já tem cliente no Asaas nunca passa
     * por aqui, e o app publicado na loja — que não manda `cpf` — continua
     * funcionando para ele.
     */
    private function garantirCpfDoCliente(Cliente $cliente, ?string $informado): string
    {
        if ($cpf = $this->capturarCpfDoCliente($cliente, $informado)) {
            return $cpf;
        }

        abort(response()->json([
            'error' => 'Para gerar a cobrança precisamos do seu CPF. '
                .'Ele é exigido pelo processador de pagamentos e fica guardado para as próximas vezes.',
            'precisa_cpf' => true,
        ], 422));
    }

    /**
     * Guarda o CPF que o aluno informou e devolve o que vale agora — sem
     * nunca barrar ninguém.
     *
     * Separado de `garantirCpfDoCliente()` por uma razão concreta: os pontos
     * de entrada querem APROVEITAR o CPF quando ele vem, mas não podem exigir.
     * Exigir na porta alcançaria também quem já tem cliente criado no Asaas e
     * nunca precisou informar documento — inclusive quem está no app já
     * publicado na loja, que não manda o campo. Seria quebrar quem paga hoje
     * para consertar quem não consegue pagar.
     *
     * CPF malformado aborta, e aqui a exceção é certa: o aluno DIGITOU algo,
     * então há o que corrigir na tela. Ignorar em silêncio mandaria um número
     * errado ao Asaas e a falha voltaria disfarçada de outra coisa.
     */
    public function capturarCpfDoCliente(Cliente $cliente, ?string $informado): ?string
    {
        $guardado = preg_replace('/\D/', '', (string) ($cliente->cpf ?? ''));
        $novo = preg_replace('/\D/', '', (string) $informado);

        if ($novo === '') {
            return $guardado ?: null;
        }

        if (! \App\Support\CadastroHelper::validarCPF($novo)) {
            abort(response()->json([
                'error' => 'CPF inválido. Confira os números e tente de novo.',
                'precisa_cpf' => true,
            ], 422));
        }

        // Grava só quando muda, para não escrever no banco a cada cobrança.
        if ($novo !== $guardado) {
            $cliente->forceFill(['cpf' => $novo])->save();
        }

        return $novo;
    }

    // ─────────────────────────────────────────────
    // COBRANÇA PIX AVULSA (cobrança única)
    // ─────────────────────────────────────────────

    /**
     * Cria uma cobrança PIX única no Asaas + registro local em payments e
     * devolve o payload/QR code prontos para o front (web ou mobile).
     *
     * `$amount` é o PREÇO DO PROFISSIONAL. O valor cobrado do cliente (preço +
     * comissão + taxa de cartão) é derivado aqui — quem chama não calcula nada,
     * e por isso nenhum dos ~20 pontos de entrada precisou aprender a conta.
     *
     * $paymentFields: colunas extras de payments (trainer_id, studio_id, loja_id,
     * membership_id...). $idemPrefix/$extRef seguem os prefixos do fluxo web para
     * o webhook e a conciliação continuarem funcionando igual.
     */
    public function criarCobrancaPixAvulsa(Cliente $cliente, float $amount, string $description, string $extRef, string $idemPrefix, ?array $split, array $paymentFields, array $bookingData, string $logContext = 'pagamento', string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);
            $conta = self::detalhar($amount, $billingType);

            $pixPayload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $conta['total'],
                'dueDate' => now()->addDay()->format('Y-m-d'),
                'description' => $description,
                'externalReference' => $extRef,
            ];

            if ($split) {
                $pixPayload['split'] = $split;
            }

            $paymentRes = Http::withHeaders($this->asaasHeaders())
                ->post($this->asaas().'/payments', $pixPayload);

            if ($paymentRes->failed()) {
                $corpoErro = $paymentRes->json();
                Log::error("Asaas {$logContext}: falha ao criar pagamento", ['body' => $corpoErro]);

                // O corpo do erro era descartado: a causa ficava só no log e o
                // usuário levava sempre "tente novamente" — conselho inútil
                // quando o problema é a conta do recebedor estar bloqueada.
                abort(response()->json([
                    'error' => self::mensagemDeErro($corpoErro, 'Falha ao gerar cobrança. Tente novamente.'),
                ], 500));
            }

            $asaasPaymentId = $paymentRes->json()['id'];
            // invoiceUrl = página de checkout hospedada do Asaas (aceita cartão
            // de crédito/débito e Pix quando billingType = UNDEFINED).
            $invoiceUrl = $paymentRes->json()['invoiceUrl'] ?? null;
            // QR Pix só faz sentido quando a cobrança é Pix.
            $qrData = $billingType === 'PIX' ? $this->pixQrCode($asaasPaymentId) : [];

            $valores = $this->calculateSplit($amount, $billingType);
            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'gateway_fee' => $valores['gateway_fee'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'status' => 'pending',
                'payment_method' => $billingType === 'PIX' ? 'pix' : 'card',
                'idempotency_key' => $idemPrefix.'_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ], $paymentFields));

            Log::info("Asaas {$logContext}: cobrança criada", [
                'payment_id' => $payment->id,
                'asaas_payment_id' => $asaasPaymentId,
                'billing_type' => $billingType,
                'preco' => $conta['base'],
                'cobrado' => $conta['total'],
                'cliente_id' => $cliente->id,
            ]);

            return [
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'invoiceUrl' => $invoiceUrl,
                // `amount` é o que o cliente PAGA — é com ele que o front
                // desenha o valor do QR code. `valores` leva a quebra para a
                // tela poder dizer de onde veio o acréscimo.
                'amount' => $conta['total'],
                'valores' => $conta,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("Asaas {$logContext}: exception ao criar pagamento", ['error' => $e->getMessage()]);

            abort(response()->json(['error' => 'Erro interno. Tente novamente.'], 500));
        }
    }

    /**
     * Cobrança avulsa do marketplace (conta da plataforma) com repasse integral
     * ao recebedor. Não cria registro Payment — a confirmação é feita por quem
     * chama, casando o externalReference no webhook. Usada na consulta do
     * nutricionista. Retorna ['asaasPaymentId','invoiceUrl'] ou [] em falha.
     *
     * `$amount` é o preço do profissional; o cliente paga preço + comissão.
     */
    public function criarCobrancaAvulsaComSplit(Cliente $cliente, float $amount, string $description, string $extRef, ?array $split, string $billingType = 'UNDEFINED'): array
    {
        $this->garantirValorMinimo($amount);

        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType, // UNDEFINED = checkout escolhe Pix/cartão/boleto
                'value' => self::valorCobrado($amount, $billingType),
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'description' => $description,
                'externalReference' => $extRef,
            ];
            if ($split) {
                $payload['split'] = $split;
            }

            $res = Http::withHeaders($this->asaasHeaders())->post($this->asaas().'/payments', $payload);
            if ($res->failed()) {
                Log::error('Asaas consulta nutri: falha ao criar cobrança', ['body' => $res->json()]);

                return [];
            }

            $data = $res->json();

            return [
                'asaasPaymentId' => $data['id'] ?? null,
                'invoiceUrl' => $data['invoiceUrl'] ?? null,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Asaas consulta nutri: exceção ao criar cobrança', ['error' => $e->getMessage()]);

            return [];
        }
    }

    // ─────────────────────────────────────────────
    // ASSINATURA MENSAL PIX (Asaas /subscriptions)
    // ─────────────────────────────────────────────

    /**
     * Cria uma assinatura mensal via PIX. A cada mês o Asaas gera uma nova
     * cobrança PIX (o aluno paga manualmente cada uma). Devolve o array de
     * dados da primeira cobrança (payload/QR) para o front.
     *
     * `$amount` é a mensalidade do PROFISSIONAL; o cliente paga mensalidade +
     * comissão. O parâmetro `$companyFeeRate` que existia aqui foi REMOVIDO:
     * ele era a porta por onde um `0.10` literal reentrava no cálculo (ver o
     * histórico em CLAUDE.md, "Revenue Model"). Não há mais taxa por cobrança
     * — só a da plataforma, em `config('taxas.comissao')`.
     */
    public function criarAssinaturaPix(Cliente $cliente, float $amount, string $description, string $extRefPrefix, string $idemPrefix, ?array $split, array $subFields, array $bookingData, string $billingType = 'PIX'): array
    {
        try {
            $asaasCustomerId = $this->obterOuCriarClienteAsaas($cliente);
            $conta = self::detalhar($amount, $billingType);

            $payload = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $conta['total'],
                'nextDueDate' => now()->format('Y-m-d'),
                'cycle' => 'MONTHLY',
                'description' => $description,
                'externalReference' => $extRefPrefix.'_'.$cliente->id.'_'.time(),
            ];
            if ($split) {
                $payload['split'] = $split;
            }

            $subRes = Http::withHeaders($this->asaasHeaders())->post($this->asaas().'/subscriptions', $payload);
            $subData = $subRes->json();

            if (! empty($subData['errors'])) {
                $errMsg = AsaasService::mensagemDeErro($subData, 'Falha ao criar assinatura.');
                Log::error('Asaas assinatura PIX: falha', ['body' => $subData]);

                /*
                 * Este é o caminho exato do defeito relatado: pacote de personal
                 * vira assinatura mensal, o split é recusado por conta
                 * bloqueada e, até aqui, a descrição crua do Asaas — com
                 * walletId dentro — era repassada ao app com 422.
                 *
                 * A trava de `montarSplit` deve impedir que se chegue aqui;
                 * quando chega, é porque o status local estava desatualizado
                 * (corrida com a sincronização). Reportar marca a subconta e
                 * avisa o admin, para a próxima tentativa já ser barrada antes.
                 */
                self::reportarBloqueioDeRecebedor(
                    $this->recebedorDosCampos($subFields),
                    $subData,
                    ['fluxo' => 'assinatura_pix', 'cliente_id' => $cliente->id]
                );

                abort(response()->json(['error' => $errMsg], 422));
            }
            if ($subRes->failed() || empty($subData['id'])) {
                Log::error('Asaas assinatura PIX: http error', ['body' => $subData]);

                abort(response()->json(['error' => 'Falha ao gerar assinatura. Tente novamente.'], 500));
            }

            $subscriptionId = $subData['id'];

            $firstPayment = $this->primeiraCobrancaAssinatura($subscriptionId);
            if (! $firstPayment || empty($firstPayment['id'])) {
                Log::error('Asaas assinatura PIX: primeira cobrança indisponível', ['subscription_id' => $subscriptionId]);

                abort(response()->json(['error' => 'Falha ao gerar a primeira cobrança. Tente novamente.'], 500));
            }
            $asaasPaymentId = $firstPayment['id'];
            $invoiceUrl = $firstPayment['invoiceUrl'] ?? null;

            $qrData = $billingType === 'PIX' ? $this->pixQrCode($asaasPaymentId) : [];

            $valores = $this->calculateSplit($amount, $billingType);
            $this->registrarAssinatura($cliente->id, $subscriptionId, $billingType === 'PIX' ? 'pix' : 'card', $valores, $subFields, $bookingData, $subData['nextDueDate'] ?? null);

            $payment = Payment::create(array_merge([
                'user_id' => $cliente->id,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'gateway_fee' => $valores['gateway_fee'],
                'stripe_payment_intent_id' => $asaasPaymentId,
                'asaas_subscription_id' => $subscriptionId,
                'status' => 'pending',
                'payment_method' => $billingType === 'PIX' ? 'pix' : 'card',
                'idempotency_key' => $idemPrefix.'_'.$asaasPaymentId,
                'booking_data' => json_encode($bookingData),
            ], $this->subFieldsToPayment($subFields)));

            Log::info('Asaas assinatura PIX criada', [
                'payment_id' => $payment->id,
                'subscription_id' => $subscriptionId,
                'asaas_payment_id' => $asaasPaymentId,
                'cliente_id' => $cliente->id,
            ]);

            return [
                'paymentId' => $payment->id,
                'asaasPaymentId' => $asaasPaymentId,
                'subscriptionId' => $subscriptionId,
                'pixPayload' => $qrData['payload'] ?? '',
                'pixQrCode' => $qrData['encodedImage'] ?? '',
                'invoiceUrl' => $invoiceUrl,
                'amount' => $conta['total'],
                'valores' => $conta,
                'recorrente' => true,
            ];
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Asaas assinatura PIX: exception', ['error' => $e->getMessage()]);

            abort(response()->json(['error' => 'Erro interno. Tente novamente.'], 500));
        }
    }

    /**
     * Busca a primeira cobrança gerada por uma assinatura (com pequena
     * tentativa de retry, pois o Asaas pode levar um instante para gerá-la).
     */
    public function primeiraCobrancaAssinatura(string $subscriptionId): ?array
    {
        for ($i = 0; $i < 3; $i++) {
            $res = Http::withHeaders($this->asaasHeaders())
                ->get($this->asaas()."/subscriptions/{$subscriptionId}/payments");
            $data = $res->json();

            if (! empty($data['data'][0]['id'])) {
                return $data['data'][0];
            }
            usleep(700000); // 0,7s
        }

        return null;
    }

    public function registrarAssinatura(int $userId, string $subscriptionId, string $method, array $valores, array $subFields, array $bookingData, ?string $nextDueDate): Subscription
    {
        return Subscription::updateOrCreate(
            ['asaas_subscription_id' => $subscriptionId],
            array_merge([
                'user_id' => $userId,
                'payment_method' => $method,
                'amount_total' => $valores['amount_total'],
                'company_fee' => $valores['company_fee'],
                'trainer_amount' => $valores['trainer_amount'],
                'gateway_fee' => $valores['gateway_fee'] ?? 0,
                'status' => 'active',
                'next_due_date' => $nextDueDate,
                'booking_data' => json_encode($bookingData),
            ], $subFields)
        );
    }

    /** Mapeia os vínculos da assinatura para as colunas da tabela payments. */
    public function subFieldsToPayment(array $subFields): array
    {
        return collect($subFields)
            ->only(['trainer_id', 'membership_id', 'academia_id', 'plano_id', 'studio_id', 'studio_plano_id'])
            ->toArray();
    }

    // ─────────────────────────────────────────────
    // CONSULTAS (QR code e status)
    // ─────────────────────────────────────────────

    /** Payload copia-e-cola e imagem base64 do QR code PIX de uma cobrança. */
    public function pixQrCode(string $asaasPaymentId): array
    {
        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}/pixQrCode");

        return $res->json() ?? [];
    }

    /** Consulta uma cobrança no Asaas (status atual etc.). Null em falha HTTP. */
    public function consultarPagamento(string $asaasPaymentId): ?array
    {
        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}");

        return $res->failed() ? null : $res->json();
    }

    /**
     * Os splits que o Asaas tem registrados para uma cobrança.
     *
     * Existe porque `payments` NÃO guarda o split enviado (ver o docblock de
     * ConferirRepasses), e sem isso "o repasse saiu?" só se responde abrindo o
     * painel do Asaas cobrança por cobrança. Duas perguntas diferentes se
     * respondem aqui, e confundi-las já custou tempo:
     *
     *  - lista VAZIA  → a cobrança foi criada SEM split. O dinheiro do
     *    profissional caiu inteiro na conta da plataforma. Causa a montante:
     *    recebedor sem walletId no código antigo, que seguia sem split.
     *  - lista com `status` recusado/cancelado → o split foi enviado e barrado
     *    DEPOIS. Causa a jusante: a validação de saque respondeu REFUSED —
     *    tipicamente ASAAS_WEBHOOK_TOKEN divergente, que recusa tudo.
     *
     * Devolve null quando não deu para consultar; quem chama deve tratar null
     * como "não sei", nunca como "não tem split".
     *
     * `$erro` recebe o status HTTP e o corpo quando a consulta falha. Sem isso
     * o chamador só sabe "falhou", e as causas pedem ações OPOSTAS: 401/403 é
     * credencial, 5xx é instabilidade (tentar de novo), e um 404 aqui — logo
     * depois de o GET da própria cobrança ter funcionado com as mesmas
     * credenciais — é forte indício de que o Asaas não tem split nenhum para
     * ela. Quem decide continua sendo o humano: devolver `[]` por causa de um
     * 404 seria o "não sei" virando "não tem", que é justamente o que o
     * contrato deste método existe para impedir.
     *
     * @param  array{status:int, corpo:string}|null  $erro
     * @return array<int, array<string, mixed>>|null
     */
    public function consultarSplitsDoPagamento(string $asaasPaymentId, ?array &$erro = null): ?array
    {
        $erro = null;

        $res = Http::withHeaders($this->asaasHeaders())
            ->get($this->asaas()."/payments/{$asaasPaymentId}/splits");

        if ($res->failed()) {
            $erro = [
                'status' => $res->status(),
                // Truncado: o corpo de erro do Asaas é curto, mas isto vai para
                // terminal e log e não vale despejar uma página de HTML.
                'corpo' => mb_strimwidth(trim((string) $res->body()), 0, 300, '…'),
            ];

            Log::warning('Asaas: consulta de splits falhou', [
                'payment' => $asaasPaymentId,
                'status' => $erro['status'],
                'corpo' => $erro['corpo'],
            ]);

            return null;
        }

        return (array) ($res->json('data') ?? []);
    }

    // ─────────────────────────────────────────────
    // TRANSFERÊNCIAS (saída de dinheiro da conta da PLATAFORMA)
    // ─────────────────────────────────────────────

    /**
     * Saldo disponível na conta da plataforma. Null quando não deu para consultar
     * — e quem chama DEVE tratar null como "não sei" e abortar, nunca como zero
     * ou como saldo infinito.
     */
    public function saldoPlataforma(): ?float
    {
        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(15)
                ->get($this->asaas().'/finance/balance');

            if ($res->failed() || ! isset($res->json()['balance'])) {
                Log::warning('Asaas: falha ao consultar saldo da plataforma', ['status' => $res->status()]);

                return null;
            }

            return (float) $res->json()['balance'];
        } catch (\Throwable $e) {
            Log::warning('Asaas: exceção ao consultar saldo da plataforma', ['erro' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Transfere por Pix da conta da PLATAFORMA para uma chave externa.
     *
     * Usa a access_token raiz: é a conta onde a comissão da plataforma fica, e é
     * de onde sai o bônus de indicação. Diferente de sacarPersonal/sacarSubconta,
     * que usam a apiKey da subconta do profissional.
     *
     * `$externalReference` não é cosmético: é por ele que o webhook de autorização
     * de saque reconhece a transferência como nossa e a aprova (qualquer outra é
     * recusada). Sempre passe um identificador verificável no nosso banco.
     *
     * Nunca lança: devolve ['ok' => false, 'erro' => mensagem] para quem chama
     * decidir. Um throw aqui, no meio de um fluxo de dinheiro, é pior que um
     * retorno tratável.
     *
     * @return array{ok:bool, id?:string, status?:string, authorized?:bool, receipt?:?string, erro?:string, duplicado?:bool}
     */
    public function transferirPix(
        float $valor,
        string $chavePix,
        string $tipoChave,
        string $descricao,
        string $externalReference
    ): array {
        if ($valor <= 0) {
            return ['ok' => false, 'erro' => 'Valor inválido para transferência.'];
        }

        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(30)
                ->post($this->asaas().'/transfers', [
                    'operationType' => 'PIX',
                    'value' => round($valor, 2),
                    'pixAddressKey' => $chavePix,
                    'pixAddressKeyType' => $tipoChave,
                    'description' => $descricao,
                    'externalReference' => $externalReference,
                ]);
        } catch (\Throwable $e) {
            // Timeout é o caso perigoso: a transferência PODE ter sido criada.
            // Sinalizamos indeterminado para quem chama não liberar nova tentativa
            // automática — a conciliação é pelo externalReference.
            Log::critical('Asaas transferência: exceção — estado INDETERMINADO, conciliar por externalReference', [
                'external_reference' => $externalReference,
                'erro' => $e->getMessage(),
            ]);

            return ['ok' => false, 'erro' => 'Falha de comunicação com o Asaas.', 'indeterminado' => true];
        }

        // 409 = transferência duplicada (mesmo valor/chave nos últimos 15 min).
        if ($res->status() === 409) {
            return [
                'ok' => false,
                'duplicado' => true,
                'erro' => 'O Asaas recusou como transferência duplicada. Aguarde 15 minutos.',
            ];
        }

        $data = $res->json() ?? [];

        // Erros de negócio (saldo insuficiente, chave Pix inválida...) chegam em
        // data.errors[].description.
        if (! empty($data['errors'])) {
            Log::warning('Asaas transferência: erro de negócio', [
                'external_reference' => $externalReference,
                'status' => $res->status(),
                'errors' => $data['errors'],
            ]);

            return ['ok' => false, 'erro' => $data['errors'][0]['description'] ?? 'Falha ao transferir.'];
        }

        if ($res->failed() || empty($data['id'])) {
            Log::error('Asaas transferência: falha HTTP', [
                'external_reference' => $externalReference,
                'status' => $res->status(),
            ]);

            return ['ok' => false, 'erro' => 'Falha ao transferir. Tente novamente.'];
        }

        // A02 — nunca logamos a chave Pix de destino, só metadados.
        Log::info('Asaas transferência criada', [
            'external_reference' => $externalReference,
            'transfer_id' => $data['id'],
            'status' => $data['status'] ?? null,
            'authorized' => $data['authorized'] ?? null,
            'valor' => round($valor, 2),
        ]);

        return [
            'ok' => true,
            'id' => (string) $data['id'],
            'status' => (string) ($data['status'] ?? 'PENDING'),
            // false = conta exige token SMS; a transferência fica parada até
            // alguém confirmar no painel do Asaas.
            'authorized' => (bool) ($data['authorized'] ?? true),
            'receipt' => $data['transactionReceiptUrl'] ?? null,
        ];
    }

    /** Consulta uma transferência (conciliação). Null em falha HTTP. */
    public function consultarTransferencia(string $transferId): ?array
    {
        try {
            $res = Http::withHeaders($this->asaasHeaders())
                ->timeout(15)
                ->get($this->asaas()."/transfers/{$transferId}");

            return $res->failed() ? null : $res->json();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Tipo da chave Pix a partir do formato. O Asaas exige `pixAddressKeyType`
     * junto da chave, e errar o tipo faz a transferência ser recusada.
     */
    public static function tipoChavePix(string $chave): string
    {
        $chave = trim($chave);

        if (filter_var($chave, FILTER_VALIDATE_EMAIL)) {
            return 'EMAIL';
        }

        $digitos = preg_replace('/\D/', '', $chave);

        // Só é documento/telefone se a chave for SÓ dígitos (com pontuação) —
        // uma chave aleatória (EVP) pode conter 11 dígitos entre letras.
        if ($digitos !== '' && preg_match('/^[\d.\-\/()+ ]+$/', $chave)) {
            if (strlen($digitos) === 14) {
                return 'CNPJ';
            }
            // Telefone brasileiro vem com DDI 55 (12-13) ou só DDD (10-11).
            if (strlen($digitos) >= 12 && strlen($digitos) <= 13) {
                return 'PHONE';
            }
            if (strlen($digitos) === 11) {
                // 11 dígitos é ambíguo: CPF ou celular com DDD. Celular tem 9 na
                // terceira posição (DD + 9XXXXXXXX); CPF normalmente não.
                return $chave[2] === '9' && ! str_contains($chave, '.') ? 'PHONE' : 'CPF';
            }
            if (strlen($digitos) === 10) {
                return 'PHONE';
            }
        }

        return 'EVP';
    }
}
