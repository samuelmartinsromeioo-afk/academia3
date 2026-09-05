@extends('layouts.nutri')
@section('titulo', 'Agenda')

@section('conteudo')
    <div class="topbar">
        <div><h1>Agenda</h1><div class="sub">Consultas e lembretes.</div></div>
        <button class="btn" onclick="document.getElementById('nova').style.display='block'"><i class="ph ph-plus"></i> Agendar consulta</button>
    </div>

    <div id="nova" class="card" style="display:none; margin-bottom:18px;">
        <form method="POST" action="{{ route('nutri.consultas.store') }}">
            @csrf
            <div class="grid" style="grid-template-columns:2fr 1.4fr 1fr 1fr 1fr;">
                <div><label>Paciente</label>
                    <select name="paciente_id" required>
                        <option value="">Selecione…</option>
                        @foreach ($pacientes as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                    </select>
                </div>
                <div><label>Data e hora</label><input type="datetime-local" name="data_hora" required></div>
                <div><label>Duração (min)</label><input type="number" name="duracao_min" value="60"></div>
                <div><label>Tipo</label><select name="tipo"><option value="acompanhamento">Acompanhamento</option><option value="primeira">Primeira consulta</option><option value="retorno">Retorno</option></select></div>
                <div><label>Modalidade</label><select name="modalidade"><option>Presencial</option><option>Online</option></select></div>
            </div>
            <div style="margin-top:10px;"><label>Observações</label><input name="observacoes"></div>
            <button class="btn" style="margin-top:12px;"><i class="ph ph-check"></i> Agendar</button>
        </form>
    </div>

    <div class="card">
        @if ($consultas->count())
        <table>
            <thead><tr><th>Data</th><th>Paciente</th><th>Tipo</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($consultas as $c)
                <tr>
                    <td><strong>{{ $c->data_hora->format('d/m/Y H:i') }}</strong><div class="muted" style="font-size:.72rem;">{{ $c->duracao_min }} min · {{ $c->modalidade }}</div></td>
                    <td>{{ $c->paciente->nome ?? '—' }}</td>
                    <td class="muted">{{ ucfirst($c->tipo) }}</td>
                    <td>
                        <span class="badge {{ $c->status==='concluida'?'badge-ok':($c->status==='cancelada'?'badge-dim':'badge-warn') }}">{{ ucfirst($c->status) }}</span>
                    </td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="{{ $c->googleCalendarUrl() }}" target="_blank" class="btn btn-ghost btn-sm" title="Google Agenda"><i class="ph ph-google-logo"></i></a>
                        <a href="{{ route('nutri.consultas.ics',$c->id) }}" class="btn btn-ghost btn-sm" title="Baixar .ics"><i class="ph ph-calendar-plus"></i></a>
                        @if ($c->status==='agendada')
                        <form method="POST" action="{{ route('nutri.consultas.update',$c->id) }}" style="display:inline;">@csrf @method('PUT')<input type="hidden" name="status" value="concluida"><button class="btn btn-sm"><i class="ph ph-check"></i></button></form>
                        @endif
                        <form method="POST" action="{{ route('nutri.consultas.destroy',$c->id) }}" style="display:inline;" onsubmit="return confirm('Remover consulta?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <div class="empty"><i class="ph ph-calendar-dots"></i>Nenhuma consulta no período.</div>
        @endif
    </div>

    <div class="card" style="margin-top:14px; background:rgba(212,255,0,.03); border-color:rgba(212,255,0,.2);">
        <div class="muted" style="font-size:.82rem;"><i class="ph ph-info"></i> Reduza faltas: adicione a consulta ao Google Agenda pelo botão em cada linha. Lembretes automáticos por WhatsApp/e-mail podem ser disparados aos pacientes com contato cadastrado.</div>
    </div>
@endsection
