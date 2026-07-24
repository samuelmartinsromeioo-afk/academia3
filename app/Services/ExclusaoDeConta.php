<?php

namespace App\Services;

use App\Models\Anamnese;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Loja;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Exclusão de conta (direito de eliminação — LGPD / requisito da App Store).
 *
 * Para evitar quebra de integridade referencial e respeitar a retenção legal
 * de registros financeiros/fiscais, a exclusão ANONIMIZA o perfil e APAGA os
 * dados pessoais e sensíveis (saúde, fotos, medidas, mensagens). O e-mail é
 * trocado por um placeholder e a senha por um valor aleatório, de modo que a
 * conta não pode mais ser acessada.
 *
 * Usado tanto pelo painel web (LgpdController) quanto pela API mobile
 * (Api\ContaController), a partir do mesmo modelo autenticado.
 */
class ExclusaoDeConta
{
    /**
     * Exclui (anonimiza) a conta do titular informado, detectando o papel pelo
     * tipo do model.
     */
    public function executar(Model $titular): void
    {
        match (true) {
            $titular instanceof Personal => $this->anonimizarPersonal($titular),
            $titular instanceof Academia => $this->anonimizarAcademia($titular),
            $titular instanceof Studio   => $this->anonimizarStudio($titular),
            $titular instanceof Loja     => $this->anonimizarLoja($titular),
            $titular instanceof Cliente  => $this->anonimizarCliente($titular),
            default                      => null,
        };
    }

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
}
