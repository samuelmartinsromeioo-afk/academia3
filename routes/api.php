<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AsaasWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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
