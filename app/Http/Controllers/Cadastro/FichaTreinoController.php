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
            // BÍCEPS
            ['nome' => 'Rosca Direta com Barra', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Fique em pé, cotovelos fixos próximos ao corpo. Segure a barra com pegada supinada (palmas para cima). Suba até a contração máxima do bíceps e desça de forma controlada. Evite usar o balanço do tronco — o movimento deve ser apenas dos cotovelos para cima.'],
            ['nome' => 'Rosca com Halteres Alternada', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Em pé ou sentado com cotovelos fixos ao lado do corpo. Suba um haltere de cada vez, girando levemente o punho para fora no topo para maximizar a contração do bíceps. Alterne os braços de forma controlada. Mantenha o tronco estável durante todo o movimento.'],
            ['nome' => 'Rosca Martelo', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Segure os halteres com pegada neutra (palmas voltadas para o corpo). Cotovelos fixos ao lado do tronco. Suba os halteres sem virar os punhos. Trabalha bíceps braquial e braquiorradial. Desça de forma lenta e controlada para máxima tensão muscular.'],
            ['nome' => 'Rosca Scott', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Apoie a parte de trás dos braços no suporte inclinado da máquina Scott. Isso isola completamente o bíceps, eliminando o uso do trapézio. Suba de forma controlada e desça lentamente sem estender totalmente os cotovelos. Ideal para pico e definição do bíceps.'],
            ['nome' => 'Rosca Concentrada', 'grupo' => 'Bíceps', 'divisoes' => ['costas_biceps'], 'observacao' => 'Sentado, apoie o cotovelo na parte interna da coxa. Suba o haltere até a contração máxima mantendo o cotovelo fixo. Concentre-se em sentir o bíceps contrair. Desça de forma lenta e completa. Excelente para isolamento e definição do bíceps.'],
            // PEITO
            ['nome' => 'Supino Reto com Barra', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Deite no banco plano, costas bem apoiadas e pés no chão. Segure a barra um pouco mais larga que os ombros. Desça lentamente até tocar levemente o peito na linha dos mamilos, cotovelos a ~45° do corpo. Suba expirando no esforço. Mantenha a curvatura natural da lombar.'],
            ['nome' => 'Supino Inclinado com Halteres', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco a 30-45°. Desça os halteres até os cotovelos ficarem na linha do banco. Empurre para cima e levemente para dentro, não batendo no topo. Foca na parte superior do peitoral. Mantenha as escápulas retraídas e o peito projetado durante o movimento.'],
            ['nome' => 'Crucifixo com Halteres', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Banco plano, braços estendidos acima do peito com cotovelos levemente flexionados. Abra os braços como se fosse abraçar uma grande árvore, descendo até sentir o alongamento no peito. Retorne usando a força do peitoral. Mantenha a mesma curvatura dos cotovelos durante todo o movimento.'],
            ['nome' => 'Peck Deck (Voador)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Sentado com costas apoiadas, posicione cotovelos ou punhos nos suportes na altura do peito. Junte os braços à frente contraindo o peitoral no ponto de maior aproximação. Abra de forma controlada sentindo o alongamento. Mantenha os ombros afastados das orelhas.'],
            ['nome' => 'Flexão de Braço (Push-up)', 'grupo' => 'Peito', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos no chão um pouco mais largas que os ombros, corpo em linha reta. Desça o peito até quase tocar o chão, cotovelos a ~45° do corpo. Empurre de volta contraindo o peitoral. Core contraído e quadril não pode cair. Para aumentar a dificuldade, eleve os pés em um banco.'],
            // OMBRO
            ['nome' => 'Desenvolvimento com Halteres', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Sentado em banco com encosto vertical. Halteres na altura dos ombros, cotovelos a 90°. Empurre para cima até quase estender os braços, sem travar. Desça de forma controlada até a posição inicial. Core contraído e evite arquear a lombar.'],
            ['nome' => 'Elevação Lateral', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Em pé, halteres nas mãos com cotovelos levemente flexionados. Eleve os braços lateralmente até a altura dos ombros (não além, para não sobrecarregar o supraspinoso). Desça de forma lenta e controlada. Use cargas moderadas — esse exercício é muito mais eficaz feito com controle total do que com carga alta.'],
            ['nome' => 'Elevação Frontal', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Segure halteres ou barra com pegada pronada. Braços com cotovelos levemente flexionados. Eleve os braços à frente até a altura dos ombros (90°). Desça de forma controlada. Pode ser feito com ambos os braços ou alternado. Foca no deltóide anterior.'],
            ['nome' => 'Desenvolvimento Arnold', 'grupo' => 'Ombro', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Começa com halteres na frente e palmas voltadas para você (posição de rosca). Ao subir, gire os pulsos para que as palmas fiquem voltadas para frente no topo. Desça revertendo o movimento. Trabalha as três cabeças do deltóide. Exige coordenação e boa mobilidade de ombro.'],
            // TRÍCEPS
            ['nome' => 'Tríceps Corda no Pulley', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'De pé de frente para a polia alta. Cotovelos fixos próximos ao corpo, levemente à frente do tronco. Empurre a corda para baixo até a extensão total, separando as pontas ao final. Retorne de forma controlada sem deixar os cotovelos subirem muito. Foca na cabeça lateral do tríceps.'],
            ['nome' => 'Tríceps Testa', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Deitado no banco com barra W acima do peito, braços estendidos. Mantendo cotovelos apontando para o teto e fixos, desça o peso em direção à testa ou ligeiramente atrás. Estenda os cotovelos de volta à posição inicial. Foca na cabeça longa do tríceps. Use cargas controladas.'],
            ['nome' => 'Rosca Francesa', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps'], 'observacao' => 'Sentado ou em pé com um haltere seguro com as duas mãos acima da cabeça. Cotovelos próximos da cabeça e fixos. Dobre os cotovelos abaixando o haltere atrás da cabeça até sentir o alongamento. Suba estendendo completamente os cotovelos. Excelente para a cabeça longa do tríceps.'],
            ['nome' => 'Mergulho (Dips) no Banco', 'grupo' => 'Tríceps', 'divisoes' => ['peito_ombro_triceps', 'full_body'], 'observacao' => 'Mãos apoiadas no banco atrás do corpo, pernas estendidas à frente. Dobre os cotovelos descendo o quadril até eles chegarem a ~90°. Empurre de volta contraindo o tríceps. Para aumentar a dificuldade, eleve os pés em outro banco. Cotovelos devem apontar para trás, não para os lados.'],
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
            // ABDÔMEN
            ['nome' => 'Abdominal Crunch', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com joelhos dobrados e pés no chão. Mãos atrás da cabeça (sem puxar o pescoço) ou cruzadas no peito. Contraia o abdômen elevando os ombros do chão — a lombar permanece apoiada. Contraia no topo e desça de forma controlada. Expire ao contrair, inspire ao descer.'],
            ['nome' => 'Abdominal Oblíquo', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com joelhos dobrados. Direcione um ombro em direção ao joelho oposto ao contrair. Alterne os lados de forma lenta e controlada. Mãos na nuca sem puxar a cabeça. Sinta a contração na lateral do abdômen (oblíquos) — não é uma rotação do pescoço.'],
            ['nome' => 'Prancha Frontal', 'grupo' => 'Core', 'divisoes' => ['abdomen_core', 'full_body'], 'observacao' => 'Apoie-se nos antebraços e nas pontas dos pés, corpo em linha reta da cabeça aos calcanhares. Contraia abdômen, glúteos e quadríceps. Respiração estável. Evite deixar o quadril cair ou subir. Olhe para o chão para manter a coluna neutra. Comece com 20-30s e aumente progressivamente.'],
            ['nome' => 'Prancha Lateral', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado de lado, apoie-se no antebraço e no lado do pé. Eleve o quadril formando uma linha reta. Contraia os oblíquos para manter a posição. Pode adicionar elevação do quadril para maior intensidade. Troque de lado após o tempo determinado. Foca nos oblíquos e quadrado lombar.'],
            ['nome' => 'Elevação de Pernas', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Suspenso na barra ou apoiado nos suportes de cotovelo. Com pernas estendidas (ou dobradas para iniciantes), eleve-as até a horizontal contraindo o abdômen. Desça de forma controlada sem balançar. Foca no abdômen inferior. Versão avançada: leve os pés até a barra.'],
            ['nome' => 'Russian Twist', 'grupo' => 'Abdômen', 'divisoes' => ['abdomen_core'], 'observacao' => 'Sentado com joelhos dobrados, tronco levemente reclinado e pés levantados. Com mãos juntas ou segurando peso, gire o tronco de um lado ao outro tocando o chão. Abdômen contraído durante todo o exercício. Para maior dificuldade, use um haltere ou anilha.'],
            ['nome' => 'Dead Bug', 'grupo' => 'Core', 'divisoes' => ['abdomen_core'], 'observacao' => 'Deitado com braços estendidos para cima e pernas a 90°. Simultaneamente, estenda o braço direito acima da cabeça e a perna esquerda em direção ao chão, sem tocar. Retorne e repita do outro lado. Lombar colada no chão durante todo o movimento. Excelente para estabilidade do core.'],
            ['nome' => 'Hiperextensão Lombar', 'grupo' => 'Lombar', 'divisoes' => ['abdomen_core'], 'observacao' => 'No banco de hiperextensão, quadris apoiados. Braços cruzados no peito. Desça o tronco e suba até a linha do corpo — não além para não hiperestender. Contraia lombar e glúteos no topo. Fundamental para fortalecer a região lombar e prevenir lesões de coluna.'],
            // CORPO INTEIRO
            ['nome' => 'Burpee', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'cardio'], 'observacao' => 'Em pé, agache tocando as mãos no chão. Jogue os pés para trás ficando em posição de prancha. Faça uma flexão (opcional). Pule os pés de volta próximo às mãos. Salte para cima com braços acima da cabeça. Exercício completo de alta intensidade que combina força e condicionamento cardiovascular.'],
            ['nome' => 'Kettlebell Swing', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Segure o kettlebell com as duas mãos. Incline o tronco à frente com joelhos levemente dobrados (não é um agachamento) e balance para trás entre as pernas. Empurre o quadril à frente explosivamente para balançar até a altura dos ombros. O movimento é do quadril — os braços são apenas guias, não puxam.'],
            ['nome' => 'Levantamento Terra', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body', 'pernas_gluteos'], 'observacao' => 'Barra no chão, pés na largura dos ombros. Agache com coluna neutra e segure a barra com mãos fora das pernas. Suba empurrando o chão com os pés e estendendo o quadril. A barra deve roçar as pernas durante toda a subida. Nunca curve a lombar. É o exercício que recruta mais grupos musculares do corpo.'],
            ['nome' => 'Thruster', 'grupo' => 'Corpo Inteiro', 'divisoes' => ['full_body'], 'observacao' => 'Segure halteres na altura dos ombros. Faça um agachamento completo e ao subir, aproveite o impulso para empurrar os halteres acima da cabeça em um único movimento fluido. Volte à posição inicial trazendo os halteres aos ombros enquanto entra no agachamento. Combina força de perna e empurão de ombro.'],
            // CARDIO
            ['nome' => 'Esteira (Corrida)', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Inicie caminhando 2-3 minutos para aquecer. Aumente a velocidade gradualmente. Postura ereta, core contraído e pouso do pé sob o quadril. Use os braços em movimento coordenado. Para HIIT: alterne 30s rápido / 60s lento por 8-10 ciclos. Sempre reduza gradualmente ao terminar.'],
            ['nome' => 'Bicicleta Ergométrica', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Ajuste o banco para que o joelho fique levemente dobrado na extensão máxima da pedalada. Postura ereta ou levemente à frente. Para cardio moderado: resistência leve e duração maior. Para HIIT: alta resistência em sprints de 20-30s com 40s de recuperação. Baixo impacto, ideal para quem tem problemas nos joelhos.'],
            ['nome' => 'Pular Corda', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Cotovelos próximos ao corpo, movimentando os pulsos para girar a corda. Pouso suave nas pontas dos pés, joelhos levemente dobrados. Comece com 30s e aumente progressivamente. Excelente para coordenação, agilidade e condicionamento. Pode alternar entre pulos duplos, alternados ou com uma perna.'],
            ['nome' => 'Elíptico', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Postura ereta com core contraído. Use tanto pernas quanto braços para aumentar o gasto calórico. Pedalando para frente: mais quadríceps. Pedalando para trás: mais glúteos e isquiotibiais. Exercício de baixo impacto, excelente para cardio sem sobrecarregar articulações dos joelhos e tornozelos.'],
            ['nome' => 'Jumping Jack', 'grupo' => 'Cardio', 'divisoes' => ['cardio', 'full_body'], 'observacao' => 'Comece com pés juntos e braços ao lado do corpo. Salte abrindo as pernas além da largura dos ombros enquanto eleva os braços acima da cabeça. Salte de volta à posição inicial. Mantenha um ritmo constante. Ótimo exercício de aquecimento ou para circuitos de alta intensidade.'],
            ['nome' => 'HIIT Intervalado', 'grupo' => 'Cardio', 'divisoes' => ['cardio'], 'observacao' => 'Alterne períodos de esforço máximo (20-40s) com recuperação ativa (10-20s). Exemplos: sprint, mountain climber, burpee, agachamento com salto. Complete 4-8 rounds. Este protocolo maximiza o gasto calórico e melhora o condicionamento cardiorrespiratório em menos tempo que o cardio contínuo tradicional.'],
        ];
    }
}