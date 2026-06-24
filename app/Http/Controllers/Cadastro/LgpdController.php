<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
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
use App\Models\FotoProgresso;
use App\Models\MedidaCorporal;
use App\Models\Mensagem;
use App\Models\Meta;
use App\Models\Notificacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * LGPD — direitos do titular dos dados (aluno, personal, academia, studio, loja).
 *
 * - Política de privacidade (pública).
 * - Acesso/portabilidade: exportar os próprios dados (JSON).
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

    public function meusDados()
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $voltar = match ($ctx['tipo']) {
            'personal' => route('personal.dashboard'),
            'academia' => route('academia.dashboard'),
            'studio'   => route('studio.dashboard'),
            'loja'     => route('loja.dashboard'),
            default    => route('cliente.index'),
        };

        return view('lgpd.meus-dados', ['tipo' => $ctx['tipo'], 'voltar' => $voltar]);
    }

    // Exporta todos os dados do titular em JSON (direito de acesso/portabilidade).
    public function exportar()
    {
        $ctx = $this->ctx();
        if (! $ctx) {
            return redirect()->route('login.index');
        }

        $dados = match ($ctx['tipo']) {
            'personal' => $this->dadosPersonal($ctx['id']),
            'academia' => $this->dadosNegocio(Academia::find($ctx['id'])),
            'studio'   => $this->dadosNegocio(Studio::find($ctx['id'])),
            'loja'     => $this->dadosNegocio(Loja::find($ctx['id'])),
            default    => $this->dadosCliente($ctx['id']),
        };

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

        $u = match ($ctx['tipo']) {
            'personal' => Personal::find($ctx['id']),
            'academia' => Academia::find($ctx['id']),
            'studio'   => Studio::find($ctx['id']),
            'loja'     => Loja::find($ctx['id']),
            default    => Cliente::find($ctx['id']),
        };

        if (! $u || ! Hash::check($request->senha, $u->senha)) {
            return back()->withErrors(['senha' => 'Senha incorreta.']);
        }

        match ($ctx['tipo']) {
            'personal' => $this->anonimizarPersonal($u),
            'academia' => $this->anonimizarAcademia($u),
            'studio'   => $this->anonimizarStudio($u),
            'loja'     => $this->anonimizarLoja($u),
            default    => $this->anonimizarCliente($u),
        };

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
            'anamnese'  => Anamnese::where('cliente_id', $id)->first()?->toArray(),
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

    private function dadosNegocio(?Model $m): array
    {
        return [
            'perfil' => $m ? collect($m->toArray())->except(['senha', 'asaas_api_key', 'asaas_account_id', 'asaas_wallet_id'])->all() : [],
        ];
    }

    // ───────── anonimização ─────────

    private function anonimizarCliente(Cliente $c): void
    {
        Anamnese::where('cliente_id', $c->id)->delete();
        foreach (FotoProgresso::where('cliente_id', $c->id)->get() as $f) {
            $this->apagar($f->caminho);
        }
        FotoProgresso::where('cliente_id', $c->id)->delete();
        MedidaCorporal::where('cliente_id', $c->id)->delete();
        Meta::where('cliente_id', $c->id)->delete();
        RegistroExercicio::where('cliente_id', $c->id)->delete();
        TreinoConcluido::where('cliente_id', $c->id)->delete();
        Mensagem::where('cliente_id', $c->id)->delete();
        Notificacao::where('destinatario_tipo', 'cliente')->where('destinatario_id', $c->id)->delete();
        $this->apagar($c->foto);

        $c->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_' . $c->id . '@snrfit.local',
            'whatsapp' => null, 'foto' => null, 'cep' => null, 'rua' => null, 'bairro' => null,
            'cidade' => null, 'estado' => null, 'complemento' => null,
            'resumo_objetivo' => null, 'condicao_clinica' => null, 'altura' => null, 'peso' => null,
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarPersonal(Personal $p): void
    {
        foreach (($p->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        Mensagem::where('personal_id', $p->id)->delete();
        Notificacao::where('destinatario_tipo', 'personal')->where('destinatario_id', $p->id)->delete();
        $this->apagar($p->foto);

        $p->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_' . $p->id . '@snrfit.local',
            'cpf' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '',
            'complemento' => '', 'foto' => '', 'certificado' => '', 'whatsapp' => null,
            'chave_pix' => null, 'cref' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null, 'stripe_account_id' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarAcademia(Academia $a): void
    {
        foreach (($a->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        $a->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_' . $a->id . '@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '',
            'complemento' => '', 'endereco' => '',
            'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarStudio(Studio $s): void
    {
        foreach (($s->fotos ?? collect()) as $foto) {
            $this->apagar($foto->path);
            $foto->delete();
        }
        $s->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_' . $s->id . '@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '', 'endereco' => '',
            'whatsapp' => null, 'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function anonimizarLoja(Loja $l): void
    {
        $this->apagar($l->logo);
        $l->forceFill([
            'nome' => 'Conta removida', 'email' => 'removido_' . $l->id . '@snrfit.local',
            'cnpj' => '', 'cep' => '', 'rua' => '', 'bairro' => '', 'cidade' => '', 'estado' => '', 'endereco' => '',
            'whatsapp' => null, 'logo' => null, 'latitude' => null, 'longitude' => null, 'chave_pix' => null,
            'asaas_account_id' => null, 'asaas_wallet_id' => null, 'asaas_api_key' => null,
            'status' => 'rejeitado',
            'senha' => Hash::make(Str::random(40)),
        ])->save();
    }

    private function apagar(?string $caminho): void
    {
        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
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
