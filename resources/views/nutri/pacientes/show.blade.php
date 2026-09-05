@extends('layouts.nutri')
@section('titulo', $paciente->nome)

@section('conteudo')
    <div class="topbar">
        <div>
            <h1>{{ $paciente->nome }}</h1>
            <div class="sub">
                {{ $paciente->objetivo ?? 'Sem objetivo definido' }}
                @if($paciente->idade) · {{ $paciente->idade }} anos @endif
                @if($paciente->sexo) · {{ $paciente->sexo }} @endif
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('nutri.pacientes.edit',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-pencil"></i> Editar</a>
            <a href="{{ route('nutri.chat.conversa',$paciente->id) }}" class="btn btn-ghost btn-sm"><i class="ph ph-chat-circle"></i> Chat</a>
            <a href="{{ route('nutri.relatorios.paciente',$paciente->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="ph ph-file-text"></i> Relatório</a>
            <a href="{{ route('nutri.planos.store') }}" onclick="event.preventDefault(); document.getElementById('formNovoPlano').submit();" class="btn btn-sm"><i class="ph ph-plus"></i> Novo plano</a>
        </div>
    </div>

    <form id="formNovoPlano" method="POST" action="{{ route('nutri.planos.store') }}" style="display:none;">
        @csrf
        <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
        <input type="hidden" name="nome" value="Plano de {{ $paciente->nome }}">
        <input type="hidden" name="objetivo" value="{{ $paciente->objetivo }}">
    </form>

    @if ($paciente->estaAusente())
        @php $ult = $paciente->ultimaInteracaoEm(); @endphp
        <div class="flash flash-err" style="background:rgba(255,170,0,.08); border-color:rgba(255,170,0,.35); color:var(--warn);">
            <i class="ph ph-user-minus"></i>
            <div>
                <strong>Paciente ausente há {{ $paciente->diasSemRetorno() }} dias.</strong>
                Último retorno em {{ $ult ? $ult->format('d/m/Y') : '—' }} — considere fazer um follow-up.
                @if($paciente->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/\D/','',$paciente->whatsapp) }}" target="_blank" style="color:var(--warn); text-decoration:underline;">Chamar no WhatsApp</a>
                @endif
            </div>
        </div>
    @endif

    <!-- Portal do paciente -->
    <div class="card" style="margin-bottom:18px; border-color:rgba(212,255,0,.25);">
        <label>Link do portal do paciente (app)</label>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <input id="portalLink" readonly value="{{ route('portal.home',$paciente->portal_token) }}">
            <button class="btn btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('portalLink').value); this.innerHTML='<i class=\'ph ph-check\'></i> Copiado'"><i class="ph ph-copy"></i> Copiar</button>
        </div>
        <div class="muted" style="font-size:.75rem; margin-top:8px;">O paciente acessa plano, lista de compras, diário e check-ins sem precisar de senha.</div>
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;">
        <!-- Antropometria -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                <h3>Antropometria</h3>
                <a href="{{ route('nutri.antropometria.index',$paciente->id) }}" class="btn btn-ghost btn-sm">Ver evolução</a>
            </div>
            @if ($ultima)
                <div class="grid" style="grid-template-columns:repeat(3,1fr); gap:10px;">
                    <div class="stat"><div class="n" style="font-size:1.3rem;">{{ $ultima->peso ?? '—' }}<small style="font-size:.7rem"> kg</small></div><div class="l">Peso</div></div>
                    <div class="stat"><div class="n" style="font-size:1.3rem;">{{ $ultima->imc ?? '—' }}</div><div class="l">IMC — {{ \App\Models\Nutri\Antropometria::classificarImc($ultima->imc) }}</div></div>
                    <div class="stat"><div class="n" style="font-size:1.3rem;">{{ $ultima->percentual_gordura ?? '—' }}<small style="font-size:.7rem"> %</small></div><div class="l">Gordura</div></div>
                </div>
                <div class="muted" style="font-size:.75rem; margin-top:10px;">Última avaliação: {{ $ultima->data->format('d/m/Y') }}</div>
            @else
                <div class="empty" style="padding:20px;"><i class="ph ph-ruler"></i>Sem avaliações.<br><a href="{{ route('nutri.antropometria.index',$paciente->id) }}" class="muted">Registrar</a></div>
            @endif
        </div>

        <!-- Anamnese -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                <h3>Anamnese</h3>
                <a href="{{ route('nutri.anamnese.form',$paciente->id) }}" class="btn btn-ghost btn-sm">Nova anamnese</a>
            </div>
            @forelse ($paciente->anamneses->take(3) as $a)
                <div style="padding:10px 0; border-bottom:1px solid var(--border);">
                    <strong>{{ $a->modelo->nome ?? 'Anamnese' }}</strong>
                    <span class="badge {{ $a->origem==='pre_consulta'?'badge-warn':'badge-dim' }}" style="margin-left:6px;">{{ $a->origem==='pre_consulta'?'Pré-consulta':'Consultório' }}</span>
                    <div class="muted" style="font-size:.75rem;">{{ optional($a->preenchida_em)->format('d/m/Y H:i') }}</div>
                </div>
            @empty
                <div class="empty" style="padding:20px;"><i class="ph ph-clipboard-text"></i>Sem anamnese registrada.</div>
            @endforelse
        </div>

        <!-- Planos -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                <h3>Planos alimentares</h3>
            </div>
            @forelse ($paciente->planos as $plano)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border);">
                    <div>
                        <a href="{{ route('nutri.planos.editor',$plano->id) }}"><strong>{{ $plano->nome }}</strong></a>
                        @if($plano->ativo)<span class="badge badge-ok" style="margin-left:6px;">Ativo</span>@endif
                        <div class="muted" style="font-size:.72rem;">v{{ $plano->versao }} · atualizado {{ $plano->updated_at?->diffForHumans() }}</div>
                    </div>
                    <a href="{{ route('nutri.planos.editor',$plano->id) }}" class="btn btn-ghost btn-sm">Abrir</a>
                </div>
            @empty
                <div class="empty" style="padding:20px;"><i class="ph ph-fork-knife"></i>Nenhum plano.<br><a href="#" onclick="document.getElementById('formNovoPlano').submit(); return false;" class="muted">Criar plano</a></div>
            @endforelse
        </div>

        <!-- Financeiro -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:14px;">
                <h3>Cobranças</h3>
                <a href="{{ route('nutri.financeiro') }}" class="btn btn-ghost btn-sm">Financeiro</a>
            </div>
            @forelse ($paciente->cobrancas->take(4) as $c)
                <div style="display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border);">
                    <div>{{ $c->descricao }}<div class="muted" style="font-size:.72rem;">{{ optional($c->vencimento)->format('d/m/Y') }}</div></div>
                    <div style="text-align:right;">R$ {{ number_format($c->valor,2,',','.') }}<div><span class="badge {{ $c->status==='pago'?'badge-ok':'badge-warn' }}">{{ ucfirst($c->status) }}</span></div></div>
                </div>
            @empty
                <div class="empty" style="padding:20px;"><i class="ph ph-currency-dollar"></i>Sem cobranças.</div>
            @endforelse
        </div>
    </div>

    @if ($paciente->observacoes)
        <div class="card" style="margin-top:18px;"><label>Observações</label><div class="muted">{{ $paciente->observacoes }}</div></div>
    @endif

    <form method="POST" action="{{ route('nutri.pacientes.destroy',$paciente->id) }}" style="margin-top:18px;" onsubmit="return confirm('Arquivar este paciente? O histórico é mantido.')">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><i class="ph ph-archive"></i> Arquivar paciente</button>
    </form>
@endsection
