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

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Formatter\Formatter;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;

trait ResponseTrait
{
    /**
     * Obtient le code d'état de la réponse.
     */
    public function status(): int
    {
        return $this->getStatusCode();
    }

    /**
     * Obtient la phrase de motif de réponse associée au code d'état.
     */
    public function statusText(): string
    {
        return $this->getReasonPhrase();
    }

    /**
     * Obtient le contenu de la réponse
     */
    public function content(): string
    {
        return $this->getBody()->getContents();
    }

    /**
     * Définissez un en-tête sur la réponse.
     *
     * @param string|string[] $values
     */
    public function header(string $key, array|string $values, bool $replace = true): static
    {
        if ($replace) {
            return $this->withHeader($key, $values);
        }

        return $this->withAddedHeader($key, $values);
    }

    /**
     * Ajoutez un cookie à la réponse.
     *
     * @param CookieInterface|string $cookie
     */
    public function cookie($cookie): static
    {
        if (is_string($cookie) && function_exists('cookie')) {
            $cookie = cookie(...func_get_args());
        }

        return $this->withCookie($cookie);
    }

    /**
     * Définit l'en-tête de date
     */
    public function withDate(DateTime $date): self
    {
        $date->setTimezone(new DateTimeZone('UTC'));

        return $this->withHeader('Date', $date->format('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * Copie tous les en-têtes de l'instance de réponse globale dans cette Redirection.
     * Utile lorsque vous venez de définir un en-tête pour s'assurer qu'il est bien envoyé avec la réponse de redirection..
     */
    public function withHeaders(array $headers = []): static
    {
        $new     = clone $this;
        $headers = $headers === [] ? Services::response()->getHeaders() : $headers;

        foreach ($headers as $name => $header) {
            $new = $new->withHeader($name, $header);
        }

        return $new;
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
     * @param SplFileInfo|string $file    Le chemin absolue du fichier à télécharger ou une instance SplFileInfo
     * @param ?string            $name    Le nom que vous souhaitez donner au fichier téléchargé
     * @param array              $headers Les entêtes supplémentaires à definir dans la réponse
     */
    public function download(SplFileInfo|string $file, ?string $name = null, array $headers = []): static
    {
        if (is_string($file) && ! is_file($file)) {
            throw new LoadException('The requested file was not found');
        }

        return $this->withHeaders($headers)->withFile($file, ['download' => true, 'name' => $name]);
    }

    /**
     * Créez une nouvelle instance de réponse diffusée en continu sous forme de téléchargement de fichier.
     */
    public function streamDownload(callable|StreamInterface|string $stream, string $name, array $headers = []): static
    {
        if (! ($stream instanceof StreamInterface)) {
            $stream = to_stream($stream);
        }

        return $this->withHeaders($headers)->withBody($stream)->withType(pathinfo($name, PATHINFO_EXTENSION))->withDownload($name);
    }

    /**
     * Renvoie le contenu brut d'un fichier binaire.
     */
    public function file(SplFileInfo|string $file, array $headers = []): static
    {
        return $this->withHeaders($headers)->withFile($file);
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
