<?php

namespace App\Http\Controllers;

use App\Models\Cadastro\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;

class StripeConnectController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function iniciarOnboarding(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);

        // Reutiliza conta existente ou cria nova
        if (!$personal->stripe_account_id) {
            $account = Account::create([
                'type'         => 'express',
                'country'      => 'BR',
                'email'        => $personal->email,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'name' => $personal->nome,
                ],
            ]);

            $personal->update(['stripe_account_id' => $account->id]);

            Log::info('Stripe Connect: conta criada', [
                'personal_id' => $personalId,
                'account_id'  => $account->id,
            ]);
        }

        $accountLink = AccountLink::create([
            'account'     => $personal->stripe_account_id,
            'refresh_url' => route('personal.stripe.conectar'),
            'return_url'  => route('personal.stripe.callback'),
            'type'        => 'account_onboarding',
        ]);

        return redirect($accountLink->url);
    }

    public function callbackOnboarding(Request $request)
    {
        $personalId = session('personal_id');
        if (!$personalId) return redirect()->route('login.index');

        $personal = Personal::findOrFail($personalId);

        if (!$personal->stripe_account_id) {
            return redirect()->route('personal.dashboard')
                ->with('error', 'Conta Stripe não encontrada. Tente conectar novamente.');
        }

        $account = Account::retrieve($personal->stripe_account_id);

        $complete = $account->details_submitted && ($account->charges_enabled || $account->payouts_enabled);

        $personal->update(['stripe_onboarding_complete' => $complete]);

        Log::info('Stripe Connect: callback onboarding', [
            'personal_id'      => $personalId,
            'account_id'       => $personal->stripe_account_id,
            'details_submitted' => $account->details_submitted,
            'charges_enabled'  => $account->charges_enabled,
            'complete'         => $complete,
        ]);

        if ($complete) {
            return redirect()->route('personal.dashboard')
                ->with('success', 'Conta Stripe conectada com sucesso! Os repasses serão feitos automaticamente.');
        }

        return redirect()->route('personal.dashboard')
            ->with('error', 'Onboarding incompleto. Por favor, finalize o cadastro na Stripe para receber repasses automáticos.');
    }
}
