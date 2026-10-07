<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personais | SnrFit</title>
    <link rel="icon" type="image/png" href="{{ asset('SnrFit.png') }}">
    @include('partials.pwa')
    <link href="https://fonts.googleapis.com/css2?family=Syncopate:wght@700&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    @include('partials.brand-head')
    <style>
        :root {
            --primary: #7cff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --border: rgba(255,255,255,0.08);
            --accent: #1a5fd4;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0f1217 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 40px;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-family: 'Syncopate', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 3px;
        }
        .logo, .logo span { color: var(--primary); }

        .btn-top {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 9px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.78rem;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-top:hover { border-color: var(--primary); color: var(--primary); }

        .container { max-width: 1200px; margin: 0 auto; padding: 36px 20px; }

        .welcome { margin-bottom: 28px; }
        .welcome h1 { font-size: 1.6rem; font-weight: 900; }
        .welcome h1 em { color: var(--accent); font-style: normal; }
        .welcome p { color: var(--text-muted); margin-top: 4px; font-size: 0.9rem; }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0 14px;
            margin-bottom: 28px;
            max-width: 480px;
        }
        .search-wrapper i { color: var(--accent); }
        .search-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 13px 12px;
            color: #fff;
            outline: none;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 22px;
        }

        .card {
            background: var(--card-bg);
            position: relative;
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            transition: 0.25s;
            display: flex;
            flex-direction: column;
        }
        .card:hover { transform: translateY(-4px); border-color: rgba(26,95,212,0.4); box-shadow: 0 12px 30px rgba(0,0,0,0.5); }

        /* Pioneiro: só o selo ao lado do nome — sem realce no card.
           A regra abaixo não é destaque, é só o alinhamento do selo. */
        .card.pioneiro .card-body h3 { display: flex; align-items: center; gap: 5px; }

        .card-img {
            height: 160px;
            background: linear-gradient(135deg, rgba(26,95,212,0.18), rgba(124,255,0,0.06));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 2.4rem;
            position: relative;
            overflow: hidden;
        }
        .card-img img { width: 100%; height: 100%; object-fit: cover; }

        .card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(6px);
            color: var(--accent);
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 5px 11px;
            border-radius: 20px;
            border: 1px solid rgba(26,95,212,0.4);
        }

        .card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }

        .card-body h3 { font-size: 1.05rem; font-weight: 800; margin-bottom: 6px; }

        .card-meta { color: var(--text-muted); font-size: 0.78rem; margin-bottom: 4px; display: flex; align-items: center; gap: 7px; }
        .card-meta i { color: var(--accent); width: 14px; text-align: center; }

        .rating { color: #ffc107; font-size: 0.8rem; margin: 8px 0 12px; }
        .rating .num { color: var(--text-muted); }

        .card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        .preco { font-weight: 900; font-size: 1.05rem; }
        .preco small { color: var(--text-muted); font-weight: 400; font-size: 0.7rem; }

        .btn-detalhes {
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 800;
            font-size: 0.78rem;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-detalhes:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(124,255,0,0.25); }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: 0.4; color: var(--accent); }

        /* Abas de tipo de profissional */
        .tabs { display: inline-flex; gap: 6px; background: rgba(255,255,255,0.04); border: 1px solid var(--border); border-radius: 12px; padding: 5px; margin-bottom: 24px; }
        .tab { background: transparent; border: none; color: var(--text-muted); font-family: inherit; font-weight: 800; font-size: 0.82rem; padding: 10px 20px; border-radius: 9px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .tab .cnt { font-weight: 700; font-size: 0.72rem; opacity: 0.7; }
        .tab.active { background: var(--primary); color: #000; }
        .tab:not(.active):hover { color: #fff; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        /* Card do nutricionista (verde-lima em vez do azul) */
        .card.nutri:hover { border-color: rgba(124,255,0,0.4); }
        .card.nutri .card-img { background: linear-gradient(135deg, rgba(124,255,0,0.14), rgba(124,255,0,0.03)); color: var(--primary); }
        .card.nutri .card-badge { color: var(--primary); border-color: rgba(124,255,0,0.4); }
        .chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 4px; }
        .chip { font-size: 0.68rem; background: rgba(124,255,0,0.08); color: var(--primary); border: 1px solid rgba(124,255,0,0.2); padding: 3px 9px; border-radius: 20px; }
        .chip-mais { background: rgba(255,255,255,0.05); color: var(--text-muted); border-color: var(--border); }

        /* Filtro por modalidade de atendimento */
        .filtros-modalidade { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 18px; }
        .filtro-pill {
            display: inline-flex; align-items: center; gap: 7px; cursor: pointer;
            background: transparent; color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.12); border-radius: 999px;
            padding: 8px 15px; font-family: inherit; font-size: 0.78rem; font-weight: 700;
            transition: 0.2s;
        }
        .filtro-pill:hover { color: #fff; border-color: rgba(124,255,0,0.4); }
        .filtro-pill.active { background: var(--primary); color: #000; border-color: var(--primary); }
        .filtro-nota { font-size: 0.72rem; color: var(--text-muted); margin-left: 4px; }
        .filtro-rotulo { font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-right: 2px; }
        .pill-cnt { font-size: 0.66rem; opacity: 0.65; font-weight: 700; }
        .aviso-preferencia {
            display: flex; gap: 10px; align-items: flex-start;
            background: rgba(124,255,0,0.06); border: 1px solid rgba(124,255,0,0.22);
            border-radius: 12px; padding: 12px 15px; margin-bottom: 18px;
            font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;
        }
        .aviso-preferencia i { color: var(--primary); font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .aviso-preferencia strong { color: #fff; }
        .aviso-preferencia a { color: var(--primary); }

        @media (max-width: 600px) {
            .top-bar { padding: 14px 20px; }
        }
    </style>
</head>
<body class="ed-page">

<div class="top-bar">
    <div class="logo">SNR<span>FIT</span></div>
    <a href="{{ route('cliente.index') }}" class="btn-top"><i class="ph ph-arrow-left"></i> Voltar ao painel</a>
</div>

<div class="container">
    <div class="welcome">
        <div class="ed-eyebrow"><i class="ph ph-user"></i> Descobrir</div><h1 class="ed-h">Explorar <span class="ed-mark">Profissionais</span></h1>
        <p>Encontre personal trainers, veja avaliações e entre em contato.</p>
    </div>

    <div class="search-wrapper">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" id="buscaProfissional" placeholder="Buscar por nome ou cidade...">
    </div>

    {{-- Filtro por modalidade de atendimento.
         Só duas opções além de "Todas", de propósito: quem é Híbrido atende nos
         dois formatos, então aparece TANTO em Presencial quanto em Online. Uma
         pílula "Híbrido" separada esconderia esse profissional justamente das
         buscas que ele atende — ver filtrar() no fim do arquivo. --}}
    @php $modalidadeFiltro = $modalidadeFiltro ?? ''; @endphp
    <div class="filtros-modalidade" id="filtrosModalidade" data-inicial="{{ $modalidadeFiltro }}">
        <button class="filtro-pill {{ $modalidadeFiltro === '' ? 'active' : '' }}" data-modalidade="" onclick="filtrarModalidade(this)">
            <i class="ph ph-list"></i> Todas
        </button>
        <button class="filtro-pill {{ $modalidadeFiltro === 'Presencial' ? 'active' : '' }}" data-modalidade="Presencial" onclick="filtrarModalidade(this)">
            <i class="ph ph-barbell"></i> Presencial
        </button>
        <button class="filtro-pill {{ $modalidadeFiltro === 'Online' ? 'active' : '' }}" data-modalidade="Online" onclick="filtrarModalidade(this)">
            <i class="ph ph-monitor-play"></i> Online
        </button>
        <span class="filtro-nota" id="filtroNota"></span>
    </div>

    {{-- Filtro por especialidade.
         As pílulas vêm do catálogo que o controller derivou dos profissionais
         listados, então nunca existe uma opção que não casa com ninguém. Quem
         não declarou especialidade NÃO entra em nenhum filtro (ao contrário da
         modalidade) — ver filtroEspecialidades() no ClienteController. --}}
    @if (! empty($especialidadesDisponiveis))
        @php $especialidadeFiltro = $especialidadeFiltro ?? ''; @endphp
        <div class="filtros-modalidade" id="filtrosEspecialidade" data-inicial="{{ $especialidadeFiltro }}">
            <span class="filtro-rotulo">Especialidade</span>
            <button class="filtro-pill {{ $especialidadeFiltro === '' ? 'active' : '' }}" data-especialidade="" onclick="filtrarEspecialidade(this)">
                <i class="ph ph-list"></i> Todas
            </button>
            @foreach ($especialidadesDisponiveis as $esp => $quantos)
                <button class="filtro-pill {{ $especialidadeFiltro === $esp ? 'active' : '' }}" data-especialidade="{{ $esp }}" onclick="filtrarEspecialidade(this)">
                    <i class="ph ph-medal"></i> {{ $esp }} <span class="pill-cnt">{{ $quantos }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Quando o filtro vem da preferência do cadastro (e não de um clique), o
         aluno precisa saber por que a lista já está reduzida — senão parece que
         faltam profissionais. --}}
    @if ($modalidadeFiltro !== '' && ! request()->has('modalidade') && ($cliente->modalidade_preferida ?? null) === $modalidadeFiltro)
        <div class="aviso-preferencia">
            <i class="ph ph-info"></i>
            <span>
                Mostrando quem atende <strong>{{ $modalidadeFiltro }}</strong>, como você escolheu no cadastro.
                <a href="{{ route('personais.explorar', ['modalidade' => 'todas']) }}">Ver todos</a>
            </span>
        </div>
    @endif

    {{-- ABA: PERSONAIS --}}
    <div class="tab-panel active" id="panel-personais">
        @if ($personais->isEmpty())
            <div class="empty-state"><i class="ph ph-user-minus"></i><p>Nenhum personal disponível no momento.</p></div>
        @else
            <div class="grid grid-prof">
                @foreach ($personais as $personal)
                    @php
                        $esps = array_values(array_filter(array_map('trim', (array) $personal->especialidades)));
                        // Delimitado por "|" para o filtro casar o item inteiro: sem os
                        // delimitadores, "Musculação" daria match dentro de outra string.
                        $espAttr = $esps ? '|' . mb_strtolower(implode('|', $esps)) . '|' : '';
                    @endphp
                    <div class="card {{ $personal->eh_pioneiro ? 'pioneiro' : '' }}"
                         data-busca="{{ strtolower($personal->nome . ' ' . ($personal->cidade ?? '') . ' ' . implode(' ', $esps)) }}"
                         data-modalidade="{{ $personal->modalidade ?? '' }}"
                         data-especialidades="{{ $espAttr }}">
                        <div class="card-img">
                            @if ($personal->foto)
                                <img src="{{ asset('storage/' . $personal->foto) }}" alt="{{ $personal->nome }}">
                            @elseif ($personal->fotos->isNotEmpty())
                                <img src="{{ asset('storage/' . $personal->fotos->first()->path) }}" alt="{{ $personal->nome }}">
                            @else
                                <i class="ph ph-user-list"></i>
                            @endif
                            <span class="card-badge"><i class="ph ph-barbell"></i> Personal</span>
                        </div>
                        <div class="card-body">
                            <h3>
                                {{ $personal->nome }}
                                @if ($personal->eh_pioneiro)
                                    @include('partials.badge-pioneiro', ['posicao' => $personal->pioneiro_posicao, 'estado' => $personal->estado, 'tipo' => 'personal', 'tamanho' => 15])
                                @endif
                            </h3>
                            @if ($personal->cidade)
                                <div class="card-meta"><i class="ph ph-map-pin"></i> {{ $personal->cidade }}{{ $personal->estado ? ' - ' . $personal->estado : '' }}</div>
                            @endif
                            {{-- Modalidade de atendimento: o aluno precisa saber se o
                                 profissional atende presencialmente antes de se
                                 interessar. O ícone muda conforme o formato. --}}
                            @if ($personal->modalidade)
                                <div class="card-meta">
                                    <i class="ph {{ match ($personal->modalidade) {
                                        'Online'  => 'ph-monitor-play',
                                        'Híbrido' => 'ph-arrows-left-right',
                                        default   => 'ph-barbell',
                                    } }}"></i> {{ $personal->modalidade }}
                                </div>
                            @endif
                            {{-- Especialidades declaradas no cadastro. Ficaram anos sendo
                                 coletadas e nunca exibidas; é por elas que o aluno
                                 escolhe entre dois personais do mesmo preço. --}}
                            @if ($esps)
                                <div class="chips">
                                    @foreach (array_slice($esps, 0, 3) as $esp)
                                        <span class="chip">{{ $esp }}</span>
                                    @endforeach
                                    @if (count($esps) > 3)
                                        <span class="chip chip-mais">+{{ count($esps) - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="rating">
                                @if($personal->eh_novo_profissional)
                                    <span class="num" style="color: var(--primary);"><i class="ph ph-plant"></i> Novo profissional</span>
                                @else
                                    @php $media = (float) $personal->media_avaliacao; @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="ph-star {{ $i <= round($media) ? 'ph-fill' : 'ph' }}"></i>
                                    @endfor
                                    <span class="num">{{ $personal->media_avaliacao }} ({{ $personal->avaliacoes->count() }})</span>
                                @endif
                            </div>
                            <div class="card-footer">
                                <div class="preco">R$ {{ number_format($personal->valor_secao ?? 0, 2, ',', '.') }} <small>/aula</small></div>
                                <a href="{{ route('cliente.index') }}?personal={{ $personal->id }}" class="btn-detalhes">Ver detalhes <i class="ph ph-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="empty-state" id="semResultados" style="display:none;">
        <i class="ph ph-magnifying-glass"></i>
        <p>Nenhum profissional encontrado para a sua busca.</p>
    </div>
</div>

<script>
    const inputBusca = document.getElementById('buscaProfissional');

    // Modalidade selecionada: '' (todas), 'Presencial' ou 'Online'.
    // Começa no que o servidor decidiu — ?modalidade= da URL ou, sem ela, a
    // preferência que o aluno declarou no cadastro.
    let modalidadeAtiva = document.getElementById('filtrosModalidade')?.dataset.inicial || '';

    /**
     * Quem é Híbrido atende presencial E online, então satisfaz os dois filtros.
     * Sem isso, o profissional mais flexível seria escondido justamente das
     * buscas que ele atende — o oposto do que o aluno espera.
     */
    function atendeModalidade(card) {
        if (!modalidadeAtiva) return true;
        const m = card.dataset.modalidade || '';
        return m === modalidadeAtiva || m === 'Híbrido';
    }

    // Especialidade selecionada: '' (todas) ou o valor exato de uma pílula.
    let especialidadeAtiva = document.getElementById('filtrosEspecialidade')?.dataset.inicial || '';

    /**
     * Ao contrário da modalidade, quem NÃO declarou especialidade não passa: o
     * aluno clicou pedindo uma competência específica, e devolver um perfil que
     * nunca a afirmou faria a pílula mentir. O atributo vem delimitado por "|"
     * para casar o item inteiro, nunca um pedaço de outro.
     */
    function atendeEspecialidade(card) {
        if (!especialidadeAtiva) return true;
        const lista = card.dataset.especialidades || '';
        return lista.includes('|' + especialidadeAtiva.toLowerCase() + '|');
    }

    function filtrar() {
        const termo = (inputBusca?.value || '').toLowerCase().trim();
        const panel = document.querySelector('.tab-panel.active');
        let visiveis = 0;
        panel?.querySelectorAll('.card').forEach(card => {
            const ok = (!termo || card.dataset.busca.includes(termo))
                && atendeModalidade(card)
                && atendeEspecialidade(card);
            card.style.display = ok ? '' : 'none';
            if (ok) visiveis++;
        });

        const vazio = document.getElementById('semResultados');
        if (vazio) vazio.style.display = (visiveis === 0 && panel && panel.querySelectorAll('.card').length) ? '' : 'none';

        // Deixa explícito que o híbrido entra na conta, senão o aluno estranha
        // ver "Híbrido" num card filtrado por "Online".
        const nota = document.getElementById('filtroNota');
        if (nota) {
            nota.textContent = modalidadeAtiva
                ? `${visiveis} profissional(is) — inclui quem atende em formato híbrido`
                : '';
        }
    }

    function filtrarModalidade(botao) {
        modalidadeAtiva = botao.dataset.modalidade || '';
        document.querySelectorAll('#filtrosModalidade .filtro-pill')
            .forEach(b => b.classList.toggle('active', b === botao));

        // Mantém o filtro na URL para o link ser compartilhável e sobreviver ao
        // recarregar.
        const url = new URLSearchParams(location.search);
        modalidadeAtiva ? url.set('modalidade', modalidadeAtiva) : url.delete('modalidade');
        history.replaceState(null, '', location.pathname + (url.toString() ? '?' + url : ''));

        filtrar();
    }

    function filtrarEspecialidade(botao) {
        especialidadeAtiva = botao.dataset.especialidade || '';
        document.querySelectorAll('#filtrosEspecialidade .filtro-pill')
            .forEach(b => b.classList.toggle('active', b === botao));

        // Compõe com ?modalidade= em vez de substituir: os dois filtros são
        // independentes e o link tem de carregar os dois.
        const url = new URLSearchParams(location.search);
        especialidadeAtiva ? url.set('especialidade', especialidadeAtiva) : url.delete('especialidade');
        history.replaceState(null, '', location.pathname + (url.toString() ? '?' + url : ''));

        filtrar();
    }
    if (inputBusca) inputBusca.addEventListener('input', filtrar);

    // A pílula ativa e `modalidadeAtiva` já vieram marcadas do servidor (atributo
    // data-inicial), então aqui basta aplicar o filtro uma vez na carga. Não
    // chamamos filtrarModalidade() para não reescrever a URL do aluno que apenas
    // abriu a página com a própria preferência.
    filtrar();
</script>
</body>
</html>
