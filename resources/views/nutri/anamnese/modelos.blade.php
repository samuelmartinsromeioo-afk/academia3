@extends('layouts.nutri')
@section('titulo', 'Modelos de anamnese')

@section('conteudo')
    <div class="topbar">
        <div><h1>Modelos de anamnese</h1><div class="sub">Crie modelos reutilizáveis e específicos por perfil.</div></div>
        <button class="btn" onclick="novoModelo()"><i class="ph ph-plus"></i> Novo modelo</button>
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;">
        @foreach ($modelos as $m)
            <div class="card">
                <div style="display:flex; justify-content:space-between;">
                    <div>
                        <strong>{{ $m->nome }}</strong>
                        <span class="badge badge-dim">{{ \App\Models\Nutri\AnamneseModelo::PERFIS[$m->perfil] ?? $m->perfil }}</span>
                        @if ($m->uso_portal)<span class="badge badge-ok"><i class="ph ph-device-mobile"></i> Portal</span>@endif
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button class="btn btn-ghost btn-sm" onclick='editarModelo(@json($m))'><i class="ph ph-pencil"></i></button>
                        <form method="POST" action="{{ route('nutri.anamnese.modelos.deletar',$m->id) }}" onsubmit="return confirm('Remover modelo?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                    </div>
                </div>
                @php $secoes = array_keys($m->camposPorSecao()); @endphp
                <div class="muted" style="font-size:.8rem; margin-top:10px;">
                    <strong>{{ count($m->campos) }}</strong> campos em <strong>{{ count($secoes) }}</strong> seção(ões)
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:8px;">
                    @foreach (array_slice($secoes, 0, 6) as $s)
                        <span class="badge badge-dim" style="font-size:.66rem;">{{ $s }}</span>
                    @endforeach
                    @if (count($secoes) > 6)<span class="muted" style="font-size:.7rem;">+{{ count($secoes)-6 }}</span>@endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Editor -->
    <div id="editor" class="card" style="margin-top:22px; display:none;">
        <h3 id="editorTitulo" style="margin-bottom:16px;">Novo modelo</h3>
        <form method="POST" action="{{ route('nutri.anamnese.modelos.salvar') }}">
            @csrf
            <input type="hidden" name="id" id="mid">
            <div class="grid" style="grid-template-columns:2fr 1fr;">
                <div><label>Nome do modelo</label><input type="text" name="nome" id="mnome" required></div>
                <div><label>Perfil</label>
                    <select name="perfil" id="mperfil">
                        @foreach (\App\Models\Nutri\AnamneseModelo::PERFIS as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
            </div>

            <label style="display:flex; gap:8px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:14px 0 0; font-size:.85rem;">
                <input type="checkbox" name="uso_portal" value="1" id="mportal" style="width:auto;">
                Usar este modelo como <strong style="margin:0 4px;">questionário do portal</strong> (o que o paciente responde sozinho)
            </label>
            <span class="muted" style="font-size:.7rem;">Prefira um modelo curto aqui. Marcar este desmarca o anterior.</span>

            <label style="margin-top:16px;">Campos</label>
            <span class="muted" style="font-size:.7rem; display:block; margin-bottom:8px;">
                Campos com a mesma <strong>seção</strong> são agrupados no formulário. Sem seção, caem em "Geral".
            </span>
            {{-- Sugere as seções já usadas nos modelos deste nutri. --}}
            <datalist id="secoes">
                @foreach ($modelos->flatMap->campos->pluck('secao')->filter()->unique()->sort() as $s)
                    <option value="{{ $s }}">
                @endforeach
            </datalist>
            <div id="campos"></div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="addCampo()"><i class="ph ph-plus"></i> Adicionar campo</button>

            <div style="margin-top:18px; display:flex; gap:10px;">
                <button class="btn"><i class="ph ph-check"></i> Salvar modelo</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editor').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    let idx = 0;
    const TIPOS = @json(\App\Models\Nutri\AnamneseModelo::TIPOS);

    const esc = s => String(s ?? '').replace(/"/g,'&quot;');

    function linhaCampo(campo = {label:'', tipo:'texto', opcoes:[], secao:'', ajuda:''}) {
        const i = idx++;
        const opts = Object.entries(TIPOS).map(([k,v])=>`<option value="${k}" ${campo.tipo===k?'selected':''}>${v}</option>`).join('');
        const div = document.createElement('div');
        div.style = 'display:flex; gap:8px; margin-bottom:8px; align-items:center; flex-wrap:wrap;';
        div.innerHTML = `
            <input type="text" name="campos[${i}][secao]" value="${esc(campo.secao)}" placeholder="Seção" list="secoes" style="flex:1; min-width:120px;">
            <input type="text" name="campos[${i}][label]" value="${esc(campo.label)}" placeholder="Pergunta / campo" required style="flex:2; min-width:200px;">
            <select name="campos[${i}][tipo]" style="flex:1; min-width:130px;">${opts}</select>
            <input type="text" name="campos[${i}][opcoes]" value="${(campo.opcoes||[]).join(', ')}" placeholder="Opções (vírgula)" style="flex:1; min-width:140px;">
            <input type="text" name="campos[${i}][ajuda]" value="${esc(campo.ajuda)}" placeholder="Ajuda (opcional)" style="flex:1; min-width:140px;">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentNode.remove()"><i class="ph ph-x"></i></button>`;
        return div;
    }
    function addCampo(campo){ document.getElementById('campos').appendChild(linhaCampo(campo)); }
    function novoModelo(){
        document.getElementById('editorTitulo').textContent='Novo modelo';
        document.getElementById('mid').value='';
        document.getElementById('mnome').value='';
        document.getElementById('mperfil').value='geral';
        document.getElementById('mportal').checked=false;
        document.getElementById('campos').innerHTML=''; idx=0;
        addCampo(); addCampo();
        document.getElementById('editor').style.display='block';
        document.getElementById('editor').scrollIntoView({behavior:'smooth'});
    }
    function editarModelo(m){
        document.getElementById('editorTitulo').textContent='Editar modelo';
        document.getElementById('mid').value=m.id;
        document.getElementById('mnome').value=m.nome;
        document.getElementById('mperfil').value=m.perfil;
        document.getElementById('mportal').checked=!!m.uso_portal;
        document.getElementById('campos').innerHTML=''; idx=0;
        (m.campos||[]).forEach(addCampo);
        document.getElementById('editor').style.display='block';
        document.getElementById('editor').scrollIntoView({behavior:'smooth'});
    }
    // Normaliza "opcoes" (texto -> array) no submit.
    document.querySelector('#editor form').addEventListener('submit', function(){
        document.querySelectorAll('[name$="[opcoes]"]').forEach(function(inp){
            const base = inp.name.replace('[opcoes]','');
            (inp.value||'').split(',').map(s=>s.trim()).filter(Boolean).forEach((op,j)=>{
                const h=document.createElement('input'); h.type='hidden'; h.name=`${base}[opcoes][${j}]`; h.value=op; inp.parentNode.appendChild(h);
            });
            inp.disabled = true;
        });
    });
</script>
@endsection
