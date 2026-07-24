<?php

namespace App\Services;

use App\Models\Celebracao;

/**
 * Monta e persiste "celebrações" — notificações em modal tela cheia que
 * comemoram conquistas (primeiro login, sequência de treinos, perda de peso,
 * progressão de carga). Os modais são exibidos pelo partial
 * `partials/celebracao-modal` no próximo carregamento do usuário.
 *
 * Cada construtor devolve (ou null) um array no formato padrão:
 *   ['tipo','titulo','mensagem','icone','cor_medalha','dados_extras']
 */
class Celebracoes
{
    /** Marcos de sequência -> medalha (cor harmônica + ícone Phosphor). */
    public const MEDALHAS = [
        3   => ['cor' => '#CD7F32', 'icone' => 'ph-fire'],       // bronze
        7   => ['cor' => '#B0BEC5', 'icone' => 'ph-lightning'],  // prata
        14  => ['cor' => '#FFD700', 'icone' => 'ph-barbell'],    // ouro
        30  => ['cor' => '#FF8C00', 'icone' => 'ph-medal'],      // laranja épico
        60  => ['cor' => '#FF4D6D', 'icone' => 'ph-trophy'],     // rosa heroico
        100 => ['cor' => '#B14CFF', 'icone' => 'ph-crown'],      // roxo lendário
    ];

    /** Persiste uma notificação na fila do usuário. */
    public static function push(string $papel, int $usuarioId, ?array $notif): void
    {
        if (! $notif) {
            return;
        }

        Celebracao::create([
            'papel'       => $papel,
            'usuario_id'  => $usuarioId,
            'tipo'        => $notif['tipo'],
            'titulo'      => $notif['titulo'],
            'mensagem'    => $notif['mensagem'],
            'icone'       => $notif['icone'] ?? null,
            'cor_medalha' => $notif['cor_medalha'] ?? null,
            'dados_extras'=> $notif['dados_extras'] ?? null,
        ]);
    }

    /** Boas-vindas no primeiro login. */
    public static function primeiroLogin(string $nome): array
    {
        $primeiro = trim(explode(' ', trim($nome))[0] ?? '');

        return [
            'tipo'        => 'primeiro_login',
            'titulo'      => 'Agora você é da família, ' . ($primeiro ?: 'guerreiro') . '!',
            'mensagem'    => 'Seja muito bem-vindo(a) à SNR FIT. A partir de hoje você não treina '
                           . 'mais sozinho(a): aqui a gente cresce junto, comemora cada vitória e não '
                           . 'solta a mão de ninguém. Sua história começa agora — e a gente vai estar do seu lado em cada passo.',
            'icone'       => 'ph-hand-waving',
            'cor_medalha' => '#d4ff00',
            'dados_extras'=> ['nome' => $nome],
        ];
    }

    /**
     * Sequência de dias: só dispara quando a sequência ATUAL bate exatamente um marco.
     * Mensagens diferentes por nível de progressão.
     */
    public static function sequenciaDias(int $atual): ?array
    {
        if (! isset(self::MEDALHAS[$atual])) {
            return null;
        }

        $msgs = [
            3   => ['3 dias e você não parou',      'Três dias seguidos! Você começou de verdade — e a gente tá aqui vendo cada passo. A família SNR treina junto com você. Segue firme!'],
            7   => ['Uma semana como um dos nossos', '7 dias sem falhar! Isso é ritmo de quem veste a camisa. Orgulho de ter você no nosso time — bora manter essa chama acesa!'],
            14  => ['Duas semanas, e agora é raiz',  '14 dias seguidos! Você já é parte da nossa história. Isso deixou de ser esforço e virou identidade. A gente caminha com você.'],
            30  => ['Um mês de história com a gente', '30 dias juntos! Um mês inteiro de dedicação lado a lado. Você mostrou do que é feito e a família SNR tem muito orgulho de você. Que venham os próximos!'],
            60  => ['60 dias — você é referência aqui', '60 dias seguidos! Pouquíssimos chegam até aqui. Você virou inspiração pra galera da SNR. Isso é grandeza — e a gente celebra com você!'],
            100 => ['100 dias. Você é lenda da SNR',  'Cem dias seguidos! Você não é mais só constante: é lenda viva da nossa família. Sua jornada motiva todo mundo aqui. Obrigado por caminhar com a gente até aqui.'],
        ];

        [$titulo, $mensagem] = $msgs[$atual];

        return [
            'tipo'        => 'sequencia_dias',
            'titulo'      => $titulo,
            'mensagem'    => $mensagem,
            'icone'       => self::MEDALHAS[$atual]['icone'],
            'cor_medalha' => self::MEDALHAS[$atual]['cor'],
            'dados_extras'=> ['sequencia_atual' => $atual],
        ];
    }

    /** Perda de peso (vs. avaliação anterior). Só >= 0,5 kg. Mensagem por faixa. */
    public static function perdaPeso(float $kg): ?array
    {
        $kg = round($kg, 1);
        if ($kg < 0.5) {
            return null;
        }

        $fmt = number_format($kg, 1, ',', '.') . ' kg';

        [$titulo, $mensagem] = match (true) {
            $kg >= 10   => ['Uma transformação que é nossa', "{$fmt} a menos! Isso é mudança de vida — e a gente viveu cada etapa com você. Você é a prova viva de que na SNR dá certo. Que orgulho ser parte dessa história!"],
            $kg >= 5    => ['A gente comemora com você',     "{$fmt} a menos! Que jornada linda. Você está mudando de patamar e a família SNR celebra junto cada quilo dessa vitória. Segue com a gente!"],
            $kg >= 3    => ['Seu resultado é orgulho nosso',  "{$fmt} perdidos! Isso é trabalho sério, e a gente tá aqui torcendo por você. Seu corpo tá respondendo — bora continuar juntos!"],
            $kg >= 1    => ['Tá acontecendo, e a gente vê',   "{$fmt} a menos! Seu esforço já aparece na balança. A SNR caminha com você — mantém a constância que a gente segura essa jornada junto."],
            default     => ['Cada grama conta pra gente',    "{$fmt} a menos desde a última avaliação. É o começo de algo grande, e você não está sozinho(a): a família SNR tá com você. Continua firme!"],
        };

        return [
            'tipo'        => 'perda_peso',
            'titulo'      => $titulo,
            'mensagem'    => $mensagem,
            'icone'       => 'ph-trend-down',
            'cor_medalha' => '#00C853',
            'dados_extras'=> ['kg_perdido' => $kg],
        ];
    }

    /** Progressão de carga em um exercício. Mensagem por magnitude do aumento. */
    public static function progressaoCarga(string $exercicio, float $aumento, float $novaCarga): array
    {
        $aumento   = round($aumento, 1);
        $fmtAum    = self::kg($aumento);
        $fmtCarga  = self::kg($novaCarga);

        [$titulo, $mensagem] = match (true) {
            $aumento >= 10  => ['Força de quem é da SNR',   "+{$fmtAum} no {$exercicio} (chegou a {$fmtCarga})! Salto absurdo de carga. Isso é força de outro mundo — e a família toda vibra com você. Bora pra cima juntos!"],
            $aumento >= 5   => ['Cada vez mais forte, juntos', "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Que progressão monstra. Você tá evoluindo e a gente tá aqui do seu lado a cada repetição!"],
            $aumento >= 2.5 => ['A gente vê você evoluir',   "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Seu corpo tá ficando mais forte. Continua com técnica que a família SNR segue com você!"],
            default         => ['Mais forte a cada treino',  "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Pequenos aumentos, grandes ganhos. Passo a passo, a gente cresce junto. Segue subindo!"],
        };

        return [
            'tipo'        => 'progressao_carga',
            'titulo'      => $titulo,
            'mensagem'    => $mensagem,
            'icone'       => 'ph-trend-up',
            'cor_medalha' => '#d4ff00',
            'dados_extras'=> ['exercicio' => $exercicio, 'aumento_kg' => $aumento, 'nova_carga' => $novaCarga],
        ];
    }

    /** Recorde de carga: bateu a maior carga de todos os tempos num exercício. */
    public static function recordeCarga(string $exercicio, float $novaCarga, ?float $recordeAnterior = null): array
    {
        $fmtCarga = self::kg($novaCarga);
        $extra = ($recordeAnterior !== null && $recordeAnterior > 0)
            ? ' — superou seu antigo recorde de ' . self::kg($recordeAnterior)
            : '';

        return [
            'tipo'        => 'recorde_carga',
            'titulo'      => 'NOVO RECORDE seu!',
            'mensagem'    => "Você levantou {$fmtCarga} no {$exercicio}{$extra}. Nunca tinha ido tão longe — "
                           . 'e a gente viu esse recorde nascer. Isso é força de quem é da família SNR. Guarda esse momento!',
            'icone'       => 'ph-trophy',
            'cor_medalha' => '#FFD700',
            'dados_extras'=> ['exercicio' => $exercicio, 'nova_carga' => $novaCarga, 'recorde_anterior' => $recordeAnterior],
        ];
    }

    private static function kg(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',') . ' kg';
    }
}
