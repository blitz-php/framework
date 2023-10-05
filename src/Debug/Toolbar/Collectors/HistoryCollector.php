<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use DateTime;

/**
 * Collecteur d'historique pour la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\History</a>
 */
class HistoryCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected bool $hasTimeline = false;

    /**
     * {@inheritDoc}
     */
    protected bool $hasTabContent = true;

    /**
     * {@inheritDoc}
     */
    protected bool $hasLabel = true;

    /**
     * {@inheritDoc}
     */
    protected string $title = 'Historique';

    /**
     * Fichiers d'historique
     */
    protected array $files = [];

    /**
     * Spécifiez la limite de temps et le nombre de fichiers pour l'historique de débogage.
     *
     * @param string $current Heure actuelle de l'historique
     * @param int    $limit   Fichiers d'historique max.
     */
    public function setFiles(string $current, int $limit = 20)
    {
        $filenames = glob(FRAMEWORK_STORAGE_PATH . 'debugbar/debugbar_*.json');

        $files   = [];
        $counter = 0;

        foreach (array_reverse($filenames) as $filename) {
            $counter++;

            // Les fichiers les plus anciens seront supprimés
            if ($limit >= 0 && $counter > $limit) {
                unlink($filename);

                continue;
            }

            // Récupère le contenu de cette requête d'historique spécifique
            $contents = file_get_contents($filename);

            $contents = @json_decode($contents);
            if (json_last_error() === JSON_ERROR_NONE) {
                preg_match('/debugbar_(.*)\.json$/s', $filename, $time);
                $time = sprintf('%.6f', $time[1] ?? 0);

                // Fichiers de la barre de débogage affichés dans History Collector
                $files[] = [
                    'time'        => $time,
                    'datetime'    => DateTime::createFromFormat('U.u', $time)->format('Y-m-d H:i:s.u'),
                    'active'      => $time === $current,
                    'status'      => $contents->vars->response->statusCode,
                    'method'      => $contents->method,
                    'url'         => $contents->url,
                    'isAJAX'      => $contents->isAJAX ? 'Oui' : 'Non',
                    'contentType' => $contents->vars->response->contentType,
                ];
            }
        }

        $this->files = $files;
    }

    /**
     * {@inheritDoc}
     */
    public function display(): array
    {
        return ['files' => $this->files];
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        return count($this->files);
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return empty($this->files);
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAJySURBVEhL3ZU7aJNhGIVTpV6i4qCIgkIHxcXLErS4FBwUFNwiCKGhuTYJGaIgnRoo4qRu6iCiiIuIXXTTIkIpuqoFwaGgonUQlC5KafU5ycmNP0lTdPLA4fu+8573/a4/f6hXpFKpwUwmc9fDfweKbk+n07fgEv33TLSbtt/hvwNFT1PsG/zdTE0Gp+GFfD6/2fbVIxqNrqPIRbjg4t/hY8aztcngfDabHXbKyiiXy2vcrcPH8oDCry2FKDrA+Ar6L01E/ypyXzXaARjDGGcoeNxSDZXE0dHRA5VRE5LJ5CFy5jzJuOX2wHRHRnjbklZ6isQ3tIctBaAd4vlK3jLtkOVWqABBXd47jGHLmjTmSScttQV5J+SjfcUweFQEbsjAas5aqoCLXutJl7vtQsAzpRowYqkBinyCC8Vicb2lOih8zoldd0F8RD7qTFiqAnGrAy8stUAvi/hbqDM+YzkAFrLPdR5ZqoLXsd+Bh5YCIH7JniVdquUWxOPxDfboHhrI5XJ7HHhiqQXox+APe/Qk64+gGYVCYZs8cMpSFQj9JOoFzVqqo7k4HIvFYpscCoAjOmLffUsNUGRaQUwDlmofUa34ecsdgXdcXo4wbakBgiUFafXJV8A4DJ/2UrxUKm3E95H8RbjLcgOJRGILhnmCP+FBy5XvwN2uIPcy1AJvWgqC4xm2aU4Xb3lF4I+Tpyf8hRe5w3J7YLymSeA8Z3nSclv4WLRyFdfOjzrUFX0klJUEtZtntCNc+F69cz/FiDzEPtjzmcUMOr83kDQEX6pAJxJfpL3OX22n01YN7SZCoQnaSdoZ+Jz+PZihH3wt/xlCoT9M6nEtmRSPCQAAAABJRU5ErkJggg==';
    }
}
