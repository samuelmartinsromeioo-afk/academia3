<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Loja;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use App\Models\DocumentoSubconta;
use App\Services\DocumentoSubcontaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Reenvio de documento da subconta pelo painel do próprio recebedor.
 *
 * Existe porque o cadastro resolve só o primeiro envio. O Asaas pode RECUSAR o
 * documento dias depois (foto ilegível, documento vencido), e sem um caminho de
 * reenvio aqui a pessoa voltaria a depender do painel do Asaas — exatamente o
 * que esta funcionalidade existe para evitar.
 */
class DocumentoSubcontaController extends Controller
{
    /**
     * Papéis que têm subconta. Cliente NÃO entra: não recebe pagamento, logo
     * não tem subconta nem documento a enviar.
     */
    private const PERFIS = [
        'personal_id' => Personal::class,
        'academia_id' => Academia::class,
        'studio_id' => Studio::class,
        'loja_id' => Loja::class,
    ];

    public function store(Request $request, DocumentoSubcontaService $servico)
    {
        $conta = $this->recebedorLogado();

        if (! $conta) {
            // Aluno logado cai aqui: tem sessão, mas não tem subconta.
            return back()->with('erro_documento', 'Esta conta não recebe pagamentos, então não há documento a enviar.');
        }

        $request->validate([
            /*
             * O TIPO é uma lista fechada do que NÓS oferecemos na tela, não
             * `string` livre. Sem isso o campo iria cru para o `type` de
             * `POST /myAccount/documents/{id}` e qualquer valor digitado
             * chegaria ao Asaas — e um tipo que ele não conhece faz o envio
             * falhar longe daqui, sem a pessoa entender por quê.
             */
            'tipo' => ['required', Rule::in([
                DocumentoSubconta::TIPO_IDENTIFICACAO,
                DocumentoSubconta::TIPO_SELFIE,
                DocumentoSubconta::TIPO_CONTRATO_SOCIAL,
                DocumentoSubconta::TIPO_CERTIFICADO_MEI,
            ])],
            // `required` AQUI, ao contrário do cadastro: este formulário existe
            // só para enviar arquivo, então sem arquivo não há o que fazer. No
            // cadastro é nullable para o 403 do Asaas não impedir a conta.
            'documento' => DocumentoSubcontaService::regraValidacao(obrigatorio: true),
        ], DocumentoSubcontaService::mensagensValidacao('documento'));

        $doc = $servico->guardar($conta, $request->file('documento'), $request->input('tipo'));

        if (! $doc) {
            return back()->with('erro_documento', 'Não foi possível guardar o arquivo. Tente novamente.');
        }

        /*
         * Tenta enviar na hora, para a pessoa ver o resultado em vez de ficar
         * sem saber se funcionou. Nunca lança; se não der, `asaas:enviar-documentos`
         * tenta de novo a cada 30 min e o status na tela acompanha.
         */
        $r = $servico->enviarPendentes($conta);

        Log::info('DocumentoSubconta: reenvio pelo painel', [
            'recebedor' => class_basename($conta),
            'id' => $conta->id,
            'tipo' => $doc->tipo,
            'resultado' => $r,
        ]);

        return back()->with('ok_documento', $this->mensagemDeResultado($doc->fresh(), $r));
    }

    /**
     * Traduz o resultado do envio para o dono — incluindo o caso em que o
     * Asaas só aceita pelo link, que NÃO é erro e não pode ser lido como tal.
     */
    private function mensagemDeResultado(DocumentoSubconta $doc, array $r): string
    {
        if ($doc->status === DocumentoSubconta::STATUS_SO_LINK) {
            return 'Recebemos o seu arquivo, mas este documento o Asaas só aceita pelo link oficial dele — '
                .'use o botão "Enviar documentos e regularizar" acima. É uma exigência do banco, não da nossa plataforma.';
        }

        if ($doc->status === DocumentoSubconta::STATUS_ENVIADO) {
            return 'Documento enviado para análise. A liberação costuma levar algumas horas.';
        }

        if (! $r['ok']) {
            return 'Documento recebido e guardado. Sua conta de recebimentos ainda está sendo criada — '
                .'assim que ela existir, enviamos o documento automaticamente, sem você precisar fazer nada.';
        }

        return 'Documento recebido. Vamos enviá-lo para análise em instantes.';
    }

    private function recebedorLogado()
    {
        foreach (self::PERFIS as $chave => $model) {
            if ($id = session($chave)) {
                return $model::find($id);
            }
        }

        return null;
    }
}
