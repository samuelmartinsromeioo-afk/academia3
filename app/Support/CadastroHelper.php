<?php

namespace App\Support;

use App\Models\Cadastro\Personal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Regras compartilhadas entre o cadastro web (Cadastro\*Controller) e o
 * cadastro pela API mobile (Api\RegisterController): validação de CPF e
 * criação da subconta Asaas do personal.
 */
class CadastroHelper
{
    public static function validarCPF(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += $cpf[$i] * (($t + 1) - $i);
            }
            $resto = ($soma * 10) % 11;
            if ($resto === 10 || $resto === 11) $resto = 0;
            if ($resto !== (int) $cpf[$t]) return false;
        }

        return true;
    }

    public static function criarSubcontaAsaasPersonal(Personal $personal): void
    {
        try {
            $payload = [
                'name'          => $personal->nome,
                'email'         => $personal->email,
                'cpfCnpj'       => preg_replace('/\D/', '', $personal->cpf),
                'birthDate'     => $personal->idade,
                'address'       => $personal->rua,
                'addressNumber' => 'S/N',
                'province'      => $personal->bairro,
                'postalCode'    => preg_replace('/\D/', '', $personal->cep),
                'complement'    => $personal->complemento,
                'personType'    => 'FISICA',
                'incomeValue'   => 5000,
            ];

            if ($personal->whatsapp) {
                $payload['mobilePhone'] = preg_replace('/\D/', '', $personal->whatsapp);
            }

            $res = Http::withHeaders([
                'access_token' => config('services.asaas.key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.asaas.url') . '/accounts', $payload);

            $data = $res->json();

            if ($res->successful() && !empty($data['walletId'])) {
                $personal->update([
                    'asaas_account_id' => $data['id']        ?? null,
                    'asaas_wallet_id'  => $data['walletId']  ?? null,
                    'asaas_api_key'    => $data['apiKey']    ?? null,
                ]);
                Log::info('Asaas: subconta criada para personal', [
                    'personal_id' => $personal->id,
                    'wallet_id'   => $data['walletId'],
                ]);
            } else {
                Log::warning('Asaas: subconta não criada', [
                    'personal_id' => $personal->id,
                    'status'      => $res->status(),
                    'response'    => $data,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Asaas: exceção ao criar subconta', [
                'personal_id' => $personal->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
