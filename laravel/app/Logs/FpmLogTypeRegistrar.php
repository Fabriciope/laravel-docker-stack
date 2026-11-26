<?php

namespace App\Logs;

use Opcodes\LogViewer\LogFile;
use Opcodes\LogViewer\LogTypeRegistrar;

/**
 * Força o tipo de alguns logs pelo NOME, em vez do palpite do pacote. Necessário porque:
 *  - o pai joga qualquer "*access*" no "HTTP Access" (parser errado para o www.access.log
 *    do php-fpm, que tem formato próprio) — daí "reconhecido, mas No results";
 *  - a detecção por CONTEÚDO falha em arquivo VAZIO (ex.: logo após um clean_logs) e o
 *    tipo default fica cacheado — então o log só "sumia" depois de truncar.
 *
 * Forçando pelo nome, cada um é reconhecido mesmo vazio (mostra 0 entradas até chegar
 * conteúdo) e sempre com o parser certo (App\Logs\*, registrados via LogViewer::extend).
 * Bindado como singleton no AppServiceProvider — o pacote só registra o dele
 * `if (! bound)`, então o nosso vence.
 */
class FpmLogTypeRegistrar extends LogTypeRegistrar
{
    public function guessTypeFromFileName(LogFile $file): ?string
    {
        return match ($file->name) {
            'www.access.log' => 'php-fpm-access',
            'www.app.log' => 'php-errors',
            'deploy.log' => 'deploy',
            // xdebug.log é formatado só em dev (o tipo 'xdebug' só é registrado lá);
            // em prod cai no palpite do pai — e o arquivo nem existe por lá.
            'xdebug.log' => app()->isProduction() ? parent::guessTypeFromFileName($file) : 'xdebug',
            default => parent::guessTypeFromFileName($file),
        };
    }
}
