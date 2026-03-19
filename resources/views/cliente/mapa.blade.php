<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa - Academias & Personais</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root {
            --primary: #d4ff00;
            --bg-dark: #0a0b0d;
            --card-bg: #16181d;
            --text-main: #ffffff;
            --text-muted: #a0a0a0;
            --error: #ff4444;
            --success: #00ff88;
            --border: rgba(255, 255, 255, 0.08);
            --academia-color: #d4ff00;
            --personal-color: #00c8ff;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-dark);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            background: rgba(0, 0, 0, 0.6);
            border-bottom: 1px solid var(--border);
            z-index: 1000;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .top-bar a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .top-bar a:hover { color: var(--primary); }

        .top-bar h1 {
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: 1px;
            color: var(--primary);
        }

        .main-layout {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 340px;
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .filter-tab {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-tab.active-academia {
            background: rgba(212, 255, 0, 0.1);
            border-color: var(--academia-color);
            color: var(--academia-color);
        }

        .filter-tab.active-personal {
            background: rgba(0, 200, 255, 0.1);
            border-color: var(--personal-color);
            color: var(--personal-color);
        }

        .filter-tab.active-todos {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .search-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 12px;
        }

        .search-wrapper i { color: var(--primary); font-size: 0.85rem; }

        .search-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            padding: 10px 12px;
            color: #fff;
            outline: none;
            font-size: 0.85rem;
        }

        .sidebar-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .sidebar-list::-webkit-scrollbar { width: 4px; }
        .sidebar-list::-webkit-scrollbar-track { background: transparent; }
        .sidebar-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

        .pin-card {
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 8px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pin-card:hover { background: rgba(255,255,255,0.04); }
        .pin-card.selected { background: rgba(212,255,0,0.05); border-color: rgba(212,255,0,0.3); }

        .pin-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .pin-icon.academia { background: rgba(212,255,0,0.12); color: var(--academia-color); }
        .pin-icon.personal { background: rgba(0,200,255,0.12); color: var(--personal-color); }

        .pin-info h4 { font-size: 0.85rem; font-weight: 700; margin-bottom: 3px; }
        .pin-info p { font-size: 0.72rem; color: var(--text-muted); margin: 0; }
        .pin-info span {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }

        .badge-academia { background: rgba(212,255,0,0.1); color: var(--academia-color); }
        .badge-personal { background: rgba(0,200,255,0.1); color: var(--personal-color); }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state i { font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.4; }

        /* Mapa */
        #map {
        flex: 1;
        z-index: 1;
        height: 100%;
        width: 100%;
        }

        /* Popup customizado */
        .leaflet-popup-content-wrapper {
            background: var(--card-bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8) !important;
            color: #fff !important;
        }

        .leaflet-popup-tip { background: var(--card-bg) !important; }
        .leaflet-popup-close-button { color: var(--text-muted) !important; }
        .leaflet-popup-close-button:hover { color: var(--primary) !important; }

        .popup-content { padding: 5px; min-width: 200px; }
        .popup-content h3 { font-size: 0.95rem; font-weight: 900; margin-bottom: 6px; }
        .popup-content p { font-size: 0.78rem; color: var(--text-muted); margin: 3px 0; }
        .popup-content .popup-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .popup-info-value { color: var(--primary); font-weight: 700; font-size: 0.85rem; }

        /* Legend */
        .map-legend {
            position: absolute;
            bottom: 30px;
            right: 10px;
            z-index: 500;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.75rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .legend-item:last-child { margin-bottom: 0; }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        /* Loading */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(10,11,13,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .counter-badge {
            background: rgba(255,255,255,0.07);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-left: auto;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="{{ route('cliente.index') }}">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
    <h1><i class="fas fa-map-marked-alt"></i> &nbsp;MAPA DE ACADEMIAS & PERSONAIS</h1>
    <span style="font-size: 0.8rem; color: var(--text-muted);">
        <i class="fas fa-map-pin" style="color: var(--primary);"></i> Localize profissionais perto de você
    </span>
</div>

<div class="main-layout">
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar por nome ou cidade...">
            </div>
            <div class="filter-tabs">
                <button class="filter-tab active-todos" onclick="filtrar('todos', this)">
                    <i class="fas fa-globe"></i> Todos
                </button>
                <button class="filter-tab" onclick="filtrar('academia', this)">
                    <i class="fas fa-dumbbell"></i> Academias
                </button>
                <button class="filter-tab" onclick="filtrar('personal', this)">
                    <i class="fas fa-user"></i> Personais
                </button>
            </div>
        </div>
        <div class="sidebar-list" id="sidebarList">
            <div class="empty-state">
                <i class="fas fa-circle-notch fa-spin"></i>
                <p>Carregando locais...</p>
            </div>
        </div>
    </div>

    <div style="flex: 1; position: relative;">
        <div id="map"></div>
        <div class="map-legend">
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--academia-color);"></div>
                <span>Academia</span>
            </div>
            <div class="legend-item">
                <div class="legend-dot" style="background: var(--personal-color);"></div>
                <span>Personal</span>
            </div>
        </div>
        <div class="loading-overlay" id="loadingOverlay">
            <div class="loading-spinner"></div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Inicializa o mapa centrado no Brasil
    const map = L.map('map', {
        center: [-15.7801, -47.9292],
        zoom: 5,
        zoomControl: true,
    });

    // Tile escuro para combinar com o design
    L.tileLayer('https:\/\/{s}.basemaps.cartocdn.com\/dark_all\/{z}\/{x}\/{y}{r}.png', {
        attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
        maxZoom: 19
    }).addTo(map);

    // Ícones customizados
    function criarIcone(tipo) {
        const cor = tipo === 'academia' ? '#d4ff00' : '#00c8ff';
        const icone = tipo === 'academia' ? '🏋️' : '👤';
        return L.divIcon({
            className: '',
            html: `
                <div style="
                    width: 36px; height: 36px;
                    background: ${cor};
                    border-radius: 50% 50% 50% 0;
                    transform: rotate(-45deg);
                    display: flex; align-items: center; justify-content: center;
                    box-shadow: 0 4px 15px ${cor}66;
                    border: 2px solid rgba(0,0,0,0.3);
                ">
                    <span style="transform: rotate(45deg); font-size: 14px;">${icone}</span>
                </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -40],
        });
    }

    let todosOsPins = [];
    let marcadores = [];
    let filtroAtivo = 'todos';

    function renderizarSidebar(pins) {
        const lista = document.getElementById('sidebarList');

        if (pins.length === 0) {
            lista.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-map-pin"></i>
                    <p>Nenhum local encontrado.</p>
                    <small style="font-size: 0.75rem;">Tente outro filtro ou busca.</small>
                </div>`;
            return;
        }

        lista.innerHTML = pins.map((pin, i) => `
            <div class="pin-card" id="card-${i}" onclick="focarPin(${pin.latitude}, ${pin.longitude}, ${i})">
                <div class="pin-icon ${pin.tipo}">
                    <i class="fas fa-${pin.tipo === 'academia' ? 'dumbbell' : 'user'}"></i>
                </div>
                <div class="pin-info" style="flex: 1; min-width: 0;">
                    <h4 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${pin.nome}</h4>
                    <p>${pin.endereco}</p>
                    <span class="badge-${pin.tipo}">${pin.tipo === 'academia' ? 'Academia' : 'Personal'}</span>
                </div>
            </div>
        `).join('');
    }

    function focarPin(lat, lng, index) {
        map.flyTo([lat, lng], 16, { animate: true, duration: 0.8 });

        // Destacar card
        document.querySelectorAll('.pin-card').forEach(c => c.classList.remove('selected'));
        const card = document.getElementById('card-' + index);
        if (card) {
            card.classList.add('selected');
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Abrir popup do marcador correspondente
        if (marcadores[index]) {
            setTimeout(() => marcadores[index].openPopup(), 600);
        }
    }

    function filtrar(tipo, btn) {
        filtroAtivo = tipo;

        // Atualiza estilo dos botões
        document.querySelectorAll('.filter-tab').forEach(b => {
            b.className = 'filter-tab';
        });
        btn.classList.add(`active-${tipo}`);

        aplicarFiltros();
    }

    function aplicarFiltros() {
        const busca = document.getElementById('searchInput').value.toLowerCase();

        const filtrados = todosOsPins.filter(pin => {
            const tipoOk = filtroAtivo === 'todos' || pin.tipo === filtroAtivo;
            const buscaOk = !busca || pin.nome.toLowerCase().includes(busca) || pin.endereco.toLowerCase().includes(busca);
            return tipoOk && buscaOk;
        });

        // Atualiza marcadores no mapa
        marcadores.forEach(m => map.removeLayer(m));
        marcadores = [];

        filtrados.forEach((pin, i) => {
            const marker = L.marker([pin.latitude, pin.longitude], { icon: criarIcone(pin.tipo) })
                .addTo(map)
                .bindPopup(`
                    <div class="popup-content">
                        <span class="popup-badge" style="background: rgba(${pin.tipo === 'academia' ? '212,255,0' : '0,200,255'},0.15); color: ${pin.tipo === 'academia' ? 'var(--academia-color)' : 'var(--personal-color)'};">
                            ${pin.tipo === 'academia' ? '🏋️ Academia' : '👤 Personal'}
                        </span>
                        <h3>${pin.nome}</h3>
                        <p><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> ${pin.endereco}</p>
                        <p class="popup-info-value"><i class="fas fa-tag"></i> ${pin.info}</p>
                    </div>
                `);

            marker.on('click', () => {
                document.querySelectorAll('.pin-card').forEach(c => c.classList.remove('selected'));
                const card = document.getElementById('card-' + i);
                if (card) {
                    card.classList.add('selected');
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            marcadores.push(marker);
        });

        renderizarSidebar(filtrados);

        // Ajusta zoom para mostrar todos os pins
        if (filtrados.length > 0) {
            const group = L.featureGroup(marcadores);
            map.fitBounds(group.getBounds().pad(0.2));
        }
    }

    // Busca os dados
    fetch('/mapa/dados')
        .then(r => r.json())
        .then(data => {
            todosOsPins = [
                ...data.academias,
                ...data.personals,
            ];

            document.getElementById('loadingOverlay').style.display = 'none';
            aplicarFiltros();
        })
        .catch(err => {
            console.error(err);
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('sidebarList').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle" style="color: var(--error);"></i>
                    <p>Erro ao carregar dados.</p>
                </div>`;
        });

    // Busca em tempo real
    document.getElementById('searchInput').addEventListener('input', aplicarFiltros);
</script>
</body>
</html>
