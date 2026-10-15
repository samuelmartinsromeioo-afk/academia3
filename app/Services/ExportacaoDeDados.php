<?php

namespace App\Services;

use App\Models\Anamnese;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\Studio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\Consentimento;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Mensagem;
use App\Models\Meta;
use App\Models\TermoAceite;
use Illuminate\Database\Eloquent\Model;

/**
 * Exportação dos dados do titular (direito de acesso e portabilidade — LGPD,
 * art. 18, II e V).
 *
 * Extraído de `Cadastro\LgpdController`, onde era um punhado de métodos
 * privados e por isso só existia no site. A Política e os Termos do Aluno
 * afirmavam que o titular exporta os dados "no app e no site", e no app não
 * havia nem tela nem endpoint — a promessa valia pela metade. Agora o site
 * (`LgpdController@exportar`) e a API (`Api\ContaController@exportar`) leem
 * daqui, do mesmo jeito que a exclusão já compartilhava `ExclusaoDeConta`.
 *
 * O formato é JSON legível por máquina, que é o que o art. 18 pede de
 * portabilidade — não um PDF.
 *
 * O que NUNCA sai daqui, e não é esquecimento:
 *  - `senha` (hash): não é dado do titular, é credencial, e exportá-la amplia
 *    a superfície de ataque sem lhe dar nenhuma informação sobre si;
 *  - `asaas_*`: são identificadores de conta no processador de pagamentos, não
 *    dados pessoais do titular.
 */
class ExportacaoDeDados
{
    /** Monta o pacote completo do titular, detectando o papel pelo model. */
    public function montar(Model $titular): array
    {
        $dados = match (true) {
            $titular instanceof Personal => $this->dadosPersonal($titular->id),
            $titular instanceof Academia,
            $titular instanceof Studio,
            $titular instanceof Loja => $this->dadosNegocio($titular),
            $titular instanceof Cliente => $this->dadosCliente($titular->id),
            default => [],
        };

        return [
            'exportado_em' => now()->toIso8601String(),
            'titular' => $this->papel($titular),
            'dados' => $dados,
            /*
             * Os registros de consentimento entram na exportação porque são
             * dado do titular sobre o titular, e são justamente o que ele
             * precisa para conferir o que autorizou e quando — o art. 18, VIII
             * dá direito à informação sobre o consentimento, e um pacote de
             * portabilidade que omitisse isso devolveria os dados sem o
             * fundamento pelo qual estão aqui.
             */
            'aceites_de_termos' => TermoAceite::doUsuario($titular)
                ->orderBy('aceito_em')
                ->get(['versao', 'aceito_em', 'ip', 'origem'])
                ->toArray(),
            'consentimentos' => Consentimento::doUsuario($titular)
                ->orderBy('registrado_em')
                ->get(['tipo', 'concedido', 'registrado_em', 'ip', 'origem'])
                ->toArray(),
        ];
    }

    /** Nome do arquivo sugerido no download. */
    public function nomeArquivo(): string
    {
        return 'meus-dados-snrfit-'.now()->format('Y-m-d').'.json';
    }

    /** JSON pronto para resposta, legível por humano e por máquina. */
    public function json(Model $titular): string
    {
        return (string) json_encode(
            $this->montar($titular),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function papel(Model $titular): string
    {
        return match (true) {
            $titular instanceof Personal => 'personal',
            $titular instanceof Academia => 'academia',
            $titular instanceof Studio => 'studio',
            $titular instanceof Loja => 'loja',
            $titular instanceof Cliente => 'cliente',
            default => 'desconhecido',
        };
    }

    private function dadosCliente($id): array
    {
        $c = Cliente::find($id);

        return [
            'perfil' => collect($c?->toArray())->except(['senha'])->all(),
            'anamnese' => Anamnese::where('cliente_id', $id)->first()?->toArray(),
            'medidas' => MedidaCorporal::where('cliente_id', $id)->get()->toArray(),
            'fotos_progresso' => FotoProgresso::where('cliente_id', $id)->get(['data', 'peso', 'observacao', 'caminho'])->toArray(),
            'metas' => Meta::where('cliente_id', $id)->get()->toArray(),
            'treinos_concluidos' => TreinoConcluido::where('cliente_id', $id)->get()->toArray(),
            'cargas' => RegistroExercicio::where('cliente_id', $id)->get()->toArray(),
            'mensagens' => Mensagem::where('cliente_id', $id)->get(['remetente', 'texto', 'created_at'])->toArray(),
        ];
    }

    private function dadosPersonal($id): array
    {
        $p = Personal::find($id);

        return [
            'perfil' => collect($p?->toArray())->except(['senha', 'asaas_api_key', 'asaas_account_id', 'asaas_wallet_id'])->all(),
            'fichas' => FichaTreino::where('personal_id', $id)->get()->toArray(),
            'mesociclos' => Mesociclo::where('personal_id', $id)->get()->toArray(),
            'templates' => FichaTemplate::where('personal_id', $id)->get()->toArray(),
            'mensagens' => Mensagem::where('personal_id', $id)->get(['remetente', 'texto', 'created_at'])->toArray(),
        ];
    }

    private function dadosNegocio(?Model $m): array
    {
        return [
            'perfil' => $m ? collect($m->toArray())->except(['senha', 'asaas_api_key', 'asaas_account_id', 'asaas_wallet_id'])->all() : [],
        ];
    }
}
