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
 * Envia a mesma mensagem por três canais: aviso in-app (`notificacoes`), push
 * no aparelho (via `Notificacao::para` → ExpoPushService) e e-mail (via
 * NotificacaoMail).
 *
 * Isto é o CANAL. O texto de cada evento de negócio vive em `AvisoService`,
 * um lugar só — porque o web e o app disparam os mesmos eventos e a cópia
 * duplicada entre eles já nasceu divergente mais de uma vez.
 *
 * ─── O WHATSAPP FOI REMOVIDO (2026-10-19) ───────────────────────────────────
 *
 * Eram quatro canais. O quarto saiu por decisão do titular, e a remoção foi
 * até o fim de propósito: `WhatsAppService` deixou de existir, e os parâmetros
 * `$template`/`$params` — que só existiam para nomear o template aprovado na
 * Meta — saíram das assinaturas em vez de ficarem ignorados. Parâmetro morto
 * numa assinatura pública é convite para alguém religar o canal sem perceber
 * o que isso reintroduz.
 *
 * O que isso reintroduziria: o envio ia para `graph.facebook.com`, ou seja a
 * Meta recebia o TELEFONE e o CONTEÚDO de cada aviso. A Política de
 * Privacidade afirma que a Meta só recebe dado "apenas com o seu
 * consentimento, para medição de anúncios" — frase que era FALSA enquanto este
 * canal existia, porque o aviso transacional não passava por consentimento
 * nenhum. Hoje a frase é verdadeira. Religar o canal volta a torná-la falsa.
 *
 * A coluna `whatsapp` dos cadastros FICA: é dado de contato, para o
 * profissional ligar ou escrever na mão. O que saiu foi a plataforma escrever
 * por conta própria.
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
    public static function conta($conta, string $assunto, string $texto): bool
    {
        $tipo = self::tipoDe($conta);
        if (! $tipo) {
            return false;
        }

        \App\Models\Notificacao::para($tipo, (int) $conta->id, $assunto, $texto);

        return self::enviar(
            $conta->email ?? null,
            $conta->nome ?? self::FALLBACK_NOME[$tipo],
            $assunto,
            $texto
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
     * Notifica um Personal (aviso in-app, push e e-mail).
     *
     * @param  Personal  $personal  Destinatário (usa o email do cadastro).
     * @param  string  $assunto  Assunto do e-mail / título exibido.
     * @param  string  $texto  Mensagem legível.
     * @return bool true se ao menos um canal foi enviado com sucesso.
     */
    public static function personal(Personal $personal, string $assunto, string $texto): bool
    {
        return self::conta($personal, $assunto, $texto);
    }

    /**
     * Notifica um Cliente (aviso in-app, push e e-mail).
     *
     * @param  Cliente  $cliente  Destinatário (usa o email do cadastro).
     * @param  string  $assunto  Assunto do e-mail / título exibido.
     * @param  string  $texto  Mensagem legível.
     * @return bool true se ao menos um canal foi enviado com sucesso.
     */
    public static function cliente(Cliente $cliente, string $assunto, string $texto): bool
    {
        return self::conta($cliente, $assunto, $texto);
    }

    /**
     * Envia o aviso por e-mail, sem gravar aviso in-app nem disparar push.
     *
     * Separado de `conta()` porque há destinatário que NÃO é uma das cinco
     * contas (o e-mail do admin em `config('services.admin.email')`) e porque
     * a exclusão de conta precisa avisar alguém que está deixando de existir —
     * ali o aviso in-app seria apagado pela própria exclusão segundos depois.
     *
     * @return bool true se o e-mail foi entregue.
     */
    public static function enviar(?string $email, string $nome, string $assunto, string $texto): bool
    {
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            Mail::to($email)->send(new NotificacaoMail($assunto, $nome, $texto));
            Log::info('Notificação por e-mail enviada', ['email' => $email, 'assunto' => $assunto]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar notificação por e-mail', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
