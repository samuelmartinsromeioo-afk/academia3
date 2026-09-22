{{--
    Campo "Cupom de indicação" compartilhado pelos 5 formulários de cadastro.
    Prefill: ?cupom=XXXX do link de convite, ou o valor reenviado após erro.
    A checagem é só um feedback visual — quem valida de verdade é o servidor.
--}}
@php
    $cupomPrefill = old('cupom', \App\Models\Cupom::normalizar(request('cupom')));
@endphp

<div class="form-group full-width">
    <label>{{ config('indicacao.label') }}</label>
    <div class="input-wrapper">
        <i class="ph ph-gift"></i>
        <input type="text"
               name="cupom"
               id="campoCupom"
               value="{{ $cupomPrefill }}"
               maxlength="40"
               autocomplete="off"
               spellcheck="false"
               style="text-transform: uppercase;"
               placeholder="Ex: MARIA7F3K">
    </div>
    <small id="cupomFeedback"
           style="display:block;margin-top:6px;font-size:.75rem;line-height:1.4;color:#9ca3af;">
        {{ config('indicacao.ajuda') }}
    </small>
</div>

<script>
(function () {
    const campo    = document.getElementById('campoCupom');
    const feedback = document.getElementById('cupomFeedback');
    if (!campo || !feedback) return;

    const AJUDA = @json(config('indicacao.ajuda'));
    const URL   = @json(route('cupom.validar'));
    let timer   = null;

    function mostrar(texto, cor) {
        feedback.textContent = texto;
        feedback.style.color = cor;
    }

    function checar() {
        const codigo = campo.value.trim();

        if (!codigo) {
            mostrar(AJUDA, '#9ca3af');
            return;
        }

        mostrar('Verificando…', '#9ca3af');

        fetch(URL + '?codigo=' + encodeURIComponent(codigo), {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(d => mostrar(d.mensagem, d.valido ? '#7cff00' : '#ff6b6b'))
            .catch(() => mostrar(AJUDA, '#9ca3af'));
    }

    campo.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
        clearTimeout(timer);
        timer = setTimeout(checar, 450);
    });

    if (campo.value.trim()) checar();
})();
</script>
