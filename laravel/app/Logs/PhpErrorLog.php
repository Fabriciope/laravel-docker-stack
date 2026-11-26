<?php

namespace App\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\Log;

/**
 * error_log do PHP no pool do php-fpm (storage/logs/php-fpm/www.app.log; definido por
 * `error_log` em docker/{development,production}/php-fpm/99-overrides.ini). Formato fixo
 * do PHP:
 *
 *   [25-Jul-2026 19:17:00 America/Sao_Paulo] PHP Warning:  mensagem in /arquivo on line N
 *
 * Pega o que o Laravel não captura (fatais precoces, erros antes do handler). O rótulo do
 * PHP (Warning/Notice/Deprecated/Fatal error/…) vira o nível, colorido pelo App\Logs\
 * PhpErrorLevel (o LaravelLogLevel embutido casa só MAIÚSCULO e deixava tudo sem cor).
 */
class PhpErrorLog extends Log
{
    public static string $name = 'PHP Errors';

    public static string $levelClass = PhpErrorLevel::class;

    public static string $regex = '/^\[(?P<datetime>[^\]]+)\] PHP (?P<phplevel>[^:]+):\s+(?P<message>.*)/';

    public static string $regexLevelKey = 'phplevel';

    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Level', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $this->context = [];
        $this->datetime = static::parseDateTime($matches['datetime'] ?? null)?->setTimezone(LogViewer::timezone());
        // Rótulo cru do PHP ("Warning", "Fatal error", "Deprecated"...); o PhpErrorLevel
        // preserva no display e mapeia a cor. Mantê-lo cru deixa índice (matches()) e
        // display consistentes — ambos passam o mesmo token para o PhpErrorLevel.
        $this->level = trim($matches['phplevel'] ?? '');
        $this->message = trim($matches['message'] ?? '');
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        // Ex.: "25-Jul-2026 19:17:00 America/Sao_Paulo" (identificador de fuso = 'e').
        // rescue + "?: null": nunca lançar nem devolver false (o matches() do pacote trata
        // exceção como "não casou" e quebraria o agrupamento de entradas).
        return $datetime
            ? rescue(fn () => Carbon::createFromFormat('d-M-Y H:i:s e', $datetime) ?: null, null, false)
            : null;
    }
}
