<?php

namespace App\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\LogLevels\HttpStatusCodeLevel;
use Opcodes\LogViewer\Logs\Log;

/**
 * Access log do pool do php-fpm (storage/logs/php-fpm/www.access.log). O formato é o
 * `access.format` definido em docker/{development,production}/php-fpm/php-fpm-overrides.conf:
 *
 *   %R [%{%d/%b/%Y:%H:%M:%S %z}t] "%m %r%Q%q" %s %{milli}d %{kilo}M %C %f
 *   127.0.0.1 [25/Jul/2026:19:17:00 +0000] "GET /" 200 12.345 2048 5.00 /var/www/public/index.php
 *
 * Além de método/rota/status (como o HTTP Access comum), carrega os campos exclusivos do
 * php-fpm: tempo de resposta (ms), memória (KB), CPU (%) e o script — que ficam na mensagem.
 * O nível é colorido pelo status HTTP (2xx/3xx/4xx/5xx), igual ao HTTP Access embutido.
 */
class FpmAccessLog extends Log
{
    public static string $name = 'PHP-FPM Access';

    public static string $levelClass = HttpStatusCodeLevel::class;

    public static string $regex = '/^(?P<ip>\S+) \[(?P<datetime>[^\]]+)\] "(?P<method>\S+) (?P<path>[^"]*)" (?P<status>\d+) (?P<time>\S+) (?P<memory>\S+) (?P<cpu>\S+) (?P<script>\S+)/';

    public static string $regexLevelKey = 'status';

    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Status', 'data_path' => 'level'],
        ['label' => 'Request', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $this->context = [
            'ip' => $matches['ip'] ?? null,
            'method' => $matches['method'] ?? null,
            'path' => $matches['path'] ?? null,
            'status' => isset($matches['status']) ? (int) $matches['status'] : null,
            'time_ms' => isset($matches['time']) ? (float) $matches['time'] : null,
            'memory_kb' => isset($matches['memory']) ? (int) $matches['memory'] : null,
            'cpu_percent' => isset($matches['cpu']) ? (float) $matches['cpu'] : null,
            'script' => $matches['script'] ?? null,
        ];

        $this->datetime = static::parseDateTime($matches['datetime'] ?? null)?->setTimezone(LogViewer::timezone());
        $this->level = $matches['status'] ?? null;
        $this->message = sprintf(
            '%s %s — %sms · %sKB · CPU %s%%',
            $matches['method'] ?? '',
            $matches['path'] ?? '',
            $matches['time'] ?? '?',
            $matches['memory'] ?? '?',
            $matches['cpu'] ?? '?',
        );
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        // Ex.: "25/Jul/2026:19:17:00 +0000" (mesmo formato do HTTP Access).
        return $datetime ? Carbon::createFromFormat('d/M/Y:H:i:s O', $datetime) : null;
    }
}
