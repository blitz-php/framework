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

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Formatter\Formatter;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Psr7\Utils;

trait ResponseTrait
{
    /**
     * Définit l'en-tête de date
     */
    public function withDate(DateTime $date): self
    {
        $date->setTimezone(new DateTimeZone('UTC'));

        return $this->withHeader('Date', $date->format('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * Convertit le $body en JSON et définit l'en-tête Content Type.
     */
    public function json(array|string $body, int $status = StatusCode::OK): self
    {
        return $this->withType('application/json')
            ->withStringBody(Formatter::type('json')->format($body))
            ->withStatus($status);
    }

    /**
     * Renvoie le corps actuel, converti en JSON s'il ne l'est pas déjà.
     *
     * @throws InvalidArgumentException Si la propriété body n'est pas un json valide.
     */
    public function toJson(): array
    {
        $body = $this->getBody()->getContents();

        return Formatter::type('json')->parse($body);
    }

    /**
     * Convertit $body en XML et définit le Content-Type correct.
     */
    public function xml(array|string $body, int $status = StatusCode::OK): self
    {
        return $this->withType('application/xml')
            ->withStringBody(Formatter::type('xml')->format($body))
            ->withStatus($status);
    }

    /**
     * Récupère le corps actuel dans XML et le renvoie.
     */
    public function toXml()
    {
        $body = $this->getBody()->getContents();

        return Formatter::type('xml')->parse($body);
    }

    /**
     * Définit les en-têtes appropriés pour garantir que cette réponse n'est pas mise en cache par les navigateurs.
     *
     * @todo Recommander la recherche de ces directives, pourrait avoir besoin: 'private', 'no-transform', 'no-store', 'must-revalidate'
     */
    public function noCache(): self
    {
        return $this->withoutHeader('Cache-Control')
            ->withHeader('Cache-Control', ['no-store', 'max-age=0', 'no-cache']);
    }

    /**
     * Génère les en-têtes qui forcent un téléchargement à se produire.
     * Et envoie le fichier au navigateur.
     *
     * @param string      $filename Le nom que vous souhaitez donner au fichier téléchargé ou le chemin d'accès au fichier à envoyer
     * @param string|null $data     Les données à télécharger. Définissez null si le $filename est le chemin du fichier
     */
    public function download(string $filename, ?string $data = ''): static
    {
        if (is_file($filename)) {
            $filepath = realpath($filename);
            $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
            $filename = end($filename);

            return $this->withFile($filepath, ['download' => true, 'name' => $filename]);
        }

        if (! empty($data)) {
            return $this->withStringBody($data)
                ->withType(pathinfo($filename, PATHINFO_EXTENSION))
                ->withDownload($filename);
        }

        return $this;
    }

    /**
     * Renvoi une vue comme reponse
     */
    public function view(string $view, array $data = [], array|int $optionsOrStatus = StatusCode::OK): static
    {
        if (is_int($optionsOrStatus)) {
            $status  = $optionsOrStatus;
            $options = [];
        } else {
            $status  = $optionsOrStatus['status'] ?? StatusCode::OK;
            $options = array_filter($optionsOrStatus, static fn ($k) => $k !== 'status', ARRAY_FILTER_USE_KEY);
        }

        $viewContent = view($view, $data, $options)->get();

        return $this->withStatus($status)->withBody(Utils::streamFor($viewContent));
    }
}
