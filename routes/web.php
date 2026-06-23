<?php

use Illuminate\Support\Facades\Route;

// Importação dos Controllers
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RecuperarSenhaController;
use App\Http\Controllers\Cadastro\SelecaoController;
use App\Http\Controllers\Cadastro\ClienteController;
use App\Http\Controllers\Cadastro\PersonalController;
use App\Http\Controllers\Cadastro\AcademiaController;
use App\Http\Controllers\Cadastro\StudioController;
use App\Http\Controllers\Cadastro\LojaController;
use App\Http\Controllers\App\MapaController;
use App\Http\Controllers\Cadastro\PacoteController;
use App\Http\Controllers\App\FotoController;
use App\Http\Controllers\Cadastro\FichaTreinoController;
use App\Http\Controllers\Cadastro\MesocicloController;
use App\Http\Controllers\Cadastro\AnamneseController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\AvaliacaoFisicaController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/



// ✅ ROTAS PROTEGIDAS DO ADMIN (com middleware)
Route::middleware('check.admin')->group(function () {
    // Dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/admin/personals/{id}/teste-aprovar', [AdminController::class, 'testeAprovar'])->name('admin.personals.teste-aprovar')->middleware('check.admin');
    // Gerenciar Personals
    Route::get('/admin/personals', [AdminController::class, 'listarPersonals'])->name('admin.personals.lista');
    Route::get('/admin/personals/{id}/detalhes', [AdminController::class, 'verDetalhes'])->name('admin.personals.detalhes');
    Route::post('/admin/personals/{id}/aprovar', [AdminController::class, 'aprovar'])->name('admin.personals.aprovar');
    Route::post('/admin/personals/{id}/rejeitar', [AdminController::class, 'rejeitar'])->name('admin.personals.rejeitar');
    Route::delete('/admin/personals/{id}', [AdminController::class, 'deletar'])->name('admin.personals.deletar');
    Route::post('/admin/personals/{id}/criar-asaas', [AdminController::class, 'criarSubcontaAsaas'])->name('admin.personals.criar-asaas');

    // Gerenciar Studios
    Route::get('/admin/studios', [AdminController::class, 'listarStudios'])->name('admin.studios.lista');
    Route::get('/admin/studios/{id}/detalhes', [AdminController::class, 'verDetalhesStudio'])->name('admin.studios.detalhes');
    Route::post('/admin/studios/{id}/aprovar', [AdminController::class, 'aprovarStudio'])->name('admin.studios.aprovar');
    Route::post('/admin/studios/{id}/rejeitar', [AdminController::class, 'rejeitarStudio'])->name('admin.studios.rejeitar');
    Route::delete('/admin/studios/{id}', [AdminController::class, 'deletarStudio'])->name('admin.studios.deletar');
    Route::post('/admin/studios/{id}/criar-asaas', [AdminController::class, 'criarSubcontaAsaasStudio'])->name('admin.studios.criar-asaas');

    // Gerenciar Lojas de Suplementos
    Route::get('/admin/lojas', [AdminController::class, 'listarLojas'])->name('admin.lojas.lista');
    Route::get('/admin/lojas/{id}/detalhes', [AdminController::class, 'verDetalhesLoja'])->name('admin.lojas.detalhes');
    Route::post('/admin/lojas/{id}/bloquear', [AdminController::class, 'bloquearLoja'])->name('admin.lojas.bloquear');
    Route::post('/admin/lojas/{id}/reativar', [AdminController::class, 'reativarLoja'])->name('admin.lojas.reativar');
    Route::delete('/admin/lojas/{id}', [AdminController::class, 'deletarLoja'])->name('admin.lojas.deletar');

    // Relatórios Financeiros
    Route::get('/admin/relatorio-financeiro', [AdminController::class, 'relatorioFinanceiro'])->name('admin.relatorio-financeiro');
 
    // Logout
    Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
});

// ==========================================
// AUTENTICAÇÃO E LOGIN
// ==========================================
Route::get('/', [LoginController::class, 'index'])->name('login.index');
Route::get('/login', [LoginController::class,'create'])->name('login.create');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('login.logout');

// ==========================================
// PÁGINAS PÚBLICAS - TERMOS E PRIVACIDADE
// ==========================================
Route::get('/termos', function () {
    return view('termos');
})->name('termos');

Route::get('/privacidade', function () {
    return view('privacidade');
})->name('privacidade');

// ==========================================
// RECUPERAÇÃO DE SENHA
// ==========================================
Route::get('/recuperar-senha', [RecuperarSenhaController::class, 'showSolicitarForm'])->name('senha.solicitar.form');
Route::post('/recuperar-senha', [RecuperarSenhaController::class, 'enviarLink'])->name('senha.solicitar');
Route::get('/nova-senha', [RecuperarSenhaController::class, 'showResetarForm'])->name('senha.resetar.form');
Route::post('/nova-senha', [RecuperarSenhaController::class, 'resetar'])->name('senha.resetar');


// ==========================================
// SELEÇÃO E CADASTROS
// ==========================================
Route::get('/cadastro/selecionar', [SelecaoController::class, 'index'])->name('cadastro.SelecaoCadastro');
Route::get('/cadastro/ir-cadastro/{tipo}', [SelecaoController::class, 'redirecionar'])->name('cadastro.ir');

// Cadastro - Cliente
Route::get('/cadastro/cliente', [ClienteController::class, 'create'])->name('form.cliente');
Route::post('/cadastro/cliente', [ClienteController::class, 'store'])->name('cliente.store');

// Cadastro - Personal
Route::get('/cadastro/personal', [PersonalController::class, 'create'])->name('form.personal');
Route::post('/cadastro/personal', [PersonalController::class, 'store'])->name('personal.store');

// Cadastro - Academia
Route::get('/cadastro/academia', [AcademiaController::class, 'create'])->name('form.academia');
Route::post('/cadastro/academia', [AcademiaController::class, 'store'])->name('academia.store');

// Cadastro - Studio
Route::get('/cadastro/studio', [StudioController::class, 'create'])->name('form.studio');
Route::post('/cadastro/studio', [StudioController::class, 'store'])->name('studio.store');

// Cadastro - Loja de Suplementos
Route::get('/cadastro/loja', [LojaController::class, 'create'])->name('form.loja');
Route::post('/cadastro/loja', [LojaController::class, 'store'])->name('loja.store');


// ==========================================
// RECURSOS GERAIS (Mapas, Fotos e Avaliações)
// ==========================================
Route::get('/mapa', [MapaController::class, 'index'])->name('mapa.index');
Route::get('/mapa/dados', [MapaController::class, 'dados'])->name('mapa.dados');

Route::post('/personal/fotos', [FotoController::class, 'storePersonal'])->name('personal.fotos.store');
Route::post('/academia/fotos', [FotoController::class, 'storeAcademia'])->name('academia.fotos.store');
Route::post('/studio/fotos', [FotoController::class, 'storeStudio'])->name('studio.fotos.store');
Route::delete('/fotos/{id}', [FotoController::class, 'destroy'])->name('fotos.destroy');

Route::post('/avaliar', [AvaliacaoController::class, 'store'])->name('avaliar.store')->middleware('check.login');


// ==========================================
// ÁREA DO CLIENTE (Aluno)
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::get('/horarios-disponiveis/{personalId}/{dia}', [ClienteController::class, 'buscarHorariosDisponiveis'])->name('horarios.disponiveis');
    Route::get('/studio-horarios-disponiveis/{studioId}/{dia}', [ClienteController::class, 'buscarHorariosStudio'])->name('studio.horarios.disponiveis');
    Route::post('/pacotes/contratar', [ClienteController::class, 'contratarPacote'])->name('pacotes.contratar');
    Route::get('/pagamento/sucesso', [PaymentController::class, 'pagarSucesso'])->name('payment.success');
    Route::get('/cliente', [ClienteController::class, 'index'])->name('cliente.index');
    Route::put('/cliente/update/{id}', [ClienteController::class, 'update'])->name('cliente.update');
    // ✅ CORRIGIDO: Rota para agendamento de aula AVULSA do cliente
    Route::post('/agendar', [ClienteController::class, 'reservarHorario'])->name('agendar.horario');
    Route::get('/academias/explorar', [ClienteController::class, 'listarAcademias'])->name('academias.explorar');
    Route::get('/academias/{id}/detalhes', [ClienteController::class, 'detalheAcademia'])->name('academias.detalhes');
    Route::get('/studios/explorar', [ClienteController::class, 'listarStudios'])->name('studios.explorar');
    Route::get('/studios/{id}/detalhes', [ClienteController::class, 'detalheStudio'])->name('studios.detalhes');
    Route::get('/lojas/explorar', [ClienteController::class, 'listarLojas'])->name('lojas.explorar');
    Route::get('/lojas/{id}/detalhes', [ClienteController::class, 'detalheLoja'])->name('lojas.detalhes');
    Route::get('/personais/explorar', [ClienteController::class, 'listarPersonais'])->name('personais.explorar');
    Route::post('/academias/contratar', [ClienteController::class, 'contratarAcademia'])->name('academias.contratar');
    Route::get('/pacotes/personal/{id}', [PacoteController::class, 'show'])->name('pacotes.show');
    Route::get('/cliente/minhas-fichas-solicitadas', [ClienteController::class, 'minhasSolicitacoesFicha'])->name('cliente.solicitacoes-ficha');
    Route::get('/cliente/avaliacao-fisica', [AvaliacaoFisicaController::class, 'minhaAvaliacao'])->name('cliente.avaliacao-fisica');
});

// ==========================================
// ÁREA DO PERSONAL
// ==========================================
Route::middleware('check.login')->group(function () {
    // Dashboard
    Route::get('/personal/dashboard', [PersonalController::class, 'index'])->name('personal.dashboard');
    Route::put('/personal/update/{id}', [PersonalController::class, 'update'])->name('personal.update');

    // Configurar pacotes/preços
    Route::get('/personal/configurar-precos/{id}', [PersonalController::class, 'configurarPrecos'])->name('personal.configurarPrecos');
    Route::post('/personal/store-precos', [PersonalController::class, 'storePrecos'])->name('personal.storePrecos');

    // Gestão de Alunos do Personal
    Route::get('/personal/alunos', [PersonalController::class, 'meusAlunos'])->name('personal.alunos');
    Route::get('/personal/clientes', [PersonalController::class, 'listarAlunos'])->name('personal.clientes.listar');

    // ✅ GESTÃO DE AGENDA DO PERSONAL
    Route::post('/agenda/store', [PersonalController::class, 'storeHorario'])->name('agenda.store');
    Route::put('/agenda/cancelar/{id}', [PersonalController::class, 'cancelarAula'])->name('agenda.cancelar');
    Route::post('/personal/horario', [PersonalController::class, 'storeHorario'])->name('personal.storeHorario');
    Route::get('/personal/agenda/{data}', [PersonalController::class, 'getAgendaDia'])->name('personal.getAgenda');
    Route::post('/personal/cancelar-dia', [PersonalController::class, 'cancelarDia'])->name('personal.cancelarDia');
    Route::post('/personal/bloquear-fixo', [PersonalController::class, 'bloquearHorarioFixo'])->name('personal.bloquearFixo');
    Route::get('/cliente/{id}/detalhes', [PersonalController::class, 'detalhesAluno'])->name('cliente.detalhes');

    // ✅ NOVO: Finalizar aula e notificar via WhatsApp
    Route::post('/personal/aulas/{id}/finalizar', [PersonalController::class, 'finalizarAula'])->name('aulas.finalizar');
    Route::post('/personal/aulas/{id}/concluir', [PersonalController::class, 'finalizarAula'])->name('aulas.concluir');

    // Frequência dos Alunos
    Route::get('/personal/frequencia', [PersonalController::class, 'frequencia'])->name('personal.frequencia');
    Route::post('/personal/frequencia/marcar', [PersonalController::class, 'marcarPresenca'])->name('personal.frequencia.marcar');
    Route::delete('/personal/frequencia/registro/{id}', [PersonalController::class, 'removerPresenca'])->name('personal.frequencia.remover');
    Route::get('/personal/frequencia/{clienteId}', [PersonalController::class, 'frequenciaAluno'])->name('personal.frequencia.aluno');

    // Solicitações de Ficha
    Route::get('/personal/solicitacoes-ficha', [PersonalController::class, 'listarSolicitacoesFicha'])->name('personal.solicitacoes-ficha');
    Route::post('/personal/solicitacoes-ficha/{id}/concluir', [PersonalController::class, 'concluirSolicitacaoFicha'])->name('personal.solicitacoes-ficha.concluir');
    Route::post('/personal/configurar-valor-ficha', [PersonalController::class, 'atualizarValorFicha'])->name('personal.valor-ficha');

    // Avaliação Física
    Route::get('/personal/avaliacao-fisica', [AvaliacaoFisicaController::class, 'index'])->name('personal.avaliacao-fisica');
    Route::get('/personal/valores-avaliacoes', [AvaliacaoFisicaController::class, 'valores'])->name('personal.avaliacao-fisica.valores');
    Route::post('/personal/avaliacao-fisica/precos', [AvaliacaoFisicaController::class, 'salvarPrecos'])->name('personal.avaliacao-fisica.precos');
    Route::post('/personal/avaliacao-fisica/pacotes', [AvaliacaoFisicaController::class, 'criarPacote'])->name('personal.avaliacao-fisica.pacotes.store');
    Route::put('/personal/avaliacao-fisica/pacotes/{id}', [AvaliacaoFisicaController::class, 'atualizarPacote'])->name('personal.avaliacao-fisica.pacotes.update');
    Route::delete('/personal/avaliacao-fisica/pacotes/{id}', [AvaliacaoFisicaController::class, 'excluirPacote'])->name('personal.avaliacao-fisica.pacotes.destroy');
    Route::delete('/personal/avaliacao-fisica/registro/{id}', [AvaliacaoFisicaController::class, 'destroy'])->name('personal.avaliacao-fisica.destroy');
    Route::get('/personal/avaliacao-fisica/{clienteId}', [AvaliacaoFisicaController::class, 'show'])->name('personal.avaliacao-fisica.aluno');
    Route::post('/personal/avaliacao-fisica/{clienteId}', [AvaliacaoFisicaController::class, 'store'])->name('personal.avaliacao-fisica.store');
});


// ==========================================
// ÁREA DA ACADEMIA
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::get('/academia/dashboard', [AcademiaController::class, 'dashboard'])->name('academia.dashboard');
    Route::put('/academia/update/{id}', [AcademiaController::class, 'update'])->name('academia.update');
    Route::get('/academia/alunos', [AcademiaController::class, 'listarAlunos'])->name('academia.alunos');

    // Gestão de Planos da Academia
    Route::get('/academia/planos', [AcademiaController::class, 'listarPlanos'])->name('academia.planos');
    Route::post('/academia/planos', [AcademiaController::class, 'storePlano'])->name('academia.planos.store');
    Route::put('/academia/planos/{id}', [AcademiaController::class, 'updatePlano'])->name('academia.planos.update');
    Route::delete('/academia/planos/{id}', [AcademiaController::class, 'destroyPlano'])->name('academia.planos.destroy');

    // Gestão de Filiais da Academia
    Route::get('/academia/filiais', [AcademiaController::class, 'filiais'])->name('academia.filiais');
    Route::post('/academia/filiais', [AcademiaController::class, 'storeFilial'])->name('academia.filiais.store');
    Route::put('/academia/filiais/{id}', [AcademiaController::class, 'updateFilial'])->name('academia.filiais.update');
    Route::delete('/academia/filiais/{id}', [AcademiaController::class, 'destroyFilial'])->name('academia.filiais.destroy');

    // Gestão da Academia: profissionais, aulas e infraestrutura
    Route::get('/academia/gestao', [AcademiaController::class, 'gestao'])->name('academia.gestao');
    Route::post('/academia/infraestrutura', [AcademiaController::class, 'atualizarInfraestrutura'])->name('academia.infraestrutura');
    Route::post('/academia/profissionais', [AcademiaController::class, 'storeProfessor'])->name('academia.professores.store');
    Route::put('/academia/profissionais/{id}', [AcademiaController::class, 'updateProfessor'])->name('academia.professores.update');
    Route::delete('/academia/profissionais/{id}', [AcademiaController::class, 'destroyProfessor'])->name('academia.professores.destroy');
    Route::post('/academia/aulas', [AcademiaController::class, 'storeAula'])->name('academia.aulas.store');
    Route::put('/academia/aulas/{id}', [AcademiaController::class, 'updateAula'])->name('academia.aulas.update');
    Route::delete('/academia/aulas/{id}', [AcademiaController::class, 'destroyAula'])->name('academia.aulas.destroy');

    // Fichas de treino criadas pela academia
    Route::get('/academia/alunos/{clienteId}/fichas', [FichaTreinoController::class, 'fichasDoAlunoAcademia'])->name('academia.aluno-fichas');
    Route::post('/academia/fichas/criar', [FichaTreinoController::class, 'criarFichaAcademia'])->name('academia.fichas.criar');
    Route::post('/academia/fichas/{fichaId}/exercicio', [FichaTreinoController::class, 'adicionarExercicioAcademia'])->name('academia.fichas.exercicio.adicionar');
    Route::put('/academia/fichas/exercicio/{exercicioId}', [FichaTreinoController::class, 'editarExercicioAcademia'])->name('academia.fichas.exercicio.editar');
    Route::delete('/academia/fichas/{fichaId}', [FichaTreinoController::class, 'deletarFichaAcademia'])->name('academia.fichas.deletar');
    Route::delete('/academia/fichas/exercicio/{exercicioId}', [FichaTreinoController::class, 'deletarExercicioAcademia'])->name('academia.fichas.exercicio.deletar');
});


// ==========================================
// ÁREA DO STUDIO
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::get('/studio/dashboard', [StudioController::class, 'dashboard'])->name('studio.dashboard');
    Route::put('/studio/update/{id}', [StudioController::class, 'update'])->name('studio.update');

    // Gestão de Alunos do Studio
    Route::get('/studio/alunos', [StudioController::class, 'listarAlunos'])->name('studio.alunos');
    Route::delete('/studio/alunos/{id}/desvincular', [StudioController::class, 'desvincularAluno'])->name('studio.alunos.desvincular');

    // Gestão de Planos do Studio
    Route::get('/studio/planos', [StudioController::class, 'listarPlanos'])->name('studio.planos');
    Route::post('/studio/planos', [StudioController::class, 'storePlano'])->name('studio.planos.store');
    Route::put('/studio/planos/{id}', [StudioController::class, 'updatePlano'])->name('studio.planos.update');
    Route::delete('/studio/planos/{id}', [StudioController::class, 'destroyPlano'])->name('studio.planos.destroy');

    // Horários de funcionamento e bloqueios de slots
    Route::get('/studio/horarios', [StudioController::class, 'horarios'])->name('studio.horarios');
    Route::post('/studio/horarios', [StudioController::class, 'storeHorario'])->name('studio.horarios.store');
    Route::delete('/studio/horarios/{id}', [StudioController::class, 'destroyHorario'])->name('studio.horarios.destroy');
    Route::post('/studio/aulas', [StudioController::class, 'storeAula'])->name('studio.aulas.store');
    Route::delete('/studio/aulas/{id}', [StudioController::class, 'destroyAula'])->name('studio.aulas.destroy');
    Route::post('/studio/profissionais', [StudioController::class, 'storeProfessor'])->name('studio.professores.store');
    Route::put('/studio/profissionais/{id}', [StudioController::class, 'updateProfessor'])->name('studio.professores.update');
    Route::delete('/studio/profissionais/{id}', [StudioController::class, 'destroyProfessor'])->name('studio.professores.destroy');
    Route::post('/studio/bloquear-slot', [StudioController::class, 'bloquearSlot'])->name('studio.bloquear-slot');
    Route::delete('/studio/bloqueios/{id}', [StudioController::class, 'desbloquearSlot'])->name('studio.desbloquear-slot');

    // Agenda do dia
    Route::get('/studio/agenda/{data}', [StudioController::class, 'getAgendaDia'])->name('studio.getAgenda');
});

// ==========================================
// ÁREA DA LOJA DE SUPLEMENTOS
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::get('/loja/dashboard', [LojaController::class, 'dashboard'])->name('loja.dashboard');
    Route::put('/loja/update/{id}', [LojaController::class, 'update'])->name('loja.update');

    // Gestão de produtos e estoque
    Route::post('/loja/produtos', [LojaController::class, 'storeProduto'])->name('loja.produtos.store');
    Route::put('/loja/produtos/{id}', [LojaController::class, 'updateProduto'])->name('loja.produtos.update');
    Route::put('/loja/produtos/{id}/estoque', [LojaController::class, 'ajustarEstoque'])->name('loja.produtos.estoque');
    Route::delete('/loja/produtos/{id}', [LojaController::class, 'destroyProduto'])->name('loja.produtos.destroy');

    // Pedidos recebidos pela loja
    Route::put('/loja/pedidos/{id}/concluir', [LojaController::class, 'concluirPedido'])->name('loja.pedidos.concluir');
});

// ==========================================
// PACOTES
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::post('/pacotes/salvar', [PacoteController::class, 'store'])->name('pacotes.store');
});

// ==========================================
// FICHAS DE TREINO
// ==========================================

// PERSONAL
Route::middleware('check.login')->group(function () {
    Route::get('/personal/fichas-treino', [FichaTreinoController::class, 'meusAlunos'])->name('fichas-treino.alunos');
    Route::get('/personal/fichas-treino/{clienteId}', [FichaTreinoController::class, 'fichasDoAluno'])->name('fichas-treino.aluno');
    Route::post('/personal/fichas-treino/criar', [FichaTreinoController::class, 'criarFicha'])->name('fichas-treino.criar');
    Route::post('/personal/fichas-treino/{fichaId}/exercicio', [FichaTreinoController::class, 'adicionarExercicio'])->name('fichas-treino.exercicio.adicionar');
    Route::put('/personal/fichas-treino/exercicio/{exercicioId}', [FichaTreinoController::class, 'editarExercicio'])->name('fichas-treino.exercicio.editar');
    Route::put('/personal/fichas-treino/{fichaId}/editar', [FichaTreinoController::class, 'editarFicha'])->name('fichas-treino.editar');
    Route::delete('/personal/fichas-treino/{fichaId}', [FichaTreinoController::class, 'deletarFicha'])->name('fichas-treino.deletar');
    Route::delete('/personal/fichas-treino/exercicio/{exercicioId}', [FichaTreinoController::class, 'deletarExercicio'])->name('fichas-treino.exercicio.deletar');

    // Feature 1 — Evolução de carga (personal vê um aluno)
    Route::get('/personal/evolucao-carga/{clienteId}', [FichaTreinoController::class, 'evolucaoCargaAluno'])->name('evolucao-carga.aluno');

    // Feature 2 — Dashboard de aderência (personal vê todos os alunos)
    Route::get('/personal/aderencia', [FichaTreinoController::class, 'dashboardAderencia'])->name('aderencia.dashboard');

    // Feature 4 — Periodização (personal)
    Route::get('/personal/periodizacao', [MesocicloController::class, 'index'])->name('periodizacao.index');
    Route::get('/personal/periodizacao/aluno/{clienteId}', [MesocicloController::class, 'doAluno'])->name('periodizacao.aluno');
    Route::post('/personal/periodizacao/aluno/{clienteId}', [MesocicloController::class, 'criar'])->name('periodizacao.criar');
    Route::put('/personal/periodizacao/treino/{treinoId}', [MesocicloController::class, 'atualizarTreino'])->name('periodizacao.treino.atualizar');
    Route::post('/personal/periodizacao/treino/{treinoId}/exercicio', [MesocicloController::class, 'adicionarExercicio'])->name('periodizacao.exercicio.add');
    Route::delete('/personal/periodizacao/exercicio/{exercicioId}', [MesocicloController::class, 'deletarExercicio'])->name('periodizacao.exercicio.del');
    Route::post('/personal/periodizacao/{mesocicloId}/ativar', [MesocicloController::class, 'ativar'])->name('periodizacao.ativar');
    Route::post('/personal/periodizacao/{mesocicloId}/renovar', [MesocicloController::class, 'renovar'])->name('periodizacao.renovar');
    Route::delete('/personal/periodizacao/{mesocicloId}', [MesocicloController::class, 'deletar'])->name('periodizacao.deletar');

    // Feature 5 — Anamnese (personal visualiza)
    Route::get('/personal/anamnese/{clienteId}', [AnamneseController::class, 'verPersonal'])->name('anamnese.personal');
});

// CLIENTE
Route::middleware('check.login')->group(function () {
    Route::get('/cliente/fichas-treino', [FichaTreinoController::class, 'minhasFichas'])->name('fichas-treino.minhas');
    Route::post('/cliente/fichas-treino/{fichaId}/concluido', [FichaTreinoController::class, 'marcarConcluido'])->name('fichas-treino.concluido');
    Route::post('/cliente/fichas-treino/{fichaId}/nao-concluido', [FichaTreinoController::class, 'desmarcarConcluido'])->name('fichas-treino.nao-concluido');
    Route::get('/api/fichas-treino/{fichaId}/{data}', [FichaTreinoController::class, 'buscarFichaDia'])->name('fichas-treino.api.dia');

    // Feature 1 — Evolução de carga (aluno vê os próprios dados)
    Route::get('/cliente/evolucao-carga', [FichaTreinoController::class, 'evolucaoCarga'])->name('evolucao-carga.minha');
    Route::get('/api/evolucao-carga', [FichaTreinoController::class, 'evolucaoCargaDados'])->name('evolucao-carga.dados');

    // Feature 2 — Meu desempenho (aluno vê os próprios dados)
    Route::get('/cliente/meu-desempenho', [FichaTreinoController::class, 'meuDesempenho'])->name('desempenho.meu');

    // Feature 4 — Treino do dia / periodização (aluno)
    Route::get('/cliente/treino-do-dia', [MesocicloController::class, 'treinoDoDia'])->name('periodizacao.treino-do-dia');
    Route::post('/cliente/treino-do-dia/{mesocicloId}/concluir', [MesocicloController::class, 'concluirDia'])->name('periodizacao.concluir');

    // Feature 5 — Anamnese (aluno preenche)
    Route::get('/cliente/anamnese', [AnamneseController::class, 'form'])->name('anamnese.form');
    Route::post('/cliente/anamnese', [AnamneseController::class, 'salvar'])->name('anamnese.salvar');
});

// ==========================================
// API - AULAS
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::post('/aulas/{id}/fechar', [AulaController::class, 'fecharAula'])->name('aulas.fechar');
    Route::post('/aulas/{id}/concluir', [AulaController::class, 'concluirAula'])->name('aulas.api.concluir');
});
