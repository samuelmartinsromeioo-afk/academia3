{{--
    Renderiza UM campo de anamnese. Compartilhado entre o formulário do
    nutricionista e o questionário do portal do paciente — antes a lógica estava
    duplicada nos dois, e cada tipo novo precisava ser escrito duas vezes.

    $campo    array do schema: label, tipo, opcoes, ajuda
    $valor    resposta atual (string, número ou array em múltipla escolha)
    $prefixo  nome do input (padrão "respostas")
--}}
@php
    $label = $campo['label'];
    $tipo = $campo['tipo'] ?? 'texto';
    $prefixo = $prefixo ?? 'respostas';
    $valor = $valor ?? null;
    $nome = $prefixo.'['.$label.']';
    $ajuda = trim($campo['ajuda'] ?? '');
@endphp

<div style="margin-bottom:16px;">
    <label>{{ $label }}</label>

    @if ($tipo === 'textarea')
        <textarea name="{{ $nome }}" rows="3">{{ $valor }}</textarea>

    @elseif ($tipo === 'numero')
        <input type="number" step="any" name="{{ $nome }}" value="{{ $valor }}">

    @elseif ($tipo === 'data')
        <input type="date" name="{{ $nome }}" value="{{ $valor }}">

    @elseif ($tipo === 'sim_nao')
        <select name="{{ $nome }}">
            <option value="">—</option>
            <option @selected($valor === 'Sim')>Sim</option>
            <option @selected($valor === 'Não')>Não</option>
        </select>

    @elseif ($tipo === 'opcoes')
        <select name="{{ $nome }}">
            <option value="">—</option>
            @foreach (($campo['opcoes'] ?? []) as $op)
                <option @selected($valor === $op)>{{ $op }}</option>
            @endforeach
        </select>

    @elseif ($tipo === 'multipla')
        @php $marcados = is_array($valor) ? $valor : array_filter([$valor]); @endphp
        <div style="display:flex; flex-wrap:wrap; gap:10px 16px; padding:4px 0;">
            @foreach (($campo['opcoes'] ?? []) as $op)
                <label style="display:flex; gap:6px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:0; font-size:.85rem;">
                    {{-- [] no name porque múltipla escolha grava array --}}
                    <input type="checkbox" name="{{ $nome }}[]" value="{{ $op }}" style="width:auto;" @checked(in_array($op, $marcados))>
                    {{ $op }}
                </label>
            @endforeach
        </div>

    @elseif ($tipo === 'escala')
        <div style="display:flex; align-items:center; gap:12px;">
            <input type="range" min="0" max="10" step="1" name="{{ $nome }}"
                   value="{{ $valor !== null && $valor !== '' ? $valor : 5 }}"
                   oninput="this.nextElementSibling.textContent = this.value" style="flex:1;">
            <strong style="min-width:24px; text-align:center;">{{ $valor !== null && $valor !== '' ? $valor : 5 }}</strong>
        </div>

    @else
        <input type="text" name="{{ $nome }}" value="{{ $valor }}">
    @endif

    @if ($ajuda)
        <span class="muted" style="font-size:.7rem;">{{ $ajuda }}</span>
    @endif
</div>
