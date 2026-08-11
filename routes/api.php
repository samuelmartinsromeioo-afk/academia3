<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\AvaliacaoFisicaController as ApiAvaliacaoFisicaController;
use App\Http\Controllers\Api\PaymentController as ApiPaymentController;
use App\Http\Controllers\Api\PersonalController as ApiPersonalController;
use App\Http\Controllers\AsaasWebhookController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ═════════════════════════════════════════════
// API MOBILE (v1) — autenticação por token Sanctum
// Documentação: API_DOCS.md na raiz do projeto.
// ═════════════════════════════════════════════
Route::prefix('v1')->group(function () {
    // Públicas
    Route::post('/login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [ApiAuthController::class, 'register'])->middleware('throttle:10,1');

    // Cadastros dos demais papéis (entram como "pendente" até aprovação do admin)
    Route::middleware('throttle:10,1')->prefix('register')->group(function () {
        Route::post('/personal', [\App\Http\Controllers\Api\RegisterController::class, 'personal']);
        Route::post('/academia', [\App\Http\Controllers\Api\RegisterController::class, 'academia']);
        Route::post('/studio', [\App\Http\Controllers\Api\RegisterController::class, 'studio']);
        Route::post('/loja', [\App\Http\Controllers\Api\RegisterController::class, 'loja']);
    });

    // Streaming de vídeo dos exercícios com suporte a Range (206) — pública
    // porque o player (AVPlayer/ExoPlayer) não envia o token Bearer.
    Route::get('/media/exercicio-video/{path}', [\App\Http\Controllers\Api\MediaController::class, 'exercicioVideo'])
        ->where('path', '.*');

    // Protegidas por token
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiAuthController::class, 'me']);

        // Editar o próprio perfil (qualquer papel — detecta pelo token)
        Route::get('/perfil', [\App\Http\Controllers\Api\PerfilController::class, 'show']);
        Route::put('/perfil', [\App\Http\Controllers\Api\PerfilController::class, 'update']);
        Route::post('/perfil/foto', [\App\Http\Controllers\Api\PerfilController::class, 'foto']);

        // Exclusão da própria conta (requisito da App Store — qualquer papel).
        Route::delete('/conta', [\App\Http\Controllers\Api\ContaController::class, 'destroy']);

        // Celebrações / gamificação (qualquer papel — detecta pelo token)
        Route::get('/celebracoes', [\App\Http\Controllers\Api\CelebracaoController::class, 'index']);
        Route::post('/celebracoes/marcar-vistas', [\App\Http\Controllers\Api\CelebracaoController::class, 'marcarVistas']);

        // Dashboard do personal
        Route::prefix('personal')->group(function () {
            Route::get('/profile', [ApiPersonalController::class, 'profile']);
            Route::get('/clientes', [ApiPersonalController::class, 'clientes']);

            // Agenda
            Route::get('/agenda/{data}', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'agendaDia']);
            Route::post('/agenda', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'storeHorario']);
            Route::post('/agenda/cancelar-dia', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'cancelarDia']);
            Route::post('/agenda/bloquear-fixo', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'bloquearFixo']);
            Route::post('/agenda/{id}/cancelar', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'cancelarAula'])->whereNumber('id');
            Route::post('/aulas/{id}/finalizar', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'finalizarAula'])->whereNumber('id');

            // Frequência
            Route::get('/frequencia', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'frequencia']);
            Route::get('/frequencia/{clienteId}', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'frequenciaAluno'])->whereNumber('clienteId');
            Route::post('/frequencia', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'marcarPresenca']);
            Route::delete('/frequencia/{id}', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'removerPresenca'])->whereNumber('id');

            // Acompanhamento dos alunos
            Route::get('/alunos/{clienteId}', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'detalhesAluno'])->whereNumber('clienteId');
            Route::get('/alunos/{clienteId}/anamnese', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'anamneseAluno'])->whereNumber('clienteId');
            Route::get('/alunos/{clienteId}/progresso', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'progressoAluno'])->whereNumber('clienteId');
            Route::post('/alunos/{clienteId}/metas', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'criarMetaAluno'])->whereNumber('clienteId');
            Route::get('/alunos/{clienteId}/evolucao-carga', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'evolucaoCargaAluno'])->whereNumber('clienteId');
            Route::get('/aderencia', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'aderencia']);
            Route::post('/aderencia/cutucar/{clienteId}', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'cutucar'])->whereNumber('clienteId');

            // Preços
            Route::get('/precos', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'precos']);
            Route::post('/precos', [\App\Http\Controllers\Api\PersonalGestaoController::class, 'salvarPrecos']);

            // Templates de ficha
            Route::get('/templates', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'templates']);
            Route::post('/templates', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'criarTemplate']);
            Route::post('/templates/de-ficha/{fichaId}', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'salvarDeFicha'])->whereNumber('fichaId');
            Route::post('/templates/{id}/exercicios', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'adicionarExercicioTemplate'])->whereNumber('id');
            Route::delete('/templates/{id}/exercicios/{index}', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'removerExercicioTemplate'])->whereNumber('id');
            Route::post('/templates/{id}/aplicar', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'aplicarTemplate'])->whereNumber('id');
            Route::delete('/templates/{id}', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'excluirTemplate'])->whereNumber('id');

            // Feed e relatório
            Route::get('/feed', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'feed']);
            Route::get('/relatorio/{clienteId}', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'relatorio'])->whereNumber('clienteId');
            Route::post('/relatorio/{clienteId}/enviar', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'enviarRelatorio'])->whereNumber('clienteId');

            // Vínculo com academias
            Route::get('/academias/buscar', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'buscarAcademias']);
            Route::get('/academias/minhas-solicitacoes', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'minhasSolicitacoes']);
            Route::post('/academias/{academia}/solicitar', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'solicitarVinculo'])->whereNumber('academia');
            Route::delete('/academias/{academia}/cancelar', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'cancelarVinculo'])->whereNumber('academia');

            // Solicitações de ficha
            Route::get('/solicitacoes-ficha', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'solicitacoesFicha']);
            Route::post('/solicitacoes-ficha/{id}/concluir', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'concluirSolicitacaoFicha'])->whereNumber('id');
            Route::post('/valor-ficha', [\App\Http\Controllers\Api\PersonalExtrasController::class, 'atualizarValorFicha']);
        });

        // Painel da academia
        Route::prefix('academia')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\AcademiaController::class, 'dashboard']);
            Route::put('/perfil', [\App\Http\Controllers\Api\AcademiaController::class, 'atualizarPerfil']);
            Route::post('/infraestrutura', [\App\Http\Controllers\Api\AcademiaController::class, 'atualizarInfraestrutura']);

            Route::get('/alunos', [\App\Http\Controllers\Api\AcademiaController::class, 'alunos']);
            Route::post('/alunos', [\App\Http\Controllers\Api\AcademiaController::class, 'criarAluno']);
            Route::get('/alunos/{clienteId}/anamnese', [\App\Http\Controllers\Api\AcademiaController::class, 'anamnese'])->whereNumber('clienteId');
            Route::post('/alunos/{clienteId}/anamnese', [\App\Http\Controllers\Api\AcademiaController::class, 'salvarAnamnese'])->whereNumber('clienteId');

            Route::get('/planos', [\App\Http\Controllers\Api\AcademiaController::class, 'planos']);
            Route::post('/planos', [\App\Http\Controllers\Api\AcademiaController::class, 'criarPlano']);
            Route::put('/planos/{id}', [\App\Http\Controllers\Api\AcademiaController::class, 'atualizarPlano'])->whereNumber('id');
            Route::delete('/planos/{id}', [\App\Http\Controllers\Api\AcademiaController::class, 'excluirPlano'])->whereNumber('id');

            Route::get('/filiais', [\App\Http\Controllers\Api\AcademiaController::class, 'filiais']);
            Route::post('/filiais', [\App\Http\Controllers\Api\AcademiaController::class, 'criarFilial']);
            Route::delete('/filiais/{id}', [\App\Http\Controllers\Api\AcademiaController::class, 'excluirFilial'])->whereNumber('id');

            Route::get('/gestao', [\App\Http\Controllers\Api\AcademiaController::class, 'gestao']);
            Route::post('/professores', [\App\Http\Controllers\Api\AcademiaController::class, 'criarProfessor']);
            Route::delete('/professores/{id}', [\App\Http\Controllers\Api\AcademiaController::class, 'excluirProfessor'])->whereNumber('id');
            Route::post('/aulas', [\App\Http\Controllers\Api\AcademiaController::class, 'criarAula']);
            Route::delete('/aulas/{id}', [\App\Http\Controllers\Api\AcademiaController::class, 'excluirAula'])->whereNumber('id');

            Route::get('/solicitacoes', [\App\Http\Controllers\Api\AcademiaController::class, 'solicitacoes']);
            Route::post('/solicitacoes/{personalId}/{acao}', [\App\Http\Controllers\Api\AcademiaController::class, 'responderSolicitacao'])->whereNumber('personalId');
        });

        // Painel do studio
        Route::prefix('studio')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\StudioController::class, 'dashboard']);
            Route::put('/perfil', [\App\Http\Controllers\Api\StudioController::class, 'atualizarPerfil']);
            Route::get('/alunos', [\App\Http\Controllers\Api\StudioController::class, 'alunos']);
            Route::delete('/alunos/{id}', [\App\Http\Controllers\Api\StudioController::class, 'desvincularAluno'])->whereNumber('id');
            Route::get('/planos', [\App\Http\Controllers\Api\StudioController::class, 'planos']);
            Route::post('/planos', [\App\Http\Controllers\Api\StudioController::class, 'criarPlano']);
            Route::delete('/planos/{id}', [\App\Http\Controllers\Api\StudioController::class, 'excluirPlano'])->whereNumber('id');
            Route::get('/horarios', [\App\Http\Controllers\Api\StudioController::class, 'horarios']);
            Route::post('/horarios', [\App\Http\Controllers\Api\StudioController::class, 'salvarHorario']);
            Route::delete('/horarios/{id}', [\App\Http\Controllers\Api\StudioController::class, 'excluirHorario'])->whereNumber('id');
            Route::post('/professores', [\App\Http\Controllers\Api\StudioController::class, 'criarProfessor']);
            Route::delete('/professores/{id}', [\App\Http\Controllers\Api\StudioController::class, 'excluirProfessor'])->whereNumber('id');
            Route::post('/aulas', [\App\Http\Controllers\Api\StudioController::class, 'criarAula']);
            Route::delete('/aulas/{id}', [\App\Http\Controllers\Api\StudioController::class, 'excluirAula'])->whereNumber('id');
            Route::post('/bloqueios', [\App\Http\Controllers\Api\StudioController::class, 'bloquearSlot']);
            Route::delete('/bloqueios/{id}', [\App\Http\Controllers\Api\StudioController::class, 'desbloquearSlot'])->whereNumber('id');
            Route::get('/agenda/{data}', [\App\Http\Controllers\Api\StudioController::class, 'agendaDia']);
        });

        // Painel da loja
        Route::prefix('loja')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\LojaController::class, 'dashboard']);
            Route::get('/produtos', [\App\Http\Controllers\Api\LojaController::class, 'produtos']);
            Route::post('/produtos', [\App\Http\Controllers\Api\LojaController::class, 'criarProduto']);
            Route::post('/produtos/{id}', [\App\Http\Controllers\Api\LojaController::class, 'atualizarProduto'])->whereNumber('id');
            Route::put('/produtos/{id}/estoque', [\App\Http\Controllers\Api\LojaController::class, 'ajustarEstoque'])->whereNumber('id');
            Route::delete('/produtos/{id}', [\App\Http\Controllers\Api\LojaController::class, 'excluirProduto'])->whereNumber('id');
            Route::get('/pedidos', [\App\Http\Controllers\Api\LojaController::class, 'pedidos']);
            Route::put('/pedidos/{id}/concluir', [\App\Http\Controllers\Api\LojaController::class, 'concluirPedido'])->whereNumber('id');
        });

        // Notificações in-app e chat personal↔aluno
        Route::get('/notificacoes', [\App\Http\Controllers\Api\ChatController::class, 'notificacoes']);
        Route::get('/notificacoes/nao-lidas', [\App\Http\Controllers\Api\ChatController::class, 'naoLidas']);
        Route::post('/notificacoes/marcar-todas', [\App\Http\Controllers\Api\ChatController::class, 'marcarTodas']);
        Route::post('/notificacoes/{id}/lida', [\App\Http\Controllers\Api\ChatController::class, 'marcarLida'])->whereNumber('id');
        Route::get('/chat', [\App\Http\Controllers\Api\ChatController::class, 'conversas']);
        Route::get('/chat/{outroId}', [\App\Http\Controllers\Api\ChatController::class, 'mensagens'])->whereNumber('outroId');
        Route::post('/chat/{outroId}', [\App\Http\Controllers\Api\ChatController::class, 'enviar'])->whereNumber('outroId');

        // Gestão de treinos (personal E academia): fichas + periodização
        Route::prefix('gestao')->group(function () {
            Route::get('/catalogo-exercicios', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'catalogoExercicios']);
            Route::get('/alunos/{clienteId}/fichas', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'fichasDoAluno'])->whereNumber('clienteId');
            Route::get('/alunos/{clienteId}/historico-treinos', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'historicoTreinos'])->whereNumber('clienteId');
            Route::post('/fichas', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'criarFicha']);
            Route::put('/fichas/{fichaId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'editarFicha'])->whereNumber('fichaId');
            Route::delete('/fichas/{fichaId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'deletarFicha'])->whereNumber('fichaId');
            Route::post('/fichas/{fichaId}/exercicios', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'adicionarExercicio'])->whereNumber('fichaId');
            Route::put('/exercicios/{exercicioId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'editarExercicio'])->whereNumber('exercicioId');
            Route::delete('/exercicios/{exercicioId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'deletarExercicio'])->whereNumber('exercicioId');

            Route::get('/alunos/{clienteId}/mesociclos', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'mesociclosDoAluno'])->whereNumber('clienteId');
            Route::post('/alunos/{clienteId}/mesociclos', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'criarMesociclo'])->whereNumber('clienteId');
            Route::put('/mesociclo-treinos/{treinoId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'atualizarTreino'])->whereNumber('treinoId');
            Route::post('/mesociclo-treinos/{treinoId}/exercicios', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'adicionarExercicioMeso'])->whereNumber('treinoId');
            Route::delete('/mesociclo-exercicios/{exercicioId}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'deletarExercicioMeso'])->whereNumber('exercicioId');
            Route::post('/mesociclos/{id}/ativar', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'ativarMesociclo'])->whereNumber('id');
            Route::post('/mesociclos/{id}/renovar', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'renovarMesociclo'])->whereNumber('id');
            Route::delete('/mesociclos/{id}', [\App\Http\Controllers\Api\TreinosGestaoController::class, 'deletarMesociclo'])->whereNumber('id');
        });

        // Fichas de treino (aluno)
        Route::prefix('fichas')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\FichaTreinoController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\FichaTreinoController::class, 'show'])->whereNumber('id');
            Route::post('/{id}/concluir', [\App\Http\Controllers\Api\FichaTreinoController::class, 'concluir'])->whereNumber('id');
            Route::post('/{id}/desmarcar', [\App\Http\Controllers\Api\FichaTreinoController::class, 'desmarcar'])->whereNumber('id');
        });

        // Evolução de carga e desempenho (aluno)
        Route::get('/evolucao-carga', [\App\Http\Controllers\Api\FichaTreinoController::class, 'evolucaoCarga']);
        Route::get('/desempenho', [\App\Http\Controllers\Api\FichaTreinoController::class, 'desempenho']);

        // Treino do dia — periodização/mesociclo (aluno)
        Route::get('/treino-do-dia', [\App\Http\Controllers\Api\MesocicloController::class, 'treinoDoDia']);
        Route::post('/treino-do-dia/{mesocicloId}/concluir', [\App\Http\Controllers\Api\MesocicloController::class, 'concluirDia'])->whereNumber('mesocicloId');

        // Progresso, metas e anamnese (aluno)
        Route::prefix('progresso')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ProgressoController::class, 'index']);
            Route::post('/medidas', [\App\Http\Controllers\Api\ProgressoController::class, 'salvarMedida']);
            Route::delete('/medidas/{id}', [\App\Http\Controllers\Api\ProgressoController::class, 'excluirMedida'])->whereNumber('id');
            Route::post('/fotos', [\App\Http\Controllers\Api\ProgressoController::class, 'uploadFoto']);
            Route::delete('/fotos/{id}', [\App\Http\Controllers\Api\ProgressoController::class, 'excluirFoto'])->whereNumber('id');
        });
        Route::prefix('metas')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ProgressoController::class, 'metas']);
            Route::post('/', [\App\Http\Controllers\Api\ProgressoController::class, 'salvarMeta']);
            Route::post('/{id}/alternar', [\App\Http\Controllers\Api\ProgressoController::class, 'alternarMeta'])->whereNumber('id');
            Route::delete('/{id}', [\App\Http\Controllers\Api\ProgressoController::class, 'excluirMeta'])->whereNumber('id');
        });
        Route::get('/anamnese', [\App\Http\Controllers\Api\ProgressoController::class, 'anamnese']);
        Route::post('/anamnese', [\App\Http\Controllers\Api\ProgressoController::class, 'salvarAnamnese']);

        // Explorar e contratar (aluno)
        Route::prefix('explorar')->group(function () {
            Route::get('/personais', [\App\Http\Controllers\Api\ExplorarController::class, 'personais']);
            Route::get('/academias', [\App\Http\Controllers\Api\ExplorarController::class, 'academias']);
            Route::get('/academias/{id}', [\App\Http\Controllers\Api\ExplorarController::class, 'academiaDetalhe'])->whereNumber('id');
            Route::get('/studios', [\App\Http\Controllers\Api\ExplorarController::class, 'studios']);
            Route::get('/studios/{id}', [\App\Http\Controllers\Api\ExplorarController::class, 'studioDetalhe'])->whereNumber('id');
            Route::get('/lojas', [\App\Http\Controllers\Api\ExplorarController::class, 'lojas']);
            Route::get('/lojas/{id}', [\App\Http\Controllers\Api\ExplorarController::class, 'lojaDetalhe'])->whereNumber('id');
        });
        Route::get('/personais/{id}/pacotes', [\App\Http\Controllers\Api\ExplorarController::class, 'pacotesDoPersonal'])->whereNumber('id');
        Route::get('/personais/{personalId}/horarios/{dia}', [\App\Http\Controllers\Api\ExplorarController::class, 'horariosPersonal'])->whereNumber('personalId');
        Route::get('/studios/{studioId}/horarios/{dia}', [\App\Http\Controllers\Api\ExplorarController::class, 'horariosStudio'])->whereNumber('studioId');
        Route::post('/agendar', [\App\Http\Controllers\Api\ExplorarController::class, 'agendarAulaAvulsa']);
        Route::post('/pacotes/contratar', [\App\Http\Controllers\Api\ExplorarController::class, 'contratarPacote']);
        Route::post('/academias/contratar', [\App\Http\Controllers\Api\ExplorarController::class, 'contratarAcademia']);

        // Minha academia (aluno contratado) e avaliação de serviços
        Route::get('/minha-academia', [\App\Http\Controllers\Api\ExplorarController::class, 'minhaAcademia']);
        // Rate limit extra (A04/A07): evita spam de avaliações além do limite global.
        Route::post('/avaliar', [\App\Http\Controllers\Api\AvaliacaoServicoController::class, 'store'])->middleware('throttle:20,1');

        // Avaliação física
        Route::prefix('avaliacoes')->group(function () {
            Route::get('/', [ApiAvaliacaoFisicaController::class, 'index']);
            Route::post('/', [ApiAvaliacaoFisicaController::class, 'store']);
            Route::get('/{id}', [ApiAvaliacaoFisicaController::class, 'show'])->whereNumber('id');
        });

        // Pagamentos (Asaas)
        Route::prefix('payments')->group(function () {
            Route::get('/', [ApiPaymentController::class, 'index']);
            // Rate limit extra (A04): criação de cobrança é operação sensível.
            Route::post('/', [ApiPaymentController::class, 'store'])->middleware('throttle:20,1');
            Route::get('/{id}', [ApiPaymentController::class, 'show'])->whereNumber('id');
            Route::get('/{id}/pix', [ApiPaymentController::class, 'pix'])->whereNumber('id');
            Route::get('/{id}/status', [ApiPaymentController::class, 'status'])->whereNumber('id');
        });
    });
});

// Atalhos sem o prefixo v1 (POST /api/login etc.) — mesmos controllers.
Route::post('/login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/register', [ApiAuthController::class, 'register'])->middleware('throttle:10,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me', [ApiAuthController::class, 'me']);
});

// Rotas de pagamento — usa middleware web para ter acesso à sessão.
// Chamadas AJAX do Blade devem incluir o header X-CSRF-TOKEN.
Route::middleware(['web'])->group(function () {
    Route::post('/criar-pagamento', [PaymentController::class, 'criarPagamento']);
    Route::post('/criar-pagamento-academia', [PaymentController::class, 'criarPagamentoAcademia']);
    Route::post('/criar-pagamento-cartao', [PaymentController::class, 'criarPagamentoCartao']);
    Route::post('/criar-pagamento-cartao-academia', [PaymentController::class, 'criarPagamentoCartaoAcademia']);
    Route::post('/academia/criar-conta-asaas', [PaymentController::class, 'criarContaAsaasAcademia']);
    Route::get('/academia/saldo', [PaymentController::class, 'saldoAcademia']);
    Route::post('/academia/sacar', [PaymentController::class, 'sacarAcademia']);
    Route::get('/pagamento/status/{asaasPaymentId}', [PaymentController::class, 'verificarStatusPagamento']);
    Route::post('/assinaturas/{id}/cancelar', [PaymentController::class, 'cancelarAssinatura']);
    Route::get('/payments/history', [PaymentController::class, 'listPaymentHistory']);
    Route::get('/trainer/payouts', [PaymentController::class, 'listPayouts']);
    Route::get('/personal/saldo', [PaymentController::class, 'saldoPersonal']);
    Route::post('/personal/sacar', [PaymentController::class, 'sacarPersonal']);
    Route::post('/personal/sacar-subconta', [PaymentController::class, 'sacarSubconta']);
    Route::post('/criar-pagamento-studio-plano', [PaymentController::class, 'criarPagamentoStudioPlano']);
    Route::post('/criar-pagamento-cartao-studio-plano', [PaymentController::class, 'criarPagamentoCartaoStudioPlano']);
    Route::post('/criar-pagamento-aula-studio', [PaymentController::class, 'criarPagamentoAulaStudio']);
    Route::post('/criar-pagamento-cartao-aula-studio', [PaymentController::class, 'criarPagamentoCartaoAulaStudio']);
    Route::get('/studio/saldo', [PaymentController::class, 'saldoStudio']);
    Route::post('/studio/sacar', [PaymentController::class, 'sacarStudio']);
    // Loja de suplementos — checkout de produtos e carteira
    Route::post('/criar-pagamento-loja', [PaymentController::class, 'criarPagamentoLoja']);
    Route::post('/criar-pagamento-cartao-loja', [PaymentController::class, 'criarPagamentoCartaoLoja']);
    Route::post('/loja/criar-conta-asaas', [PaymentController::class, 'criarContaAsaasLoja']);
    Route::get('/loja/saldo', [PaymentController::class, 'saldoLoja']);
    Route::post('/loja/sacar', [PaymentController::class, 'sacarLoja']);
});

// Webhook Asaas — sem sessão/CSRF; autenticidade verificada pelo IP/token Asaas.
Route::post('/asaas-webhook', [AsaasWebhookController::class, 'handle']);
