@extends('layouts.nutri')
@section('titulo','Metas de '.$paciente->nome)

@section('conteudo')
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
        <div>
            <h2 style="margin:0;">Metas comportamentais</h2>
            <div class="muted" style="font-size:.85rem;">
                {{ $paciente->nome }} — hábitos que o paciente marca no portal, entre as consultas.
            </div>
        </div>
        <a href="{{ route('nutri.pacientes.show',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-arrow-left"></i> Voltar ao paciente</a>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-bottom:12px;">Nova meta</h3>
        <form method="POST" action="{{ route('nutri.metas.store',$paciente->id) }}">
            @csrf
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px;">
                <div style="grid-column:1/-1;">
                    <label>Título</label>
                    <input name="titulo" required maxlength="255" placeholder="Ex.: Beber água, Dormir 7h, Caminhar">
                </div>
                <div>
                    <label>Tipo</label>
                    <select name="tipo" id="tipoMeta" onchange="toggleQtd()">
                        @foreach (\App\Models\Nutri\Meta::TIPOS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label>Frequência</label>
                    <select name="frequencia">
                        @foreach (\App\Models\Nutri\Meta::FREQUENCIAS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="campo-qtd" style="display:none;">
                    <label>Alvo</label>
                    <input type="number" step="0.1" min="0" name="alvo" placeholder="Ex.: 2">
                </div>
                <div class="campo-qtd" style="display:none;">
                    <label>Unidade</label>
                    <input name="unidade" maxlength="20" placeholder="L, min, passos">
                </div>
                <div style="grid-column:1/-1;">
                    <label>Descrição (opcional, o paciente lê)</label>
                    <textarea name="descricao" rows="2" maxlength="1000" placeholder="Ex.: Espalhe ao longo do dia, não tudo de uma vez."></textarea>
                </div>
            </div>
            <button class="btn btn-sm" style="margin-top:14px;"><i class="ph ph-plus"></i> Criar meta</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom:12px;">Metas do paciente</h3>

        @forelse ($metas as $meta)
            <div style="padding:14px 0; border-bottom:1px solid var(--border); {{ $meta->ativo ? '' : 'opacity:.5;' }}">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                    <div style="flex:1; min-width:220px;">
                        <strong>{{ $meta->titulo }}</strong>
                        @if ($meta->alvoLabel())
                            <span class="muted" style="font-size:.8rem;">· alvo {{ $meta->alvoLabel() }}</span>
                        @endif
                        @unless ($meta->ativo)<span class="muted" style="font-size:.72rem;"> · pausada</span>@endunless
                        @if ($meta->descricao)
                            <div class="muted" style="font-size:.8rem; margin-top:3px;">{{ $meta->descricao }}</div>
                        @endif
                        <div class="muted" style="font-size:.72rem; margin-top:5px;">
                            {{ \App\Models\Nutri\Meta::FREQUENCIAS[$meta->frequencia] ?? $meta->frequencia }}
                            · adesão 30 dias: <strong style="color:var(--primary);">{{ $meta->adesao() }}%</strong>
                            ({{ $meta->registros->where('concluida',true)->count() }} dia(s) cumprido(s))
                        </div>
                    </div>
                    <div style="display:flex; gap:6px;">
                        <form method="POST" action="{{ route('nutri.metas.alternar',$meta->id) }}">
                            @csrf
                            <button class="btn btn-ghost btn-sm" title="{{ $meta->ativo ? 'Pausar' : 'Reativar' }}">
                                <i class="ph ph-{{ $meta->ativo ? 'pause' : 'play' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('nutri.metas.destroy',$meta->id) }}"
                              onsubmit="return confirm('Remover a meta e todo o histórico de adesão dela?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty" style="padding:26px;">
                <i class="ph ph-target"></i>
                Nenhuma meta ainda. Crie a primeira acima — ela aparece no portal do paciente na hora.
            </div>
        @endforelse
    </div>

    <script>
        // Alvo e unidade só existem no tipo "quantidade".
        function toggleQtd() {
            const q = document.getElementById('tipoMeta').value === 'quantidade';
            document.querySelectorAll('.campo-qtd').forEach(e => e.style.display = q ? 'block' : 'none');
        }
        toggleQtd();
    </script>
@endsection
