<?php

namespace App\Logs;

use Opcodes\LogViewer\LogLevels\LevelClass;
use Opcodes\LogViewer\LogLevels\LevelInterface;

/**
 * Níveis do log do Xdebug (xdebug.log): INFO, WARN, ERR — que aparecem nos canais
 * ([Config], [Step Debug], ...). As linhas "Log opened/closed at" não têm nível
 * (ficam sem cor). Classe própria no molde das do pacote.
 */
class XdebugLevel implements LevelInterface
{
    /** Token cru do xdebug.log => severidade interna do log-viewer. */
    const MAP = [
        'ERR' => 'error',
        'WARN' => 'warning',
        'INFO' => 'info',
    ];

    public string $value;

    public function __construct(?string $value = null)
    {
        $value = $value ?? '';
        $this->value = static::MAP[$value] ?? $value;
    }

    public static function from(?string $value = null): LevelInterface
    {
        return new static($value);
    }

    public static function caseValues(): array
    {
        return array_values(static::MAP);
    }

    public function getName(): string
    {
        return $this->value === '' ? 'None' : ucfirst($this->value);
    }

    public function getClass(): LevelClass
    {
        return match ($this->value) {
            'info' => LevelClass::info(),
            'warning' => LevelClass::warning(),
            'error' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
