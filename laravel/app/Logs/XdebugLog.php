<?php

namespace App\Logs;

use Opcodes\LogViewer\Logs\Log;

/**
 * Log do Xdebug (storage/logs/php-fpm/xdebug.log) — SÓ em dev (o Xdebug não roda
 * em produção; ver o registro condicional no AppServiceProvider). Cada linha:
 *
 *   [212] Log opened at 2026-07-26 16:16:45.272282
 *   [212] [Step Debug] WARN: Creating socket for 'localhost:9003', ...
 *   [212] Log closed at 2026-07-26 16:16:45.684586
 *
 * Toda linha começa com o PID entre colchetes (vira a 1ª coluna). Os canais trazem
 * INFO/WARN/ERR, coloridos pelo App\Logs\XdebugLevel; as linhas Log opened/closed
 * ficam sem nível. O Xdebug não carimba data por linha — daí não há coluna de data.
 */
class XdebugLog extends Log
{
    public static string $name = 'Xdebug';

    public static string $levelClass = XdebugLevel::class;

    public static string $regex = '/^\[(?P<pid>\d+)\] (?P<message>(?:\[(?P<channel>[^\]]+)\] (?P<level>[A-Z]+): )?.*)/';

    public static array $columns = [
        ['label' => 'PID', 'data_path' => 'context.pid'],
        ['label' => 'Level', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $level = $matches['level'] ?? '';

        $this->context = [
            'pid' => isset($matches['pid']) ? (int) $matches['pid'] : null,
            'channel' => ($matches['channel'] ?? '') !== '' ? $matches['channel'] : null,
        ];
        $this->datetime = null;                       // xdebug não carimba data por linha
        $this->level = $level !== '' ? $level : null;
        $this->message = trim($matches['message'] ?? '');
    }
}
