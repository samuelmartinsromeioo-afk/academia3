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
    Route::get('/pagamento/status/{asaasPaymentId}', [PaymentController::class, 'verificarStatusPagamento']);
    Route::get('/payments/history', [PaymentController::class, 'listPaymentHistory']);
    Route::get('/trainer/payouts', [PaymentController::class, 'listPayouts']);
    Route::get('/personal/saldo', [PaymentController::class, 'saldoPersonal']);
    Route::post('/personal/sacar', [PaymentController::class, 'sacarPersonal']);
});

// Webhook Asaas — sem sessão/CSRF; autenticidade verificada pelo IP/token Asaas.
Route::post('/asaas-webhook', [AsaasWebhookController::class, 'handle']);
