<?php

namespace App\Logs;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Opcodes\LogViewer\Facades\LogViewer;
use Opcodes\LogViewer\Logs\Log;

/**
 * Log do scripts/deploy (storage/logs/deploy.log). Cada entrada:
 *
 *   [26-07-2026 14:10:13][SUCCESS] Application deployed successfully at ...
 *
 * Níveis INFO/SUCCESS/WARN/ERROR coloridos pelo App\Logs\DeployLevel. Linhas sem
 * esse prefixo (o separador "===", linhas em branco e a saída crua de um comando
 * que falhou) não abrem entrada nova — o log-viewer as anexa à entrada anterior
 * (multi-linha), então a saída de erro fica junto do [ERROR] que a gerou.
 */
class DeployLog extends Log
{
    public static string $name = 'Deploy';

    public static string $levelClass = DeployLevel::class;

    public static string $regex = '/^\[(?P<datetime>\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2})\]\[(?P<level>[A-Z]+)\] (?P<message>.*)/';

    public static array $columns = [
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Level', 'data_path' => 'level'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function fillMatches(array $matches = []): void
    {
        $this->context = [];
        $this->datetime = static::parseDateTime($matches['datetime'] ?? null)?->setTimezone(LogViewer::timezone());
        $this->level = $matches['level'] ?? null;
        $this->message = trim($matches['message'] ?? '');
    }

    public static function parseDatetime(?string $datetime): ?CarbonInterface
    {
        // Ex.: "26-07-2026 14:10:13" (hora local do host — America/Sao_Paulo).
        // rescue + "?: null": nunca lançar nem devolver false — o matches() do
        // pacote trata exceção como "não casou" e quebraria o agrupamento.
        return $datetime
            ? rescue(fn () => Carbon::createFromFormat('d-m-Y H:i:s', $datetime) ?: null, null, false)
            : null;
    }
}
