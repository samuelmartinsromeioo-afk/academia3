<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Cadastro\SelecaoController;
use App\Http\Controllers\Cadastro\PersonalController;
use App\Http\Controllers\Cadastro\AcademiaController;
use App\Http\Controllers\Cadastro\ClienteController;
use App\Http\Controllers\App\MapaController;
use App\Http\Controllers\App\FotoController;
use App\Models\cadastro\Plano;
use App\Http\Controllers\App\AgendaController;
use App\Models\Agenda;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
//tela inicial
Route::get('/', [LoginController::class, 'index'])->name('login.index');
//tela login
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::get('/login',[LoginController::class,'create'])->name('login.create');
Route::get('/mapa', [MapaController::class, 'index'])->name('mapa.index');
Route::get('/mapa/dados', [MapaController::class, 'dados'])->name('mapa.dados');
Route::post('/academia/planos', [AcademiaController::class, 'storePlano'])->name('academia.planos.store');
Route::put('/academia/planos/{id}', [AcademiaController::class, 'updatePlano'])->name('academia.planos.update');
Route::delete('/academia/planos/{id}', [AcademiaController::class, 'destroyPlano'])->name('academia.planos.destroy');
Route::post('/personal/fotos', [FotoController::class, 'storePersonal'])->name('personal.fotos.store');
Route::post('/academia/fotos', [FotoController::class, 'storeAcademia'])->name('academia.fotos.store');
Route::delete('/fotos/{id}', [FotoController::class, 'destroy'])->name('fotos.destroy');




// 1. Tela com os 3 botões de escolha
Route::get('/cadastro/selecionar', [SelecaoController::class, 'index'])->name('cadastro.SelecaoCadastro');
// 2. Rota que processa a escolha e redireciona

Route::get('/cadastro/ir-cadastro/{tipo}', [SelecaoController::class, 'redirecionar'])->name('cadastro.ir');





//rotas para direcionar ao cadastro do aluno
Route::get('/cadastro/cliente', [ClienteController::class, 'create'])->name('form.cliente');
Route::post('/cadastro/cliente', [ClienteController::class, 'store'])->name('cliente.store');

//rotas para direcionar ao cadastro do personal
Route::get('/cadastro/personal', [PersonalController::class, 'create'])->name('form.personal');
Route::post('/cadastro/personal', [PersonalController::class, 'store'])->name('personal.store');

//rotas para direcionar ao cadastro da academia
Route::get('/cadastro/academia', [AcademiaController::class, 'create'])->name('form.academia');
Route::post('/cadastro/academia', [AcademiaController::class, 'store'])->name('academia.store');

//rotas para direcionar para a tela do aluno 
Route::get('/cliente', [ClienteController::class, 'index'])->name('cliente.index');
Route::put('/cliente/update/{id}', [ClienteController::class, 'update'])->name('cliente.update');
//rota para que o aluno consiga agendar um horario 
Route::post('/agendar', [ClienteController::class, 'reservarHorario'])->name('agendar.horario');
// Listagem de todas as academias
Route::get('/academias/explorar', [ClienteController::class, 'listarAcademias'])->name('academias.explorar');
// Detalhes de uma academia específica
Route::get('/academias/{id}/detalhes', [ClienteController::class, 'detalhesAcademia'])->name('academias.detalhes');
// Ação de contratar (vincular)
Route::post('/academias/contratar', [ClienteController::class, 'contratarAcademia'])->name('academias.contratar');
// Rota para processar a contratação
Route::post('/academias/contratar', [App\Http\Controllers\Cadastro\ClienteController::class, 'contratarAcademia'])->name('academias.contratar');

//rotas para direcionar para a tela do personal
Route::get('/personal/dashboard', [PersonalController::class, 'index'])->name('personal.dashboard');
Route::put('/personal/update/{id}', [PersonalController::class, 'update'])->name('personal.update');
Route::get('/personal/alunos', [PersonalController::class, 'meusAlunos'])->name('personal.alunos');

//rota para deslogar da tela 
Route::post('/logout', [App\Http\Controllers\loginController::class, 'logout'])->name('login.logout');

//rota da agenda 
Route::post('/agenda/store', [PersonalController::class, 'storeHorario'])->name('agenda.store');
Route::put('/personal/update/{id}', [PersonalController::class, 'update'])->name('personal.update');
Route::put('/agenda/cancelar/{id}', [PersonalController::class, 'cancelarAula'])->name('agenda.cancelar');
Route::post('/personal/horario', [PersonalController::class, 'storeHorario'])->name('personal.storeHorario');
Route::get('/personal/agenda/{data}', [PersonalController::class, 'getAgendaDia'])->name('personal.getAgenda');
Route::post('/personal/cancelar-dia', [PersonalController::class, 'cancelarDia'])->name('personal.cancelarDia');
Route::post('/personal/bloquear-fixo', [PersonalController::class, 'bloquearHorarioFixo'])->name('personal.bloquearFixo');

//rota para listar os alunos que o personal possui 
Route::get('/personal/cliente', [PersonalController::class, 'listarAlunos'])->name('personal.alunos');

//rota para a tela academia
Route::get('/academia/dashboard', [AcademiaController::class, 'dashboard'])->name('academia.dashboard');
Route::put('/academia/update/{id}', [AcademiaController::class, 'update'])->name('academia.update');
Route::get('/academia/alunos', [AcademiaController::class, 'listarAlunos'])->name('academia.alunos');
Route::get('/academia/planos', [AcademiaController::class, 'listarPlanos'])->name('academia.planos');
Route::put('/academia/update/{id}', [AcademiaController::class, 'update'])->name('academia.update');
Route::get('/academia/alunos', [AcademiaController::class, 'listarAlunos'])->name('academia.alunos');