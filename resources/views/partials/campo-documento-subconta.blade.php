{{--
    Campo do documento de identidade, exigido pela análise da conta de
    recebimentos (Asaas). Incluído pelos QUATRO cadastros que têm subconta:
    personal, academia, studio e loja. O cadastro de ALUNO não o inclui — aluno
    não recebe pagamento, logo não tem subconta nem documento a enviar.

    Mesma convenção do `partials/campo-cupom`: depende de `.form-group` +
    `.input-wrapper`, que os cadastros definem com o mesmo desenho. Sem essas
    classes o input aparece como uma caixa branca crua.

    `required` está no HTML mas NÃO na regra do servidor, e isso é deliberado:
    pede o arquivo na tela (é o que faz a conta ser analisada sem a pessoa ir ao
    Asaas) sem transformar uma indisponibilidade do Asaas em cadastro impossível
    de concluir. Ver a nota longa em `Cadastro\PersonalController@store`.
--}}
<div class="form-group full-width">
    <label>Documento de identidade (RG ou CNH)</label>
    <div class="input-wrapper">
        <i class="ph ph-identification-card"></i>
        <input type="file" name="documento_identidade" required
               accept="image/jpeg,image/png,application/pdf">
    </div>

    <p style="font-size:0.78rem; line-height:1.5; color:var(--text-dim, #9aa0a6); margin:8px 0 0;">
        Usado só para a análise da sua <strong>conta de recebimentos</strong>, que é o que
        libera você a receber pagamentos pela plataforma. Enviando aqui, você
        <strong>não precisa ir ao site do Asaas</strong> depois.
        <br>
        JPG, PNG ou PDF, até 10 MB, legível e sem cortes.
        {{-- HEIC é o formato padrão da câmera do iPhone e a análise do Asaas não
             o aceita; avisar aqui evita a recusa dias depois, longe da causa. --}}
        Foto de iPhone em HEIC não é aceita — use Ajustes &gt; Câmera &gt; Formatos
        &gt; "Mais compatível", ou envie um print da foto.
    </p>

    @error('documento_identidade')
        <p style="font-size:0.78rem; color:#ff8a8a; margin:6px 0 0;">{{ $message }}</p>
    @enderror
</div>
