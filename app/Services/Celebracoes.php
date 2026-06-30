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
            'titulo'      => 'Bem-vindo(a) à SnrFit, ' . ($primeiro ?: 'atleta') . '!',
            'mensagem'    => 'Que bom ter você aqui. Esta é a sua casa de treino: '
                           . 'organize tudo num lugar só e deixe cada conquista virar história. Bora pra cima!',
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
            3   => ['Sequência de 3 dias!',     'Começou com tudo! Três dias seguidos sem falhar — a consistência tá nascendo. Mantém o ritmo!'],
            7   => ['Uma semana inteira!',      '7 dias seguidos! O hábito já pegou e seu corpo sente a diferença. Não para agora!'],
            14  => ['Duas semanas firme!',      '14 dias seguidos! Você virou um guerreiro do treino — isso já é estilo de vida. Respeito!'],
            30  => ['Um mês ÉPICO!',            '30 dias seguidos! Um mês inteiro de dedicação total. Você provou do que é capaz. Sensacional!'],
            60  => ['Dois meses IMPARÁVEIS!',   '60 dias seguidos! Praticamente um herói da disciplina — pouquíssimos chegam aqui. Lenda viva!'],
            100 => ['100 DIAS. VOCÊ É LENDA!',  'Cem dias seguidos de treino. Isso não é mais consistência: é grandeza. Você está no topo!'],
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
            $kg >= 10   => ['Mudança de vida!',            "{$fmt} a menos! Isso é uma transformação e tanto. Você é prova de que dá certo. Parabéns de verdade!"],
            $kg >= 5    => ['Transformação em andamento!', "{$fmt} a menos! Que jornada incrível. Você está mudando de patamar — não desacelera!"],
            $kg >= 3    => ['Evolução de verdade!',        "{$fmt} perdidos! Isso é trabalho sério. Seu corpo está respondendo. Orgulho do seu comprometimento!"],
            $kg >= 1    => ['Tá funcionando!',             "{$fmt} a menos! Seu esforço já aparece na balança. Mantém a constância que os resultados vêm."],
            default     => ['Cada grama conta!',           "{$fmt} a menos desde a última avaliação. É o começo de uma grande mudança. Continua firme!"],
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
            $aumento >= 10  => ['Que máquina!',           "+{$fmtAum} no {$exercicio} (chegou a {$fmtCarga})! Salto absurdo de carga. Força de outro mundo — keep going!"],
            $aumento >= 5   => ['Força em outro nível!',  "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Que progressão monstra. Cada vez mais forte!"],
            $aumento >= 2.5 => ['Evoluindo na carga!',    "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Seu corpo está ficando mais forte. Continua com técnica!"],
            default         => ['Mais forte!',            "+{$fmtAum} no {$exercicio} (agora {$fmtCarga})! Pequenos aumentos, grandes ganhos. Segue subindo!"],
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

    private static function kg(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',') . ' kg';
    }
}
