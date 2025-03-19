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
     * @param bool $isCli Defini a `false` s'il est exécuté via le Web
     *
     * @return array|string chaine HTML sur le web ou tableau en CLI
     */
    public static function run(bool $isCli = true, ?string $argument = null)
    {
        $output = static::checkIni($argument);

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
     * @internal Utilisé uniquement à des fins de test.
     */
    public static function checkIni(?string $argument = null): array
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
            'max_input_vars'          => ['remark' => 'La valeur par défaut est 1000.'],
            'request_order'           => ['recommended' => 'GP'],
            'variables_order'         => ['recommended' => 'GPCS'],
            'date.timezone'           => ['recommended' => 'UTC'],
            'mbstring.language'       => ['recommended' => 'neutral'],
            'opcache.enable'          => ['recommended' => '1'],
            'opcache.enable_cli'      => ['recommended' => '0', 'remark' => 'Activer lorsque vous utilisez des files d\'attente ou que vous exécutez des tâches CLI répétitives'],
            'opcache.jit'             => ['recommended' => 'tracing'],
            'opcache.jit_buffer_size' => ['recommended' => '128', 'remark' => 'Ajustez avec votre espace mémoire libre'],
            'zend.assertions'         => ['recommended' => '-1'],
        ];

        if ($argument === 'opcache') {
            $items = [
                'opcache.enable'                  => ['recommended' => '1'],
                'opcache.enable_cli'              => ['recommended' => '0', 'remark' => 'Activer lorsque vous utilisez des files d\'attente ou que vous exécutez des tâches CLI répétitives'],
                'opcache.jit'                     => ['recommended' => 'tracing', 'remark' => 'Désactiver lorsque vous utilisez des extensions tierces'],
                'opcache.jit_buffer_size'         => ['recommended' => '128', 'remark' => 'Ajustez avec votre espace mémoire libre'],
                'opcache.memory_consumption'      => ['recommended' => '128', 'remark' => 'Ajustez avec votre espace mémoire libre'],
                'opcache.interned_strings_buffer' => ['recommended' => '16'],
                'opcache.max_accelerated_files'   => ['remark' => 'Ajuster en fonction du nombre de fichiers PHP dans votre projet (par exemple : find your_project/ -iname \'*.php\'|wc -l)'],
                'opcache.max_wasted_percentage'   => ['recommended' => '10'],
                'opcache.validate_timestamps'     => ['recommended' => '0', 'remark' => 'Lorsque vous le désactivez, opcache conserve votre code dans la mémoire partagée. Le redémarrage du serveur web est nécessaire'],
                'opcache.revalidate_freq'         => [],
                'opcache.file_cache'              => ['remark' => 'Mise en cache du fichier de localisation, ce qui devrait améliorer les performances lorsque la mémoire du SHM est pleine.'],
                'opcache.file_cache_only'         => ['remark' => 'Mise en cache du code optique dans la mémoire partagée, désactivée lorsque vous utilisez Windows'],
                'opcache.file_cache_fallback'     => ['remark' => 'Activer lorsque vous utilisez Windows'],
                'opcache.save_comments'           => ['recommended' => '0', 'remark' => 'Activé lorsque vous utilisez l\'annotation docblock `package require`'],
            ];
        }

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
