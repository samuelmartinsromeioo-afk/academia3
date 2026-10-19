<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\Consentimento;
use App\Services\AvisoService;
use App\Services\ExclusaoDeConta;
use App\Services\ExportacaoDeDados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * LGPD — direitos do titular dos dados (aluno, personal, academia, studio, loja).
 *
 * - Política de privacidade (pública).
 * - Acesso/portabilidade: exportar os próprios dados (JSON), via
 *   `ExportacaoDeDados` — a MESMA coleta que a API mobile usa.
 * - Consentimentos: ver o estado e revogar (cookies/medição e dados de saúde).
 * - Eliminação: excluir a conta. Para evitar quebra de integridade e respeitar
 *   a retenção legal de registros financeiros, a exclusão ANONIMIZA o perfil e
 *   APAGA os dados pessoais/sensíveis (saúde, fotos, medidas, mensagens).
 */
class LgpdController extends Controller
{
    public function politica()
    {
        return view('lgpd.politica');
    }

    public function meusDados(Request $request)
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $voltar = match ($ctx['tipo']) {
            'personal' => route('personal.dashboard'),
            'academia' => route('academia.dashboard'),
            'studio' => route('studio.dashboard'),
            'loja' => route('loja.dashboard'),
            default => route('cliente.index'),
        };

        $conta = $this->conta($ctx);

        return view('lgpd.meus-dados', [
            'tipo' => $ctx['tipo'],
            'voltar' => $voltar,
            // Estado de cada consentimento, para a página poder MOSTRAR a
            // escolha atual em vez de só oferecer o botão. Um painel que não
            // diz o que está valendo agora não é controle, é adivinhação.
            'cookiesAceito' => $conta
                ? Consentimento::concedido($conta, Consentimento::TIPO_MARKETING)
                : ($request->cookie(config('services.meta.consent_cookie', 'snrfit_consent')) === 'granted'),
            'saudeConsentido' => $conta ? Consentimento::concedido($conta, Consentimento::TIPO_SAUDE) : false,
            'temAnamnese' => $ctx['tipo'] === 'cliente'
                && Anamnese::where('cliente_id', $ctx['id'])->exists(),
        ]);
    }

    /**
     * Registra a escolha do banner de cookies (art. 7º, I, da LGPD).
     *
     * PÚBLICA de propósito: o banner aparece para o visitante que ainda não tem
     * conta, e nesse caso o cookie é o único registro possível. Havendo conta
     * na sessão, a escolha vai TAMBÉM para `consentimentos` — e é essa linha,
     * não o cookie, que o envio server-to-server consulta (ver
     * MetaConversionsService::trackServer). Sem ela, a renovação de assinatura
     * não tem como saber de consentimento algum, porque num webhook não existe
     * cookie.
     *
     * O cookie é gravado NO SERVIDOR, e não só pelo JS, para `Cookie::queue`
     * aplicar os mesmos atributos do resto da aplicação (Secure em produção).
     */
    public function registrarCookies(Request $request)
    {
        $dados = $request->validate([
            'escolha' => 'required|in:granted,denied',
        ]);

        $concedido = $dados['escolha'] === 'granted';

        if ($conta = $this->conta($this->ctx())) {
            Consentimento::registrar(
                $conta,
                Consentimento::TIPO_MARKETING,
                $concedido,
                $request,
                Consentimento::ORIGEM_SITE
            );
        }

        // 1 ano, sem httpOnly: o banner precisa ler no JS para não reaparecer.
        // Está em EncryptCookies::$except — sem isso o valor volta null.
        Cookie::queue(Cookie::make(
            config('services.meta.consent_cookie', 'snrfit_consent'),
            $dados['escolha'],
            60 * 24 * 365,
            '/',
            null,
            null,
            false,
            false,
            'Lax'
        ));

        return response()->json(['success' => true, 'escolha' => $dados['escolha']]);
    }

    /**
     * Revoga o consentimento de dados de saúde e APAGA a anamnese.
     *
     * A Política promete revogação "a qualquer momento, sem justificar", e até
     * aqui não havia rota nenhuma para isso: o único caminho era excluir a
     * conta inteira — ou seja, para tirar um dado o titular tinha de perder
     * todos. Revogar não é encerrar a conta: o aluno continua treinando, só
     * sem dado de saúde na Plataforma.
     *
     * A revogação é registrada ANTES da exclusão e numa transação: o livro de
     * consentimentos é o que explica por que o dado existiu e por que deixou de
     * existir, então apagar o dado e perder o registro do ato seria pior do que
     * não apagar.
     */
    public function revogarSaude(Request $request)
    {
        $ctx = $this->ctx();
        if (! $ctx || $ctx['tipo'] !== 'cliente') {
            return redirect()->route('login.index');
        }

        $cliente = Cliente::find($ctx['id']);
        if (! $cliente) {
            return redirect()->route('login.index');
        }

        DB::transaction(function () use ($cliente, $request) {
            Consentimento::registrar(
                $cliente,
                Consentimento::TIPO_SAUDE,
                false,
                $request,
                Consentimento::ORIGEM_SITE
            );

            Anamnese::where('cliente_id', $cliente->id)->delete();
        });

        // Fora da transação, de propósito: o aviso é efeito colateral de um ato
        // já consumado e não pode desfazê-lo se o e-mail falhar.
        AvisoService::consentimentoSaudeRevogado($cliente);

        return back()->with('sucesso', 'Consentimento revogado e anamnese apagada. '
            .'Sua conta continua ativa, e enviamos a confirmação para o seu e-mail. '
            .'Você pode preencher a anamnese de novo quando quiser.');
    }

    /** A conta logada, qualquer que seja o papel — ou null. */
    private function conta(?array $ctx)
    {
        if (! $ctx) {
            return null;
        }

        return match ($ctx['tipo']) {
            'personal' => Personal::find($ctx['id']),
            'academia' => Academia::find($ctx['id']),
            'studio' => Studio::find($ctx['id']),
            'loja' => Loja::find($ctx['id']),
            default => Cliente::find($ctx['id']),
        };
    }

    /**
     * Exporta todos os dados do titular em JSON (acesso e portabilidade).
     *
     * A coleta vive em `ExportacaoDeDados` para o app usar a MESMA — antes era
     * um punhado de métodos privados aqui, então a exportação prometida "no app
     * e no site" só existia no site.
     */
    public function exportar(ExportacaoDeDados $exportacao)
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $titular = $this->conta($ctx);
        if (! $titular) {
            return redirect()->route('login.index');
        }

        return response($exportacao->json($titular))
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$exportacao->nomeArquivo().'"');
    }

    // Exclui a conta: confirma senha, anonimiza o perfil e apaga dados pessoais.
    public function excluirConta(Request $request)
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $request->validate(['senha' => 'required|string'], ['senha.required' => 'Confirme sua senha para excluir a conta.']);

        $u = match ($ctx['tipo']) {
            'personal' => Personal::find($ctx['id']),
            'academia' => Academia::find($ctx['id']),
            'studio' => Studio::find($ctx['id']),
            'loja' => Loja::find($ctx['id']),
            default => Cliente::find($ctx['id']),
        };

        if (! $u || ! Hash::check($request->senha, $u->senha)) {
            return back()->withErrors(['senha' => 'Senha incorreta.']);
        }

        // `executar` encerra as cobranças, confirma por escrito e anonimiza, na
        // ordem certa — ver o cabeçalho do método. Não há nada a chamar antes.
        app(ExclusaoDeConta::class)->executar($u);

        session()->flush();

        return redirect()->route('login.index')
            ->with('sucesso', 'Sua conta foi encerrada, as assinaturas mensais foram canceladas e '
                .'seus dados pessoais foram removidos. Enviamos a confirmação para o seu e-mail. '
                .'Obrigado por usar o SnrFit.');
    }

    private function ctx(): ?array
    {
        foreach (['personal' => 'personal_id', 'cliente' => 'cliente_id', 'academia' => 'academia_id', 'studio' => 'studio_id', 'loja' => 'loja_id'] as $tipo => $chave) {
            if (session($chave)) {
                return ['tipo' => $tipo, 'id' => session($chave)];
            }
        }

        return null;
    }
}
