{{--
    Seletor de dia + horário para remarcar, mostrando SÓ o que o personal tem
    livre. Antes eram três campos em branco (data/início/fim) e ele precisava
    abrir a agenda em outra aba para descobrir o que estava vago.

    Usado pelos dois fluxos — aceite de reposição (pacote) e remarcação de
    avulsa —, por isso `uid`: os dois podem aparecer na mesma página e os ids
    de elemento não podem colidir.

    Espera:
      $diasLivres  array<Y-m-d, {rotulo, horarios[]}>  de AgendaService
      $dur         int  duração da aula, em minutos
      $uid         string  sufixo único para os ids
--}}
@if($diasLivres === [])
    <div class="sem-vaga">
        <i class="ph ph-calendar-x"></i>
        Sua agenda está cheia nos próximos 21 dias para uma aula de {{ $dur }} min.
        Libere um horário na agenda para poder remarcar.
    </div>
@else
    <input type="hidden" name="data" id="data{{ $uid }}">
    <input type="hidden" name="hora_inicio" id="ini{{ $uid }}">
    <input type="hidden" name="hora_fim" id="fim{{ $uid }}">

    <div class="campos">
        <div class="campo">
            <label>Dia disponível</label>
            <select id="dia{{ $uid }}" data-uid="{{ $uid }}" class="sel-dia" required>
                <option value="">Escolha o dia…</option>
                @foreach($diasLivres as $data => $info)
                <option value="{{ $data }}">{{ $info['rotulo'] }} ({{ count($info['horarios']) }} livre{{ count($info['horarios']) === 1 ? '' : 's' }})</option>
                @endforeach
            </select>
        </div>
        <div class="campo">
            <label>Horário livre ({{ $dur }} min)</label>
            <select id="hora{{ $uid }}" data-uid="{{ $uid }}" class="sel-hora" required disabled>
                <option value="">Escolha o dia primeiro</option>
            </select>
        </div>
    </div>

    <script>
        // Grade deste bloco, já filtrada pelo servidor. Vai por json_encode
        // porque o echo normal do Blade escapa as aspas e quebra o JS.
        window.vagas = window.vagas || {};
        window.vagas[{!! json_encode($uid) !!}] = {!! json_encode($diasLivres) !!};
    </script>
@endif
