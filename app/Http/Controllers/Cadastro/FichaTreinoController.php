<?php

namespace App\Http\Controllers\Cadastro;

use App\Http\Controllers\Controller;
use App\Models\Cadastro\FichaTreino;
use App\Models\Cadastro\ExercicioFicha;
use App\Models\Cadastro\TreinoConcluido;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Cliente;
use App\Models\Agenda;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FichaTreinoController extends Controller
{
    // ✅ PERSONAL: Ver seus alunos com pacote
    public function meusAlunos()
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        // Alunos com pacote ativo (frequencia_pacote preenchido)
        $alunos = Agenda::with('cliente')
            ->where('personal_id', $personalId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->get()
            ->unique('cliente_id')
            ->values();

        return view('personal.PersonalFichasTreinoAlunos', compact('alunos'));
    }

    // ✅ PERSONAL: Ver fichas de um aluno específico
    public function fichasDoAluno($clienteId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $cliente = Cliente::findOrFail($clienteId);
        $fichas = FichaTreino::with('exercicios')
            ->where('personal_id', $personalId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        $exerciciosData = $this->getExerciciosData();
        return view('personal.PersonalFichasTreinoLista', compact('cliente', 'fichas', 'exerciciosData'));
    }

    // ✅ PERSONAL: Criar nova ficha
    public function criarFicha(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'dia_semana' => 'required|integer|min:0|max:6',
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
            'nivel' => 'required|in:iniciante,avancado',
            'divisao' => 'nullable|string|max:100',
        ]);

        // ✅ Verificar se já existe ficha para este dia
        $jáExiste = FichaTreino::where('personal_id', $personalId)
            ->where('cliente_id', $request->cliente_id)
            ->where('dia_semana', $request->dia_semana)
            ->where('ativo', true)
            ->exists();

        if ($jáExiste) {
            return redirect()->route('fichas-treino.aluno', $request->cliente_id)
                ->with('error', 'Já existe uma ficha para este dia da semana!');
        }

        FichaTreino::create([
            'personal_id' => $personalId,
            'cliente_id' => $request->cliente_id,
            'dia_semana' => $request->dia_semana,
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
            'ativo' => true,
            'nivel' => $request->nivel,
            'divisao' => $request->nivel === 'avancado' ? $request->divisao : null,
        ]);

        return redirect()->route('fichas-treino.aluno', $request->cliente_id)
            ->with('success', 'Ficha criada com sucesso!');
    }

    // ✅ PERSONAL: Adicionar exercício à ficha
    public function adicionarExercicio(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        // Verificar se personal é o dono
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_exercicio' => 'required|string|max:255',
            'series' => 'required|integer|min:1',
            'repeticoes' => 'required|integer|min:1',
            'peso' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
        ]);

        // Encontrar maior ordem para adicionar ao final
        $ultimaOrdem = ExercicioFicha::where('ficha_id', $fichaId)->max('ordem') ?? 0;

        ExercicioFicha::create([
            'ficha_id' => $fichaId,
            'nome_exercicio' => $request->nome_exercicio,
            'series' => $request->series,
            'repeticoes' => $request->repeticoes,
            'peso' => $request->peso,
            'observacoes' => $request->observacoes,
            'ordem' => $ultimaOrdem + 1,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Exercício adicionado!');
    }

    // ✅ PERSONAL: Editar ficha
    public function editarFicha(Request $request, $fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'nome_treino' => 'required|string|max:255',
            'observacoes' => 'nullable|string',
        ]);

        $ficha->update([
            'nome_treino' => $request->nome_treino,
            'observacoes' => $request->observacoes,
        ]);

        return redirect()->route('fichas-treino.aluno', $ficha->cliente_id)
            ->with('success', 'Ficha atualizada!');
    }

    // ✅ PERSONAL: Deletar exercício
    public function deletarExercicio($exercicioId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $exercicio = ExercicioFicha::findOrFail($exercicioId);
        $ficha = $exercicio->ficha;

        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $exercicio->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Exercício removido!');
    }

    // ✅ PERSONAL: Deletar ficha inteira
    public function deletarFicha($fichaId)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->personal_id != $personalId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $clienteId = $ficha->cliente_id;
        $ficha->delete();

        return redirect()->route('fichas-treino.aluno', $clienteId)
            ->with('success', 'Ficha deletada!');
    }

    // ✅ CLIENTE: Ver suas fichas de treino
    public function minhasFichas()
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $cliente = Cliente::findOrFail($clienteId);

        // Buscar todos os personals que têm pacote com este cliente
        $personalsComPacote = Agenda::where('cliente_id', $clienteId)
            ->where('cancelado', false)
            ->where('frequencia_pacote', '!=', null)
            ->pluck('personal_id')
            ->unique();

        // Buscar fichas de treino para estes personals
        $fichas = FichaTreino::with('exercicios', 'personal')
            ->where('cliente_id', $clienteId)
            ->whereIn('personal_id', $personalsComPacote)
            ->where('ativo', true)
            ->orderBy('dia_semana')
            ->get();

        // Agrupar por personal
        $fichasPorPersonal = $fichas->groupBy('personal_id');

        return view('cliente.MinhasFichasTreino', compact('cliente', 'fichasPorPersonal'));
    }

    // ✅ CLIENTE: Marcar treino como concluído
    public function marcarConcluido(Request $request, $fichaId)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $request->validate([
            'data_treino' => 'required|date',
            'observacoes' => 'nullable|string',
        ]);

        $data = $request->data_treino;

        // Buscar ou criar registro do treino
        $treino = TreinoConcluido::firstOrCreate(
            [
                'ficha_id' => $fichaId,
                'cliente_id' => $clienteId,
                'data_treino' => $data,
            ],
            [
                'concluido' => true,
                'observacoes' => $request->observacoes,
            ]
        );

        // Se já existia, apenas atualiza
        if (!$treino->wasRecentlyCreated) {
            $treino->update([
                'concluido' => true,
                'observacoes' => $request->observacoes,
            ]);
        }

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino marcado como concluído!');
    }

    // ✅ CLIENTE: Desmarcar treino como concluído
    public function desmarcarConcluido($fichaId)
    {
        $clienteId = session('cliente_id');
        if (!$clienteId) return redirect()->route('login.index');

        $ficha = FichaTreino::findOrFail($fichaId);
        
        if ($ficha->cliente_id != $clienteId) {
            return redirect()->back()->with('error', 'Acesso negado!');
        }

        $hoje = now()->format('Y-m-d');

        TreinoConcluido::where('ficha_id', $fichaId)
            ->where('cliente_id', $clienteId)
            ->where('data_treino', $hoje)
            ->update(['concluido' => false]);

        return redirect()->route('fichas-treino.minhas')
            ->with('success', 'Treino desmarcado!');
    }

    // ✅ API: Buscar ficha de um dia específico (para modal)
    public function buscarFichaDia($fichaId, $data)
    {
        $ficha = FichaTreino::with('exercicios')->findOrFail($fichaId);

        $clienteId  = session('cliente_id');
        $personalId = session('personal_id');

        if ($ficha->cliente_id !== $clienteId && $ficha->personal_id !== $personalId) {
            return response()->json(['error' => 'Não autorizado'], 403);
        }

        $treino = TreinoConcluido::where('ficha_id', $fichaId)
            ->where('data_treino', $data)
            ->first();

        return response()->json([
            'ficha' => $ficha,
            'treino' => $treino,
        ]);
    }

    private function getExerciciosData(): array
    {
        return [
            // COSTAS
            ['nome' => 'Remada Curvada com Barra', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Incline o tronco a ~45°, coluna neutra e joelhos levemente flexionados. Segure a barra com pegada pronada (palmas para baixo). Puxe em direção ao umbigo retraindo as escápulas no topo. Desça de forma controlada. Não use o balanço do corpo para puxar — o movimento deve ser dos cotovelos para trás.'],
            ['nome' => 'Remada Unilateral com Haltere', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Apoie um joelho e a mão oposta no banco, coluna paralela ao chão. Puxe o haltere em direção ao quadril, cotovelo colado ao corpo. Contraia o dorsal no topo e desça lentamente. Evite rotacionar o tronco durante o movimento — as costas devem permanecer estáveis.'],
            ['nome' => 'Puxada Alta (Lat Pulldown)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Sente-se com as coxas fixas sob os suportes. Segure a barra com pegada um pouco mais larga que os ombros. Puxe até a altura do queixo levando os cotovelos para baixo e para trás. Mantenha o peito alto e levemente inclinado para trás. Suba de forma controlada sem deixar os ombros subirem.'],
            ['nome' => 'Remada Cavalinho', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Apoie o peito no suporte da máquina. Puxe as alças em direção ao corpo retraindo as escápulas. Contraia o dorsal e trapézio no topo. Desça de forma lenta e controlada. Mantenha a respiração ritmada — expire ao puxar, inspire ao soltar.'],
            ['nome' => 'Remada na Polia Baixa', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Sente-se com os pés apoiados e costas eretas, levemente reclinadas. Puxe o cabo em direção ao abdômen com cotovelos próximos ao corpo. Contraia as costas no topo e retorne de forma controlada. Evite curvar a lombar ou usar o balanço do tronco.'],
            ['nome' => 'Barra Fixa (Pull-up)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Segure a barra com pegada pronada um pouco mais larga que os ombros. Parta de braços estendidos e puxe o corpo até o queixo ultrapassar a barra. Mantenha o core contraído e evite balançar. Desça de forma controlada até a extensão total dos cotovelos.'],
            ['nome' => 'Barra Fixa Supinada (Chin-up)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Pegada supinada (palmas para você) na largura dos ombros. Puxe o corpo até o queixo ultrapassar a barra. Recruta mais o bíceps que o pull-up tradicional, além do grande dorsal. Excelente variação para quem está desenvolvendo força para o pull-up pronado.'],
            ['nome' => 'Remada T-bar', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Posicione uma barra em um canto ou use a máquina T-bar. Incline o tronco, segure as alças e puxe em direção ao peito. Pegada neutra trabalha mais o dorsal; pegada larga, mais o trapézio. Mantenha coluna neutra e retraia as escápulas no topo do movimento.'],
            ['nome' => 'Remada com Cabo na Polia Alta', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Em pé ou ajoelhado frente à polia alta com corda ou barra. Puxe o cabo em direção ao peito mantendo cotovelos largos. Excelente para trabalhar a parte superior do dorsal e os romboides. Controle o retorno sem deixar os ombros subir.'],
            ['nome' => 'Pulldown Braço Reto (Straight Arm)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'De pé frente à polia alta com corda ou barra reta. Braços quase estendidos (leve flexão no cotovelo). Puxe o cabo de cima para baixo até a altura do quadril mantendo os braços retos. Isola o grande dorsal sem envolver o bíceps. Controle o retorno lentamente.'],
            ['nome' => 'Puxada Fechada com Pegada Neutra', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Use a barra V ou pegada paralela estreita no lat pulldown. Puxe em direção ao peito com cotovelos apontando para baixo e para dentro. Pegada neutra reduz estresse nos pulsos e trabalha bem a parte inferior do dorsal. Mantenha o peito projetado para frente.'],
            ['nome' => 'Face Pull', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'peito_ombro_triceps'], 'observacao' => 'Polia na altura dos olhos com corda. Puxe a corda em direção ao rosto separando as pontas ao final. Cotovelos acima dos ombros no ponto final. Trabalha deltóide posterior, trapézio médio e romboides. Fundamental para a saúde do ombro e correção postural.'],
            ['nome' => 'Remada Invertida (Australian Pull-up)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'full_body'], 'observacao' => 'Barra baixa ou argolas. Deite abaixo da barra com braços estendidos, corpo em linha reta, calcanhares no chão. Puxe o peito até a barra mantendo o corpo rígido. Ótima regressão para o pull-up ou exercício com peso corporal acessível. Eleve os pés para aumentar a dificuldade.'],
            ['nome' => 'Remada Máquina (Seated Row Machine)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Sente-se e posicione o peito no suporte. Puxe as alças em direção ao tronco retraindo as escápulas. Máquina proporciona movimento guiado, ideal para iniciantes ou para trabalhar de forma isolada sem compensações. Controle a fase excêntrica (retorno) por 2-3 segundos.'],
            ['nome' => 'Superman (Extensão Lombar no Chão)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'abdomen_core'], 'observacao' => 'Deitado de bruços, braços e pernas estendidos. Eleve simultaneamente braços e pernas do chão, contraindo a lombar e glúteos. Segure 2-3 segundos no topo e desça com controle. Excelente exercício sem equipamento para fortalecer toda a cadeia posterior.'],
            ['nome' => 'Good Morning', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'pernas_gluteos'], 'observacao' => 'Barra apoiada na parte superior das costas, pés na largura dos ombros. Com joelhos levemente flexionados, incline o tronco à frente até quase ficar paralelo ao chão. Volte estendendo o quadril. Fortalece lombar, isquiotibiais e glúteos. Use cargas leves inicialmente para dominar o padrão de movimento.'],
            ['nome' => 'Pullover com Haltere', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'peito_ombro_triceps'], 'observacao' => 'Deitado transversalmente no banco, apenas as escápulas apoiadas. Segure um haltere com as duas mãos acima do peito. Braços quase estendidos, desça o haltere atrás da cabeça até sentir o alongamento do dorsal e peito. Retorne à posição inicial. Trabalha grande dorsal, peitoral e tríceps.'],
            ['nome' => 'Remada com Polia em Pé', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'De pé frente à polia baixa com corda ou barra. Puxe em direção ao umbigo com cotovelos próximos ao corpo. Levemente inclinado à frente. Versão funcional da remada que permite maior amplitude de movimento e variação de ângulo.'],
            ['nome' => 'Remada Alta com Barra (Upright Row)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'peito_ombro_triceps'], 'observacao' => 'Em pé, segure a barra com pegada pronada próxima ao centro. Puxe verticalmente até a altura do queixo, com cotovelos acima das mãos. Trabalha trapézio superior e deltóide lateral. Evite pegada muito fechada para não sobrecarregar os ombros. Use cargas moderadas.'],
            ['nome' => 'Remada Pendlay', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Barra no chão, tronco paralelo ao chão. Puxe a barra do chão até o abdômen de forma explosiva e retorne completamente ao chão a cada repetição. Permite cargas máximas e desenvolve força e potência no dorsal. Exige técnica apurada — não arredondar a lombar.'],
            ['nome' => 'Puxada com Corda na Polia', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Use a corda no lat pulldown. Puxe em direção ao peito separando as pontas da corda ao final para aumentar a amplitude. Permite maior rotação do ombro e ativação do dorsal. Controle lentamente no retorno para máximo alongamento.'],
            ['nome' => 'Extensão Lombar na Máquina', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps', 'abdomen_core'], 'observacao' => 'Sente-se na máquina de extensão lombar com quadris fixos no suporte. Incline para frente e estenda de volta à posição ereta contraindo a lombar. Movimento controlado — não hiperextenda além da posição neutra da coluna. Fortalece a cadeia posterior de forma segura.'],
            ['nome' => 'Remada com Elástico', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Prenda o elástico em um ponto fixo na altura do peito. Segure as alças e recue até o elástico ter tensão. Puxe em direção ao abdômen retraindo as escápulas. Ideal para viagens, reabilitação ou aquecimento. Controle a resistência — não deixe o elástico puxar os braços para frente.'],
            ['nome' => 'Serrote (Meadows Row)', 'grupo' => 'Costas', 'divisoes' => ['costas_biceps'], 'observacao' => 'Uma extremidade da barra fixada no canto do chão. De lado, segure a outra extremidade com uma mão. Puxe em direção ao quadril em ângulo diagonal. Permite grande amplitude e carga elevada. Excelente para espessura do dorsal. Mantenha o core contraído e evite girar o tronco.'],
            // BÍCEPS
            ['nome' => 'Rosca Direta com Barra', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Fique em pé, cotovelos fixos próximos ao corpo. Segure a barra com pegada supinada (palmas para cima). Suba até a contração máxima do bíceps e desça de forma controlada. Evite usar o balanço do tronco — o movimento deve ser apenas dos cotovelos para cima.'],
            ['nome' => 'Rosca com Halteres Alternada', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Em pé ou sentado com cotovelos fixos ao lado do corpo. Suba um haltere de cada vez, girando levemente o punho para fora no topo para maximizar a contração do bíceps. Alterne os braços de forma controlada. Mantenha o tronco estável durante todo o movimento.'],
            ['nome' => 'Rosca Martelo', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure os halteres com pegada neutra (palmas voltadas para o corpo). Cotovelos fixos ao lado do tronco. Suba os halteres sem virar os punhos. Trabalha bíceps braquial e braquiorradial. Desça de forma lenta e controlada para máxima tensão muscular.'],
            ['nome' => 'Rosca Scott', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Apoie a parte de trás dos braços no suporte inclinado da máquina Scott. Isso isola completamente o bíceps, eliminando o uso do trapézio. Suba de forma controlada e desça lentamente sem estender totalmente os cotovelos. Ideal para pico e definição do bíceps.'],
            ['nome' => 'Rosca Concentrada', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Sentado, apoie o cotovelo na parte interna da coxa. Suba o haltere até a contração máxima mantendo o cotovelo fixo. Concentre-se em sentir o bíceps contrair. Desça de forma lenta e completa. Excelente para isolamento e definição do bíceps.'],
            ['nome' => 'Rosca Barra W (EZ Bar)', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure a barra W nas pegadas anguladas para reduzir o estresse nos pulsos. Cotovelos fixos ao lado do tronco. Suba até a contração máxima e desça de forma controlada. Permite cargas um pouco maiores que o haltere com menor desconforto no pulso.'],
            ['nome' => 'Rosca Inclinada com Halteres', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Banco reclinado a ~45-60°. Braços pendurados com halteres. A inclinação cria maior alongamento inicial do bíceps. Suba lentamente e contraia no topo. Por trabalhar na amplitude estendida, é excelente para desenvolvimento do tamanho do bíceps.'],
            ['nome' => 'Rosca Inversa (Reverse Curl)', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure a barra com pegada pronada (palmas para baixo). Suba até a contração máxima. Trabalha principalmente o braquiorradial e braquial, além do bíceps. Fortalece o antebraço e melhora a estabilidade do punho. Use cargas menores que na rosca supinada.'],
            ['nome' => 'Rosca Unilateral na Polia', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Polia baixa com alça de mão. Em pé lateral à máquina, puxe o cabo em direção ao ombro. A polia mantém tensão constante em todo o movimento — diferente do haltere que perde tensão no topo. Ótimo para pico e contração máxima do bíceps.'],
            ['nome' => 'Rosca High Cable (Polia Alta)', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Fique entre duas polias altas, segure as alças e abra os braços em "T". Dobre os cotovelos trazendo as mãos à cabeça — como um pose de bíceps. Mantém tensão no bíceps em ângulo diferente. Excelente para a cabeça curta do bíceps.'],
            ['nome' => 'Rosca Zottman', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Suba os halteres com pegada supinada (trabalha bíceps). No topo, gire os punhos para pegada pronada e desça lentamente (trabalha braquiorradial na excêntrica). Combina dois movimentos em um, otimizando o trabalho de bíceps e antebraço.'],
            ['nome' => 'Rosca 21', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Dividida em 3 partes de 7 reps: 7 reps na metade inferior (do fundo até 90°), 7 reps na metade superior (de 90° até o topo), e 7 reps completas. Total: 21 repetições seguidas. Maximiza o tempo sob tensão e trabalha o bíceps em todos os ângulos. Use carga mais leve que o habitual.'],
            ['nome' => 'Rosca Spider', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Apoie o peito no banco inclinado e deixe os braços pendurados à frente. Suba os halteres mantendo os cotovelos fixos apontando para o chão. A gravidade cria resistência constante mesmo no topo do movimento. Excelente para trabalhar a cabeça longa do bíceps.'],
            ['nome' => 'Rosca com Elástico', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Pise no elástico e segure as pontas. Cotovelos fixos ao lado do corpo. Suba o antebraço até a contração e desça controlado. A resistência aumenta ao longo do movimento. Ideal para viagens, reabilitação ou exercícios em casa.'],
            ['nome' => 'Rosca Cabo com Corda', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Polia baixa com corda. Suba mantendo os cotovelos fixos. No topo, separe levemente as pontas da corda para aumentar a contração. A corda permite pegada neutra, reduzindo estresse no pulso. Variação confortável e eficaz para trabalhar bíceps e braquial.'],
            ['nome' => 'Rosca Máquina de Bíceps', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Sente-se e posicione os cotovelos no suporte da máquina. Puxe os antebraços até a contração máxima. Máquina garante o alinhamento perfeito e isola completamente o bíceps. Ideal para iniciantes ou para trabalhar com fadiga avançada com segurança.'],
            ['nome' => 'Rosca Cross-body', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure um haltere com pegada neutra. Em vez de subir reto, traga o haltere em direção ao ombro oposto cruzando o corpo. Enfatiza a cabeça longa do bíceps. Alterne os braços. Mantenha o cotovelo fixo e evite girar o tronco durante o movimento.'],
            ['nome' => 'Rosca Apoiada no Banco Inclinado (Prone)', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Deite de bruços no banco inclinado a 45°, braços pendurados com halteres. Suba os halteres contraindo os bíceps. O posicionamento elimina qualquer impulso do tronco, isolando completamente o bíceps. Excelente para quem tende a usar compensações.'],
            ['nome' => 'Curl de Cabo Cruzado', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Entre duas polias baixas, segure a alça da polia esquerda com a mão direita e vice-versa. Suba os antebraços simultaneamente em direção aos ombros. Mantém tensão constante e trabalha o bíceps em ângulo único. Ótimo para finalização de treino.'],
            ['nome' => 'Rosca com Supinação Forçada', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Inicie com pegada neutra na posição baixa e vá supinando o punho ao longo da subida, finalizando com palmas para cima e polegar para fora. Maximiza a contração do bíceps pela supinação do antebraço. Excelente para pico do bíceps.'],
            ['nome' => 'Rosca Isométrica', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure o haltere a 90° (posição intermediária da rosca) e mantenha estático pelo tempo determinado. Depois complete repetições dinâmicas. Trabalha resistência muscular e fortalece a posição intermediária onde o bíceps é mais fraco. Excelente para queimar final de série.'],
            ['nome' => 'Rosca com Barra no Smith Machine', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'A barra guiada do Smith Machine elimina o equilíbrio, permitindo foco total na contração. Cotovelos fixos, movimento controlado. Permite testar novas cargas com mais segurança. Varie a pegada (pronada, supinada) para diferentes estímulos.'],
            // PEITO
            ['nome' => 'Supino Reto com Barra', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Deite no banco plano, costas bem apoiadas e pés no chão. Segure a barra um pouco mais larga que os ombros. Desça lentamente até tocar levemente o peito na linha dos mamilos, cotovelos a ~45° do corpo. Suba expirando no esforço. Mantenha a curvatura natural da lombar.'],
            ['nome' => 'Supino Inclinado com Halteres', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco a 30-45°. Desça os halteres até os cotovelos ficarem na linha do banco. Empurre para cima e levemente para dentro, não batendo no topo. Foca na parte superior do peitoral. Mantenha as escápulas retraídas e o peito projetado durante o movimento.'],
            ['nome' => 'Crucifixo com Halteres', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco plano, braços estendidos acima do peito com cotovelos levemente flexionados. Abra os braços como se fosse abraçar uma grande árvore, descendo até sentir o alongamento no peito. Retorne usando a força do peitoral. Mantenha a mesma curvatura dos cotovelos durante todo o movimento.'],
            ['nome' => 'Peck Deck (Voador)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Sentado com costas apoiadas, posicione cotovelos ou punhos nos suportes na altura do peito. Junte os braços à frente contraindo o peitoral no ponto de maior aproximação. Abra de forma controlada sentindo o alongamento. Mantenha os ombros afastados das orelhas.'],
            ['nome' => 'Flexão de Braço (Push-up)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos no chão um pouco mais largas que os ombros, corpo em linha reta. Desça o peito até quase tocar o chão, cotovelos a ~45° do corpo. Empurre de volta contraindo o peitoral. Core contraído e quadril não pode cair. Para aumentar a dificuldade, eleve os pés em um banco.'],
            ['nome' => 'Supino Declinado com Barra', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco declinado (~15-30°). Deite com pés fixos nos suportes. Desça a barra em direção à parte inferior do peito. Cotovelos a ~45° do corpo. Trabalha prioritariamente a porção inferior do peitoral. Desça devagar e suba de forma controlada.'],
            ['nome' => 'Supino Inclinado com Barra', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco a 30-45°. Segure a barra na largura dos ombros. Desça até o peito superior. Trabalha prioritariamente o feixe clavicular do peitoral. Mantenha escápulas retraídas e peito projetado. Não descole os glúteos do banco.'],
            ['nome' => 'Supino com Halteres (Banco Plano)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Halteres permitem maior amplitude que a barra. Desça até os cotovelos ficarem abaixo do nível do banco. Empurre para cima e levemente para dentro sem bater os halteres no topo. Exige mais estabilidade que o supino com barra, ativando músculos estabilizadores.'],
            ['nome' => 'Crossover na Polia Alta', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polias altas dos dois lados. Segure as alças, incline levemente o tronco para frente e una as mãos à frente do peito em arco. Mantém tensão constante no peitoral, especialmente na posição contraída. Excelente para definição e separação peitoral.'],
            ['nome' => 'Crossover na Polia Baixa', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polias baixas dos dois lados. Une as mãos à frente do peito mas com trajetória ascendente. Trabalha prioritariamente a parte superior do peitoral. Cotovelos levemente dobrados em todo o arco. Controle o retorno sentindo o alongamento.'],
            ['nome' => 'Cable Fly (Polia Média)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polias na altura dos ombros. Traga as mãos para frente em arco horizontal. Trabalha a porção média do peitoral com tensão constante. Varie a altura das polias para diferentes ângulos. Mantenha cotovelos levemente dobrados e evite usar os braços para puxar.'],
            ['nome' => 'Dips no Paralelo (Peito)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Apoie nas barras paralelas com o tronco inclinado para frente (~30-45°). Desça dobrando os cotovelos até sentir o alongamento no peito. Suba contraindo o peitoral. A inclinação do tronco direciona o esforço para o peito; tronco ereto foca no tríceps.'],
            ['nome' => 'Crucifixo Inclinado', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco a 30-45°. Abra os halteres lateralmente com cotovelos levemente dobrados. Trabalha a parte superior do peitoral em amplitude alongada. Retorne em arco usando o peitoral. Evite descer excessivamente para não sobrecarregar a articulação do ombro.'],
            ['nome' => 'Chest Press Machine', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Máquina de supino guiada. Ajuste a altura do assento para que as alças fiquem na linha do peito. Empurre à frente até quase estender e retorne controlado. Segura para iniciantes e para trabalhar em fadiga avançada. Varie a pegada (neutra ou pronada) para diferentes estímulos.'],
            ['nome' => 'Supino no Smith Machine', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'A barra guiada do Smith permite testar novas cargas com mais segurança. Banco plano, inclinado ou declinado. Útil para treinar sem parceiro de treino. Atenção ao posicionamento do banco em relação à barra para o movimento ser natural.'],
            ['nome' => 'Flexão Diamante', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Mãos no chão formando um losango com os dedos. Corpo em linha reta. Desça o peito entre as mãos. Trabalha prioritariamente tríceps, mas também a porção medial do peitoral. Mantenha o core rígido. Versão mais difícil que a flexão tradicional.'],
            ['nome' => 'Flexão Archer', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos afastadas acima da largura dos ombros. Desça o corpo em direção a uma das mãos enquanto estende o outro braço. Alterne os lados. Trabalha unilateralmente cada porção do peitoral. Excelente progressão entre a flexão normal e a flexão de um braço.'],
            ['nome' => 'Flexão com Pés Elevados', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Coloque os pés em um banco e as mãos no chão. O ângulo trabalha prioritariamente a parte superior do peitoral, similar ao supino inclinado. Mantenha o corpo em linha reta. Quanto mais alto os pés, maior o foco no peito superior.'],
            ['nome' => 'Pullover na Máquina', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'costas_biceps'], 'observacao' => 'Ajuste o assento para os cotovelos ficarem alinhados ao eixo da máquina. Empurre os braços para baixo em arco amplo. Trabalha dorsal e porção inferior do peitoral simultaneamente. Movimento de grande amplitude — não force além do que a mobilidade permite.'],
            ['nome' => 'Flexão com Palmas Elevadas', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Use push-up handles ou halteres no chão para elevar as mãos. Isso aumenta a amplitude de movimento na descida, alongando mais o peitoral. Desça o peito abaixo do nível das mãos. Excelente para ganho de massa e mobilidade peitoral.'],
            ['nome' => 'Landmine Press', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Uma extremidade da barra fixada no chão. Segure a outra extremidade com uma ou duas mãos na altura do peito. Empurre em arco diagonal para frente-cima. Trabalha peito, ombro e tríceps em ângulo funcional. Ótimo para pessoas com limitação de mobilidade de ombro.'],
            ['nome' => 'Crucifixo Declinado', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco declinado com halteres. Abra os braços em arco com cotovelos levemente dobrados. Trabalha a porção inferior do peitoral em amplitude alongada. Retorne contraindo o peito. Mantenha os pés bem fixos nos suportes para segurança.'],
            // OMBRO
            ['nome' => 'Desenvolvimento com Halteres', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Sentado em banco com encosto vertical. Halteres na altura dos ombros, cotovelos a 90°. Empurre para cima até quase estender os braços, sem travar. Desça de forma controlada até a posição inicial. Core contraído e evite arquear a lombar.'],
            ['nome' => 'Elevação Lateral', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Em pé, halteres nas mãos com cotovelos levemente flexionados. Eleve os braços lateralmente até a altura dos ombros (não além, para não sobrecarregar o supraspinoso). Desça de forma lenta e controlada. Use cargas moderadas — esse exercício é muito mais eficaz feito com controle total do que com carga alta.'],
            ['nome' => 'Elevação Frontal', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Segure halteres ou barra com pegada pronada. Braços com cotovelos levemente flexionados. Eleve os braços à frente até a altura dos ombros (90°). Desça de forma controlada. Pode ser feito com ambos os braços ou alternado. Foca no deltóide anterior.'],
            ['nome' => 'Desenvolvimento Arnold', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Começa com halteres na frente e palmas voltadas para você (posição de rosca). Ao subir, gire os pulsos para que as palmas fiquem voltadas para frente no topo. Desça revertendo o movimento. Trabalha as três cabeças do deltóide. Exige coordenação e boa mobilidade de ombro.'],
            ['nome' => 'Desenvolvimento Militar com Barra', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Em pé ou sentado. Barra na frente na altura do peito. Empurre para cima até a extensão completa e desça à frente do queixo. Exercício base de força para ombro. Core muito contraído para não arquear a lombar com cargas pesadas.'],
            ['nome' => 'Push Press', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Barra na frente, pés na largura dos ombros. Dobre levemente os joelhos e estenda explosivamente as pernas enquanto empurra a barra para cima. Usa o impulso das pernas para superar o ponto morto. Permite cargas maiores que o desenvolvimento estrito. Ótimo para potência.'],
            ['nome' => 'Elevação Lateral na Polia', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia baixa lateral. Eleve o cabo em arco lateral até a altura do ombro. A polia mantém tensão constante em todo o movimento, inclusive na posição baixa onde o haltere perde tensão. Excelente para deltóide lateral. Faça com controle total.'],
            ['nome' => 'Pássaro (Bent-over Lateral Raise)', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Incline o tronco à frente até quase paralelo ao chão. Eleve os halteres lateralmente até a altura dos ombros com cotovelos levemente dobrados. Trabalha prioritariamente o deltóide posterior. Essencial para postura e equilíbrio muscular do ombro.'],
            ['nome' => 'Face Pull com Corda', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'costas_biceps'], 'observacao' => 'Polia na altura dos olhos com corda. Puxe a corda em direção ao rosto separando as pontas no final com cotovelos acima dos ombros. Trabalha deltóide posterior, trapézio e manguito rotador. Fundamental para saúde do ombro e prevenção de lesões.'],
            ['nome' => 'Encolhimento de Ombros (Shrugs)', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'costas_biceps'], 'observacao' => 'Segure halteres ou barra ao lado do corpo. Eleve os ombros em direção às orelhas sem dobrar os cotovelos. Contraia o trapézio superior no topo por 1-2 segundos. Desça de forma controlada. Não gire os ombros — movimento é puramente vertical.'],
            ['nome' => 'Elevação Lateral Sentada', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Sente-se no final do banco. Eleve os halteres lateralmente. Posição sentada elimina o impulso do corpo, isolando mais o deltóide. Ótima variação para trabalhar com controle máximo. Use cargas levemente menores que na versão em pé.'],
            ['nome' => 'Desenvolvimento na Máquina de Ombro', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Ajuste o assento para as alças ficarem na altura dos ombros. Empurre para cima em movimento guiado. Seguro para iniciantes e para trabalhar em alta fadiga sem risco. Varie a pegada (neutra ou pronada) para diferentes estímulos no deltóide.'],
            ['nome' => 'Rotação Externa com Elástico', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Cotovelo dobrado a 90° fixo ao lado do corpo. Segure o elástico e gire o antebraço para fora (rotação externa). Mantém a saúde do manguito rotador. Fundamental para prevenir lesões. Movimento pequeno e controlado — não force a amplitude.'],
            ['nome' => 'Rotação Interna com Cabo', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia baixa lateral. Cotovelo dobrado a 90°, braço cruzado à frente do corpo. Gire o antebraço em direção ao abdômen (rotação interna). Trabalha o subescapular. Importante para equilíbrio do manguito rotador junto com os exercícios de rotação externa.'],
            ['nome' => 'Elevação Frontal com Barra', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Segure a barra reta com pegada pronada na largura dos ombros. Eleve até a altura dos ombros mantendo os braços quase estendidos. Controle a descida por 2-3 segundos. Permite carga um pouco maior que o haltere. Foca no deltóide anterior.'],
            ['nome' => 'Cuban Press', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Combina rotação externa e desenvolvimento. Parta com halteres em remada alta até cotovelos a 90°. Então gire os antebraços para cima (externa) e pressione acima da cabeça. Volta revertendo. Excelente para saúde do ombro e força do manguito rotador.'],
            ['nome' => 'Band Pull-apart', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Segure o elástico à frente do corpo com braços estendidos. Puxe as mãos para os lados até o elástico tocar o peito. Trabalha deltóide posterior e romboides. Ótimo para aquecimento do ombro e correção postural. Faça 2-3 séries antes do treino de peito ou ombro.'],
            ['nome' => 'Y-T-W com Halteres', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'costas_biceps'], 'observacao' => 'Incline o tronco 45° ou deite de bruços no banco. Eleve os halteres leves formando as letras Y (acima da cabeça), T (lateralmente) e W (cotovelos dobrados puxando para cima). 5-8 reps de cada. Trabalha toda a musculatura estabilizadora do ombro.'],
            ['nome' => 'Desenvolvimento com Kettlebell', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Segure o kettlebell no rack position (punho girado, apoiado no antebraço). Empurre para cima até extensão completa. A base do kettlebell mais larga que o haltere exige mais estabilidade do ombro. Ótimo para desenvolver força funcional.'],
            ['nome' => 'Elevação Lateral com Cabo Cruzado', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia baixa do lado oposto. Passe o cabo cruzando à frente do corpo e eleve lateralmente. Mantém tensão no deltóide lateral em todo o arco — inclusive na posição baixa onde o haltere perde tensão. Variação avançada para máxima ativação.'],
            ['nome' => 'Overhead Press Unilateral', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Pressione um haltere acima da cabeça de cada vez enquanto mantém o outro em repouso. Exige mais estabilização do core e correção de desequilíbrios entre os lados. Faça todas as reps de um lado antes de trocar, ou alterne. Mantenha o core contraído.'],
            ['nome' => 'Lateral Raise Machine', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Ajuste o assento para o eixo da máquina alinhar com o ombro. Eleve os braços lateralmente contra a resistência da máquina. Movimento guiado elimina compensações. Ideal para iniciantes aprendendo o padrão do movimento ou para trabalhar em alta fadiga com segurança.'],
            ['nome' => 'Pássaro na Máquina (Rear Delt Fly Machine)', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'costas_biceps'], 'observacao' => 'Sente-se de frente para o encosto com o peito apoiado. Segure as alças com braços estendidos. Abra os braços para trás contraindo o deltóide posterior. Máquina permite isolar completamente sem compensações. Essencial para equilíbrio muscular do ombro.'],
            // TRÍCEPS
            ['nome' => 'Tríceps Corda no Pulley', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'De pé de frente para a polia alta. Cotovelos fixos próximos ao corpo, levemente à frente do tronco. Empurre a corda para baixo até a extensão total, separando as pontas ao final. Retorne de forma controlada sem deixar os cotovelos subirem muito. Foca na cabeça lateral do tríceps.'],
            ['nome' => 'Tríceps Testa', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado no banco com barra W acima do peito, braços estendidos. Mantendo cotovelos apontando para o teto e fixos, desça o peso em direção à testa ou ligeiramente atrás. Estenda os cotovelos de volta à posição inicial. Foca na cabeça longa do tríceps. Use cargas controladas.'],
            ['nome' => 'Rosca Francesa', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Sentado ou em pé com um haltere seguro com as duas mãos acima da cabeça. Cotovelos próximos da cabeça e fixos. Dobre os cotovelos abaixando o haltere atrás da cabeça até sentir o alongamento. Suba estendendo completamente os cotovelos. Excelente para a cabeça longa do tríceps.'],
            ['nome' => 'Mergulho (Dips) no Banco', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos apoiadas no banco atrás do corpo, pernas estendidas à frente. Dobre os cotovelos descendo o quadril até eles chegarem a ~90°. Empurre de volta contraindo o tríceps. Para aumentar a dificuldade, eleve os pés em outro banco. Cotovelos devem apontar para trás, não para os lados.'],
            ['nome' => 'Dips no Paralelo (Tríceps)', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Apoie nas barras paralelas com tronco ereto (quanto mais ereto, mais foco no tríceps). Desça dobrando os cotovelos até ~90° e suba estendendo completamente. Excelente exercício com peso corporal para tríceps. Adicione peso com colete ou cinto de carga.'],
            ['nome' => 'Tríceps com Barra na Polia', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia alta com barra reta. Cotovelos fixos ao lado do tronco. Empurre a barra para baixo até extensão total. Barra reta trabalha a cabeça medial do tríceps. Mantenha os pulsos neutros e não deixe os cotovelos se afastarem do corpo durante o movimento.'],
            ['nome' => 'Tríceps Pegada Invertida (Reverse Grip)', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia alta com barra reta e pegada supinada. Empurre para baixo com as palmas para cima. Ativa fortemente a cabeça medial do tríceps. Exige menos carga que a pegada pronada. Excelente variação para completar o trabalho do tríceps.'],
            ['nome' => 'Overhead Tríceps na Polia', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Polia baixa com corda. De costas para a máquina, segure a corda atrás da cabeça com cotovelos dobrados. Estenda os cotovelos empurrando para frente-cima. Posição overhead alonga a cabeça longa maximizando o trabalho. Use cargas moderadas para manter a técnica.'],
            ['nome' => 'Kickback com Haltere', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Incline o tronco à frente, cotovelo próximo ao corpo em 90°. Estenda o braço para trás até a extensão completa. Contraia no final e desça controlado. Foca na cabeça lateral do tríceps. A posição inclinada garante que não haja impulso — movimento puro de extensão do cotovelo.'],
            ['nome' => 'Skull Crusher com Halteres', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado com halteres acima do peito. Dobre os cotovelos abaixando os halteres lateralmente à cabeça. Estenda de volta. Permite maior amplitude de movimento que a barra e reduz estresse nos pulsos. Controle absoluto na descida para não machucar a articulação.'],
            ['nome' => 'Extensão Unilateral com Haltere', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Segure um haltere com uma mão acima da cabeça. Cotovelo fixo próximo à cabeça. Dobre abaixando o haltere atrás da cabeça e estenda. Trabalha unilateralmente, corrigindo desequilíbrios. Pode apoiar o cotovelo com a mão livre para estabilizar.'],
            ['nome' => 'Tríceps Máquina', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Ajuste o assento e posicione os cotovelos no suporte. Empurre as alças para baixo até a extensão total. Movimento guiado isola o tríceps com segurança. Ideal para iniciantes, reabilitação ou para trabalhar em alta fadiga no final do treino.'],
            ['nome' => 'Close Grip Push-up', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos juntas abaixo do peito. Corpo em linha reta. Desça com cotovelos próximos ao corpo. Suba contraindo o tríceps. Exercício com peso corporal acessível que trabalha intensamente tríceps e peitoral medial. Mantenha o core rígido durante todo o movimento.'],
            ['nome' => 'Rolling Triceps Extension', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado com halteres. Faça o skull crusher descendo à cabeça e, em vez de subir direto, role os cotovelos para frente e pressione como um supino fechado. Combina dois movimentos aumentando o tempo sob tensão. Trabalha tríceps em amplitude completa.'],
            ['nome' => 'Tríceps com Elástico', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Prenda o elástico acima (numa barra ou porta) ou pise nele. Empurre para baixo (pushdown) ou para trás (kickback). Resistência variável — máxima no final do movimento. Ideal para viagens, aquecimento ou recuperação. Mantenha cotovelos fixos.'],
            ['nome' => 'JM Press', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado com barra W. Posição intermediária entre supino fechado e skull crusher. Cotovelos se movem levemente ao descer, mas permanecem à frente do corpo. Permite cargas elevadas para o tríceps. Exercício avançado — domine primeiro o skull crusher e o supino fechado.'],
            ['nome' => 'Supino Fechado com Barra', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Supino com pegada estreita (mãos a ~30cm de distância). Desça a barra ao peito com cotovelos próximos ao corpo. Suba empurrando com foco nos tríceps. Permite cargas elevadas. Trabalha tríceps, peitoral medial e ombro anterior. Um dos melhores exercícios básicos para tríceps.'],
            ['nome' => 'Tríceps Francês com Barra W', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado com barra W. Cotovelos apontados para o teto. Dobre os cotovelos abaixando a barra à testa ou atrás da cabeça para maior amplitude. Estenda completamente. A barra W reduz o estresse nos pulsos em comparação à barra reta. Excelente para cabeça longa.'],
            ['nome' => 'Extensão de Tríceps Deitado com Cabo', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deite no banco com a cabeça próxima à polia baixa. Segure a barra ou corda atrás da cabeça. Estenda os cotovelos puxando o cabo sobre a cabeça até os braços ficarem paralelos ao banco. A polia mantém tensão constante no alongamento.'],
            ['nome' => 'Dips Ponderados', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Dips no paralelo com peso adicional (colete, cinto de carga ou haltere entre as pernas). Tronco ereto para focar no tríceps. Avanço progressivo dos dips com peso corporal. Exige boa mobilidade de ombro. Não use excessivamente em ombros problemáticos.'],
            ['nome' => 'Pushdown na Máquina de Tríceps', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Máquina de pressão para baixo com cotovelos apoiados. Pressione até a extensão total e contraia o tríceps no final. Máquina garante o isolamento perfeito. Use para finalizar o treino com alta repetição e total esgotamento do tríceps sem risco de lesão.'],
            // PERNAS
            ['nome' => 'Agachamento Livre com Barra', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'full_body'], 'observacao' => 'Barra na parte superior das costas (trapézio). Pés na largura dos ombros, pontas levemente abertas. Desça mantendo o tronco ereto e joelhos alinhados com os pés, até a coxa ficar paralela ao chão. Suba expirando no esforço. Core contraído e coluna neutra durante todo o movimento.'],
            ['nome' => 'Leg Press 45°', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Costas bem apoiadas na máquina. Pés na plataforma na largura dos ombros. Desça a plataforma de forma controlada até as coxas ficarem a ~90°, sem deixar os joelhos passarem muito dos pés. Suba empurrando pelo calcanhar. Nunca trave os joelhos no topo.'],
            ['nome' => 'Cadeira Extensora', 'grupo' => 'Quadríceps', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Sentado com costas apoiadas, apoio na parte inferior da perna. Estenda os joelhos até quase a extensão total, contraindo o quadríceps no topo. Desça de forma lenta e controlada. Exercício de isolamento para o quadríceps. Evite travar completamente os joelhos para preservar a articulação.'],
            ['nome' => 'Cadeira Flexora', 'grupo' => 'Isquiotibiais', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Deitado de bruços, apoio na parte inferior da perna (tornozelo). Dobre os joelhos puxando o peso até os calcanhares se aproximarem das nádegas. Contraia os isquiotibiais no topo e retorne de forma lenta. Evite arquear muito a lombar. Principal exercício de isolamento para os isquiotibiais.'],
            ['nome' => 'Agachamento Búlgaro', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Apoie um pé atrás em um banco. A perna da frente deve estar suficientemente à frente para manter o joelho alinhado com o pé. Desça o joelho de trás em direção ao chão, tronco ereto. Trabalha fortemente quadríceps e glúteo. Segure halteres para adicionar carga.'],
            ['nome' => 'Avanço (Lunge)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'full_body'], 'observacao' => 'Dê um passo longo à frente, descendo o joelho de trás próximo ao chão. O joelho da frente forma ~90° e não deve passar muito da ponta do pé. Empurre pelo calcanhar da frente para voltar. Pode ser feito estacionário, caminhando ou com halteres. Trabalha quadríceps, isquiotibiais e glúteos.'],
            ['nome' => 'Stiff com Halteres', 'grupo' => 'Isquiotibiais', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Em pé com halteres na frente do corpo. Joelhos com leve flexão fixa. Incline o tronco à frente descendo os halteres ao longo das pernas até sentir o alongamento dos isquiotibiais. Suba estendendo o quadril. O movimento parte do quadril — nunca curve a coluna.'],
            ['nome' => 'Glúteo na Polia (Kick Back)', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Prenda a tornozeleira no cabo baixo. De frente para a máquina, segure o suporte e empurre a perna para trás, contraindo o glúteo no ponto máximo. Core contraído e tronco estável. Retorne de forma controlada. Excelente para isolamento do glúteo máximo.'],
            ['nome' => 'Abdução de Quadril na Máquina', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Sentado na máquina com joelhos nos apoios internos. Abra as pernas contra a resistência contraindo o glúteo médio no ponto máximo. Retorne de forma controlada sem bater. Trabalha o glúteo médio, responsável pelo arredondamento lateral do quadril.'],
            ['nome' => 'Panturrilha em Pé (Calf Raise)', 'grupo' => 'Panturrilha', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Em pé com as pontas dos pés num degrau. Suba nas pontas ao máximo, contraindo a panturrilha no topo. Desça além do nível do degrau para alongar completamente. Pode ser feito com halteres para carga extra. Movimentos lentos e controlados para máxima ativação.'],
            ['nome' => 'Agachamento Sumô', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Pés bem afastados, pontas abertas a ~45°. Segure um haltere ou kettlebell na frente do corpo. Desça mantendo tronco ereto e joelhos apontando na direção dos pés. Trabalha mais o adutor interno e glúteos. Suba expirando no esforço.'],
            ['nome' => 'Hack Squat na Máquina', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Costas apoiadas na plataforma inclinada, ombros sob os suportes. Pés na largura dos ombros. Desça até ~90° e suba empurrando pelos calcanhares. Foca fortemente no quadríceps. Posição dos pés altera o foco: mais para cima = mais quadríceps, mais para baixo = mais glúteo.'],
            ['nome' => 'Agachamento Frontal (Front Squat)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'full_body'], 'observacao' => 'Barra apoiada na frente, sobre o deltóide anterior, cotovelos altos. Exige grande mobilidade de tornozelo e ombro. Foca mais no quadríceps e exige tronco mais ereto que o agachamento traseiro. Ideal para atletas e praticantes avançados.'],
            ['nome' => 'Agachamento Goblet', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Segure um kettlebell ou haltere na frente do peito com as duas mãos. Agache com os cotovelos por dentro dos joelhos no fundo. Força o tronco ereto naturalmente. Excelente para iniciantes aprenderem o padrão do agachamento e para mobilidade de quadril.'],
            ['nome' => 'Agachamento com Salto (Jump Squat)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'cardio', 'full_body'], 'observacao' => 'Agache até ~90° e salte explosivamente para cima. Pouso suave nas pontas dos pés e absorva o impacto dobrando os joelhos imediatamente. Desenvolve potência de membros inferiores. Pode ser feito com peso corporal ou segurando halteres leves.'],
            ['nome' => 'Hip Thrust com Barra', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Apoie a parte superior das costas num banco, barra sobre o quadril protegida com almofada. Pés no chão. Empurre o quadril para cima contraindo fortemente os glúteos no topo. Desça controlado. Um dos exercícios mais eficazes para hipertrofia do glúteo máximo.'],
            ['nome' => 'Ponte de Glúteo (Glute Bridge)', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Deitado com joelhos dobrados e pés no chão. Empurre o quadril para cima contraindo glúteos e core. Segure 2s no topo. Versão sem equipamento do hip thrust. Pode progredir adicionando anilha sobre o quadril ou uma perna só (single-leg bridge).'],
            ['nome' => 'Step-up na Caixa', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Suba com um pé em uma caixa ou banco, empurrando pelo calcanhar para elevar o corpo. Toque com o outro pé e desça controlado. Trabalha quadríceps e glúteos unilateralmente. Use halteres para adicionar carga. Quanto mais alto o step, maior o trabalho do glúteo.'],
            ['nome' => 'Levantamento Terra Romeno (RDL)', 'grupo' => 'Isquiotibiais', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Em pé com barra ou halteres. Joelhos quase estendidos. Incline o tronco empurrando o quadril para trás, descendo o peso ao longo das pernas. Sinta o alongamento dos isquiotibiais. Suba estendendo o quadril. Diferente do Stiff, os joelhos ficam mais retos.'],
            ['nome' => 'Adução de Quadril na Máquina', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Sentado na máquina com joelhos nos apoios externos. Feche as pernas contra a resistência contraindo os adutores (parte interna da coxa). Retorne controlado sem bater. Trabalha os adutores que não são isolados na maioria dos exercícios básicos.'],
            ['nome' => 'Afundo Reverso (Reverse Lunge)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Em pé, dê um passo para trás com uma perna descendo o joelho próximo ao chão. Retorne empurrando pelo calcanhar da perna que ficou à frente. Mais fácil para o joelho que o avanço tradicional. Ótimo para iniciantes ou reabilitação.'],
            ['nome' => 'Passada Lateral (Lateral Lunge)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Dê um passo lateral largo, dobrando o joelho da perna que avançou enquanto a outra permanece estendida. Desça o quadril e empurre de volta. Trabalha adutores, abdutores e glúteo médio. Melhora mobilidade de quadril. Pode adicionar haltere.'],
            ['nome' => 'Leg Curl em Pé na Máquina', 'grupo' => 'Isquiotibiais', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Em pé com uma perna apoiada. Dobre o joelho trazendo o calcanhar em direção aos glúteos. Contraia os isquiotibiais no topo. Trabalho unilateral corrige desequilíbrios. Mais funcional que a cadeira flexora por trabalhar em posição ereta.'],
            ['nome' => 'Panturrilha Sentado (Seated Calf Raise)', 'grupo' => 'Panturrilha', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Sentado com joelhos a 90° e apoio nas coxas. Eleve os calcanhares nas pontas dos pés e desça além do nível do suporte. Posição sentada recruta mais o sóleo (músculo abaixo do gastrocnêmio). Complementa o calf raise em pé para trabalho completo da panturrilha.'],
            ['nome' => 'Panturrilha no Leg Press', 'grupo' => 'Panturrilha', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'No leg press, posicione apenas as pontas dos pés na borda inferior da plataforma, joelhos quase estendidos. Empurre a plataforma com as pontas dos pés e volte. Permite carga muito elevada para a panturrilha. Controle absoluto para não escorregar.'],
            ['nome' => 'Monster Walk com Elástico', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Elástico nos tornozelos ou acima dos joelhos, semi-agachado. Dê passos laterais mantendo a tensão no elástico. Ativa glúteo médio e TFL. Excelente aquecimento para treinos de perna ou exercício complementar para estabilidade do quadril.'],
            ['nome' => 'Agachamento Pistol (Single-leg Squat)', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'full_body'], 'observacao' => 'Agachamento em uma perna só, estendendo a outra à frente. Desça o máximo possível mantendo o equilíbrio. Exercício avançado que exige força, equilíbrio e mobilidade. Pode progredir de agachamento parcial unilateral até o pistol completo.'],
            ['nome' => 'Box Jump', 'grupo' => 'Pernas', 'divisoes' => ['pernas_gluteos', 'full_body', 'cardio'], 'observacao' => 'Agache levemente e salte explosivamente para cima de uma caixa. Pouse suavemente com joelhos dobrados amortecendo o impacto. Desça da caixa pisando (não pulando) para preservar os joelhos. Desenvolve potência e explosão dos membros inferiores.'],
            ['nome' => 'Cadeira Flexora em Pé Unilateral', 'grupo' => 'Isquiotibiais', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Na máquina ou com cabo e tornozeleira. Dobre o joelho de uma perna de cada vez. Trabalho unilateral isola cada isquiotibial separadamente. Corrija desequilíbrios antes de trabalhar bilateralmente com cargas elevadas.'],
            ['nome' => 'Glúteo 4 apoios na Polia', 'grupo' => 'Glúteos', 'divisoes' => ['pernas_gluteos'], 'observacao' => 'Com tornozeleira no cabo baixo, posicione-se de quatro apoios. Empurre a perna para cima e para trás com o joelho dobrado a 90° (donkey kick). Contraia o glúteo no topo. Excelente para isolar o glúteo máximo. Mantenha a lombar neutra — não arqueie.'],
            // ABDÔMEN
            ['nome' => 'Abdominal Crunch', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com joelhos dobrados e pés no chão. Mãos atrás da cabeça (sem puxar o pescoço) ou cruzadas no peito. Contraia o abdômen elevando os ombros do chão — a lombar permanece apoiada. Contraia no topo e desça de forma controlada. Expire ao contrair, inspire ao descer.'],
            ['nome' => 'Abdominal Oblíquo', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com joelhos dobrados. Direcione um ombro em direção ao joelho oposto ao contrair. Alterne os lados de forma lenta e controlada. Mãos na nuca sem puxar a cabeça. Sinta a contração na lateral do abdômen (oblíquos) — não é uma rotação do pescoço.'],
            ['nome' => 'Prancha Frontal', 'grupo' => 'Core', 'divisoes' => ['abdomen_core', 'full_body'], 'observacao' => 'Apoie-se nos antebraços e nas pontas dos pés, corpo em linha reta da cabeça aos calcanhares. Contraia abdômen, glúteos e quadríceps. Respiração estável. Evite deixar o quadril cair ou subir. Olhe para o chão para manter a coluna neutra. Comece com 20-30s e aumente progressivamente.'],
            ['nome' => 'Prancha Lateral', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado de lado, apoie-se no antebraço e no lado do pé. Eleve o quadril formando uma linha reta. Contraia os oblíquos para manter a posição. Pode adicionar elevação do quadril para maior intensidade. Troque de lado após o tempo determinado. Foca nos oblíquos e quadrado lombar.'],
            ['nome' => 'Elevação de Pernas', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Suspenso na barra ou apoiado nos suportes de cotovelo. Com pernas estendidas (ou dobradas para iniciantes), eleve-as até a horizontal contraindo o abdômen. Desça de forma controlada sem balançar. Foca no abdômen inferior. Versão avançada: leve os pés até a barra.'],
            ['nome' => 'Russian Twist', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Sentado com joelhos dobrados, tronco levemente reclinado e pés levantados. Com mãos juntas ou segurando peso, gire o tronco de um lado ao outro tocando o chão. Abdômen contraído durante todo o exercício. Para maior dificuldade, use um haltere ou anilha.'],
            ['nome' => 'Dead Bug', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com braços estendidos para cima e pernas a 90°. Simultaneamente, estenda o braço direito acima da cabeça e a perna esquerda em direção ao chão, sem tocar. Retorne e repita do outro lado. Lombar colada no chão durante todo o movimento. Excelente para estabilidade do core.'],
            ['nome' => 'Hiperextensão Lombar', 'grupo' => 'Lombar', 'divisoes' => ['abdomen_core'], 'observacao' => 'No banco de hiperextensão, quadris apoiados. Braços cruzados no peito. Desça o tronco e suba até a linha do corpo — não além para não hiperestender. Contraia lombar e glúteos no topo. Fundamental para fortalecer a região lombar e prevenir lesões de coluna.'],
            ['nome' => 'Bicicleta Abdominal', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado, mãos na nuca. Eleve os ombros e traga o joelho direito em direção ao cotovelo esquerdo enquanto estende a perna esquerda. Alterne em movimento de pedalada. Trabalha reto abdominal e oblíquos simultaneamente. Mantenha ritmo controlado — não gire apenas o pescoço.'],
            ['nome' => 'Crunch Reverso', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com pernas a 90°. Em vez de subir o tronco, eleve o quadril do chão trazendo os joelhos em direção ao peito. Contraia o abdômen inferior no topo. Desça controlado sem tocar o chão completamente. Foca no reto abdominal inferior.'],
            ['nome' => 'Roda Abdominal (Ab Wheel)', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Ajoelhado com a roda no chão. Role à frente estendendo o corpo até quase tocar o chão, mantendo o core contraído. Retorne usando os abdominais. Exercício avançado — comece com amplitude curta. Exige grande força de core e estabilidade de ombro.'],
            ['nome' => 'Crunch com Cabo', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Ajoelhado de frente ou de costas para a polia alta. Segure a corda atrás da cabeça. Curve o tronco para baixo contraindo o abdômen. Retorne controlado. A polia mantém tensão constante. Permite adicionar carga progressiva ao crunch — excelente para hipertrofia abdominal.'],
            ['nome' => 'Mountain Climber', 'grupo' => 'Core', 'divisoes' => ['abdomen_core', 'cardio', 'full_body'], 'observacao' => 'Posição de prancha alta. Traga alternadamente os joelhos em direção ao peito em ritmo rápido. Core contraído e quadril estável — não deixe subir. Combina trabalho de core com elevação da frequência cardíaca. Excelente para circuitos e HIIT.'],
            ['nome' => 'Pallof Press', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'De lado para a polia na altura do peito. Segure a alça próxima ao peito e empurre à frente até os braços estenderem. Resista à rotação que a polia tenta causar. Mantenha o core neutro. Excelente para anti-rotação e estabilidade funcional do core.'],
            ['nome' => 'V-up', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com braços e pernas estendidos. Suba simultaneamente o tronco e as pernas formando um "V". Tente tocar os pés com as mãos no topo. Desça controlado. Exercício avançado que trabalha reto abdominal em todo o comprimento. Inicie com as pernas dobradas se necessário.'],
            ['nome' => 'Hollow Body Hold', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado, pressione a lombar no chão, eleve levemente os ombros e as pernas estendidas do chão (30-45cm). Braços estendidos acima da cabeça. Mantenha a posição pelo tempo. Quanto mais baixas as pernas, mais difícil. Exercício fundamental da ginástica para core anterior.'],
            ['nome' => 'Toes to Bar', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Suspenso na barra, eleve as pernas estendidas até tocar a barra com os pés. Core muito forte necessário. Iniciantes podem dobrar os joelhos (knees to chest). Controle absoluto na descida para não balançar. Trabalha abdômen inferior intensamente.'],
            ['nome' => 'Cable Woodchop', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Polia alta lateral. Puxe o cabo diagonalmente de cima para baixo e para o lado oposto em movimento de "cortar lenha". Core rotacional. Trabalha oblíquos e toda a cadeia de rotação. Faça dos dois lados. Mantenha os braços quase estendidos durante o arco.'],
            ['nome' => 'Side Bend com Haltere', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Em pé com haltere em uma mão. Incline o tronco lateralmente em direção ao haltere e retorne contraindo o oblíquo do lado oposto. Movimento lento e controlado. Não confunda com báscula de quadril — é o tronco que se inclina. Trabalha oblíquos e quadrado lombar.'],
            ['nome' => 'Abdominal Decline', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Banco declinado com os pés fixos. Realize crunch com o tronco inclinado para baixo. A posição declinada aumenta a amplitude e a intensidade do exercício. Pode adicionar carga (anilha no peito) para sobrecarga progressiva. Trabalha reto abdominal em amplitude maior.'],
            ['nome' => 'L-Sit nas Paralelas', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Apoiado nas barras paralelas ou no chão, empurre para elevar o corpo e segure as pernas estendidas paralelas ao chão formando um "L". Requer força de ombros, tríceps e core simultâneos. Exercício avançado — progrida de joelhos dobrados até pernas completamente estendidas.'],
            ['nome' => 'Prancha com Elevação de Braço', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Prancha alta (braços estendidos). Eleve um braço à frente mantendo o corpo estabilizado sem rotação do quadril. Alterne os lados. A elevação do braço desafia a estabilidade lateral do core. Core deve resistir ativamente ao desequilíbrio. Versão: adicione elevação de perna oposta.'],
            ['nome' => 'Hiperextensão Reversa', 'grupo' => 'Lombar', 'divisoes' => ['abdomen_core', 'pernas_gluteos'], 'observacao' => 'Deitado de bruços sobre um banco com as pernas para fora. Eleve as pernas estendidas contraindo glúteos e lombar. Desça controlado. Trabalha a cadeia posterior de forma segura para a coluna. Excelente para glúteo e lombar sem compressão axial.'],
            ['nome' => 'Abdominal na Máquina', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Sente-se com o peito apoiado no suporte. Empurre para baixo (flexão de tronco) contra a resistência da máquina. Permite carga progressiva para hipertrofia abdominal. Movimento controlado — não use impulso. Excelente para sobrecarga progressiva do reto abdominal.'],
            // CORPO INTEIRO
            ['nome' => 'Burpee', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Em pé, agache tocando as mãos no chão. Jogue os pés para trás ficando em posição de prancha. Faça uma flexão (opcional). Pule os pés de volta próximo às mãos. Salte para cima com braços acima da cabeça. Exercício completo de alta intensidade que combina força e condicionamento cardiovascular.'],
            ['nome' => 'Kettlebell Swing', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Segure o kettlebell com as duas mãos. Incline o tronco à frente com joelhos levemente dobrados (não é um agachamento) e balance para trás entre as pernas. Empurre o quadril à frente explosivamente para balançar até a altura dos ombros. O movimento é do quadril — os braços são apenas guias, não puxam.'],
            ['nome' => 'Levantamento Terra', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'pernas_gluteos'], 'observacao' => 'Barra no chão, pés na largura dos ombros. Agache com coluna neutra e segure a barra com mãos fora das pernas. Suba empurrando o chão com os pés e estendendo o quadril. A barra deve roçar as pernas durante toda a subida. Nunca curve a lombar. É o exercício que recruta mais grupos musculares do corpo.'],
            ['nome' => 'Thruster', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Segure halteres na altura dos ombros. Faça um agachamento completo e ao subir, aproveite o impulso para empurrar os halteres acima da cabeça em um único movimento fluido. Volte à posição inicial trazendo os halteres aos ombros enquanto entra no agachamento. Combina força de perna e empurão de ombro.'],
            ['nome' => 'Clean and Press', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Puxe a barra do chão até os ombros (clean) em movimento explosivo usando as pernas e quadril. Em seguida, pressione acima da cabeça. Dois movimentos conectados. Trabalha pernas, costas, ombros e core. Exercício olímpico que exige técnica apurada.'],
            ['nome' => 'Snatch com Kettlebell', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Balanço do kettlebell com uma mão, guiando-o em arco até ficar pressionado acima da cabeça em um único movimento explosivo. Usa pernas, quadril, costas e ombro. Exige prática de técnica antes de adicionar carga. Desenvolve potência e coordenação.'],
            ['nome' => 'Turkish Get-up', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'abdomen_core'], 'observacao' => 'Deitado com kettlebell ou haltere em uma mão estendida. Levante-se do chão sem deixar o braço cair, passando por posições de apoio, ajoelhado e finalmente em pé. Desça revertendo o movimento. Exige coordenação, estabilidade de ombro e core. Exercício completo e funcional.'],
            ['nome' => 'Farmer\'s Walk', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Segure halteres pesados ou kettlebells ao lado do corpo. Caminhe de forma controlada mantendo postura ereta. Trabalha força de preensão, trapézio, core e pernas. Quanto mais pesado e mais distância, mais intenso. Fundamental para força funcional total.'],
            ['nome' => 'Man Maker', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Com halteres no chão: agache, jogue os pés para trás, faça flexão + remada de cada lado, pule os pés de volta, faça clean e termine com press overhead. Sequência completa que trabalha todos os grupos musculares. Alta demanda cardiovascular e de força.'],
            ['nome' => 'Battle Rope (Ondas com Corda)', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Segure as pontas da corda grossa e faça ondas alternadas ou simultâneas. Pode variar com movimentos laterais, saltos ou agachamentos. Trabalha braços, ombros, core e eleva muito a frequência cardíaca. Ótimo para condicionamento físico geral.'],
            ['nome' => 'Sled Push (Trenó)', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Empurre o trenó carregado em linha reta. Tronco inclinado, passos curtos e rápidos. Trabalha quadríceps, glúteos, panturrilha, ombros e core. Alta intensidade cardiovascular. Ajuste a carga para manter velocidade constante. Excelente para desenvolvimento de potência.'],
            ['nome' => 'Sled Pull (Trenó Puxar)', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Prenda uma corda no trenó e puxe caminhando para trás. Mantém a tensão nos isquiotibiais, glúteos e costas durante o movimento. Variação do sled push com estímulo diferente. Pode usar diferentes pegadas (alta = costas, baixa = pernas).'],
            ['nome' => 'Medball Slam', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Segure a medicine ball acima da cabeça. Jogue-a violentamente no chão usando todo o corpo — extensão seguida de flexão intensa. Apanhe a bola no ricochete e repita. Exercício de potência e liberação de energia. Trabalha core, ombros, costas e pernas.'],
            ['nome' => 'Wall Ball Shot', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Segure a medicine ball no peito. Agache profundamente e ao subir, lance a bola contra a parede em altura marcada (3-4m). Apanhe a bola na descida e entre imediatamente no próximo agachamento. Combina agachamento profundo com arremesso de ombro de forma contínua.'],
            ['nome' => 'Devil Press', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Burpee com halteres. Deite segurando os halteres, faça a flexão, levante explosivamente e faça o snatch dos halteres acima da cabeça de uma só vez. Altamente exigente cardiovascular e muscularmente. Use halteres mais leves que os exercícios isolados.'],
            ['nome' => 'Complexo de Barra', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Sequência de exercícios com barra sem soltar: ex. 6x stiff + 6x remada + 6x clean + 6x agachamento frontal + 6x press. Sem pouso da barra entre os exercícios. Use carga leve — o desafio é metabólico. Excelente para condicionamento e densidade de treino.'],
            ['nome' => 'Remo Ergométrico (Rowing Machine)', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Sente-se na máquina, pés nas alças. Inicie com pernas, depois incline o tronco para trás e puxe o cabo ao abdômen. Retorne em ordem inversa. Trabalha ~86% dos grupos musculares. Baixo impacto e altamente eficiente cardiovascularmente. Mantenha coluna neutra.'],
            ['nome' => 'Agachamento com Peso Overhead', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Barra ou halteres acima da cabeça, braços estendidos. Agache mantendo os braços no lugar. Exige grande mobilidade de tornozelo, quadril e ombro. Trabalha toda a cadeia cinética. Exercício avançado de mobilidade e força simultâneas.'],
            ['nome' => 'Deadlift com Remada (Barbell Complex)', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Levantamento terra seguido imediatamente de remada curvada com a mesma barra antes de retornar ao chão. Combina dois dos maiores recrutadores musculares. Eficiente em tempo e energeticamente desafiador. Use carga adequada ao exercício mais fraco da sequência.'],
            ['nome' => 'Sandbag Clean and Press', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Puxe o saco de areia do chão até os ombros (clean) e pressione acima da cabeça. A instabilidade do saco desafia mais o core e estabilizadores que a barra. Ótimo para força funcional e condicionamento. Frequentemente usado em treinos militares e de bombeiros.'],
            ['nome' => 'Bear Crawl', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'abdomen_core'], 'observacao' => 'De quatro apoios com joelhos levemente elevados do chão (~5cm). Mova braço e perna opostos simultaneamente avançando ou recuando. Core extremamente contraído para não balançar o quadril. Trabalha ombros, core e coordenação. Excelente aquecimento ou exercício de condicionamento.'],
            // CARDIO
            ['nome' => 'Esteira (Corrida)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Inicie caminhando 2-3 minutos para aquecer. Aumente a velocidade gradualmente. Postura ereta, core contraído e pouso do pé sob o quadril. Use os braços em movimento coordenado. Para HIIT: alterne 30s rápido / 60s lento por 8-10 ciclos. Sempre reduza gradualmente ao terminar.'],
            ['nome' => 'Bicicleta Ergométrica', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Ajuste o banco para que o joelho fique levemente dobrado na extensão máxima da pedalada. Postura ereta ou levemente à frente. Para cardio moderado: resistência leve e duração maior. Para HIIT: alta resistência em sprints de 20-30s com 40s de recuperação. Baixo impacto, ideal para quem tem problemas nos joelhos.'],
            ['nome' => 'Pular Corda', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Cotovelos próximos ao corpo, movimentando os pulsos para girar a corda. Pouso suave nas pontas dos pés, joelhos levemente dobrados. Comece com 30s e aumente progressivamente. Excelente para coordenação, agilidade e condicionamento. Pode alternar entre pulos duplos, alternados ou com uma perna.'],
            ['nome' => 'Elíptico', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Postura ereta com core contraído. Use tanto pernas quanto braços para aumentar o gasto calórico. Pedalando para frente: mais quadríceps. Pedalando para trás: mais glúteos e isquiotibiais. Exercício de baixo impacto, excelente para cardio sem sobrecarregar articulações dos joelhos e tornozelos.'],
            ['nome' => 'Jumping Jack', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Comece com pés juntos e braços ao lado do corpo. Salte abrindo as pernas além da largura dos ombros enquanto eleva os braços acima da cabeça. Salte de volta à posição inicial. Mantenha um ritmo constante. Ótimo exercício de aquecimento ou para circuitos de alta intensidade.'],
            ['nome' => 'HIIT Intervalado', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Alterne períodos de esforço máximo (20-40s) com recuperação ativa (10-20s). Exemplos: sprint, mountain climber, burpee, agachamento com salto. Complete 4-8 rounds. Este protocolo maximiza o gasto calórico e melhora o condicionamento cardiorrespiratório em menos tempo que o cardio contínuo tradicional.'],
            ['nome' => 'Caminhada Inclinada na Esteira', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Incline a esteira a 10-15% e caminhe em velocidade moderada (4-6 km/h). Não segure os corrimãos — isso anula o benefício. Trabalha glúteos e panturrilha intensamente com baixo impacto. Excelente alternativa à corrida para pessoas com problema nos joelhos.'],
            ['nome' => 'Sprint na Esteira', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Aqueça 5 minutos. Eleve a velocidade para seu máximo por 20-30 segundos. Recupere em velocidade baixa por 60-90 segundos. Repita 6-10 vezes. Treino intervalado de alta intensidade que melhora o VO2máx e queima gordura de forma eficiente.'],
            ['nome' => 'Remo Ergométrico', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Machine de remo: puxe com pernas primeiro, incline o tronco para trás e finalize com os braços. Retorne em ordem inversa. Cadência constante de 18-24 remadas por minuto para cardio, até 30+ para intervalos. Trabalha 86% dos músculos do corpo com baixo impacto articular.'],
            ['nome' => 'Assault Bike', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Bicicleta com braços que se movem. Pedale e empurre/puxe os manubrios simultaneamente. Quanto mais forte o esforço, maior a resistência. Para HIIT: 10-20s no máximo e 30-40s de recuperação. Máquina de condicionamento extremamente eficaz — mesmo 5-10 minutos são intensos.'],
            ['nome' => 'Step Machine (Escada)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Simula subir escadas. Postura ereta sem apoiar o peso nos corrimãos. Trabalha glúteos, quadríceps e panturrilha enquanto eleva a frequência cardíaca. Ótima opção de baixo impacto para queima calórica. Varie a velocidade e resistência ao longo da sessão.'],
            ['nome' => 'Ski Erg', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Puxe os cabos de cima para baixo em movimento de esqui nórdico. Use braços, core e levemente as pernas. Excelente cardio para membros superiores, complementando máquinas que focam nas pernas. Boa opção para atletas com lesão no joelho.'],
            ['nome' => 'Corrida ao Ar Livre', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Corra em terrenos variados (asfalto, grama, terra). Postura ereta, passada natural sob o quadril. Frequência cardíaca alvo: 60-80% do máximo para corrida contínua. O terreno irregular ativa mais estabilizadores que a esteira. Varie pace, inclinação e distância.'],
            ['nome' => 'Natação', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Exercício de baixíssimo impacto que trabalha todo o corpo. Estilos: crawl (mais intenso), costas (lombar e ombros), peito (pernas e peito), borboleta (mais completo). Ideal para reabilitação ou pessoas com sobrepeso. Combine estilos para variabilidade.'],
            ['nome' => 'Tabata', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Protocolo: 20s de esforço máximo + 10s de descanso, repetido 8 vezes (4 minutos totais). Escolha 1-4 exercícios (burpee, squat jump, mountain climber). Extremamente eficiente para condicionamento. Frequência cardíaca próxima ao máximo. Não é adequado para iniciantes absolutos.'],
            ['nome' => 'Agility Ladder (Escada de Agilidade)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Execute padrões de passos rápidos pela escada no chão: dois pés em cada quadrado, passos laterais, passos alternados, etc. Desenvolve coordenação motora, agilidade e velocidade de pés. Ótimo para atletas de esportes coletivos. Pode ser feito com cones como alternativa.'],
            ['nome' => 'Boxe Aeróbico (Shadow Boxing)', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Soco, ganchos, uppercuts e esquivas em sequência contínua. Use luvas e saco, ou pratique no ar (shadow boxing). Eleva muito a frequência cardíaca trabalhando braços, core e pernas. Combina condicionamento físico com coordenação motora. 3 minutos de round + 1 de descanso.'],
            ['nome' => 'Aqua Aeróbica', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Exercícios aeróbicos realizados na piscina com a água na cintura. A resistência da água aumenta o esforço muscular sem impacto articular. Ideal para idosos, gestantes e pessoas em reabilitação. Queima calórica significativa com mínimo estresse nas articulações.'],
            ['nome' => 'Ciclismo (Spinning)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Aula de ciclismo indoor com intensidade variada. Alterna sprints, subidas (maior resistência) e recuperações. Alta queima calórica e trabalho intenso de quadríceps, glúteos e panturrilha. Posição: banco na altura do quadril, joelho levemente dobrado na pedalada mais baixa.'],
            ['nome' => 'Pulos de Corda Duplo (Double Under)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'A corda passa duas vezes sob os pés em cada pulo. Exige mais altura no salto e giro mais rápido de pulso. Muito mais intenso que o pulo simples. Inicie dominando o pulo simples. Cadência: salto alto + dois giros rápidos de pulso. Comum no CrossFit.'],
            ['nome' => 'Corrida em Escada', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'pernas_gluteos'], 'observacao' => 'Suba as escadas em ritmo rápido e desça caminhando para recuperar. Trabalha intensamente quadríceps e glúteos enquanto eleva muito a frequência cardíaca. Ótima alternativa para quem não tem acesso à academia. Requer cuidado na descida para não cair.'],
            ['nome' => 'High Knees (Joelhos Altos)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Corrida no lugar elevando os joelhos até a altura do quadril em ritmo rápido. Braços em movimento coordenado com as pernas. Eleva rapidamente a frequência cardíaca. Ótimo aquecimento ou exercício de cardio sem equipamento. Pouso suave nas pontas dos pés.'],
        ];
    }
}