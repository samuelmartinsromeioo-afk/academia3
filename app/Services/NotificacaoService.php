<?php

namespace App\Services;

use App\Mail\NotificacaoMail;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Canal unificado de notificações do SnrFit.
 *
 * Envia a mesma mensagem por quatro canais: aviso in-app (`notificacoes`),
 * push no aparelho (via `Notificacao::para` → ExpoPushService), WhatsApp (via
 * WhatsAppService) e e-mail (via NotificacaoMail). O texto legível usado no
 * WhatsApp é reaproveitado como corpo do e-mail, garantindo que o destinatário
 * receba o aviso mesmo fora da janela de 24h do WhatsApp ou quando não houver
 * número cadastrado.
 *
 * Isto é o CANAL. O texto de cada evento de negócio vive em `AvisoService`,
 * um lugar só — porque o web e o app disparam os mesmos eventos e a cópia
 * duplicada entre eles já nasceu divergente mais de uma vez.
 */
class NotificacaoService
{
    /** Nome genérico por papel, para quando a conta não tiver `nome`. */
    private const FALLBACK_NOME = [
        'personal' => 'Personal',
        'cliente' => 'Aluno',
        'academia' => 'Academia',
        'studio' => 'Studio',
        'loja' => 'Loja',
    ];

    /**
     * Notifica QUALQUER uma das cinco contas, detectando o papel pelo model.
     *
     * Existe porque `personal()` e `cliente()` cobriam só dois papéis: academia,
     * studio e loja não tinham como ser avisados de nada — e são justamente
     * quem precisa saber que vendeu um plano ou recebeu um pedido.
     *
     * Tolerante a null de propósito: um aviso é efeito colateral de um fluxo
     * que já terminou (pagamento confirmado, aula cancelada) e não pode
     * derrubá-lo porque a conta de destino foi apagada no meio.
     */
    public static function conta($conta, string $assunto, string $texto, string $template = '', array $params = []): bool
    {
        $tipo = self::tipoDe($conta);
        if (! $tipo) {
            return false;
        }

        \App\Models\Notificacao::para($tipo, (int) $conta->id, $assunto, $texto);

        return self::enviar(
            $conta->whatsapp ?? null,
            $conta->email ?? null,
            $conta->nome ?? self::FALLBACK_NOME[$tipo],
            $assunto,
            $texto,
            $template,
            $params
        );
    }

    /**
     * Papel de uma conta, no mesmo vocabulário de `notificacoes.destinatario_tipo`
     * e de `push_tokens.destinatario_tipo` (igual a `Api\AuthController::userType`).
     */
    public static function tipoDe($conta): ?string
    {
        return match (true) {
            $conta instanceof Personal => 'personal',
            $conta instanceof Cliente => 'cliente',
            $conta instanceof \App\Models\Cadastro\Academia => 'academia',
            $conta instanceof \App\Models\Cadastro\Studio => 'studio',
            $conta instanceof \App\Models\Cadastro\Loja => 'loja',
            default => null,
        };
    }

    /**
     * Notifica um Personal por WhatsApp e e-mail.
     *
     * @param Personal $personal  Destinatário (usa whatsapp e email do cadastro).
     * @param string   $assunto   Assunto do e-mail / título exibido.
     * @param string   $texto     Mensagem legível (mesma do WhatsApp).
     * @param string   $template  Nome do template aprovado na Meta (opcional).
     * @param array    $params    Parâmetros do template.
     * @return bool  true se ao menos um canal foi enviado com sucesso.
     */
    public static function personal(Personal $personal, string $assunto, string $texto, string $template = '', array $params = []): bool
    {
        return self::conta($personal, $assunto, $texto, $template, $params);
    }

    /**
     * Notifica um Cliente por WhatsApp e e-mail.
     *
     * @param Cliente $cliente  Destinatário (usa whatsapp e email do cadastro).
     * @param string  $assunto  Assunto do e-mail / título exibido.
     * @param string  $texto    Mensagem legível (mesma do WhatsApp).
     * @param string  $template Nome do template aprovado na Meta (opcional).
     * @param array   $params   Parâmetros do template.
     * @return bool  true se ao menos um canal foi enviado com sucesso.
     */
    public static function cliente(Cliente $cliente, string $assunto, string $texto, string $template = '', array $params = []): bool
    {
        return self::conta($cliente, $assunto, $texto, $template, $params);
    }

    /**
     * Envia uma notificação pelos canais disponíveis (WhatsApp e/ou e-mail).
     *
     * @return bool  true se ao menos um canal foi entregue com sucesso.
     */
    public static function enviar(?string $whatsapp, ?string $email, string $nome, string $assunto, string $texto, string $template = '', array $params = []): bool
    {
        $whatsappOk = false;
        $emailOk    = false;

        if (!empty($whatsapp)) {
            $whatsappOk = WhatsAppService::notificar($whatsapp, $texto, $template, $params);
        }

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($email)->send(new NotificacaoMail($assunto, $nome, $texto));
                $emailOk = true;
                Log::info('Notificação por e-mail enviada', ['email' => $email, 'assunto' => $assunto]);
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar notificação por e-mail', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $whatsappOk || $emailOk;
    }
}
