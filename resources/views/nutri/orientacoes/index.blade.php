@extends('layouts.nutri')
@section('titulo','Orientações nutricionais')

@section('conteudo')
    <div style="margin-bottom:18px;">
        <h2 style="margin:0;">Orientações nutricionais</h2>
        <div class="muted" style="font-size:.85rem;">
            Escreva uma vez e libere para quantos pacientes quiser. Eles leem no portal.
        </div>
    </div>

    @if ($categorias->isNotEmpty())
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
            <a href="{{ route('nutri.orientacoes.index') }}" class="btn btn-ghost btn-sm {{ $categoria ? '' : 'active' }}">Todas</a>
            @foreach ($categorias as $c)
                <a href="{{ route('nutri.orientacoes.index',['categoria'=>$c]) }}"
                   class="btn btn-ghost btn-sm" @if($categoria===$c) style="border-color:var(--primary); color:var(--primary);" @endif>{{ $c }}</a>
            @endforeach
        </div>
    @endif

    <div class="card" style="margin-bottom:18px;">
        <h3 style="margin-bottom:12px;" id="form-titulo">Nova orientação</h3>
        <form method="POST" action="{{ route('nutri.orientacoes.store') }}">
            @csrf
            <input type="hidden" name="id" id="ori_id">
            <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px;">
                <div><label>Título</label><input name="titulo" id="ori_titulo" required maxlength="255"></div>
                <div><label>Categoria</label><input name="categoria" id="ori_categoria" maxlength="60" placeholder="Rotina, Treino, Compras…" list="cats">
                    <datalist id="cats">@foreach ($categorias as $c)<option value="{{ $c }}">@endforeach</datalist>
                </div>
            </div>
            <div style="margin-top:12px;">
                <label>Conteúdo</label>
                <textarea name="conteudo" id="ori_conteudo" rows="8" required maxlength="20000"></textarea>
                <span class="muted" style="font-size:.7rem;">As quebras de linha são preservadas no portal.</span>
            </div>
            <div style="display:flex; gap:8px; margin-top:14px;">
                <button class="btn btn-sm"><i class="ph ph-floppy-disk"></i> Salvar</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="limparForm()">Limpar</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom:12px;">Biblioteca ({{ $orientacoes->count() }})</h3>

        @forelse ($orientacoes as $o)
            <div style="padding:14px 0; border-bottom:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:220px;">
                        @if ($o->categoria)<div style="font-size:.65rem; text-transform:uppercase; color:var(--primary); font-weight:700; letter-spacing:.5px;">{{ $o->categoria }}</div>@endif
                        <strong>{{ $o->titulo }}</strong>
                        <div class="muted" style="font-size:.8rem; margin-top:4px;">{{ Str::limit($o->conteudo, 160) }}</div>
                        <div class="muted" style="font-size:.72rem; margin-top:4px;">Liberada para {{ $o->pacientes()->count() }} paciente(s)</div>
                    </div>
                    <div style="display:flex; gap:6px; align-items:flex-start;">
                        <button class="btn btn-ghost btn-sm"
                                onclick='editar(@json($o->id), @json($o->titulo), @json($o->categoria), @json($o->conteudo))'>
                            <i class="ph ph-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('nutri.orientacoes.destroy',$o->id) }}"
                              onsubmit="return confirm('Remover da biblioteca? Ela some também dos pacientes que a recebiam.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm"><i class="ph ph-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty" style="padding:26px;"><i class="ph ph-book-open"></i>Nenhuma orientação nesta categoria.</div>
        @endforelse
    </div>

    <script>
        function editar(id, titulo, categoria, conteudo) {
            document.getElementById('ori_id').value = id;
            document.getElementById('ori_titulo').value = titulo;
            document.getElementById('ori_categoria').value = categoria || '';
            document.getElementById('ori_conteudo').value = conteudo;
            document.getElementById('form-titulo').textContent = 'Editando: ' + titulo;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function limparForm() {
            document.getElementById('ori_id').value = '';
            document.getElementById('ori_titulo').value = '';
            document.getElementById('ori_categoria').value = '';
            document.getElementById('ori_conteudo').value = '';
            document.getElementById('form-titulo').textContent = 'Nova orientação';
        }
    </script>
@endsection
