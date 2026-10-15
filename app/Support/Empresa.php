<?php

namespace App\Support;

/**
 * Acesso à qualificação do titular da plataforma (`config/empresa.php`).
 *
 * Existe para as views legais não repetirem `config('empresa.x')` nem a lógica
 * de "este dado ainda é um placeholder", e para dar um lugar único onde checar
 * se a identificação exigida pelo Decreto 7.962/2013 está completa.
 */
class Empresa
{
    /**
     * Campos sem os quais a identificação legal está incompleta.
     *
     * `endereco`/`cidade`/`uf`/`cep`/`comarca`/`telefone` saíram daqui porque
     * saíram do config — ver o bloco sobre endereço e telefone em
     * `config/empresa.php`, que registra a decisão e o que ela custa.
     */
    private const OBRIGATORIOS = [
        'razao_social', 'cnpj', 'dpo_nome',
    ];

    public static function get(string $chave): string
    {
        return (string) config("empresa.{$chave}", '');
    }

    /**
     * True quando o valor ainda é a lacuna do config (`[ALGO]`).
     *
     * A convenção do colchete é o contrato: é assim que o documento público
     * mostra o que falta, em vez de esconder o vazio com texto plausível.
     */
    public static function pendente(string $chave): bool
    {
        return (bool) preg_match('/^\s*\[.*\]\s*$/u', self::get($chave));
    }

    /** Os campos obrigatórios que ainda estão por preencher. */
    public static function lacunas(): array
    {
        return array_values(array_filter(self::OBRIGATORIOS, fn ($c) => self::pendente($c)));
    }

    /**
     * A identificação legal está incompleta?
     *
     * Útil para um aviso no painel do admin ou uma trava de deploy: publicar os
     * Termos sem razão social, CNPJ ou nome do Encarregado deixa o documento sem
     * a identificação que o Decreto 7.962/2013 (art. 2º) e a LGPD (art. 41)
     * exigem, e fragiliza toda cláusula de limitação de responsabilidade.
     */
    public static function incompleto(): bool
    {
        return self::lacunas() !== [];
    }
}
