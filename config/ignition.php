<?php

/**
 * A05 — Ignition (tela de erro do Laravel).
 *
 * As "runnable solutions" registram as rotas POST /_ignition/execute-solution e
 * /_ignition/update-config, que executam ação no servidor a partir do navegador.
 * Foi essa família de rotas que deu origem ao CVE-2021-3129 (RCE). Aqui elas
 * ficam desligadas: a tela de erro continua útil em dev, mas sem o botão que
 * escreve no projeto.
 */
return [
    'enable_runnable_solutions' => env('IGNITION_ENABLE_RUNNABLE_SOLUTIONS', false),

    // Nunca compartilhar relatório de erro (com trecho de código e env) com serviço externo.
    'enable_share_button' => env('IGNITION_ENABLE_SHARE_BUTTON', false),

    // Abrir arquivo no editor a partir do browser — só faz sentido na máquina do dev.
    'editor' => env('IGNITION_EDITOR', 'phpstorm'),

    'theme' => env('IGNITION_THEME', 'auto'),

    'register_commands' => false,
];
