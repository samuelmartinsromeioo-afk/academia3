@extends('layouts.nutri')
@section('titulo', 'Alimentos')

@section('conteudo')
    <div class="topbar"><div><h1>Alimentos</h1><div class="sub">Base oficial (TACO/TBCA) + seus alimentos e preparações.</div></div></div>

    <div class="grid" style="grid-template-columns:1fr 2fr;">
        <form method="POST" action="{{ route('nutri.alimentos.store') }}" class="card">
            <h3 style="margin-bottom:14px;">Novo alimento (por 100 g)</h3>
            @csrf
            <div style="margin-bottom:10px;"><label>Nome</label><input name="nome" required></div>
            <div class="grid" style="grid-template-columns:1fr 1fr;">
                <div><label>Grupo</label><input name="grupo"></div>
                <div><label>Medida padrão</label><input name="medida_padrao" placeholder="colher de sopa"></div>
                <div><label>Porção (g)</label><input type="number" step="0.1" name="porcao_g" value="100"></div>
                <div><label>Kcal</label><input type="number" step="0.1" name="kcal" required></div>
                <div><label>Carbo (g)</label><input type="number" step="0.1" name="carbo_g" required></div>
                <div><label>Proteína (g)</label><input type="number" step="0.1" name="proteina_g" required></div>
                <div><label>Gordura (g)</label><input type="number" step="0.1" name="gordura_g" required></div>
                <div><label>Fibra (g)</label><input type="number" step="0.1" name="fibra_g" value="0"></div>
            </div>
            <button class="btn" style="margin-top:12px;"><i class="ph ph-plus"></i> Adicionar</button>
        </form>

        <div class="card">
            <h3 style="margin-bottom:14px;">Meus alimentos</h3>
            @if ($alimentos->count())
            <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Nome</th><th>Grupo</th><th>Kcal</th><th>C</th><th>P</th><th>G</th><th></th></tr></thead>
                <tbody>
                @foreach ($alimentos as $a)
                    <tr>
                        <td><strong>{{ $a->nome }}</strong></td>
                        <td class="muted">{{ $a->grupo ?? '—' }}</td>
                        <td>{{ $a->kcal }}</td><td>{{ $a->carbo_g }}</td><td>{{ $a->proteina_g }}</td><td>{{ $a->gordura_g }}</td>
                        <td style="text-align:right;"><form method="POST" action="{{ route('nutri.alimentos.destroy',$a->id) }}" onsubmit="return confirm('Remover?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="ph ph-trash"></i></button></form></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div style="margin-top:14px;">{{ $alimentos->links() }}</div>
            @else
                <div class="empty"><i class="ph ph-carrot"></i>Você ainda não cadastrou alimentos próprios.<br>A base oficial já está disponível no editor de plano.</div>
            @endif
        </div>
    </div>
@endsection
