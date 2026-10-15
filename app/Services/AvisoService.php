<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Cadastro\Academia;
use App\Models\Cadastro\Cliente;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Studio;
use Illuminate\Support\Facades\Log;

/**
 * Avisos dos eventos de negócio — um método por evento, o texto num lugar só.
 *
 * `NotificacaoService` é o CANAL (in-app + push + WhatsApp + e-mail). Aqui fica
 * O QUE se diz em cada evento. A separação existe por um motivo concreto: quase
 * todo evento é disparado de DOIS lugares (o controller do site e o da API), e
 * quando o texto vive no controller as duas cópias divergem ou — pior — só uma
 * delas existe. Foi exatamente o que aconteceu com o cancelamento de aula:
 * `Cadastro\PersonalController@cancelarAula` e
 * `Api\PersonalGestaoController@cancelarAula` são cópias quase idênticas, as
 * duas mandavam e-mail e NENHUMA das duas avisava o aluno dentro do app.
 *
 * Três regras para quem for acrescentar um evento aqui:
 *
 * 1. **Nada neste arquivo pode lançar.** Um aviso é efeito colateral de um
 *    fluxo que já se completou (pagamento confirmado, aula apagada). Deixar uma
 *    exceção subir daqui desfaz ou interrompe esse fluxo, e aí o prejuízo é
 *    muito maior que o aviso perdido. Por isso todo método é embrulhado em
 *    try/catch que só registra no log.
 * 2. **Avise os DOIS lados.** Quem vendeu precisa saber que vendeu e quem
 *    comprou precisa da confirmação. Metade do aviso é o que gera o ticket de
 *    suporte ("paguei e ninguém me falou nada").
 * 3. **O texto diz o que fazer, não só o que houve.** "Aula cancelada" sem data,
 *    sem quem cancelou e sem o próximo passo obriga o usuário a abrir o app
 *    para descobrir — e o push aparece justamente quando ele não está no app.
 */
class AvisoService
{
    /** Executa um aviso sem nunca interromper o fluxo que o originou (regra 1). */
    private static function seguro(string $evento, callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning("AvisoService: falha ao avisar [{$evento}]", ['erro' => $e->getMessage()]);
        }
    }

    /** "Seg, 13/10 às 07:00" — data legível a partir de uma agenda. */
    private static function quando($data, ?string $horaInicio): string
    {
        try {
            $d = $data instanceof \Carbon\Carbon ? $data : \Carbon\Carbon::parse((string) $data);
            $texto = $d->format('d/m/Y');
        } catch (\Throwable $e) {
            $texto = (string) $data;
        }

        return $horaInicio ? $texto . ' às ' . substr($horaInicio, 0, 5) : $texto;
    }

    // ───────────────────── PRIVACIDADE (LGPD) ─────────────────────

    /**
     * O titular revogou o consentimento de dados de saúde → confirma por escrito.
     *
     * Disparado dos DOIS lados (site em `Cadastro\LgpdController::revogarSaude`,
     * app em `Api\ProgressoController::revogarAnamnese`), que é exatamente a
     * situação que este arquivo existe para resolver: o texto de um ato
     * irreversível não pode ter duas versões.
     *
     * A confirmação é promessa expressa da cláusula 12 dos Termos, e aqui ela
     * tem função prática além da formal: a revogação apaga a anamnese e
     * **não tem como ser desfeita**, então o titular precisa de um registro do
     * que pediu, de quando pediu e — a parte que mais gera suporte — de que a
     * sua CONTA continua ativa. Sem essa última frase, quem revoga fica em
     * dúvida se perdeu o acesso.
     */
    public static function consentimentoSaudeRevogado(?Cliente $cliente): void
    {
        if (! $cliente) {
            return;
        }

        self::seguro('consentimento_saude_revogado', function () use ($cliente) {
            NotificacaoService::cliente(
                $cliente,
                'Consentimento de dados de saúde revogado — SNR FIT',
                "Você revogou o consentimento de uso dos seus dados de saúde, e a sua "
                . "*anamnese foi apagada*.\n\n"
                . "*A sua conta continua ativa.* Você segue treinando, contratando e usando o "
                . "app e o site normalmente — só não temos mais dado de saúde seu guardado.\n\n"
                . "Pode preencher a anamnese de novo quando quiser. Lembre que, sem ela, o "
                . "profissional monta o seu treino sem conhecer lesões, restrições ou "
                . "medicamentos: informe isso diretamente a ele.\n\n"
                . 'Guarde esta mensagem: ela é o seu comprovante da revogação.'
            );
        });
    }

    // ───────────────────────── AGENDA ─────────────────────────

    /**
     * O personal cancelou UMA aula → avisa o aluno.
     *
     * O aluno é o lado que perde a aula e não tomou a decisão, então é ele que
     * precisa do aviso. Sem isto o cancelamento era silencioso dentro do app: o
     * aluno só descobria pelo e-mail (se visse) ou aparecendo para uma aula que
     * não existe mais.
     *
     * Recebe os dados já extraídos, e não a `Agenda`, porque os dois chamadores
     * **apagam a linha** antes/depois de avisar — passar o model aqui daria
     * acesso a um registro já deletado.
     */
    public static function aulaCanceladaPeloPersonal(
        ?Cliente $cliente,
        ?Personal $personal,
        $data,
        ?string $horaInicio,
        ?string $justificativa
    ): void {
        if (! $cliente) {
            return;
        }

        self::seguro('aula_cancelada_personal', function () use ($cliente, $personal, $data, $horaInicio, $justificativa) {
            $nome = $personal->nome ?? 'Seu personal';
            $quando = self::quando($data, $horaInicio);

            NotificacaoService::cliente(
                $cliente,
                'Sua aula foi cancelada — SnrFit',
                "❌ *Aula cancelada*\n\n"
                . "{$nome} cancelou a sua aula de *{$quando}*.\n"
                . ($justificativa ? "Motivo: {$justificativa}\n" : '')
                . "\nO horário foi liberado na agenda. Fale com o seu personal para remarcar."
            );
        });
    }

    /**
     * O personal cancelou o dia inteiro → avisa cada aluno afetado.
     *
     * Um aviso por aula, não um resumo: cada aluno só tem a ver com a aula
     * dele, e um texto com a lista do dia inteiro vazaria os horários (e a
     * existência) dos outros alunos.
     *
     * @param iterable<Agenda> $agendas aulas canceladas (ainda com cliente_id)
     */
    public static function diaCanceladoPeloPersonal(iterable $agendas, ?Personal $personal, ?string $justificativa = null): void
    {
        foreach ($agendas as $agenda) {
            if (! $agenda->cliente_id) {
                continue; // bloqueio de agenda não tem aluno para avisar
            }

            self::aulaCanceladaPeloPersonal(
                Cliente::find($agenda->cliente_id),
                $personal,
                $agenda->data,
                $agenda->hora_inicio,
                $justificativa
            );
        }
    }

    /**
     * A aula do aluno começa em ~1h30 → lembra o ALUNO.
     *
     * Fecha uma assimetria que durou desde o começo: o personal recebia
     * `personal:lembrete-diario` 90 min antes do primeiro compromisso e o aluno
     * não recebia **nada** — ele é o lado que precisa sair de casa, levar roupa
     * e chegar no horário, e era justamente quem não era lembrado. Falta de
     * aluno é o problema que o módulo de reposição inteiro existe para remediar
     * depois do fato.
     *
     * Único aviso realmente PROATIVO do aluno: não há clique dele nem de
     * ninguém por trás, é a plataforma alcançando quem não está no app — que é
     * o único momento em que o push tem valor de verdade.
     *
     * O texto traz a modalidade porque é o que decide o que a pessoa faz nos
     * próximos minutos: ir até a academia ou abrir o vídeo (ver Agenda::MODALIDADES
     * e os três níveis de modalidade no CLAUDE.md). `null` = não informada, e aí
     * não se afirma nada em vez de chutar "Presencial".
     */
    public static function aulaComecaEmBreve(?Cliente $cliente, ?Personal $personal, Agenda $agenda): void
    {
        if (! $cliente) {
            return;
        }

        self::seguro('aula_comeca_em_breve', function () use ($cliente, $personal, $agenda) {
            $hora = substr((string) $agenda->hora_inicio, 0, 5);
            $nome = $personal->nome ?? 'seu personal';
            $online = $agenda->modalidade === 'Online';

            $local = match (true) {
                $online => "A aula é *online*. Deixe o aparelho pronto e combine o link com {$nome}.",
                $agenda->modalidade === 'Presencial' => "A aula é *presencial*" . ($agenda->academia?->nome ? " na *{$agenda->academia->nome}*" : '') . '. Dá tempo de se arrumar e sair.',
                // Sem modalidade registrada: não inventa. Aulas antigas e
                // profissionais híbridos sem escolha do aluno caem aqui.
                default => "Confirme com {$nome} se é presencial ou online.",
            };

            NotificacaoService::cliente(
                $cliente,
                'Sua aula é daqui a pouco — SnrFit',
                "⏰ *Aula às {$hora}*\n\n"
                . "Você tem aula com {$nome} hoje às *{$hora}*.\n\n"
                . $local
                . "\n\nNão vai conseguir? Avise agora para liberar o horário."
            );
        });
    }

    // ──────────────────────── PAGAMENTOS ────────────────────────

    /**
     * Aluno fechou um pacote → confirma PARA O ALUNO.
     *
     * Só o aluno, de propósito. O personal já é avisado por
     * `Cadastro\ClienteController::notificarPersonalWhatsApp($..., 'pacote', ...)`,
     * que é chamado de dentro de `agendarAulasInterno` — o mesmo método que cria
     * as aulas — e que carrega o template aprovado na Meta
     * (`pacote_contratado_personal`). Avisar o personal daqui também geraria
     * **dois** avisos do mesmo evento; foi o que aconteceu na primeira versão
     * disto, e o teste de integração é que mostrou (esperava 1, achou 2).
     *
     * Quem não tinha nada era o aluno: ele pagava a maior compra da plataforma
     * e não recebia nenhuma confirmação.
     */
    public static function pacoteConfirmadoParaAluno(?Personal $personal, ?Cliente $cliente, ?int $frequencia): void
    {
        if (! $cliente) {
            return;
        }

        self::seguro('pacote_confirmado_aluno', function () use ($personal, $cliente, $frequencia) {
            $freqTxt = $frequencia ? "{$frequencia}x por semana" : 'pacote mensal';

            NotificacaoService::cliente(
                $cliente,
                'Pacote confirmado — SnrFit',
                "✅ *Pacote confirmado!*\n\n"
                . 'Seu pagamento foi confirmado'
                . ($personal ? " e *{$personal->nome}* já foi avisado" : '') . ".\n"
                . "Frequência: {$freqTxt}\n"
                . "\nSuas aulas já estão na sua agenda, em \"Minhas aulas\". 🏋️"
            );
        });
    }

    /** Aluno contratou um plano de academia → avisa a academia e o aluno. */
    public static function academiaContratada(?Academia $academia, ?Cliente $cliente, $valor = null): void
    {
        self::seguro('academia_contratada', function () use ($academia, $cliente, $valor) {
            if ($academia && $cliente) {
                NotificacaoService::conta(
                    $academia,
                    'Novo aluno na sua academia — SnrFit',
                    "🎉 *Novo aluno!*\n\n"
                    . "*{$cliente->nome}* contratou um plano da sua academia pela plataforma.\n"
                    . ($valor ? 'Valor: R$ ' . number_format((float) $valor, 2, ',', '.') . "\n" : '')
                    . "\nEle já aparece na sua lista de alunos."
                );
            }

            if ($cliente) {
                NotificacaoService::cliente(
                    $cliente,
                    'Plano da academia confirmado — SnrFit',
                    "✅ *Plano confirmado!*\n\n"
                    . 'Seu pagamento foi confirmado'
                    . ($academia ? " e a *{$academia->nome}* já foi avisada" : '') . ".\n"
                    . "\nBons treinos! 🏋️"
                );
            }
        });
    }

    /** Aluno assinou um plano de studio → avisa o studio e o aluno. */
    public static function studioPlanoContratado(?Studio $studio, ?Cliente $cliente, $valor = null): void
    {
        self::seguro('studio_plano_contratado', function () use ($studio, $cliente, $valor) {
            if ($studio && $cliente) {
                NotificacaoService::conta(
                    $studio,
                    'Novo aluno no seu studio — SnrFit',
                    "🎉 *Novo aluno!*\n\n"
                    . "*{$cliente->nome}* assinou um plano do seu studio pela plataforma.\n"
                    . ($valor ? 'Valor: R$ ' . number_format((float) $valor, 2, ',', '.') . "\n" : '')
                    . "\nEle já pode reservar lugar nas suas turmas."
                );
            }

            if ($cliente) {
                NotificacaoService::cliente(
                    $cliente,
                    'Plano do studio confirmado — SnrFit',
                    "✅ *Plano confirmado!*\n\n"
                    . 'Seu pagamento foi confirmado'
                    . ($studio ? " e o *{$studio->nome}* já foi avisado" : '') . ".\n"
                    . "\nJá pode reservar sua vaga nas aulas. 🧘"
                );
            }
        });
    }

    /** Aluno comprou uma aula avulsa no studio → avisa o studio. */
    public static function studioAulaContratada(?Studio $studio, ?Cliente $cliente, $data, ?string $horaInicio): void
    {
        self::seguro('studio_aula_contratada', function () use ($studio, $cliente, $data, $horaInicio) {
            if (! $studio || ! $cliente) {
                return;
            }

            NotificacaoService::conta(
                $studio,
                'Nova aula reservada — SnrFit',
                "📅 *Nova reserva!*\n\n"
                . "Aluno: *{$cliente->nome}*\n"
                . 'Quando: ' . self::quando($data, $horaInicio) . "\n"
                . "\nA vaga já foi reservada na sua grade."
            );
        });
    }

    /*
     * Pedido pago na loja: NÃO fica aqui de propósito.
     *
     * `PaymentController::notificarPedidoLoja` já montava um texto melhor do
     * que daria para montar aqui — lista de itens, forma de entrega, contato do
     * cliente — a partir do `Pedido` carregado. O que faltava não era o texto,
     * era o canal: ele ia só por WhatsApp. Lá agora ele passa por
     * `NotificacaoService::conta()`, que cobre in-app, push, WhatsApp e e-mail.
     *
     * Duplicar o texto aqui criaria duas versões do mesmo aviso, que é
     * exatamente o problema que este arquivo existe para resolver.
     */
}
