{{--
    Campo de código de indicação, compartilhado pelos cadastros de profissional
    (personal/nutricionista, academia e studio). Cliente e loja não participam
    do programa, então não incluem este parcial.

    Usa .form-group + .input-wrapper porque as três views definem essas classes
    com o mesmo visual — sem elas o campo renderiza como input branco cru,
    destoando do resto do formulário.

    O campo é opcional e nunca barra o cadastro: código inexistente é ignorado
    em silêncio pelo IndicacaoService.
--}}
<div class="form-group full-width">
    <label for="codigo_indicacao">Código de indicação (opcional)</label>
    <div class="input-wrapper">
        <i class="ph ph-gift"></i>
        <input type="text" name="codigo_indicacao" id="codigo_indicacao"
               value="{{ old('codigo_indicacao', request('ref')) }}"
               maxlength="16" autocapitalize="characters" autocomplete="off"
               placeholder="Ex.: JOAO4K2P"
               style="text-transform:uppercase; letter-spacing:1px;"
               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')">
    </div>
    <small style="display:block; margin-top:6px; color:#9ca3af; font-size:0.78rem;">
        Recebeu o código de um profissional que já usa o SNR FIT? Digite aqui.
    </small>
</div>
