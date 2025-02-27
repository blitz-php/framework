<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Exceptions;

use Throwable;

/**
 * Ce trait fournit aux exceptions du cadre la possibilité d'identifier
 * précisément où l'exception a été déclenchée plutôt qu'instanciée.
 *
 * Ceci est principalement utilisé pour les exceptions instanciées dans les factories.
 */
trait DebugTraceableTrait
{
    /**
     * Ajuste le constructeur de l'exception pour assigner le fichier/la ligne à où
     * il est réellement déclenché plutôt que d'être instancié.
     */
    final public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $trace = $this->getTrace()[0];

        if (isset($trace['class']) && $trace['class'] === static::class) {
            [
                'line' => $this->line,
                'file' => $this->file,
            ] = $trace;
        }
    }

    /**
     * Obtenir le message système traduit
     *
     * Utilisez une instance de langue non partagée dans les services.
     * Si une instance partagée est créée, la langue
     * ont les paramètres régionaux actuels, donc même si les utilisateurs appellent
     * `$this->request->setLocale()` dans le contrôleur ensuite,
     * les paramètres régionaux de la langue ne seront pas modifiés.
     */
    protected static function lang(string $line, array $args = []): string
    {
        return single_service('translator')->getLine($line, $args);
    }
}
