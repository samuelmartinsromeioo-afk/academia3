<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Saque da receita da plataforma
    |--------------------------------------------------------------------------
    |
    | O webhook de validação de saque do Asaas (AsaasWebhookController::
    | validarSaque) é fail-closed: só aprova transferência que a própria
    | plataforma criou e registrou antes, com `externalReference` conferível.
    | Isso existe para que alguém com a API key em mãos não consiga esvaziar
    | a conta — e funcionou tão bem que bloqueou também o dono dela.
    |
    | Não existe fluxo no sistema para a plataforma sacar a PRÓPRIA receita
    | (os `sacar*` são de personal, academia, studio, loja e indicação). Ela
    | sai pelo painel do Asaas, na mão — uma transferência sem
    | `externalReference`, que o webhook recusava com "Referência externa não
    | reconhecida.".
    |
    | A lista abaixo resolve isso sem desmontar a proteção. O raciocínio:
    | dinheiro que vai para uma chave QUE JÁ ESTAVA CADASTRADA AQUI não é
    | perda — é você recebendo. Quem roubar a API key continua sem conseguir
    | desviar para a conta dele, que é o ataque que importa.
    |
    | O que esta lista NÃO protege: se alguém tiver a API key E acesso a este
    | arquivo/.env, ele pode acrescentar uma chave. O `.env` de produção já é
    | segredo pelos mesmos motivos (ASAAS_API_KEY vive lá).
    |
    | Formato: chaves separadas por vírgula. Pode ser CPF/CNPJ (com ou sem
    | pontuação), e-mail, telefone ou chave aleatória — a comparação
    | normaliza pontuação em chave numérica. Cadastre EXATAMENTE como a chave
    | aparece no painel do Asaas; o log de recusa mostra o destino mascarado
    | justamente para você conferir quando não casar.
    |
    | Vazia = nenhum saque manual é aprovado (o comportamento de antes).
    |
    | Depois de editar: `php artisan config:clear`.
    |
    */

    'saque' => [
        'chaves_pix' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PLATAFORMA_PIX_CHAVES', ''))
        ))),

        /*
         * Teto por transferência. Mesmo indo para a sua chave, um valor fora
         * do padrão é sinal de que algo está errado — e o teto é o limite do
         * estrago de qualquer falha única, como em `indicacao.saque_auto_teto`.
         * Acima dele, recuse e sacar em duas vezes.
         */
        'teto' => (float) env('PLATAFORMA_SAQUE_TETO', 5000.00),
    ],

];
