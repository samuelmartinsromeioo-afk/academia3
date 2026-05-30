<?php

use Illuminate\Support\Facades\Route;

// Importação dos Controllers
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RecuperarSenhaController;
use App\Http\Controllers\Cadastro\SelecaoController;
use App\Http\Controllers\Cadastro\ClienteController;
use App\Http\Controllers\Cadastro\PersonalController;
use App\Http\Controllers\Cadastro\AcademiaController;
use App\Http\Controllers\App\MapaController;
use App\Http\Controllers\Cadastro\PacoteController;
use App\Http\Controllers\App\FotoController;
use App\Http\Controllers\Cadastro\FichaTreinoController;
use App\Http\Controllers\AvaliacaoController;
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


// ==========================================
// RECURSOS GERAIS (Mapas, Fotos e Avaliações)
// ==========================================
Route::get('/mapa', [MapaController::class, 'index'])->name('mapa.index');
Route::get('/mapa/dados', [MapaController::class, 'dados'])->name('mapa.dados');

Route::post('/personal/fotos', [FotoController::class, 'storePersonal'])->name('personal.fotos.store');
Route::post('/academia/fotos', [FotoController::class, 'storeAcademia'])->name('academia.fotos.store');
Route::delete('/fotos/{id}', [FotoController::class, 'destroy'])->name('fotos.destroy');

Route::post('/avaliar', [AvaliacaoController::class, 'store'])->name('avaliar.store')->middleware('check.login');


// ==========================================
// ÁREA DO CLIENTE (Aluno)
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::get('/horarios-disponiveis/{personalId}/{dia}', [ClienteController::class, 'buscarHorariosDisponiveis'])->name('horarios.disponiveis');
    Route::post('/pacotes/contratar', [ClienteController::class, 'contratarPacote'])->name('pacotes.contratar');
    Route::get('/pagamento/sucesso', [PaymentController::class, 'pagarSucesso'])->name('payment.success');
    Route::get('/cliente', [ClienteController::class, 'index'])->name('cliente.index');
    Route::put('/cliente/update/{id}', [ClienteController::class, 'update'])->name('cliente.update');
    // ✅ CORRIGIDO: Rota para agendamento de aula AVULSA do cliente
    Route::post('/agendar', [ClienteController::class, 'reservarHorario'])->name('agendar.horario');
    Route::get('/academias/explorar', [ClienteController::class, 'listarAcademias'])->name('academias.explorar');
    Route::get('/academias/{id}/detalhes', [ClienteController::class, 'detalhesAcademia'])->name('academias.detalhes');
    Route::post('/academias/contratar', [ClienteController::class, 'contratarAcademia'])->name('academias.contratar');
    Route::get('/pacotes/personal/{id}', [PacoteController::class, 'show'])->name('pacotes.show');
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
    Route::put('/personal/fichas-treino/{fichaId}/editar', [FichaTreinoController::class, 'editarFicha'])->name('fichas-treino.editar');
    Route::delete('/personal/fichas-treino/{fichaId}', [FichaTreinoController::class, 'deletarFicha'])->name('fichas-treino.deletar');
    Route::delete('/personal/fichas-treino/exercicio/{exercicioId}', [FichaTreinoController::class, 'deletarExercicio'])->name('fichas-treino.exercicio.deletar');
});

// CLIENTE
Route::middleware('check.login')->group(function () {
    Route::get('/cliente/fichas-treino', [FichaTreinoController::class, 'minhasFichas'])->name('fichas-treino.minhas');
    Route::post('/cliente/fichas-treino/{fichaId}/concluido', [FichaTreinoController::class, 'marcarConcluido'])->name('fichas-treino.concluido');
    Route::post('/cliente/fichas-treino/{fichaId}/nao-concluido', [FichaTreinoController::class, 'desmarcarConcluido'])->name('fichas-treino.nao-concluido');
    Route::get('/api/fichas-treino/{fichaId}/{data}', [FichaTreinoController::class, 'buscarFichaDia'])->name('fichas-treino.api.dia');
});

// ==========================================
// API - AULAS
// ==========================================
Route::middleware('check.login')->group(function () {
    Route::post('/aulas/{id}/fechar', [AulaController::class, 'fecharAula'])->name('aulas.fechar');
    Route::post('/aulas/{id}/concluir', [AulaController::class, 'concluirAula'])->name('aulas.api.concluir');
});
