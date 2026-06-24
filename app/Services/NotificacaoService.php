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
 * Envia a mesma mensagem por dois canais: WhatsApp (via WhatsAppService) e
 * e-mail (via NotificacaoMail). O texto legível usado no WhatsApp é reaproveitado
 * como corpo do e-mail, garantindo que o destinatário receba o aviso mesmo fora
 * da janela de 24h do WhatsApp ou quando não houver número cadastrado.
 */
class NotificacaoService
{
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
        \App\Models\Notificacao::para('personal', $personal->id, $assunto, $texto);

        return self::enviar(
            $personal->whatsapp ?? null,
            $personal->email ?? null,
            $personal->nome ?? 'Personal',
            $assunto,
            $texto,
            $template,
            $params
        );
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
        \App\Models\Notificacao::para('cliente', $cliente->id, $assunto, $texto);

        return self::enviar(
            $cliente->whatsapp ?? null,
            $cliente->email ?? null,
            $cliente->nome ?? 'Aluno',
            $assunto,
            $texto,
            $template,
            $params
        );
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
