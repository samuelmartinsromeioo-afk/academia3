@extends('layouts.nutri')
@section('titulo', 'Painel')

@section('conteudo')
    <div class="topbar">
        <div>
            <h1>Olá, {{ explode(' ', $nutri->nome)[0] }} 👋</h1>
            <div class="sub">{{ config('textos.nutri.painel_titulo') }}</div>
        </div>
        <a href="{{ route('nutri.pacientes.create') }}" class="btn"><i class="ph ph-plus"></i> Novo paciente</a>
    </div>

    <div class="grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:22px;">
        <div class="stat"><div class="n">{{ $totalPacientes }}</div><div class="l">Pacientes ativos</div></div>
        <div class="stat"><div class="n">{{ $proximasConsultas->count() }}</div><div class="l">Próximas consultas</div></div>
        <div class="stat"><div class="n" style="color:var(--ok)">R$ {{ number_format($recebidoMes,2,',','.') }}</div><div class="l">Recebido no mês</div></div>
        <div class="stat"><div class="n" style="color:var(--warn)">R$ {{ number_format($aReceber,2,',','.') }}</div><div class="l">A receber</div></div>
    </div>

    <div class="grid" style="grid-template-columns:1.4fr 1fr;">
        <div class="card">
            <h3 style="margin-bottom:16px;">Próximas consultas</h3>
            @forelse ($proximasConsultas as $c)
                <div style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border);">
                    <div>
                        <strong>{{ $c->paciente->nome ?? 'Paciente' }}</strong>
                        <div class="muted" style="font-size:.78rem;">{{ ucfirst($c->tipo) }} · {{ $c->modalidade }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div>{{ $c->data_hora->format('d/m') }}</div>
                        <div class="muted" style="font-size:.78rem;">{{ $c->data_hora->format('H:i') }}</div>
                    </div>
                </div>
            @empty
                <div class="empty"><i class="ph ph-calendar-x"></i>Nenhuma consulta agendada.<br><a href="{{ route('nutri.agenda') }}" class="muted">Ir para a agenda</a></div>
            @endforelse
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;">Pacientes recentes</h3>
            @forelse ($pacientesRecentes as $p)
                <a href="{{ route('nutri.pacientes.show',$p->id) }}" style="display:flex; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border);">
                    <strong>{{ $p->nome }}</strong>
                    <span class="muted" style="font-size:.78rem;">{{ $p->objetivo ?? '—' }}</span>
                </a>
            @empty
                <div class="empty"><i class="ph ph-user-plus"></i>Cadastre seu primeiro paciente.</div>
            @endforelse
        </div>
    </div>

    <div class="card" style="margin-top:22px; border-color:rgba(255,170,0,.28); background:rgba(255,170,0,.04);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="display:flex; align-items:center; gap:8px;"><i class="ph ph-user-minus" style="color:var(--warn);"></i> Pacientes ausentes
                @if($ausentes->count())<span class="badge badge-warn">{{ $ausentes->count() }}</span>@endif
            </h3>
            <span class="muted" style="font-size:.75rem;">Mais de um mês sem retorno</span>
        </div>
        @forelse ($ausentes as $p)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:11px 0; border-bottom:1px solid var(--border);">
                <div>
                    <a href="{{ route('nutri.pacientes.show',$p->id) }}"><strong>{{ $p->nome }}</strong></a>
                    <div class="muted" style="font-size:.75rem;">
                        {{ $p->objetivo ?? 'Sem objetivo' }}
                        @php $u = $p->ultimaInteracaoEm(); @endphp
                        · último contato {{ $u ? $u->format('d/m/Y') : '—' }}
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <span class="badge badge-warn">{{ $p->diasSemRetorno() }} dias</span>
                    @if($p->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/','',$p->whatsapp) }}" target="_blank" class="btn btn-ghost btn-sm" title="Chamar no WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
                    @endif
                    <a href="{{ route('nutri.pacientes.show',$p->id) }}" class="btn btn-ghost btn-sm">Ficha</a>
                </div>
            </div>
        @empty
            <div class="muted" style="font-size:.85rem; padding:8px 0;"><i class="ph ph-check-circle" style="color:var(--ok);"></i> Nenhum paciente ausente — todos com retorno recente. 🎉</div>
        @endforelse
    </div>

    <div class="card" style="margin-top:22px; border-color:rgba(212,255,0,.2); background:rgba(212,255,0,.03);">
        <div style="display:flex; gap:12px; align-items:flex-start;">
            <i class="ph ph-shield-check" style="color:var(--primary); font-size:1.4rem;"></i>
            <div>
                <strong>Seus dados são seus.</strong>
                <div class="muted" style="font-size:.85rem; margin-top:4px;">
                    {{ config('textos.profissional.diferenciais.confiabilidade') }}
                    {{ config('textos.profissional.diferenciais.portabilidade') }}
                    <a href="{{ route('nutri.exportar.pacientes') }}" style="color:var(--primary);">Exportar pacientes (CSV)</a> ·
                    <a href="{{ route('nutri.exportar.planos') }}" style="color:var(--primary);">Exportar planos (CSV)</a>
                </div>
            </div>
        </div>
    </div>
@endsection
