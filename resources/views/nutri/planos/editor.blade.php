@extends('layouts.nutri')
@section('titulo', 'Editor de plano')

@section('estilos')
<style>
    .meal { background:var(--card-bg); border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:14px; }
    .meal-head { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
    .meal-head input.mname { font-weight:700; font-size:1rem; flex:1; }
    .meal-head input.mtime { width:110px; }
    .item-row { display:grid; grid-template-columns:2.4fr .8fr .8fr repeat(4,.7fr) auto; gap:8px; align-items:center; padding:6px 0; font-size:.82rem; }
    .item-row .mac { text-align:center; color:var(--text-dim); }
    .item-head { color:var(--text-dim); font-size:.65rem; text-transform:uppercase; letter-spacing:.4px; }
    .search-box { position:relative; }
    .search-results { position:absolute; z-index:20; top:100%; left:0; right:0; background:var(--card-2); border:1px solid var(--border); border-radius:10px; max-height:240px; overflow:auto; display:none; }
    .search-results div { padding:9px 12px; cursor:pointer; font-size:.82rem; }
    .search-results div:hover { background:var(--primary); color:#000; }
    .totbar { position:sticky; bottom:0; background:linear-gradient(0deg,var(--bg-dark),rgba(10,11,13,.9)); border-top:1px solid var(--border); padding:14px 0; display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
    .tot { font-weight:800; } .tot small { color:var(--text-dim); font-weight:400; }
    .autosave { font-size:.75rem; color:var(--text-dim); }
    .lbl-sm { font-size:.62rem !important; }
    .item-wrap { border-bottom:1px solid var(--border); }
    .item-wrap .item-row { border:none; padding:6px 0; }
    .row-acts { display:flex; gap:6px; align-items:center; justify-content:flex-end; }
    .sub-line { display:flex; align-items:center; gap:10px; cursor:pointer; padding:6px 10px; margin:2px 0 6px; border-radius:8px; border:1px dashed var(--border); background:rgba(255,255,255,.02); font-size:.76rem; user-select:none; }
    .sub-line:hover { border-color:var(--primary); background:rgba(212,255,0,.05); }
    .sub-line-lbl { color:var(--primary); font-weight:700; white-space:nowrap; }
    .sub-line-hint { color:var(--text-dim); flex:1; }
    .sub-line-caret { color:var(--text-dim); }
    .sub-line.has { border-style:solid; border-color:rgba(212,255,0,.35); }
    .sub-line.open { border-style:solid; border-bottom-left-radius:0; border-bottom-right-radius:0; margin-bottom:0; }
    .subs-block { background:var(--card-2); border-radius:0 0 10px 10px; padding:10px 12px; margin:0 0 10px; border:1px solid rgba(212,255,0,.35); border-top:none; }
    .subs-title { font-size:.72rem; color:var(--text-dim); margin-bottom:8px; }
    .subs-list { display:flex; flex-direction:column; gap:6px; }
    .sub-chip { display:grid; grid-template-columns:1fr 80px 1fr auto auto; gap:8px; align-items:center; font-size:.8rem; }
    .sub-chip .sub-name { color:#fff; }
    .sub-chip input { padding:6px 8px; font-size:.78rem; }
    .sub-chip .sub-mac { font-size:.72rem; white-space:nowrap; }
    .dias-semana { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:6px; }
    .dia-pill { display:inline-flex; margin:0; cursor:pointer; }
    .dia-pill input { display:none; }
    .dia-pill span { display:inline-block; padding:7px 12px; border:1px solid var(--border); border-radius:20px; font-size:.78rem; color:var(--text-dim); transition:.15s; }
    .dia-pill input:checked + span { background:var(--primary); color:#000; border-color:var(--primary); font-weight:700; }
    .ficha-nav { display:flex; align-items:center; gap:10px; padding:10px 14px; margin-bottom:14px; }
    .ficha-chips { display:flex; gap:6px; overflow-x:auto; flex:1; padding-bottom:2px; }
    .ficha-chip { flex:0 0 auto; padding:6px 12px; border:1px solid var(--border); border-radius:20px; font-size:.76rem; color:var(--text-dim); white-space:nowrap; }
    .ficha-chip:hover { color:#fff; border-color:var(--text-dim); }
    .ficha-chip.atual { background:var(--primary); color:#000; border-color:var(--primary); font-weight:700; }
    @media(max-width:760px){ .item-row{ grid-template-columns:1fr 1fr; } .item-head{display:none;} .sub-chip{ grid-template-columns:1fr 1fr; } }
</style>
@endsection

@section('conteudo')
    <div class="topbar">
        <div>
            <h1>Editor de plano</h1>
            <div class="sub">
                {{ $plano->is_modelo ? 'Modelo reutilizável' : ('Paciente: '.($plano->paciente->nome ?? '—')) }}
                · <span class="autosave" id="autosave">Tudo salvo (v{{ $plano->versao }})</span>
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-ghost btn-sm" onclick="salvar(true)"><i class="ph ph-floppy-disk"></i> Salvar agora</button>
            <a href="{{ route('nutri.planos.pdf',$plano->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="ph ph-printer"></i> PDF</a>
            @if (!$plano->is_modelo && $plano->paciente_id)
                @if ($plano->ativo)
                    <span class="badge badge-ok" style="align-self:center;">Ativa para o paciente</span>
                    <form method="POST" action="{{ route('nutri.planos.desativar',$plano->id) }}"><button class="btn btn-ghost btn-sm"><i class="ph ph-eye-slash"></i> Desativar</button></form>
                @else
                    <form method="POST" action="{{ route('nutri.planos.ativar',$plano->id) }}"><button class="btn btn-sm"><i class="ph ph-check-circle"></i> Ativar p/ paciente</button></form>
                @endif
            @endif
        </div>
    </div>

    {{-- Navegador de fichas do paciente (folhear com as setas) --}}
    @if (isset($irmas) && $irmas->count() > 1)
        @php
            $prev = $irmas[$indiceAtual - 1] ?? $irmas->last();
            $next = $irmas[$indiceAtual + 1] ?? $irmas->first();
        @endphp
        <div class="card ficha-nav">
            <a href="{{ route('nutri.planos.editor',$prev->id) }}" class="btn btn-ghost btn-sm" title="Ficha anterior"><i class="ph ph-caret-left"></i></a>
            <div class="ficha-chips">
                @foreach ($irmas as $i => $f)
                    <a href="{{ route('nutri.planos.editor',$f->id) }}" class="ficha-chip {{ $i === $indiceAtual ? 'atual' : '' }}" title="{{ $f->nome }}">
                        {{ empty($f->dias_semana) ? 'Geral' : $f->diasSemanaLabels() }}
                    </a>
                @endforeach
            </div>
            <span class="muted" style="font-size:.75rem; white-space:nowrap;">Ficha {{ $indiceAtual + 1 }}/{{ $irmas->count() }}</span>
            <a href="{{ route('nutri.planos.editor',$next->id) }}" class="btn btn-ghost btn-sm" title="Próxima ficha"><i class="ph ph-caret-right"></i></a>
        </div>
    @endif

    <div class="grid" style="grid-template-columns:1fr 300px; align-items:start;">
        <div>
            <!-- Cabeçalho do plano -->
            <div class="card" style="margin-bottom:14px;">
                <div class="grid" style="grid-template-columns:2fr 1fr 1fr;">
                    <div><label>Nome do plano</label><input id="p_nome" value="{{ $plano->nome }}" oninput="marcarSujo()"></div>
                    <div><label>Objetivo</label>
                        <select id="p_objetivo" onchange="marcarSujo()">
                            <option value="">—</option>
                            @foreach ($objetivos as $o)<option value="{{ $o }}" @selected($plano->objetivo===$o)>{{ $o }}</option>@endforeach
                        </select>
                    </div>
                    <div><label>Meta kcal/dia</label><input type="number" id="p_kcal" value="{{ $plano->kcal_meta }}" oninput="marcarSujo()"></div>
                </div>
                <div style="margin-top:10px;"><label>Observações (visível ao paciente)</label><textarea id="p_obs" rows="2" oninput="marcarSujo()">{{ $plano->observacoes }}</textarea></div>

                @unless ($plano->is_modelo)
                <div style="margin-top:12px;">
                    <label>Dias da semana desta ficha</label>
                    <div class="dias-semana" id="diasSemana">
                        @foreach (\App\Models\Nutri\PlanoAlimentar::DIAS_SEMANA as $num => $lbl)
                            <label class="dia-pill">
                                <input type="checkbox" class="dia-cb" value="{{ $num }}" @checked(in_array($num, (array) ($plano->dias_semana ?? []))) onchange="marcarSujo()">
                                <span>{{ $lbl }}</span>
                            </label>
                        @endforeach
                    </div>
                    <span class="muted" style="font-size:.68rem;">Dias em que <strong>esta</strong> ficha vale (vazio = todos os dias). Para montar uma ficha <strong>diferente por dia</strong>, use o painel “Fichas por dia da semana” na página do paciente — cada dia vira uma ficha própria.</span>
                </div>
                @endunless
            </div>

            <div id="meals"></div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <button class="btn btn-ghost btn-sm" onclick="addRefeicao()"><i class="ph ph-plus"></i> Adicionar refeição</button>
                <select id="refPadrao" class="btn-sm" style="width:auto;">
                    <option value="">Refeição padrão…</option>
                    @foreach ($refeicoesPadrao as $rp)<option>{{ $rp }}</option>@endforeach
                </select>
                <button class="btn btn-ghost btn-sm" onclick="addRefeicaoPadrao()">Usar</button>
            </div>

            <div class="totbar">
                <span class="tot" id="tKcal">0 <small>kcal</small></span>
                <span class="tot" id="tCarb">0g <small>carbo</small></span>
                <span class="tot" id="tProt">0g <small>proteína</small></span>
                <span class="tot" id="tGord">0g <small>gordura</small></span>
                <span class="tot" id="tCusto" style="color:var(--primary)">R$ 0 <small>/mês ({{ $ufPlano ?? 'BR' }})</small></span>
                <span class="muted" id="tMeta"></span>
            </div>
        </div>

        <!-- Lateral: IA + modelo + versões -->
        <div>
            <div class="card" style="margin-bottom:14px;">
                <h3 style="margin-bottom:10px; font-size:.95rem;">Geração assistida</h3>
                <p class="muted" style="font-size:.75rem; margin-bottom:12px;">Responda às perguntas e o assistente monta um rascunho variado. Sempre revise antes de entregar.</p>
                <form method="POST" action="{{ route('nutri.planos.ia',$plano->id) }}">@csrf

                    <label class="lbl-sm">Meta calórica (kcal/dia)</label>
                    <input type="number" name="kcal_meta" value="{{ $plano->kcal_meta ?? 2000 }}" min="800" max="6000" required>

                    <label class="lbl-sm" style="margin-top:10px;">Quantas refeições por dia?</label>
                    <select name="num_refeicoes">
                        @foreach ([3,4,5,6] as $n)<option value="{{ $n }}" @selected($n===5)>{{ $n }} refeições</option>@endforeach
                    </select>

                    <label class="lbl-sm" style="margin-top:10px;">Preferência alimentar</label>
                    <select name="preferencia">
                        <option value="onivoro">Onívoro (padrão)</option>
                        <option value="vegetariano">Vegetariano</option>
                        <option value="vegano">Vegano</option>
                        <option value="low_carb">Low carb</option>
                    </select>

                    <label class="lbl-sm" style="margin-top:10px;">Proteína principal do plano</label>
                    <select name="proteina_id">
                        <option value="">Variar automaticamente</option>
                        @php $grpAtual = null; @endphp
                        @foreach ($proteinas as $prot)
                            @if ($prot->grupo !== $grpAtual)
                                @if ($grpAtual !== null)</optgroup>@endif
                                <optgroup label="{{ $prot->grupo }}">
                                @php $grpAtual = $prot->grupo; @endphp
                            @endif
                            <option value="{{ $prot->id }}">{{ $prot->nome }}</option>
                        @endforeach
                        @if ($grpAtual !== null)</optgroup>@endif
                    </select>

                    <label class="lbl-sm" style="margin-top:10px;">Estado (UF) do paciente</label>
                    <select name="uf">
                        <option value="">— (usa referência nacional)</option>
                        @foreach ($ufs as $u)<option value="{{ $u }}" @selected($ufPlano===$u)>{{ $u }}</option>@endforeach
                    </select>
                    <span class="muted" style="font-size:.68rem;">O preço dos alimentos varia por estado.</span>

                    <label class="lbl-sm" style="margin-top:10px;">Orçamento mensal do paciente (R$)</label>
                    <input type="number" name="orcamento_mensal" min="0" step="10" placeholder="Ex.: 600 (opcional)">
                    <span class="muted" style="font-size:.68rem;">Se informado, o plano prioriza opções mais econômicas.</span>

                    <label class="lbl-sm" style="margin-top:10px;">Restrições</label>
                    <div style="display:flex; flex-direction:column; gap:6px; font-size:.8rem;">
                        <label style="display:flex; gap:8px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:0;"><input type="checkbox" name="restricoes[]" value="sem_lactose" style="width:auto;"> Sem lactose</label>
                        <label style="display:flex; gap:8px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:0;"><input type="checkbox" name="restricoes[]" value="sem_gluten" style="width:auto;"> Sem glúten</label>
                        <label style="display:flex; gap:8px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:0;"><input type="checkbox" name="restricoes[]" value="sem_oleaginosas" style="width:auto;"> Sem oleaginosas</label>
                    </div>

                    @if (!$plano->is_modelo && $plano->paciente_id)
                    <div style="margin-top:12px; padding-top:12px; border-top:1px solid var(--border);">
                        <label style="display:flex; gap:8px; align-items:center; text-transform:none; color:#fff; font-weight:400; margin:0;">
                            <input type="checkbox" id="ia_por_dia" name="por_dia" value="1" style="width:auto;" onchange="document.getElementById('ia_dias').style.display=this.checked?'block':'none'">
                            Gerar uma ficha <strong>diferente para cada dia</strong> da semana
                        </label>
                        <div id="ia_dias" style="display:none; margin-top:10px;">
                            <span class="muted" style="font-size:.68rem;">Marque os dias que quer gerar (cada um vira uma ficha própria, com cardápio variado). O <strong>orçamento é dividido entre os dias</strong> — ex.: R$ 1400 ÷ 7 = R$ 200/dia.</span>
                            <div class="dias-semana" style="margin-top:6px;">
                                @foreach (\App\Models\Nutri\PlanoAlimentar::DIAS_SEMANA as $num => $lbl)
                                    <label class="dia-pill"><input type="checkbox" name="dias_semana[]" value="{{ $num }}" checked><span>{{ $lbl }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <button class="btn btn-sm" style="margin-top:12px; width:100%;" onclick="return confirmarGeracao()"><i class="ph ph-sparkle"></i> Gerar rascunho</button>
                </form>
            </div>

            <div class="card" style="margin-bottom:14px;">
                <h3 style="margin-bottom:10px; font-size:.95rem;">Reutilizar</h3>
                <form method="POST" action="{{ route('nutri.planos.modelo',$plano->id) }}">@csrf
                    <input name="nome" placeholder="Nome do modelo" value="{{ $plano->nome }} (modelo)">
                    <button class="btn btn-ghost btn-sm" style="margin-top:8px; width:100%;"><i class="ph ph-copy"></i> Salvar como modelo</button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin-bottom:10px; font-size:.95rem;">Histórico de versões</h3>
                <p class="muted" style="font-size:.72rem; margin-bottom:10px;">Nada se perde: cada salvamento gera uma versão recuperável.</p>
                @forelse ($versoes as $v)
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid var(--border);">
                        <div style="font-size:.78rem;">v{{ $v->versao }} <span class="muted">· {{ $v->origem }}</span><br><span class="muted" style="font-size:.68rem;">{{ optional($v->criado_em)->format('d/m H:i') }}</span></div>
                        <form method="POST" action="{{ route('nutri.planos.restaurar',[$plano->id,$v->id]) }}" onsubmit="return confirm('Restaurar a versão #{{ $v->versao }}?')">@csrf
                            <button class="btn btn-ghost btn-sm"><i class="ph ph-clock-counter-clockwise"></i></button>
                        </form>
                    </div>
                @empty
                    <div class="muted" style="font-size:.78rem;">Sem versões ainda.</div>
                @endforelse
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('nutri.planos.destroy',$plano->id) }}" style="margin-top:18px;" onsubmit="return confirm('Excluir este plano definitivamente?')">
        @csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i> Excluir plano</button>
    </form>
@endsection

@section('scripts')
<script>
    const SALVAR_URL = "{{ route('nutri.planos.salvar',$plano->id) }}";
    const BUSCAR_URL = "{{ route('nutri.alimentos.buscar') }}";
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const META = {{ (int) ($plano->kcal_meta ?? 0) }};
    const UF_INDICE = {{ $ufIndice }};
    const DIAS_MES = {{ (int) config('precos.dias_mes', 30) }};

    // Estado inicial vindo do servidor.
    let state = {!! json_encode([
        'refeicoes' => $plano->refeicoes->map(fn($r)=>[
            'nome'=>$r->nome,'horario'=>$r->horario,'observacoes'=>$r->observacoes,
            'itens'=>$r->itens->map(fn($i)=>[
                'alimento_id'=>$i->alimento_id,'descricao'=>$i->descricao,'quantidade_g'=>(float)$i->quantidade_g,
                'medida'=>$i->medida,'kcal'=>(float)$i->kcal,'carbo_g'=>(float)$i->carbo_g,
                'proteina_g'=>(float)$i->proteina_g,'gordura_g'=>(float)$i->gordura_g,
                // Opções de substituição (tabela separada) em formato estruturado.
                'substituicoes'=>$i->opcoes->map(fn($s)=>[
                    'alimento_id'=>$s->alimento_id,'descricao'=>$s->descricao,'quantidade_g'=>(float)$s->quantidade_g,
                    'medida'=>$s->medida,'kcal'=>(float)$s->kcal,'carbo_g'=>(float)$s->carbo_g,
                    'proteina_g'=>(float)$s->proteina_g,'gordura_g'=>(float)$s->gordura_g,
                    'base'=>$s->alimento_id && $s->quantidade_g>0 ? [
                        'kcal'=>round($s->kcal/($s->quantidade_g/100),2),
                        'carbo_g'=>round($s->carbo_g/($s->quantidade_g/100),2),
                        'proteina_g'=>round($s->proteina_g/($s->quantidade_g/100),2),
                        'gordura_g'=>round($s->gordura_g/($s->quantidade_g/100),2),
                    ] : null,
                ])->values(),
                'preco_kg'=>$i->alimento ? $i->alimento->precoKgRef() : 0,
                // base por 100g p/ recalcular no cliente quando houver alimento
                'base'=>$i->alimento_id && $i->quantidade_g>0 ? [
                    'kcal'=>round($i->kcal/($i->quantidade_g/100),2),
                    'carbo_g'=>round($i->carbo_g/($i->quantidade_g/100),2),
                    'proteina_g'=>round($i->proteina_g/($i->quantidade_g/100),2),
                    'gordura_g'=>round($i->gordura_g/($i->quantidade_g/100),2),
                ] : null,
            ])->values(),
        ])->values(),
    ]) !!};

    if (!state.refeicoes.length) {
        @foreach ($refeicoesPadrao as $rp)
        state.refeicoes.push({nome:@json($rp), horario:'', observacoes:'', itens:[]});
        @endforeach
    }

    let dirty = false, timer = null;

    function marcarSujo(){ dirty = true; document.getElementById('autosave').textContent='Editando…'; clearTimeout(timer); timer=setTimeout(()=>salvar(false), 1300); }

    function itemMacros(it){
        if (it.base && it.quantidade_g){
            const f = it.quantidade_g/100;
            return { kcal:it.base.kcal*f, carbo_g:it.base.carbo_g*f, proteina_g:it.base.proteina_g*f, gordura_g:it.base.gordura_g*f };
        }
        return { kcal:+it.kcal||0, carbo_g:+it.carbo_g||0, proteina_g:+it.proteina_g||0, gordura_g:+it.gordura_g||0 };
    }

    function render(){
        const box = document.getElementById('meals'); box.innerHTML='';
        state.refeicoes.forEach((ref, ri)=>{
            const meal = document.createElement('div'); meal.className='meal';
            const t = ref.itens.reduce((a,it)=>{const m=itemMacros(it); a.k+=m.kcal;a.c+=m.carbo_g;a.p+=m.proteina_g;a.g+=m.gordura_g;return a;},{k:0,c:0,p:0,g:0});
            meal.innerHTML = `
                <div class="meal-head">
                    <input class="mname" value="${esc(ref.nome)}" oninput="upd(${ri},'nome',this.value)">
                    <input class="mtime" type="time" value="${ref.horario||''}" oninput="upd(${ri},'horario',this.value)">
                    <span class="muted" style="font-size:.78rem;">${t.k.toFixed(0)} kcal</span>
                    <button class="btn btn-danger btn-sm" onclick="delRef(${ri})"><i class="ph ph-trash"></i></button>
                </div>
                <div class="item-row item-head"><span>Alimento</span><span>Qtd (g)</span><span>Medida</span><span class="mac">Kcal</span><span class="mac">C</span><span class="mac">P</span><span class="mac">G</span><span></span></div>
                <div id="itens-${ri}"></div>
                <div class="search-box" style="margin-top:8px;">
                    <input placeholder="Buscar alimento (TACO/TBCA) ou digite p/ item livre…" oninput="buscar(this,${ri})" onkeydown="if(event.key==='Enter'){event.preventDefault(); addLivre(${ri}, this.value); this.value='';}">
                    <div class="search-results"></div>
                </div>`;
            box.appendChild(meal);
            const cont = meal.querySelector(`#itens-${ri}`);
            ref.itens.forEach((it, ii)=>{
                if (!Array.isArray(it.substituicoes)) it.substituicoes = [];
                const m = itemMacros(it);
                const nSub = it.substituicoes.length;
                const wrap = document.createElement('div'); wrap.className='item-wrap';
                const row = document.createElement('div'); row.className='item-row';
                row.innerHTML = `
                    <span>${esc(it.descricao)}${it.base?'':' <span class="muted" style="font-size:.7rem;">(livre)</span>'}</span>
                    <input type="number" value="${it.quantidade_g||0}" oninput="updItem(${ri},${ii},'quantidade_g',+this.value)">
                    <input value="${esc(it.medida||'')}" oninput="updItem(${ri},${ii},'medida',this.value)">
                    <span class="mac">${m.kcal.toFixed(0)}</span><span class="mac">${m.carbo_g.toFixed(1)}</span>
                    <span class="mac">${m.proteina_g.toFixed(1)}</span><span class="mac">${m.gordura_g.toFixed(1)}</span>
                    <span class="row-acts">
                        <button class="btn btn-danger btn-sm" title="Remover alimento" onclick="delItem(${ri},${ii})"><i class="ph ph-x"></i></button>
                    </span>`;
                wrap.appendChild(row);

                // Faixa sempre visível para abrir/ver as substituições deste alimento.
                // Abre por padrão quando já há opções; depois respeita o clique do nutri.
                const opened = (it._showSubs === undefined) ? (nSub > 0) : it._showSubs;
                const line = document.createElement('div');
                line.className = 'sub-line' + (nSub ? ' has' : '') + (opened ? ' open' : '');
                line.onclick = ()=>toggleSubs(ri,ii);
                line.innerHTML = `
                    <span class="sub-line-lbl">↔ Substituições${nSub?` (${nSub})`:''}</span>
                    <span class="sub-line-hint">${nSub ? 'toque para ver/editar' : 'adicionar opções de troca p/ o paciente'}</span>
                    <span class="sub-line-caret">${opened ? '▾' : '▸'}</span>`;
                wrap.appendChild(line);

                if (opened) {
                    const sb = document.createElement('div'); sb.className='subs-block';
                    let chips = '';
                    it.substituicoes.forEach((s, si)=>{
                        const sm = itemMacros(s);
                        chips += `<div class="sub-chip">
                            <span class="sub-name">↔ ${esc(s.descricao)}</span>
                            <input type="number" class="sub-qtd" value="${s.quantidade_g||0}" title="Quantidade (g)" oninput="updSub(${ri},${ii},${si},'quantidade_g',+this.value)">
                            <input class="sub-med" value="${esc(s.medida||'')}" placeholder="medida" oninput="updSub(${ri},${ii},${si},'medida',this.value)">
                            <span class="muted sub-mac">${sm.kcal.toFixed(0)} kcal</span>
                            <button class="btn btn-danger btn-sm" onclick="delSub(${ri},${ii},${si})"><i class="ph ph-x"></i></button>
                        </div>`;
                    });
                    sb.innerHTML = `
                        <div class="subs-title">Substituições para "<strong>${esc(it.descricao)}</strong>" — o paciente pode trocar por qualquer uma:</div>
                        <div class="subs-list">${chips || '<span class="muted" style="font-size:.75rem;">Nenhuma opção ainda.</span>'}</div>
                        <div class="search-box" style="margin-top:6px;">
                            <input placeholder="Buscar alimento equivalente ou digite p/ opção livre (Enter)…" oninput="buscarSub(this,${ri},${ii})" onkeydown="if(event.key==='Enter'){event.preventDefault(); addSubLivre(${ri},${ii},this.value); this.value='';}">
                            <div class="search-results"></div>
                        </div>`;
                    wrap.appendChild(sb);
                }
                cont.appendChild(wrap);
            });
        });
        totais();
    }

    function totais(){
        let k=0,c=0,p=0,g=0,custoDia=0;
        state.refeicoes.forEach(r=>r.itens.forEach(it=>{
            const m=itemMacros(it); k+=m.kcal;c+=m.carbo_g;p+=m.proteina_g;g+=m.gordura_g;
            const preco=+it.preco_kg||0; custoDia += (it.quantidade_g||0)/1000 * preco * UF_INDICE;
        }));
        tKcal.innerHTML=`${k.toFixed(0)} <small>kcal</small>`; tCarb.innerHTML=`${c.toFixed(0)}g <small>carbo</small>`;
        tProt.innerHTML=`${p.toFixed(0)}g <small>proteína</small>`; tGord.innerHTML=`${g.toFixed(0)}g <small>gordura</small>`;
        const custoMes = custoDia*DIAS_MES;
        tCusto.innerHTML=`R$ ${custoMes.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2})} <small>/mês ({{ $ufPlano ?? 'BR' }})</small>`;
        const meta = +document.getElementById('p_kcal').value || META;
        tMeta.textContent = meta ? `Meta: ${meta} kcal (${(k-meta>=0?'+':'')}${(k-meta).toFixed(0)})` : '';
    }

    function esc(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
    function upd(ri,k,v){ state.refeicoes[ri][k]=v; marcarSujo(); if(k==='nome') return; }
    function updItem(ri,ii,k,v){ state.refeicoes[ri].itens[ii][k]=v; marcarSujo(); render(); }
    function delRef(ri){ state.refeicoes.splice(ri,1); marcarSujo(); render(); }
    function delItem(ri,ii){ state.refeicoes[ri].itens.splice(ii,1); marcarSujo(); render(); }
    function addRefeicao(){ state.refeicoes.push({nome:'Nova refeição',horario:'',observacoes:'',itens:[]}); marcarSujo(); render(); }
    function addRefeicaoPadrao(){ const v=document.getElementById('refPadrao').value; if(!v)return; state.refeicoes.push({nome:v,horario:'',observacoes:'',itens:[]}); marcarSujo(); render(); }
    function addLivre(ri, texto){ if(!texto.trim())return; state.refeicoes[ri].itens.push({alimento_id:null,descricao:texto.trim(),quantidade_g:0,medida:'',kcal:0,carbo_g:0,proteina_g:0,gordura_g:0,base:null,substituicoes:[]}); marcarSujo(); render(); }

    let buscaTimer=null;
    function buscar(input, ri){
        const box = input.nextElementSibling; const q=input.value.trim();
        clearTimeout(buscaTimer);
        if(q.length<2){ box.style.display='none'; return; }
        buscaTimer=setTimeout(()=>{
            fetch(`${BUSCAR_URL}?q=${encodeURIComponent(q)}`).then(r=>r.json()).then(list=>{
                box.innerHTML=''; box.style.display=list.length?'block':'none';
                list.forEach(a=>{
                    const d=document.createElement('div');
                    d.innerHTML=`<strong>${esc(a.nome)}</strong> <span class="muted">${a.kcal} kcal/100g · ${a.fonte}</span>`;
                    d.onclick=()=>{ addAlimento(ri,a); input.value=''; box.style.display='none'; };
                    box.appendChild(d);
                });
            });
        },250);
    }
    function addAlimento(ri, a){
        const qtd = a.porcao_g || 100;
        state.refeicoes[ri].itens.push({
            alimento_id:a.id, descricao:a.nome, quantidade_g:qtd, medida:a.medida_padrao||`${qtd} g`,
            base:{kcal:+a.kcal,carbo_g:+a.carbo_g,proteina_g:+a.proteina_g,gordura_g:+a.gordura_g},
            preco_kg:+a.preco_kg_ref||0,
            kcal:0,carbo_g:0,proteina_g:0,gordura_g:0, substituicoes:[]
        });
        marcarSujo(); render();
    }

    function confirmarGeracao(){
        const porDia = document.getElementById('ia_por_dia');
        if (porDia && porDia.checked) {
            const n = document.querySelectorAll('#ia_dias input[name="dias_semana[]"]:checked').length;
            if (!n) { alert('Marque pelo menos um dia da semana para gerar.'); return false; }
            return confirm('Isto vai gerar/atualizar '+n+' ficha(s) — uma por dia marcado — para este paciente. Continuar?');
        }
        return confirm('Isto substitui as refeições atuais por um novo rascunho. A versão atual fica salva no histórico. Continuar?');
    }

    // ---- Substituições (tabela separada) ----
    function toggleSubs(ri,ii){ const it=state.refeicoes[ri].itens[ii]; const cur=(it._showSubs===undefined)?((it.substituicoes||[]).length>0):it._showSubs; it._showSubs=!cur; render(); }
    function updSub(ri,ii,si,k,v){ state.refeicoes[ri].itens[ii].substituicoes[si][k]=v; marcarSujo(); render(); }
    function delSub(ri,ii,si){ state.refeicoes[ri].itens[ii].substituicoes.splice(si,1); marcarSujo(); render(); }
    function addSubLivre(ri,ii,texto){ if(!texto.trim())return; const it=state.refeicoes[ri].itens[ii]; it._showSubs=true; it.substituicoes.push({alimento_id:null,descricao:texto.trim(),quantidade_g:0,medida:'',kcal:0,carbo_g:0,proteina_g:0,gordura_g:0,base:null}); marcarSujo(); render(); }
    function addSubAlimento(ri,ii,a){
        const qtd = a.porcao_g || 100; const it=state.refeicoes[ri].itens[ii]; it._showSubs=true;
        it.substituicoes.push({
            alimento_id:a.id, descricao:a.nome, quantidade_g:qtd, medida:a.medida_padrao||`${qtd} g`,
            base:{kcal:+a.kcal,carbo_g:+a.carbo_g,proteina_g:+a.proteina_g,gordura_g:+a.gordura_g},
            kcal:0,carbo_g:0,proteina_g:0,gordura_g:0
        });
        marcarSujo(); render();
    }
    function buscarSub(input, ri, ii){
        const box = input.nextElementSibling; const q=input.value.trim();
        clearTimeout(buscaTimer);
        if(q.length<2){ box.style.display='none'; return; }
        buscaTimer=setTimeout(()=>{
            fetch(`${BUSCAR_URL}?q=${encodeURIComponent(q)}`).then(r=>r.json()).then(list=>{
                box.innerHTML=''; box.style.display=list.length?'block':'none';
                list.forEach(a=>{
                    const d=document.createElement('div');
                    d.innerHTML=`<strong>${esc(a.nome)}</strong> <span class="muted">${a.kcal} kcal/100g · ${a.fonte}</span>`;
                    d.onclick=()=>{ addSubAlimento(ri,ii,a); input.value=''; box.style.display='none'; };
                    box.appendChild(d);
                });
            });
        },250);
    }

    function payload(){
        return {
            nome: document.getElementById('p_nome').value,
            objetivo: document.getElementById('p_objetivo').value,
            kcal_meta: document.getElementById('p_kcal').value || null,
            observacoes: document.getElementById('p_obs').value,
            dias_semana: Array.from(document.querySelectorAll('.dia-cb:checked')).map(c=>+c.value),
            refeicoes: state.refeicoes.map(r=>({
                nome:r.nome, horario:r.horario||null, observacoes:r.observacoes||null,
                itens:r.itens.map(it=>({
                    alimento_id:it.alimento_id, descricao:it.descricao, quantidade_g:it.quantidade_g||0,
                    medida:it.medida||null,
                    substituicoes:(it.substituicoes||[]).map(s=>({
                        alimento_id:s.alimento_id, descricao:s.descricao, quantidade_g:s.quantidade_g||0,
                        medida:s.medida||null,
                        kcal:s.base?undefined:s.kcal, carbo_g:s.base?undefined:s.carbo_g,
                        proteina_g:s.base?undefined:s.proteina_g, gordura_g:s.base?undefined:s.gordura_g,
                    })),
                    kcal:it.base?undefined:it.kcal, carbo_g:it.base?undefined:it.carbo_g,
                    proteina_g:it.base?undefined:it.proteina_g, gordura_g:it.base?undefined:it.gordura_g,
                }))
            }))
        };
    }

    function salvar(manual){
        const p = payload(); p.manual = manual?1:0;
        document.getElementById('autosave').textContent='Salvando…';
        fetch(SALVAR_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:JSON.stringify(p) })
            .then(r=>r.json()).then(d=>{
                dirty=false;
                document.getElementById('autosave').textContent = `Tudo salvo (v${d.versao}) · ${d.salvo_em}`;
            }).catch(()=>{ document.getElementById('autosave').textContent='Erro ao salvar — tentando de novo'; setTimeout(()=>salvar(false),3000); });
    }

    window.addEventListener('beforeunload', e=>{ if(dirty){ e.preventDefault(); e.returnValue=''; } });
    render();
</script>
@endsection
