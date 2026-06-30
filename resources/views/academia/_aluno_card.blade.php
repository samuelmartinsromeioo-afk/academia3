@php
    $nasc = $aluno->idade ? \Carbon\Carbon::parse($aluno->idade) : null;
@endphp
<div class="aluno-card" data-busca="{{ strtolower($aluno->nome) }}">
    <div class="aluno-head">
        @if($aluno->foto)
            <img src="{{ asset('storage/' . $aluno->foto) }}" alt="{{ $aluno->nome }}">
        @else
            <img src="https://ui-avatars.com/api/?name={{ urlencode($aluno->nome) }}&background=d4ff00&color=000">
        @endif
        <div>
            <h3>{{ $aluno->nome }}</h3>
            <small>{{ $aluno->email }}</small>
        </div>
    </div>

    <div class="aluno-data">
        <div class="data-row">
            <i class="ph ph-hourglass-medium"></i>
            <div>
                <span class="label">Idade</span>
                <span class="value">{{ $nasc ? $nasc->age . ' anos' : 'Não informada' }}</span>
            </div>
        </div>
        <div class="data-row">
            <i class="ph ph-cake"></i>
            <div>
                <span class="label">Data de aniversário</span>
                <span class="value">{{ $nasc ? $nasc->format('d/m/Y') : 'Não informada' }}</span>
            </div>
        </div>
        <div class="data-row">
            <i class="ph ph-heartbeat"></i>
            <div>
                <span class="label">Condição clínica</span>
                <span class="value">{{ $aluno->condicao_clinica ?: 'Nenhuma registrada' }}</span>
            </div>
        </div>
        <div class="data-row">
            <i class="ph ph-target"></i>
            <div>
                <span class="label">Objetivo</span>
                <span class="value">{{ $aluno->resumo_objetivo ?: 'Não informado' }}</span>
            </div>
        </div>
    </div>

    <div class="aluno-actions">
        <a href="{{ route('academia.aluno-fichas', $aluno->id) }}" class="btn-ficha">
            <i class="ph ph-clipboard-text"></i> Ficha
        </a>
        <a href="{{ route('academia.periodizacao.aluno', $aluno->id) }}" class="btn-periodizacao">
            <i class="ph ph-lightning"></i> Periodização
        </a>
        <a href="{{ route('academia.avaliacao-fisica.aluno', $aluno->id) }}" class="btn-avaliacao">
            <i class="ph ph-heartbeat"></i> Avaliação física
        </a>
    </div>
</div>
