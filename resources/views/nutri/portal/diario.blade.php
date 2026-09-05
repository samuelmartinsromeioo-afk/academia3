@extends('nutri.portal.layout')
@section('titulo','Diário alimentar')

@section('conteudo')
    <div class="card">
        <strong>Registrar refeição</strong>
        <form method="POST" action="{{ route('portal.diario.salvar',$token) }}" enctype="multipart/form-data" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="data" value="{{ date('Y-m-d') }}">
            <div style="margin-bottom:10px;"><label>Refeição</label><input name="refeicao" placeholder="Ex.: Almoço"></div>
            <div style="margin-bottom:10px;"><label>O que você comeu?</label><textarea name="descricao" rows="2"></textarea></div>
            <div style="margin-bottom:10px;"><label>Foto (opcional)</label><input type="file" name="foto" accept="image/*"></div>
            <button class="btn" style="width:100%; justify-content:center;">Salvar no diário</button>
        </form>
    </div>

    <div class="card">
        <strong>Histórico</strong>
        @forelse ($registros as $reg)
            <div style="padding:12px 0; border-bottom:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between;">
                    <strong>{{ $reg->refeicao ?? 'Refeição' }}</strong>
                    <span class="muted" style="font-size:.75rem;">{{ $reg->data->format('d/m') }}</span>
                </div>
                @if($reg->descricao)<div class="muted" style="font-size:.85rem; margin-top:4px;">{{ $reg->descricao }}</div>@endif
                @if($reg->foto)<img src="{{ asset('storage/'.$reg->foto) }}" style="max-width:160px; border-radius:10px; margin-top:8px;">@endif
            </div>
        @empty
            <div class="muted" style="text-align:center; padding:20px;">Seu diário está vazio. Comece registrando uma refeição!</div>
        @endforelse
    </div>
@endsection
