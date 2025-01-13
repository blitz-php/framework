<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security;

use Ahc\Cli\Output\Color;

/**
 * Checks php.ini settings
 *
 * @used-by \BlitzPHP\Cli\Commands\Utilities\PhpIniCheck
 */
class CheckPhpIni
{
    /**
     * @param bool $isCli Set false if you run via Web
     *
     * @return array|string HTML string or array in CLI
     */
    public static function run(bool $isCli = true)
    {
        $output = static::checkIni();

        $thead = ['Directive', 'Globale', 'Actuelle', 'Recommandation', 'Remarque'];
        $tbody = [];

        // CLI
        if ($isCli) {
            return self::outputForCli($output, $thead, $tbody);
        }

        // Web
        return self::outputForWeb($output, $thead, $tbody);
    }

    private static function outputForCli(array $output, array $thead, array $tbody): array
    {
        $color = new Color();

        foreach ($output as $directive => $values) {
            $current        = $values['current'] ?? '';
            $notRecommended = false;

            if ($values['recommended'] !== '') {
                if ($values['recommended'] !== $current) {
                    $notRecommended = true;
                }

                $current = $notRecommended
                    ? $color->error($current === '' ? 'n/a' : $current)
                    : $current;
            }

            $directive = $notRecommended ? $color->error($directive) : $directive;
            $tbody[]   = [
                $directive, $values['global'], $current, $values['recommended'], $values['remark'],
            ];
        }

        $table = [];

        foreach ($tbody as $body) {
            $table[] = array_combine($thead, $body);
        }

        return $table;
    }

    private static function outputForWeb(array $output, array $thead, array $tbody): string
    {
        foreach ($output as $directive => $values) {
            $current        = $values['current'];
            $notRecommended = false;

            if ($values['recommended'] !== '') {
                if ($values['recommended'] !== $values['current']) {
                    $notRecommended = true;
                }

                if ($values['current'] === '') {
                    $current = 'n/a';
                }

                $current = $notRecommended
                    ? '<span style="color: red">' . $current . '</span>'
                    : $current;
            }

            $directive = $notRecommended
                ? '<span style="color: red">' . $directive . '</span>'
                : $directive;
            $tbody[] = [
                $directive, $values['global'], $current, $values['recommended'], $values['remark'],
            ];
        }

        /* $table    = new Table();
        $template = [
            'table_open' => '<table border="1" cellpadding="4" cellspacing="0">',
        ];
        $table->setTemplate($template);

        $table->setHeading($thead);

        return '<pre>' . $table->generate($tbody) . '</pre>'; */

        return '';
    }

    /**
     * @internal Used for testing purposes only.
     */
    public static function checkIni(): array
    {
        $items = [
            'error_reporting'         => ['recommended' => '5111'],
            'display_errors'          => ['recommended' => '0'],
            'display_startup_errors'  => ['recommended' => '0'],
            'log_errors'              => [],
            'error_log'               => [],
            'default_charset'         => ['recommended' => 'UTF-8'],
            'max_execution_time'      => ['remark' => 'The default is 30.'],
            'memory_limit'            => ['remark' => '> post_max_size'],
            'post_max_size'           => ['remark' => '> upload_max_filesize'],
            'upload_max_filesize'     => ['remark' => '< post_max_size'],
            'max_input_vars'          => ['remark' => 'The default is 1000.'],
            'request_order'           => ['recommended' => 'GP'],
            'variables_order'         => ['recommended' => 'GPCS'],
            'date.timezone'           => ['recommended' => 'UTC'],
            'mbstring.language'       => ['recommended' => 'neutral'],
            'opcache.enable'          => ['recommended' => '1'],
            'opcache.enable_cli'      => [],
            'opcache.jit'             => [],
            'opcache.jit_buffer_size' => [],
            'zend.assertions'         => ['recommended' => '-1'],
        ];

        $output = [];
        $ini    = ini_get_all();

        foreach ($items as $key => $values) {
            $hasKeyInIni  = array_key_exists($key, $ini);
            $output[$key] = [
                'global'      => $hasKeyInIni ? $ini[$key]['global_value'] : 'disabled',
                'current'     => $hasKeyInIni ? $ini[$key]['local_value'] : 'disabled',
                'recommended' => $values['recommended'] ?? '',
                'remark'      => $values['remark'] ?? '',
            ];
        }

        // [directive => [current_value, recommended_value]]
        return $output;
    }
}
