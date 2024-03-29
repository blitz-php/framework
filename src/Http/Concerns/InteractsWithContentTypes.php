<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http\Concerns;

use BlitzPHP\Utilities\String\Text;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Http\Concerns\InteractsWithContentTypes</a>
 */
trait InteractsWithContentTypes
{
    /**
     * Déterminez si la requête envoie du JSON.
     */
    public function isJson(): bool
    {
        return Text::contains($this->header('CONTENT_TYPE') ?? '', ['/json', '+json']);
    }

    /**
     * Déterminez si la requête actuelle attend probablement une réponse JSON.
     */
    public function expectsJson(): bool
    {
        return ($this->ajax() && ! $this->pjax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    /**
     * Déterminez si la requête actuelle demande un JSON.
     */
    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return isset($acceptable[0]) && Text::contains(strtolower($acceptable[0]), ['/json', '+json']);
    }

    /**
     * Renvoie le type de contenu le plus approprié à partir du tableau donné en fonction de la négociation de contenu.
     */
    public function prefers(array|string $contentTypes): ?string
    {
        $accepts = $this->getAcceptableContentTypes();

        $contentTypes = (array) $contentTypes;

        foreach ($accepts as $accept) {
            if (in_array($accept, ['*/*', '*'], true)) {
                return $contentTypes[0];
            }

            foreach ($contentTypes as $contentType) {
                $type = $contentType;

                if (null !== ($mimeType = $this->getMimeType($contentType))) {
                    $type = $mimeType;
                }

                $accept = strtolower($accept);

                $type = strtolower($type);

                if ($this->matchesType($type, $accept) || $accept === strtok($type, '/') . '/*') {
                    return $contentType;
                }
            }
        }

        return null;
    }

    /**
     * Déterminez si la demande actuelle accepte n'importe quel type de contenu.
     */
    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 || (
            isset($acceptable[0]) && ($acceptable[0] === '*/*' || $acceptable[0] === '*')
        );
    }

    /**
     * Détermine si une requête accepte le JSON.
     */
    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    /**
     * Détermine si une requête accepte le HTML.
     */
    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    /**
     * Déterminez si les types de contenu donnés correspondent.
     */
    public static function matchesType(string $actual, string $type): bool
    {
        if ($actual === $type) {
            return true;
        }

        $split = explode('/', $actual);

        return isset($split[1]) && preg_match('#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#', $type);
    }

    /**
     * Obtenez le format de données attendu dans la réponse.
     */
    public function format(string $default = 'html'): string
    {
        foreach ($this->getAcceptableContentTypes() as $type) {
            if ($format = $this->getFormat($type)) {
                return $format;
            }
        }

        return $default;
    }
}
