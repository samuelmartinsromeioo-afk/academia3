{{-- ===== Hero Cinemática SnrFit (6 cenas, autoplay + navegação) ===== --}}
<style>
  /* Palco em tela cheia: ocupa 100% da viewport, sem letterbox */
  .cin-stage{position:relative;width:100%;height:100vh;height:100dvh;overflow:hidden;background:#0a0b0d}
  .cin-stage > .cin-root{position:absolute;inset:0;width:100%;height:100%}

  @keyframes cinKen{from{transform:scale(1.06)}to{transform:scale(1)}}
  @keyframes cinBandGlow{0%,100%{box-shadow:0 0 0 rgba(212,255,0,0)}50%{box-shadow:0 0 22px rgba(212,255,0,.5),0 0 7px rgba(212,255,0,.4)}}
  @keyframes cinFloat{0%{transform:translate3d(0,0,0)}50%{transform:translate3d(8px,-16px,0)}100%{transform:translate3d(0,0,0)}}
  @keyframes cinBar{from{transform:scaleX(0)}to{transform:scaleX(1)}}

  .cin-scene{position:absolute;inset:0;opacity:0;visibility:hidden;transition:opacity 1.1s ease,visibility 1.1s}
  .cin-scene.cin-on{opacity:1;visibility:visible}
  .cin-scene.cin-on .cin-img{animation:cinKen 8.5s ease-out forwards}
  .cin-line{display:block;opacity:0;transform:translateY(26px)}
  .cin-scene.cin-on .cin-line{animation:cinLineIn .9s cubic-bezier(.2,.75,.2,1) forwards}
  @keyframes cinLineIn{to{opacity:1;transform:translateY(0)}}
  .cin-scene.cin-on .cin-line:nth-child(1){animation-delay:.35s}
  .cin-scene.cin-on .cin-line:nth-child(2){animation-delay:.55s}
  .cin-scene.cin-on .cin-line:nth-child(3){animation-delay:.75s}
  .cin-scene.cin-on .cin-sub{animation:cinLineIn .9s cubic-bezier(.2,.75,.2,1) 1.05s forwards}
  .cin-sub{opacity:0;transform:translateY(18px)}
  .cin-band{animation:cinBandGlow 3.4s ease-in-out 1.6s infinite}
  .cin-particle{position:absolute;border-radius:50%;background:#d4ff00;pointer-events:none;will-change:transform,opacity}
  .cin-dot{cursor:pointer;transition:background .3s ease}
  .cin-scene .cin-img{transition:translate .9s cubic-bezier(.2,.8,.3,1)}
  @keyframes cinWheelHint{0%,100%{transform:translateY(0);opacity:.9}50%{transform:translateY(7px);opacity:.4}}
  .cin-wheel{animation:cinWheelHint 2s ease-in-out infinite}
  .cin-scene.cin-on .cin-spec{animation:cinLineIn .9s cubic-bezier(.2,.75,.2,1) 1.3s forwards}
  .cin-spec{opacity:0;transform:translateY(16px)}
  .cin-spec-row{display:flex;align-items:baseline;gap:18px;padding:9px 0;border-top:1px solid rgba(255,255,255,.14)}
  .cin-spec-row span{text-shadow:0 1px 8px rgba(0,0,0,.85)}
  .cin-cross{position:absolute;width:15px;height:15px;z-index:5;pointer-events:none;opacity:.4}
  .cin-cross::before{content:"";position:absolute;left:50%;top:0;bottom:0;width:1px;background:#fff;transform:translateX(-50%)}
  .cin-cross::after{content:"";position:absolute;top:50%;left:0;right:0;height:1px;background:#fff;transform:translateY(-50%)}
  .cin-ghost{position:absolute;right:26px;bottom:66px;z-index:1;font-family:Syncopate,sans-serif;font-weight:700;font-size:15rem;line-height:1;color:rgba(255,255,255,.05);pointer-events:none;transition:opacity .6s ease;user-select:none}
  .cin-intro{position:absolute;inset:0;z-index:30;background:#0a0b0d;display:flex;align-items:flex-end;justify-content:space-between;padding:38px 48px;pointer-events:none;transition:opacity .6s ease}
  .cin-btn i{transition:transform .28s ease}
  .cin-btn:hover i{transform:translateX(4px)}

  /* Hovers (o export usava um runtime; aqui são regras CSS reais) */
  .cin-nav-ghost:hover{border-color:#d4ff00;color:#d4ff00}
  .cin-nav-solid:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(212,255,0,.25)}
  .cin-btn:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(212,255,0,.3)}
  .cin-ghostbtn:hover{border-color:#d4ff00;color:#d4ff00}

  /* Ajuste tela cheia em telas menores (o layout base foi desenhado para desktop) */
  @media (max-width:768px){
    .cin-scene h1{font-size:2.4rem!important}
    .cin-sub{font-size:1rem!important;margin-top:20px!important}
    .cin-spec{display:none!important}
    .cin-scene [style*="left:84px"]{left:22px!important;right:22px!important;max-width:none!important}
    .cin-scene [style*="right:84px"]{right:22px!important;left:22px!important;max-width:none!important}
    .cin-scene [style*="padding:0 84px"]{padding:0 22px!important}
    .cin-ghost{font-size:9rem!important;right:14px!important;bottom:130px!important}
  }
  @media (max-width:560px){
    .cin-scene h1{font-size:2rem!important}
  }

  @media (prefers-reduced-motion:reduce){
    .cin-scene{transition:none}
    .cin-scene.cin-on .cin-img{animation:none}
    .cin-line,.cin-sub,.cin-spec{opacity:1!important;transform:none!important;animation:none!important}
    .cin-band{animation:none!important}
    .cin-particle{display:none!important}
    .cin-wheel{animation:none!important}
    .cin-intro{display:none!important}
  }
</style>

<header class="cin-stage">
<div class="cin-root" style="width:100%;height:100%;background:#0a0b0d;color:#fff;overflow:hidden">

  {{-- ===== Cena 01 · Atleta ===== --}}
  <div class="cin-scene cin-on" data-scene="0" data-screen-label="Cena 01">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena1.jpg') }}" alt="Atleta sozinho no escuro da academia" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 30%;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(10,11,13,.88) 0%,rgba(10,11,13,.45) 42%,rgba(10,11,13,.05) 70%),linear-gradient(180deg,rgba(10,11,13,.55),transparent 26%,transparent 62%,rgba(10,11,13,.92))"></div>
    <div style="position:absolute;left:84px;top:50%;transform:translateY(-50%);max-width:640px;z-index:2">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px"><span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-right:12px">01</span>Onde tudo começa</div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">Todo gigante</span>
        <span class="cin-line">começa no <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">escuro</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:480px;margin:26px 0 0;text-shadow:0 1px 14px rgba(0,0,0,.7)">Cada treino registrado. Cada evolução guardada. Nada do que você constrói se perde.</p>
      <div class="cin-spec" style="max-width:440px;margin-top:30px">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Registro</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Treinos · Cargas · Histórico</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Foco</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Evolução contínua</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Cena 02 · Chalk ===== --}}
  <div class="cin-scene" data-scene="1" data-screen-label="Cena 02">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena2.jpg') }}" alt="Mãos batendo magnésio sobre a barra" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 62%;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,11,13,.82) 0%,rgba(10,11,13,.28) 38%,rgba(10,11,13,.1) 60%,rgba(10,11,13,.92) 100%)"></div>
    <div style="position:absolute;left:0;right:0;top:118px;text-align:center;z-index:2;padding:0 84px">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px"><span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-right:12px">02</span>Sem atalho, sem sorteio</div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">Disciplina é</span>
        <span class="cin-line">a única <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">sorte</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:520px;margin:26px auto 0;text-shadow:0 1px 14px rgba(0,0,0,.7)">A SnrFit cuida da agenda, do financeiro e dos alunos. Você cuida da próxima repetição.</p>
      <div class="cin-spec" style="max-width:460px;margin:30px auto 0;text-align:left;background:rgba(10,11,13,.45);backdrop-filter:blur(5px);border-radius:8px;padding:2px 16px 6px">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Gestão</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Agenda · Financeiro · Alunos</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Seu trabalho</span><span style="font-size:0.85rem;font-weight:600;color:#fff">A próxima repetição</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Cena 03 · Sprint ===== --}}
  <div class="cin-scene" data-scene="2" data-screen-label="Cena 03">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena3.jpg') }}" alt="Atleta em sprint sob luzes verdes" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:28% center;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(270deg,rgba(10,11,13,.88) 0%,rgba(10,11,13,.45) 42%,rgba(10,11,13,.05) 70%),linear-gradient(180deg,rgba(10,11,13,.5),transparent 26%,transparent 62%,rgba(10,11,13,.92))"></div>
    <div style="position:absolute;right:84px;top:50%;transform:translateY(-50%);max-width:620px;text-align:right;z-index:2">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px">A plataforma completa do mundo fitness<span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-left:12px">03</span></div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">Treino que</span>
        <span class="cin-line">vira <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">história</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:480px;margin:26px 0 0 auto;text-shadow:0 1px 14px rgba(0,0,0,.7)">Personais, alunos, academias, studios e lojas — todo o seu mundo fitness num só lugar.</p>
      <div class="cin-spec" style="max-width:440px;margin:30px 0 0 auto;text-align:left">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Perfis</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Personal · Aluno · Academia · Studio · Loja</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Plataforma</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Uma só</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Cena 04 · Personal ===== --}}
  <div class="cin-scene" data-scene="3" data-screen-label="Cena 04">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena4.jpg') }}" alt="Personal trainer gerenciando tudo pelo celular" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:72% 30%;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(10,11,13,.88) 0%,rgba(10,11,13,.45) 42%,rgba(10,11,13,.05) 70%),linear-gradient(180deg,rgba(10,11,13,.55),transparent 26%,transparent 62%,rgba(10,11,13,.92))"></div>
    <div style="position:absolute;left:84px;top:50%;transform:translateY(-50%);max-width:640px;z-index:2">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px"><span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-right:12px">04</span>Pro personal trainer</div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">Menos planilha.</span>
        <span class="cin-line">Mais <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">treino</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:480px;margin:26px 0 0;text-shadow:0 1px 14px rgba(0,0,0,.7)">Agenda, fichas e pagamentos resolvidos em segundos — direto do celular, entre um aluno e outro.</p>
      <div class="cin-spec" style="max-width:440px;margin-top:30px">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Ferramentas</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Agenda · Fichas · Pagamentos</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Onde</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Direto do celular</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Cena 05 · Dono de academia ===== --}}
  <div class="cin-scene" data-scene="4" data-screen-label="Cena 05">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena5.jpg') }}" alt="Dono de academia observando o salão do mezanino" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:22% center;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(270deg,rgba(10,11,13,.88) 0%,rgba(10,11,13,.45) 42%,rgba(10,11,13,.05) 70%),linear-gradient(180deg,rgba(10,11,13,.5),transparent 26%,transparent 62%,rgba(10,11,13,.92))"></div>
    <div style="position:absolute;right:84px;top:50%;transform:translateY(-50%);max-width:640px;text-align:right;z-index:2">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px">Pro dono de academia<span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-left:12px">05</span></div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">Sua academia</span>
        <span class="cin-line">na sua <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">mão</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:490px;margin:26px 0 0 auto;text-shadow:0 1px 14px rgba(0,0,0,.7)">Financeiro, check-in, professores e relatórios num painel só. Controle absoluto, de qualquer lugar.</p>
      <div class="cin-spec" style="max-width:440px;margin:30px 0 0 auto;text-align:left">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Painel</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Financeiro · Check-in · Relatórios</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Acesso</span><span style="font-size:0.85rem;font-weight:600;color:#fff">De qualquer lugar</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Cena 06 · Studio ===== --}}
  <div class="cin-scene" data-scene="5" data-screen-label="Cena 06">
    <img class="cin-img" src="{{ asset('images/landing/cinematica/cena6.jpg') }}" alt="Dona de studio com tablet no studio de pilates" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:32% 30%;will-change:transform">
    <div style="position:absolute;inset:0;background:linear-gradient(270deg,rgba(10,11,13,.88) 0%,rgba(10,11,13,.45) 42%,rgba(10,11,13,.05) 70%),linear-gradient(180deg,rgba(10,11,13,.5),transparent 26%,transparent 62%,rgba(10,11,13,.92))"></div>
    <div style="position:absolute;right:84px;top:50%;transform:translateY(-50%);max-width:640px;text-align:right;z-index:2">
      <div class="cin-line" style="font-size:0.72rem;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#9ca3af;margin-bottom:26px">Pro studio fitness<span style="color:#0a0b0d;background:#d4ff00;font-family:Syncopate,sans-serif;font-weight:700;padding:2px 8px;letter-spacing:1px;margin-left:12px">06</span></div>
      <h1 style="font-family:Syncopate,sans-serif;font-weight:700;font-size:3.6rem;line-height:1.06;letter-spacing:-.01em;text-transform:uppercase;margin:0">
        <span class="cin-line">O studio inteiro</span>
        <span class="cin-line">no <span class="cin-band" style="background:#d4ff00;color:#0a0b0d;padding:0 .08em">automático</span>.</span>
      </h1>
      <p class="cin-sub" style="color:#c7ccd4;font-size:1.12rem;line-height:1.7;max-width:490px;margin:26px 0 0 auto;text-shadow:0 1px 14px rgba(0,0,0,.7)">Planos, horários com vagas e recebimentos online — tudo rodando sozinho enquanto você dá aula.</p>
      <div class="cin-spec" style="max-width:440px;margin:30px 0 0 auto;text-align:left">
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Automação</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Planos · Vagas · Recebimentos</span></div>
        <div class="cin-spec-row"><span style="font-family:Syncopate,sans-serif;font-size:0.58rem;letter-spacing:2px;color:#9ca3af;text-transform:uppercase;min-width:110px">Modo</span><span style="font-size:0.85rem;font-weight:600;color:#fff">Sozinho · 24/7</span></div>
      </div>
    </div>
  </div>

  {{-- ===== Partículas ===== --}}
  <div class="cin-particles" style="position:absolute;inset:0;z-index:3;pointer-events:none;overflow:hidden"></div>

  {{-- ===== Navbar ===== --}}
  <nav style="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;justify-content:space-between;padding:26px 48px;z-index:5">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:38px;height:38px;border-radius:9px;background:#d4ff00;display:flex;align-items:center;justify-content:center;font-family:Syncopate,sans-serif;font-weight:700;font-size:15px;color:#0a0b0d">S</div>
      <div style="display:flex;flex-direction:column;gap:3px;line-height:1">
        <div style="font-family:Syncopate,sans-serif;font-size:1.15rem;letter-spacing:3px;text-transform:uppercase">SNR<span style="color:#d4ff00">FIT</span></div>
        <span style="font-size:0.58rem;font-weight:600;letter-spacing:2px;color:#9ca3af;text-transform:uppercase">Treino que vira história</span>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
      <a href="#" onclick="openModal();return false;" class="cin-nav-ghost" style="font-size:0.9rem;font-weight:600;border-radius:999px;padding:10px 22px;text-decoration:none;background:transparent;border:1px solid rgba(255,255,255,.2);color:#fff;transition:.25s">Entrar</a>
      <a href="{{ route('cadastro.SelecaoCadastro') }}" class="cin-nav-solid" style="font-size:0.9rem;font-weight:600;border-radius:999px;padding:10px 22px;text-decoration:none;background:#d4ff00;border:1px solid #d4ff00;color:#0a0b0d;transition:.25s">Cadastrar-se</a>
    </div>
  </nav>

  {{-- ===== Rodapé do hero: CTA + navegação de cenas ===== --}}
  <div style="position:absolute;left:48px;right:48px;bottom:34px;display:flex;align-items:flex-end;justify-content:space-between;z-index:5">
    <div style="display:flex;gap:14px;align-items:center">
      <a href="{{ route('cadastro.SelecaoCadastro') }}" class="cin-btn" style="font-size:1rem;font-weight:700;border-radius:999px;padding:15px 30px;text-decoration:none;display:inline-flex;align-items:center;gap:10px;background:#d4ff00;color:#0a0b0d;transition:.25s">Quero fazer história <i class="ph ph-arrow-right"></i></a>
      <a href="#" onclick="openModal();return false;" class="cin-ghostbtn" style="font-size:1rem;font-weight:600;border-radius:999px;padding:15px 30px;text-decoration:none;background:rgba(10,11,13,.4);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.2);color:#fff;transition:.25s">Já tenho conta</a>
    </div>
    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:14px">
      <div style="font-family:Syncopate,sans-serif;font-size:0.78rem;letter-spacing:2px;color:rgba(255,255,255,.65)"><span class="cin-counter" style="color:#d4ff00">01</span> / 06</div>
      <div style="display:flex;gap:8px">
        <div class="cin-dot" data-go="0" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
        <div class="cin-dot" data-go="1" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
        <div class="cin-dot" data-go="2" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
        <div class="cin-dot" data-go="3" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
        <div class="cin-dot" data-go="4" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
        <div class="cin-dot" data-go="5" style="width:44px;height:3px;background:rgba(255,255,255,.22);position:relative;overflow:hidden"><div class="cin-fill" style="position:absolute;inset:0;background:#d4ff00;transform:scaleX(0);transform-origin:left"></div></div>
      </div>
    </div>
  </div>

  <div style="position:absolute;left:14px;top:56%;transform:rotate(-90deg);transform-origin:left center;font-family:Syncopate,sans-serif;font-size:0.62rem;letter-spacing:4px;color:rgba(255,255,255,.3);white-space:nowrap;z-index:5">SNR·FIT — EST. 2026</div>

  {{-- ===== HUD: crosshairs + metadados + número fantasma ===== --}}
  <div class="cin-cross" style="left:34px;top:96px"></div>
  <div class="cin-cross" style="right:34px;top:96px"></div>
  <div class="cin-cross" style="left:34px;bottom:112px"></div>
  <div class="cin-cross" style="right:34px;bottom:112px"></div>
  <div style="position:absolute;right:14px;top:56%;transform:rotate(90deg);transform-origin:right center;font-family:Syncopate,sans-serif;font-size:0.62rem;letter-spacing:4px;color:rgba(255,255,255,.3);white-space:nowrap;z-index:5">AGENDA · FICHAS · FINANCEIRO · PAGAMENTOS</div>
  <div class="cin-ghost">01</div>

  {{-- ===== Intro: contador ===== --}}
  <div class="cin-intro" style="opacity:0;display:none">
    <span style="font-family:Syncopate,sans-serif;font-size:0.62rem;letter-spacing:3px;color:rgba(255,255,255,.5);text-transform:uppercase">© SnrFit — Treino que vira história</span>
    <span class="cin-intro-num" style="font-family:Syncopate,sans-serif;font-weight:700;font-size:4.5rem;line-height:1;color:#d4ff00">0</span>
  </div>

  {{-- ===== Hint de scroll ===== --}}
  <div style="position:absolute;left:50%;bottom:36px;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;z-index:5;pointer-events:none">
    <div style="width:22px;height:36px;border:1.5px solid rgba(255,255,255,.35);border-radius:12px;display:flex;justify-content:center;padding-top:6px"><div class="cin-wheel" style="width:3px;height:8px;border-radius:2px;background:#d4ff00"></div></div>
    <span style="font-family:Syncopate,sans-serif;font-size:0.52rem;letter-spacing:2.5px;color:rgba(255,255,255,.45);text-transform:uppercase">Role ou clique</span>
  </div>

</div>
</header>

<script>
(function(){
  var host = document.querySelector('.cin-stage > .cin-root');
  if (!host) return;
  var stage = host.parentElement;
  var scenes = [].slice.call(host.querySelectorAll('.cin-scene'));
  if (!scenes.length) return;

  var DUR = 9, AUTOPLAY = true, INTERACTIVE = true;
  var motionOff = window.matchMedia && window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  var idx = 0, timer = null;
  var playing = !motionOff && AUTOPLAY;

  function goTo(i){
    var n = scenes.length;
    idx = ((i % n) + n) % n;
    scenes.forEach(function(s, k){ s.classList.toggle('cin-on', k === idx); });
    var counter = host.querySelector('.cin-counter');
    if (counter) counter.textContent = '0' + (idx + 1);
    var ghost = host.querySelector('.cin-ghost');
    if (ghost) ghost.textContent = '0' + (idx + 1);
    host.querySelectorAll('.cin-dot').forEach(function(d, k){
      var fill = d.querySelector('.cin-fill');
      if (!fill) return;
      fill.style.animation = 'none';
      if (k < idx) { fill.style.transform = 'scaleX(1)'; }
      else if (k === idx) {
        fill.style.transform = '';
        if (playing) { void fill.offsetWidth; fill.style.animation = 'cinBar ' + DUR + 's linear forwards'; }
        else { fill.style.transform = 'scaleX(1)'; }
      } else { fill.style.transform = 'scaleX(0)'; }
    });
  }

  function resetTimer(){
    if (timer) { clearInterval(timer); timer = null; }
    if (playing) timer = setInterval(function(){ goTo(idx + 1); }, DUR * 1000);
  }

  // ---- Intro: contador 0 -> 100 ----
  var intro = host.querySelector('.cin-intro');
  if (intro && !motionOff) {
    var num = intro.querySelector('.cin-intro-num');
    intro.style.display = 'flex';
    intro.style.opacity = '1';
    var t0 = performance.now(), D = 1100;
    var tick = function(t){
      var p = Math.min(1, (t - t0) / D), e = 1 - Math.pow(1 - p, 3);
      if (num) num.textContent = Math.round(e * 100);
      if (p < 1) requestAnimationFrame(tick);
      else { intro.style.opacity = '0'; setTimeout(function(){ intro.style.display = 'none'; }, 650); }
    };
    requestAnimationFrame(tick);
  }

  // ---- Autoplay ----
  if (playing) timer = setInterval(function(){ goTo(idx + 1); }, DUR * 1000);
  goTo(0);

  // ---- Dots ----
  host.querySelectorAll('.cin-dot').forEach(function(d){
    d.addEventListener('click', function(e){
      e.stopPropagation();
      resetTimer();
      goTo(parseInt(d.getAttribute('data-go'), 10) || 0);
    });
  });

  if (!motionOff && INTERACTIVE) {
    // ---- Clique avança / arrastar vira swipe ----
    var downX = null, downY = null, downT = 0, dragged = false;
    host.addEventListener('pointerdown', function(e){ downX = e.clientX; downY = e.clientY; downT = Date.now(); dragged = false; });
    host.addEventListener('pointermove', function(e){ if (downX !== null && Math.abs(e.clientX - downX) > 12) dragged = true; });
    host.addEventListener('pointerup', function(e){
      if (downX === null) return;
      var dx = e.clientX - downX, dy = e.clientY - downY;
      var interactive = e.target.closest && e.target.closest('a,button,.cin-dot,nav');
      if (dragged && Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) { resetTimer(); goTo(idx + (dx < 0 ? 1 : -1)); }
      else if (!dragged && !interactive && Date.now() - downT < 400) { resetTimer(); goTo(idx + 1); }
      downX = null;
    });

    // ---- Setas esquerda/direita (não interferem no scroll da página) ----
    window.addEventListener('keydown', function(e){
      if (e.key === 'ArrowRight') { resetTimer(); goTo(idx + 1); }
      else if (e.key === 'ArrowLeft') { resetTimer(); goTo(idx - 1); }
    });

    // ---- Parallax da imagem ----
    var raf = null;
    host.addEventListener('mousemove', function(e){
      var r = host.getBoundingClientRect();
      var cx = e.clientX - r.left, cy = e.clientY - r.top;
      if (!raf) raf = requestAnimationFrame(function(){
        raf = null;
        var img = host.querySelector('.cin-scene.cin-on .cin-img');
        if (img) {
          var nx = cx / r.width - 0.5, ny = cy / r.height - 0.5;
          img.style.translate = (-nx * 16).toFixed(1) + 'px ' + (-ny * 10).toFixed(1) + 'px';
        }
      });
    }, { passive: true });
    host.addEventListener('mouseleave', function(){
      var img = host.querySelector('.cin-scene.cin-on .cin-img');
      if (img) img.style.translate = '0px 0px';
    }, { passive: true });
  }

  // ---- Partículas ----
  if (!motionOff) {
    var pc = host.querySelector('.cin-particles');
    if (pc) {
      for (var i = 0; i < 14; i++) {
        var dp = document.createElement('div');
        dp.className = 'cin-particle';
        var s = (2 + Math.random() * 3).toFixed(1);
        dp.style.width = s + 'px'; dp.style.height = s + 'px';
        dp.style.left = (Math.random() * 100).toFixed(2) + '%';
        dp.style.top = (Math.random() * 100).toFixed(2) + '%';
        dp.style.opacity = (0.05 + Math.random() * 0.08).toFixed(3);
        var du = (15 + Math.random() * 13).toFixed(1);
        dp.style.animation = 'cinFloat ' + du + 's ease-in-out ' + (-Math.random() * du).toFixed(1) + 's infinite';
        pc.appendChild(dp);
      }
    }
  }

})();
</script>
