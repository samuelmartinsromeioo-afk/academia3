<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Cadastro\SelecaoController;
use App\Http\Controllers\Cadastro\PersonalController;
use App\Http\Controllers\Cadastro\AcademiaController;
use App\Http\Controllers\Cadastro\ClienteController;
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


// 1. Tela com os 3 botões de escolha
Route::get('/cadastro/selecionar', [SelecaoController::class, 'index'])->name('cadastro.SelecaoCadastro');
// 2. Rota que processa a escolha e redireciona

Route::get('/cadastro/ir-cadastro/{tipo}', [SelecaoController::class, 'redirecionar'])->name('cadastro.ir');


// 3. As rotas dos formulários específicos 


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

//rotas para direcionar para a tela do personal
Route::get('/personal/dashboard', [PersonalController::class, 'index'])->name('personal.dashboard');
Route::put('/personal/update/{id}', [PersonalController::class, 'update'])->name('personal.update');

//rota para deslogar da tela 
Route::post('/logout', [App\Http\Controllers\loginController::class, 'logout'])->name('logout');

//rota da agenda 
Route::post('/agenda/store', [PersonalController::class, 'storeHorario'])->name('agenda.store');
Route::put('/personal/update/{id}', [PersonalController::class, 'update'])->name('personal.update');
Route::put('/agenda/cancelar/{id}', [PersonalController::class, 'cancelarAula'])->name('agenda.cancelar');
Route::post('/personal/horario', [PersonalController::class, 'storeHorario'])->name('personal.storeHorario');
Route::get('/personal/agenda/{data}', [PersonalController::class, 'getAgendaDia'])->name('personal.getAgenda');

//rota para listar os alunos que o personal possui 
Route::get('/personal/cliente', [PersonalController::class, 'listarAlunos'])->name('personal.alunos');

//rota para a tela academia
Route::get('/academia/dashboard', [AcademiaController::class, 'index'])->name('academia.dashboard');
Route::put('/academia/update/{id}', [AcademiaController::class, 'update'])->name('academia.update');