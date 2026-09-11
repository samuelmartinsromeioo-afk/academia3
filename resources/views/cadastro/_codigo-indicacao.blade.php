{{--
    Campo de código de indicação, compartilhado pelos cadastros de profissional
    (personal/nutricionista, academia e studio). Cliente e loja não participam
    do programa, então não incluem este parcial.

    O campo é opcional e nunca barra o cadastro: código inexistente é ignorado
    em silêncio pelo IndicacaoService.
--}}
<div style="margin-top:14px;">
    <label for="codigo_indicacao">Código de indicação <span style="opacity:.6; font-weight:400;">(opcional)</span></label>
    <input type="text" name="codigo_indicacao" id="codigo_indicacao"
           value="{{ old('codigo_indicacao', request('ref')) }}"
           maxlength="16" autocapitalize="characters" autocomplete="off"
           placeholder="Ex.: JOAO4K2P"
           oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')">
    <small style="display:block; margin-top:4px; opacity:.65;">
        Recebeu o código de um profissional que já usa o SNR FIT? Digite aqui.
    </small>
</div>
