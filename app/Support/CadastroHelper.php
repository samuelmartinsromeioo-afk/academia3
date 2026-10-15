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
    /**
     * Idade mínima para abrir conta, em anos. Fonte ÚNICA da regra: as quatro
     * portas de cadastro (web e app, aluno e personal), o `max` dos campos de
     * data nos formulários, o aviso na tela e o catálogo que o app baixa em
     * GET /cadastro/opcoes derivam todos daqui.
     *
     * Por que 18 para o aluno: a plataforma é um marketplace que cobra e
     * repassa dinheiro, e menor de 18 não contrata sozinho (CC art. 166 I) —
     * pacote, mensalidade e aula avulsa seriam negócios anuláveis. Além disso,
     * tratar dado de criança/adolescente exige consentimento do responsável
     * (LGPD art. 14), que nenhum dos dois cadastros coleta.
     *
     * Por que 21 para o personal: é regra da plataforma, mais estrita que a do
     * aluno porque ele recebe repasse em conta própria (subconta Asaas, split
     * 90/10) e responde tecnicamente pelo treino de terceiros.
     *
     * Não escreva 18 nem 21 em nenhum outro lugar: a regra em dois números
     * iguais é exatamente como `0.10` se espalhou pela comissão (ver CLAUDE.md,
     * "Revenue Model") e sobreviveu à correção.
     */
    public const IDADE_MINIMA_ALUNO = 18;

    public const IDADE_MINIMA_PROFISSIONAL = 21;

    /**
     * Teto de plausibilidade da data de nascimento, em anos.
     *
     * Existe para o lado de cima: sem ele, um ano digitado errado ("0199",
     * "1089") passa pela idade mínima com folga e grava um nascimento absurdo
     * que depois reaparece como idade negativa/estranha na avaliação física
     * (`AvaliacaoFisicaController` calcula a idade a partir desta coluna).
     */
    public const IDADE_MAXIMA_PLAUSIVEL = 110;

    /**
     * Janela de datas de nascimento aceitas, em Y-m-d.
     *
     * `max` = hoje menos a idade mínima: quem nasceu exatamente nesse dia
     * completa a idade HOJE e entra (a comparação é inclusiva).
     *
     * Serve a validação, o `min`/`max` do <input type="date"> e o catálogo do
     * app — os três lugares que precisam do mesmo corte.
     *
     * @return array{min: string, max: string}
     */
    public static function limitesNascimento(int $idadeMinima): array
    {
        return [
            'min' => now()->subYears(self::IDADE_MAXIMA_PLAUSIVEL)->toDateString(),
            'max' => now()->subYears($idadeMinima)->toDateString(),
        ];
    }

    /**
     * Regras de validação da data de nascimento (coluna `idade`, que guarda uma
     * DATA e não um número de anos — o nome é histórico).
     *
     * Sem `required`/`sometimes` de propósito: a obrigatoriedade é decisão de
     * cada porta, o corte de idade não é.
     *
     * @return array<int, string>
     */
    public static function regraNascimento(int $idadeMinima): array
    {
        $limites = self::limitesNascimento($idadeMinima);

        return [
            'date',
            'before_or_equal:' . $limites['max'],
            'after_or_equal:' . $limites['min'],
        ];
    }

    /**
     * Mensagens das regras acima.
     *
     * A padrão do Laravel para `before_or_equal` seria "deve ser uma data
     * anterior ou igual a 2008-10-10": correta e inútil para quem está
     * preenchendo — ninguém relaciona aquela data com a própria idade.
     *
     * @return array<string, string>
     */
    public static function mensagensNascimento(int $idadeMinima, string $campo = 'idade'): array
    {
        return [
            "{$campo}.required"        => 'Informe sua data de nascimento.',
            "{$campo}.date"            => 'Informe uma data de nascimento válida.',
            "{$campo}.before_or_equal" => "É preciso ter no mínimo {$idadeMinima} anos para criar uma conta.",
            "{$campo}.after_or_equal"  => 'Confira a data de nascimento: o ano informado não parece válido.',
        ];
    }

    /**
     * Regra de validação do `sexo` do aluno, a partir de config('textos').
     *
     * Aceita maiúscula e minúscula porque o cadastro grava `strtolower` e a
     * base já tem as duas formas; o que não pode é a LISTA ser uma segunda
     * cópia — foi assim que o <select> do web passou a mandar um valor que a
     * regra recusava.
     */
    public static function regraSexo(): string
    {
        $sexos = (array) config('textos.profissional.sexos', []);

        return 'in:' . implode(',', array_merge($sexos, array_map('mb_strtolower', $sexos)));
    }

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

    /**
     * Valida o número de registro do nutricionista (CRN).
     *
     * Quem registra o nutricionista é o Conselho Regional (CRN-1 a CRN-11); o
     * CFN é federal e não emite registro individual. O número traz a região
     * (1–11). Aceita variações comuns:
     *   "12345/3", "CRN-3 12345", "CRN3 12345", "3 12345", "12345 CRN 3".
     * Regra: precisa existir um bloco numérico de matrícula (>= 3 dígitos) e
     * uma região válida entre 1 e 11.
     */
    public static function validarCRN(string $crn): bool
    {
        $s = strtoupper(trim($crn));
        // Remove o prefixo CRN e separadores, isolando os números relevantes.
        $s = preg_replace('/\bCRN\b/', ' ', $s);
        // Coleta todos os grupos de dígitos, na ordem.
        preg_match_all('/\d+/', $s, $m);
        $nums = $m[0] ?? [];

        if (count($nums) < 2) {
            return false;
        }

        // A matrícula é o maior bloco (normalmente 4–6 dígitos); a região é um
        // bloco curto de 1–2 dígitos com valor 1–11.
        $matricula = null;
        $regiao    = null;

        foreach ($nums as $n) {
            if (strlen($n) >= 3 && $matricula === null) {
                $matricula = $n;
            }
        }
        foreach ($nums as $n) {
            $val = (int) $n;
            if (strlen($n) <= 2 && $val >= 1 && $val <= 11) {
                $regiao = $val;
                break;
            }
        }

        return $matricula !== null && $regiao !== null;
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
