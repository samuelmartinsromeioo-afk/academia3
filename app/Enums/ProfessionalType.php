<?php

namespace App\Enums;

/**
 * Tipo de profissional cadastrado na tabela `personals`.
 *
 * O SnrFit começou apenas com Personal Trainer; a plataforma foi aberta para
 * outras profissões da saúde/educação física. Modelado como enum (coluna
 * `professional_type`) para permitir novos tipos (fisioterapeuta, etc.) sem
 * refatorar o cadastro. Registros antigos são backfilled para PERSONAL_TRAINER.
 */
enum ProfessionalType: string
{
    case PERSONAL_TRAINER = 'PERSONAL_TRAINER';
    case NUTRITIONIST = 'NUTRITIONIST';

    /** Rótulo curto exibido ao usuário. */
    public function label(): string
    {
        return match ($this) {
            self::PERSONAL_TRAINER => 'Personal Trainer',
            self::NUTRITIONIST => 'Nutricionista',
        };
    }

    /** Conselho profissional correspondente (número de registro individual). */
    public function conselho(): string
    {
        return match ($this) {
            self::PERSONAL_TRAINER => 'CREF',
            self::NUTRITIONIST => 'CRN',
        };
    }

    /** Todos os valores válidos (para regras de validação `in:`). */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }

    public static function tryFromDefault(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::PERSONAL_TRAINER;
    }
}
