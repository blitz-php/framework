<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\HotReloader;

/**
 * @internal
 * 
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.6 - CodeIgniter\HotReloader\HotReloader</a>
 */
final class HotReloader
{
    public function run(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        ini_set('zlib.output_compression', 'Off');

        header('Cache-Control: no-store');
        header('Content-Type: text/event-stream');
        header('Access-Control-Allow-Methods: GET');

        ob_end_clean();
        set_time_limit(0);

        $hasher  = new DirectoryHasher();
        $appHash = $hasher->hash();

        while (true) {
            if (connection_status() !== CONNECTION_NORMAL || connection_aborted() === 1) {
                break;
            }

            $currentHash = $hasher->hash();

            // Si le hachage a changé, demandez au navigateur de se recharger.
            if ($currentHash !== $appHash) {
                $appHash = $currentHash;

                $this->sendEvent('reload', ['time' => date('Y-m-d H:i:s')]);
                break;
            }

            if (mt_rand(1, 10) > 8) {
                $this->sendEvent('ping', ['time' => date('Y-m-d H:i:s')]);
            }

            sleep(1);
        }
    }

    /**
     * Envoyer un événement au navigateur.
     */
    private function sendEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data) . "\n\n";

        ob_flush();
        flush();
    }
}
