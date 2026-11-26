<?php

namespace App\Logs;

use Opcodes\LogViewer\LogLevels\LevelClass;
use Opcodes\LogViewer\LogLevels\LevelInterface;

/**
 * Níveis do error_log do PHP (www.app.log): "Warning", "Notice", "Deprecated",
 * "Fatal error", "Parse error", "Core Warning", "Strict Standards"...
 *
 * Preserva o rótulo original do PHP no display (distingue Deprecated de Notice,
 * Fatal de Parse) e mapeia cada um para a cor certa. Existe porque o LaravelLogLevel
 * embutido casa só os níveis DELE em MAIÚSCULO — os rótulos do PHP caíam todos em
 * "none" (sem cor). Molde igual às classes de nível do pacote.
 */
class PhpErrorLevel implements LevelInterface
{
    /** Rótulo do PHP => severidade (cor) do log-viewer. Rótulo desconhecido => danger. */
    const MAP = [
        'Fatal error' => 'danger',
        'Parse error' => 'danger',
        'Core Error' => 'danger',
        'Compile Error' => 'danger',
        'Warning' => 'warning',
        'Core Warning' => 'warning',
        'Compile Warning' => 'warning',
        'User Warning' => 'warning',
        'Notice' => 'notice',
        'User Notice' => 'notice',
        'Deprecated' => 'notice',
        'User Deprecated' => 'notice',
        'Strict Standards' => 'notice',
    ];

    public string $value;

    public function __construct(?string $value = null)
    {
        $this->value = trim($value ?? '');
    }

    public static function from(?string $value = null): LevelInterface
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return array_keys(static::MAP);
    }

    public function getName(): string
    {
        return $this->value === '' ? 'None' : $this->value;
    }

    public function getClass(): LevelClass
    {
        if ($this->value === '') {
            return LevelClass::none();
        }

        return match (static::MAP[$this->value] ?? 'danger') {
            'warning' => LevelClass::warning(),
            'notice' => LevelClass::notice(),
            'danger' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
