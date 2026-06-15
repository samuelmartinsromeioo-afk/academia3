{{--
    Exibe os dados de um registro de avaliação física ($r), de acordo com o tipo.
    Usado tanto pela área do personal quanto pela do cliente (somente leitura).
    Depende das classes CSS: dado-item, dados-grid, snrfit-detail-grid, foto-thumb,
    btn-pdf, dor-0/3/6/9 — presentes em ambas as views — e da função abrirLightbox().
--}}
@php
    // Quais "blocos" de módulo renderizar para este tipo.
    $blocosPorTipo = [
        'antes_depois'     => ['antes_depois'],
        'antropometrica'   => ['antropometrica'],
        'dobras'           => ['dobras'],
        'bioimpedancia'    => ['bioimpedancia'],
        'dinamometro'      => ['dinamometro'],
        'forca'            => ['forca'],
        'flexibilidade'    => ['flexibilidade'],
        'neuromotora'      => ['neuromotora'],
        'funcional'        => ['funcional'],
        'cardio'           => ['cardio'],
        'oximetro'         => ['oximetro'],
        'pressao_arterial' => ['pressao_arterial'],
        'postural'         => ['postural'],
        'dor'              => ['dor'],
        'anamnese'         => ['anamnese'],
        // Legado: registros antigos do SNR Fit Tech mostram todos os módulos.
        'completa'         => ['anamnese','antropometrica','dobras','postural','neuromotora','flexibilidade','cardio','forca','funcional','dor'],
    ];
    $blocos = $blocosPorTipo[$r->tipo] ?? [];
    $multi  = count($blocos) > 1; // legado: mostra cabeçalho de cada módulo
@endphp

@if(in_array('antes_depois', $blocos))
    <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
        @if($r->foto)
            <img src="{{ asset('storage/'.$r->foto) }}" class="foto-thumb" onclick="abrirLightbox('{{ asset('storage/'.$r->foto) }}')">
        @endif
        <div class="dados-grid" style="flex:1; min-width:200px;">
            @if(!is_null($r->peso))
            <div class="dado-item"><label>Peso</label><span>{{ rtrim(rtrim(number_format($r->peso, 2, ',', '.'), '0'), ',') }} kg</span></div>
            @endif
            @if($r->medidas)
            <div class="dado-item texto" style="grid-column:1/-1;"><label>Medidas</label><span>{{ $r->medidas }}</span></div>
            @endif
        </div>
    </div>
@endif

@if(in_array('dinamometro', $blocos) && $r->forca !== null && $r->tipo === 'dinamometro')
    <div class="dados-grid">
        <div class="dado-item"><label>Força</label><span>{{ rtrim(rtrim(number_format($r->forca, 2, ',', '.'), '0'), ',') }} kgf</span></div>
    </div>
@endif

@if(in_array('oximetro', $blocos))
    <div class="dados-grid">
        @if($r->spo2 !== null)<div class="dado-item"><label>SpO2</label><span>{{ $r->spo2 }}%</span></div>@endif
        @if($r->bpm !== null)<div class="dado-item"><label>Batimentos</label><span>{{ $r->bpm }} bpm</span></div>@endif
    </div>
@endif

@if(in_array('pressao_arterial', $blocos) && $r->tipo === 'pressao_arterial')
    <div class="dados-grid">
        <div class="dado-item"><label>Pressão Arterial</label><span>{{ $r->pressao_sistolica }}/{{ $r->pressao_diastolica }} mmHg</span></div>
    </div>
@endif

@if(in_array('bioimpedancia', $blocos))
    <div class="dados-grid">
        <div class="dado-item" style="display:flex; flex-direction:column; gap:8px; align-items:flex-start;">
            <label>Relatório de Bioimpedância</label>
            @if($r->arquivo)
                <a href="{{ asset('storage/'.$r->arquivo) }}" target="_blank" class="btn-pdf"><i class="fas fa-file-pdf"></i> Abrir PDF</a>
            @else
                <span style="color:var(--text-muted); font-size:0.8rem; font-weight:500;">Sem arquivo anexado</span>
            @endif
        </div>
    </div>
@endif

@if(in_array('anamnese', $blocos) && ($r->objetivo_principal || $r->historico_atividade || $r->lesoes || $r->cirurgias || $r->medicamentos || $r->restricoes_medicas || $r->habitos_sono || $r->nivel_estresse !== null || $r->alimentacao))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-file-medical"></i> Anamnese</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->objetivo_principal)<div class="dado-item texto"><label>Objetivo</label><span>{{ $r->objetivo_principal }}</span></div>@endif
        @if($r->historico_atividade)<div class="dado-item texto"><label>Histórico de Atividade</label><span>{{ $r->historico_atividade }}</span></div>@endif
        @if($r->lesoes)<div class="dado-item texto"><label>Lesões</label><span>{{ $r->lesoes }}</span></div>@endif
        @if($r->cirurgias)<div class="dado-item texto"><label>Cirurgias</label><span>{{ $r->cirurgias }}</span></div>@endif
        @if($r->medicamentos)<div class="dado-item texto"><label>Medicamentos</label><span>{{ $r->medicamentos }}</span></div>@endif
        @if($r->restricoes_medicas)<div class="dado-item texto"><label>Restrições Médicas</label><span>{{ $r->restricoes_medicas }}</span></div>@endif
        @if($r->habitos_sono)<div class="dado-item texto"><label>Hábitos de Sono</label><span>{{ $r->habitos_sono }}</span></div>@endif
        @if($r->nivel_estresse !== null)<div class="dado-item"><label>Nível de Estresse</label><span>{{ $r->nivel_estresse }}/10</span></div>@endif
        @if($r->alimentacao)<div class="dado-item texto"><label>Alimentação</label><span>{{ $r->alimentacao }}</span></div>@endif
    </div>
@endif

@if(in_array('antropometrica', $blocos) && ($r->peso || $r->altura || $r->imc || $r->circ_cintura || $r->circ_abdomen || $r->circ_quadril || $r->circ_torax || $r->circ_braco || $r->circ_coxa || $r->circ_panturrilha))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-ruler"></i> Antropométrica</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->peso)<div class="dado-item"><label>Peso</label><span>{{ number_format($r->peso, 1, ',', '.') }} kg</span></div>@endif
        @if($r->altura)<div class="dado-item"><label>Altura</label><span>{{ number_format($r->altura, 0) }} cm</span></div>@endif
        @if($r->imc)<div class="dado-item"><label>IMC</label><span>{{ number_format($r->imc, 1, ',', '.') }}</span></div>@endif
        @if($r->circ_cintura)<div class="dado-item"><label>Cintura</label><span>{{ number_format($r->circ_cintura, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_abdomen)<div class="dado-item"><label>Abdômen</label><span>{{ number_format($r->circ_abdomen, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_quadril)<div class="dado-item"><label>Quadril</label><span>{{ number_format($r->circ_quadril, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_torax)<div class="dado-item"><label>Tórax</label><span>{{ number_format($r->circ_torax, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_braco)<div class="dado-item"><label>Braço</label><span>{{ number_format($r->circ_braco, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_coxa)<div class="dado-item"><label>Coxa</label><span>{{ number_format($r->circ_coxa, 1, ',', '.') }} cm</span></div>@endif
        @if($r->circ_panturrilha)<div class="dado-item"><label>Panturrilha</label><span>{{ number_format($r->circ_panturrilha, 1, ',', '.') }} cm</span></div>@endif
    </div>
@endif

@if(in_array('dobras', $blocos) && ($r->protocolo_dobras || $r->dobra_triceps || $r->dobra_peitoral || $r->percentual_gordura))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-compress-arrows-alt"></i> Dobras Cutâneas</p>@endif
    @if($r->protocolo_dobras)<p style="font-size:0.75rem; color:var(--text-muted); margin:0 0 8px;">Protocolo: <strong style="color:#fff;">{{ $r->protocolo_dobras }}</strong></p>@endif
    <div class="snrfit-detail-grid">
        @if($r->dobra_triceps)<div class="dado-item"><label>Tríceps</label><span>{{ number_format($r->dobra_triceps, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_biceps)<div class="dado-item"><label>Bíceps</label><span>{{ number_format($r->dobra_biceps, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_subescapular)<div class="dado-item"><label>Subescapular</label><span>{{ number_format($r->dobra_subescapular, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_suprailiaca)<div class="dado-item"><label>Suprailíaca</label><span>{{ number_format($r->dobra_suprailiaca, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_abdominal)<div class="dado-item"><label>Abdominal</label><span>{{ number_format($r->dobra_abdominal, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_coxa_dc)<div class="dado-item"><label>Coxa</label><span>{{ number_format($r->dobra_coxa_dc, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_peitoral)<div class="dado-item"><label>Peitoral</label><span>{{ number_format($r->dobra_peitoral, 1, ',', '.') }} mm</span></div>@endif
        @if($r->dobra_axilar_media)<div class="dado-item"><label>Axilar Média</label><span>{{ number_format($r->dobra_axilar_media, 1, ',', '.') }} mm</span></div>@endif
        @if($r->percentual_gordura)<div class="dado-item"><label>% Gordura</label><span>{{ number_format($r->percentual_gordura, 2, ',', '.') }}%</span></div>@endif
        @if($r->massa_gorda)<div class="dado-item"><label>Massa Gorda</label><span>{{ number_format($r->massa_gorda, 1, ',', '.') }} kg</span></div>@endif
        @if($r->massa_magra)<div class="dado-item"><label>Massa Magra</label><span>{{ number_format($r->massa_magra, 1, ',', '.') }} kg</span></div>@endif
    </div>
@endif

@if(in_array('postural', $blocos) && ($r->foto_anterior || $r->foto_posterior || $r->foto_lateral_direita || $r->foto_lateral_esquerda || ($r->postural_checklist && count($r->postural_checklist) > 0)))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-person"></i> Postural</p>@endif
    @if($r->foto_anterior || $r->foto_posterior || $r->foto_lateral_direita || $r->foto_lateral_esquerda)
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        @foreach(['foto_anterior'=>'Anterior','foto_posterior'=>'Posterior','foto_lateral_direita'=>'Lat. Direita','foto_lateral_esquerda'=>'Lat. Esquerda'] as $campo => $label)
            @if($r->$campo)
            <div style="text-align:center;">
                <img src="{{ asset('storage/'.$r->$campo) }}" class="foto-thumb" style="width:80px;height:80px;" onclick="abrirLightbox('{{ asset('storage/'.$r->$campo) }}')">
                <div style="font-size:0.6rem; color:var(--text-muted); margin-top:4px;">{{ $label }}</div>
            </div>
            @endif
        @endforeach
    </div>
    @endif
    @if($r->postural_checklist && count($r->postural_checklist) > 0)
    <div style="margin-top:12px;">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            @foreach($r->postural_checklist as $item)
            <span style="background:rgba(212,255,0,0.08); border:1px solid rgba(212,255,0,0.25); color:var(--primary); padding:3px 10px; border-radius:20px; font-size:0.65rem; font-weight:800;">{{ $item }}</span>
            @endforeach
        </div>
    </div>
    @endif
@endif

@if(in_array('neuromotora', $blocos) && ($r->equil_unipodal || $r->coordenacao_motora || $r->mob_ombro || $r->mob_quadril || $r->mob_tornozelo || $r->agach_profundidade || $r->agach_estabilidade || $r->agach_simetria))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-brain"></i> Neuromotora</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->equil_unipodal)<div class="dado-item texto"><label>Equilíbrio Unipodal</label><span>{{ $r->equil_unipodal }}</span></div>@endif
        @if($r->coordenacao_motora)<div class="dado-item texto"><label>Coordenação Motora</label><span>{{ $r->coordenacao_motora }}</span></div>@endif
        @if($r->mob_ombro)<div class="dado-item texto"><label>Mob. Ombro</label><span>{{ $r->mob_ombro }}</span></div>@endif
        @if($r->mob_quadril)<div class="dado-item texto"><label>Mob. Quadril</label><span>{{ $r->mob_quadril }}</span></div>@endif
        @if($r->mob_tornozelo)<div class="dado-item texto"><label>Mob. Tornozelo</label><span>{{ $r->mob_tornozelo }}</span></div>@endif
        @if($r->agach_profundidade)<div class="dado-item texto"><label>Agach. Profundidade</label><span>{{ $r->agach_profundidade }}</span></div>@endif
        @if($r->agach_estabilidade)<div class="dado-item texto"><label>Agach. Estabilidade</label><span>{{ $r->agach_estabilidade }}</span></div>@endif
        @if($r->agach_simetria)<div class="dado-item texto"><label>Agach. Simetria</label><span>{{ $r->agach_simetria }}</span></div>@endif
    </div>
@endif

@if(in_array('flexibilidade', $blocos) && ($r->flex_sentar_alcancar !== null || $r->flex_ombros || $r->flex_quadril !== null))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-person-walking"></i> Flexibilidade</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->flex_sentar_alcancar !== null)<div class="dado-item"><label>Sentar e Alcançar</label><span>{{ number_format($r->flex_sentar_alcancar, 1, ',', '.') }} cm</span></div>@endif
        @if($r->flex_ombros)<div class="dado-item texto"><label>Flex. Ombros</label><span>{{ $r->flex_ombros }}</span></div>@endif
        @if($r->flex_quadril !== null)<div class="dado-item"><label>Flex. Quadril</label><span>{{ number_format($r->flex_quadril, 1, ',', '.') }}</span></div>@endif
    </div>
@endif

@if(in_array('cardio', $blocos) && ($r->bpm || $r->pressao_sistolica || $r->teste_caminhada_dist || $r->teste_cooper_dist || $r->teste_rockport_tempo || $r->vo2max_estimado))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-heart-pulse"></i> Cardiorrespiratória</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->bpm)<div class="dado-item"><label>FC Repouso</label><span>{{ $r->bpm }} bpm</span></div>@endif
        @if($r->pressao_sistolica)<div class="dado-item"><label>Pressão Arterial</label><span>{{ $r->pressao_sistolica }}/{{ $r->pressao_diastolica }} mmHg</span></div>@endif
        @if($r->teste_caminhada_dist)<div class="dado-item"><label>Caminhada 6 min</label><span>{{ number_format($r->teste_caminhada_dist, 0) }} m</span></div>@endif
        @if($r->teste_cooper_dist)<div class="dado-item"><label>Cooper 12 min</label><span>{{ number_format($r->teste_cooper_dist, 0) }} m</span></div>@endif
        @if($r->teste_rockport_tempo)<div class="dado-item"><label>Rockport</label><span>{{ number_format($r->teste_rockport_tempo, 2, ',', '.') }} min</span></div>@endif
        @if($r->vo2max_estimado)<div class="dado-item"><label>VO2max</label><span>{{ number_format($r->vo2max_estimado, 1, ',', '.') }} ml/kg/min</span></div>@endif
    </div>
@endif

@if(in_array('forca', $blocos) && ($r->flexao_braco_reps || $r->prancha_tempo || $r->forca || $r->testes_submax))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-dumbbell"></i> Força</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->flexao_braco_reps)<div class="dado-item"><label>Flexão de Braço</label><span>{{ $r->flexao_braco_reps }} reps</span></div>@endif
        @if($r->prancha_tempo)<div class="dado-item"><label>Prancha</label><span>{{ $r->prancha_tempo }}s</span></div>@endif
        @if($r->forca)<div class="dado-item"><label>Dinamometria</label><span>{{ number_format($r->forca, 1, ',', '.') }} kgf</span></div>@endif
        @if($r->testes_submax)<div class="dado-item texto" style="grid-column:1/-1;"><label>Testes Submáximos</label><span>{{ $r->testes_submax }}</span></div>@endif
    </div>
@endif

@if(in_array('funcional', $blocos) && ($r->func_agachamento || $r->func_avanco || $r->func_stepup || $r->func_prancha || $r->func_mob_toracica))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-running"></i> Funcional</p>@endif
    <div class="snrfit-detail-grid">
        @if($r->func_agachamento)<div class="dado-item texto"><label>Agachamento</label><span>{{ $r->func_agachamento }}</span></div>@endif
        @if($r->func_avanco)<div class="dado-item texto"><label>Avanço</label><span>{{ $r->func_avanco }}</span></div>@endif
        @if($r->func_stepup)<div class="dado-item texto"><label>Step-up</label><span>{{ $r->func_stepup }}</span></div>@endif
        @if($r->func_prancha)<div class="dado-item texto"><label>Prancha</label><span>{{ $r->func_prancha }}</span></div>@endif
        @if($r->func_mob_toracica)<div class="dado-item texto"><label>Mob. Torácica</label><span>{{ $r->func_mob_toracica }}</span></div>@endif
    </div>
@endif

@if(in_array('dor', $blocos) && ($r->dor_lombar !== null || $r->dor_ombro !== null || $r->dor_joelho !== null || $r->dor_quadril !== null || $r->dor_cervical !== null))
    @if($multi)<p style="font-size:0.65rem; font-weight:900; text-transform:uppercase; color:var(--primary); margin:14px 0 8px;"><i class="fas fa-triangle-exclamation"></i> Avaliação de Dor</p>@endif
    <div class="snrfit-detail-grid">
        @foreach(['dor_lombar'=>'Lombar','dor_ombro'=>'Ombro','dor_joelho'=>'Joelho','dor_quadril'=>'Quadril','dor_cervical'=>'Cervical'] as $campo => $label)
            @if($r->$campo !== null)
            @php $dv = $r->$campo; @endphp
            <div class="dado-item">
                <label>{{ $label }}</label>
                <span class="{{ $dv <= 2 ? 'dor-0' : ($dv <= 5 ? 'dor-3' : ($dv <= 8 ? 'dor-6' : 'dor-9')) }}">{{ $dv }}/10</span>
            </div>
            @endif
        @endforeach
    </div>
@endif

@if($r->observacoes)
    <div class="dado-item texto" style="margin-top:14px; {{ $multi ? '' : 'grid-column:1/-1;' }}"><label>Observações</label><span>{{ $r->observacoes }}</span></div>
@endif
