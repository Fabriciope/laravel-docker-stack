<?php

namespace App\Logs;

use Opcodes\LogViewer\LogLevels\LevelClass;
use Opcodes\LogViewer\LogLevels\LevelInterface;

/**
 * Níveis do scripts/deploy (deploy.log): INFO, SUCCESS, WARN, ERROR.
 *
 * O SUCCESS ganha o verde do log-viewer (LevelClass::success), que o
 * LaravelLogLevel embutido não expõe — daí uma classe de nível própria, no
 * mesmo molde das do pacote (SupervisorLogLevel, NginxStatusLevel...).
 */
class DeployLevel implements LevelInterface
{
    /** Token cru do deploy.log => severidade interna do log-viewer. */
    const MAP = [
        'SUCCESS' => 'success',
        'INFO' => 'info',
        'WARN' => 'warning',
        'ERROR' => 'danger',
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
            'success' => LevelClass::success(),
            'info' => LevelClass::info(),
            'warning' => LevelClass::warning(),
            'danger' => LevelClass::danger(),
            default => LevelClass::none(),
        };
    }
}
