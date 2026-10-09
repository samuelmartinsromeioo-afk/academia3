<?php

namespace App\Support;

/**
 * Decide se o host servido é público (internet) ou de desenvolvimento.
 *
 * Existe para a proteção de `APP_DEBUG` não depender de alguém lembrar de
 * acertar o `APP_ENV` no servidor. A trava em `AppServiceProvider` já desligava
 * o debug à força em produção — mas só quando `APP_ENV=production`, e foi
 * exatamente esse o valor que estava errado em produção: o site respondia 404
 * com stack trace completo (`exception`, `file`, `line`, `trace`), expondo o
 * caminho absoluto do servidor. Num 500, a mesma tela mostra as variáveis de
 * ambiente — `DB_PASSWORD`, `ASAAS_*`, `TWILIO_AUTH_TOKEN`, `APP_KEY`.
 *
 * O nome do host é o sinal que ninguém esquece de configurar: ele vem da
 * requisição, não do `.env`.
 *
 * Importante: desligar o debug **não** atrapalha investigação. `APP_DEBUG`
 * controla só o que o VISITANTE vê; o stack trace completo continua indo para
 * `storage/logs/laravel.log` de qualquer forma. Por isso dá para travar sem
 * escape: não se perde nada.
 */
class Ambiente
{
    /** Hosts de desenvolvimento conhecidos (exatos). */
    private const LOCAIS = ['localhost', '127.0.0.1', '::1', 'host.docker.internal'];

    /** Sufixos de domínio usados em desenvolvimento. */
    private const SUFIXOS_LOCAIS = ['.test', '.localhost', '.local', '.invalid', '.example'];

    /**
     * O host é alcançável pela internet?
     *
     * `false` para localhost, domínios de desenvolvimento e **IP de rede
     * privada** — este último é o que mantém o desenvolvimento do app mobile
     * funcionando: o celular acessa o backend por um IP de LAN
     * (ex.: 192.168.100.7), e ali o debug precisa continuar ligado.
     */
    public static function hostEhPublico(?string $host): bool
    {
        $host = strtolower(trim((string) $host));

        if ($host === '') {
            return false; // sem host (CLI, fila): não há visitante para vazar nada
        }

        // Remove porta e colchetes de IPv6 ("[::1]:8000" → "::1").
        $host = preg_replace('/^\[(.+)\](?::\d+)?$/', '$1', $host);
        $host = preg_replace('/:\d+$/', '', $host);

        if (in_array($host, self::LOCAIS, true)) {
            return false;
        }

        foreach (self::SUFIXOS_LOCAIS as $sufixo) {
            if (str_ends_with($host, $sufixo)) {
                return false;
            }
        }

        /*
         * IP: público só fora das faixas privadas/reservadas. É o filtro nativo
         * do PHP, e não uma lista de prefixos escrita à mão — ela erraria em
         * 172.16/12 (que vai só até 172.31) e em 100.64/10 (CGNAT).
         */
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        // Nome sem ponto ("web", "app") é hostname de container/rede interna.
        return str_contains($host, '.');
    }
}
