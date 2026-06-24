<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Anamnese;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\FichaTemplate;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\Mesociclo;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\RegistroExercicio;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Mensagem;
use App\Models\Meta;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * LGPD — direitos do titular dos dados.
 *
 * - Política de privacidade (pública).
 * - Acesso/portabilidade: exportar os próprios dados (JSON).
 * - Eliminação: excluir a conta. Para evitar quebra de integridade e respeitar
 *   a retenção legal de registros financeiros, a exclusão ANONIMIZA o perfil e
 *   APAGA os dados pessoais/sensíveis (saúde, fotos, medidas, mensagens).
 */
class LgpdController extends Controller
{
    // Página pública de política de privacidade.
    public function politica()
    {
        return view('lgpd.politica');
    }

    // Painel "Privacidade e meus dados" do usuário logado.
    public function meusDados()
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }
        $voltar = $ctx['tipo'] === 'personal' ? route('personal.dashboard') : route('cliente.index');

        return view('lgpd.meus-dados', ['tipo' => $ctx['tipo'], 'voltar' => $voltar]);
    }

    // Exporta todos os dados do titular em JSON (direito de acesso/portabilidade).
    public function exportar()
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $dados = $ctx['tipo'] === 'personal'
            ? $this->dadosPersonal($ctx['id'])
            : $this->dadosCliente($ctx['id']);

        $json = json_encode([
            'exportado_em' => now()->toDateTimeString(),
            'titular'      => $ctx['tipo'],
            'dados'        => $dados,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="meus-dados-snrfit.json"');
    }

    // Exclui a conta: confirma senha, anonimiza o perfil e apaga dados pessoais.
    public function excluirConta(Request $request)
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $request->validate(['senha' => 'required|string'], ['senha.required' => 'Confirme sua senha para excluir a conta.']);

        if ($ctx['tipo'] === 'personal') {
            $u = Personal::find($ctx['id']);
        } else {
            $u = Cliente::find($ctx['id']);
        }
        if (! $u || ! Hash::check($request->senha, $u->senha)) {
            return back()->withErrors(['senha' => 'Senha incorreta.']);
        }

        if ($ctx['tipo'] === 'personal') {
            $this->anonimizarPersonal($u);
        } else {
            $this->anonimizarCliente($u);
        }

        session()->flush();

        return redirect()->route('login.index')
            ->with('sucesso', 'Sua conta foi encerrada e seus dados pessoais foram removidos. Obrigado por usar o SnrFit.');
    }

    // ───────── coleta para exportação ─────────

    private function dadosCliente($id): array
    {
        $c = Cliente::find($id);

        return [
            'perfil'    => collect($c?->toArray())->except(['senha'])->all(),
            'anamnese'  => Anamnese::where('cliente_id', $id)->first()?->makeHidden([])->toArray(),
            'medidas'   => MedidaCorporal::where('cliente_id', $id)->get()->toArray(),
            'fotos_progresso' => FotoProgresso::where('cliente_id', $id)->get(['data', 'peso', 'observacao', 'caminho'])->toArray(),
            'metas'     => Meta::where('cliente_id', $id)->get()->toArray(),
            'treinos_concluidos' => TreinoConcluido::where('cliente_id', $id)->get()->toArray(),
            'cargas'    => RegistroExercicio::where('cliente_id', $id)->get()->toArray(),
            'mensagens' => Mensagem::where('cliente_id', $id)->get(['remetente', 'texto', 'created_at'])->toArray(),
        ];
    }

    private function dadosPersonal($id): array
    {
        $p = Personal::find($id);

        return [
            'perfil'     => collect($p?->toArray())->except(['senha', 'asaas_api_key', 'asaas_account_id', 'asaas_wallet_id'])->all(),
            'fichas'     => FichaTreino::where('personal_id', $id)->get()->toArray(),
            'mesociclos' => Mesociclo::where('personal_id', $id)->get()->toArray(),
            'templates'  => FichaTemplate::where('personal_id', $id)->get()->toArray(),
            'mensagens'  => Mensagem::where('personal_id', $id)->get(['remetente', 'texto', 'created_at'])->toArray(),
        ];
    }

    // ───────── anonimização ─────────

    private function anonimizarCliente(Cliente $c): void
    {
        Anamnese::where('cliente_id', $c->id)->delete();
        foreach (FotoProgresso::where('cliente_id', $c->id)->get() as $f) {
            if ($f->caminho) {
                Storage::disk('public')->delete($f->caminho);
            }
        }
        FotoProgresso::where('cliente_id', $c->id)->delete();
        MedidaCorporal::where('cliente_id', $c->id)->delete();
        Meta::where('cliente_id', $c->id)->delete();
        RegistroExercicio::where('cliente_id', $c->id)->delete();
        TreinoConcluido::where('cliente_id', $c->id)->delete();
        Mensagem::where('cliente_id', $c->id)->delete();
        Notificacao::where('destinatario_tipo', 'cliente')->where('destinatario_id', $c->id)->delete();

        if ($c->foto) {
            Storage::disk('public')->delete($c->foto);
        }

        $c->update([
            'nome' => 'Conta removida', 'email' => 'removido_' . $c->id . '@snrfit.local',
            'whatsapp' => null, 'foto' => null, 'cep' => null, 'rua' => null, 'bairro' => null,
            'cidade' => null, 'estado' => null, 'complemento' => null,
            'resumo_objetivo' => null, 'condicao_clinica' => null, 'altura' => null, 'peso' => null,
            'senha' => Hash::make(Str::random(40)),
        ]);
    }

    private function anonimizarPersonal(Personal $p): void
    {
        foreach (($p->fotos ?? collect()) as $foto) {
            if ($foto->path) {
                Storage::disk('public')->delete($foto->path);
            }
            $foto->delete();
        }
        Mensagem::where('personal_id', $p->id)->delete();
        Notificacao::where('destinatario_tipo', 'personal')->where('destinatario_id', $p->id)->delete();

        if ($p->foto) {
            Storage::disk('public')->delete($p->foto);
        }

        $p->update([
            'nome' => 'Conta removida', 'email' => 'removido_' . $p->id . '@snrfit.local',
            'cpf' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '',
            'complemento' => '', 'foto' => '', 'certificado' => '', 'whatsapp' => null,
            'chave_pix' => null, 'cref' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ]);
    }

    private function ctx(): ?array
    {
        if (session('personal_id')) {
            return ['tipo' => 'personal', 'id' => session('personal_id')];
        }
        if (session('cliente_id')) {
            return ['tipo' => 'cliente', 'id' => session('cliente_id')];
        }
        return null;
    }
}
