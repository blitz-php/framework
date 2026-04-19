<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Session\Cookie\Cookie;
use GuzzleHttp\Psr7\LimitStream;
use Psr\Http\Message\ResponseInterface;

/**
 * Émetteur de réponse
 *
 * Émet une réponse à l'API du serveur PHP.
 *
 * Cet émetteur offre quelques changements par rapport aux émetteurs proposés par
 * diactors :
 *
 * - Les cookies sont émis en utilisant setcookie() pour ne pas entrer en conflit avec ext/session
 * - Pour les serveurs fastcgi avec PHP-FPM, session_write_close() est appelé simplement
 * avant fastcgi_finish_request() pour s'assurer que les données de session sont enregistrées
 * correctement (en particulier sur les backends de session plus lents).
 *
 * @credit      CakePHP 4.0 (Cake\Http\ResponseEmitter)
 */
class ResponseEmitter
{
    /**
     * Constructeur
     *
     * @param int $maxBufferLength Taille maximale de la mémoire tampon de sortie pour chaque itération.
     */
    public function __construct(protected int $maxBufferLength = 8192)
    {
    }

    /**
     * Émet une réponse.
     *
     * Émet une réponse, comprenant la ligne d'état, les en-têtes et le corps du message,
     * en fonction de l'environnement.
     */
    public function emit(ResponseInterface $response): bool
    {
        $file = '';
        $line = 0;

        if (headers_sent($file, $line)) {
            $message = "Impossible d'émettre les en-têtes. En-têtes envoyés dans le fichier={$file} ligne={$line}";
            if (on_dev()) {
                trigger_error($message, E_USER_WARNING);
            }

            logger()->warning($message);
        }

        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->flush();

        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));
        if (is_array($range)) {
            $this->emitBodyRange($range, $response);
        } else {
            $this->emitBody($response);
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return true;
    }

    /**
     * Émettre des en-têtes de réponse.
     *
     * Boucle à travers chaque en-tête, émettant chacun ; si la valeur d'en-tête
     * est un tableau avec plusieurs valeurs, garantit que chacune est envoyée
     * de manière à créer des en-têtes agrégés (au lieu de remplacer
     * la précédente).
     */
    public function emitHeaders(ResponseInterface $response): void
    {
        $cookies = [];
        if ($response instanceof Response) {
            $cookies = iterator_to_array($response->getCookieCollection());
        }

        foreach ($response->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                $cookies = array_merge($cookies, $values);

                continue;
            }
            $first = true;

            foreach ($values as $value) {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value,
                ), $first);
                $first = false;
            }
        }

        $this->emitCookies($cookies);
    }

    /**
     * Emet le corps du message.
     */
    protected function emitBody(ResponseInterface $response): void
    {
        if (in_array($response->getStatusCode(), [StatusCode::NO_CONTENT, StatusCode::NOT_MODIFIED], true)) {
            return;
        }
        $body = $response->getBody();

        if (! $body->isSeekable()) {
            echo $body;

            return;
        }

        $body->rewind();

        while (! $body->eof()) {
            echo $body->read($this->maxBufferLength);
        }
    }

    /**
     * Émettre une plage du corps du message.
     *
     * @param array $range La plage de données à émettre
     */
    protected function emitBodyRange(array $range, ResponseInterface $response): void
    {
        [, $first, $last] = $range;

        $body = $response->getBody();

        if (! $body->isSeekable()) {
            $contents = $body->getContents();
            echo substr($contents, $first, $last - $first + 1);

            return;
        }

        $body = new LimitStream($body, -1, $first);
        $body->rewind();
        $pos    = 0;
        $length = $last - $first + 1; /** @var int $length */

        while (! $body->eof() && $pos < $length) {
            if ($pos + $this->maxBufferLength > $length) {
                echo $body->read($length - $pos);
                break;
            }

            echo $body->read($this->maxBufferLength);
            $pos = $body->tell();
        }
    }

    /**
     * Émettre la ligne d'état.
     *
     * Émet la ligne d'état en utilisant la version du protocole et le code d'état de
     * la réponse; si une expression de raison est disponible, elle est également émise.
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase !== '' && $reasonPhrase !== '0' ? ' ' . $reasonPhrase : ''),
        ));
    }

    /**
     * émettre des cookies en utilisant setcookie()
     *
     * @param list<CookieInterface|string> $cookies Un tableau d'en-têtes Set-Cookie.
     */
    protected function emitCookies(array $cookies): void
    {
        foreach ($cookies as $cookie) {
            $this->setCookie($cookie);
        }
    }

    /**
     * Methode d'aide pour definir le cookie.
     */
    protected function setCookie(CookieInterface|string $cookie): bool
    {
        if (is_string($cookie)) {
            $cookie = Cookie::createFromHeaderString($cookie, ['path' => '']);
        }

        return setcookie($cookie->getName(), $cookie->getScalarValue(), $cookie->getOptions());
    }

    /**
     * Boucle à travers le tampon de sortie, en vidant chacun, avant d'émettre
     * la réponse.
     *
     * @param int|null $maxBufferLevel Vide jusqu'à ce niveau de tampon.
     */
    protected function flush(?int $maxBufferLevel = null): void
    {
        $maxBufferLevel ??= ob_get_level();

        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
        }
    }

    /**
     * Analyser l'en-tête de la plage de contenu
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param string $header L'en-tête Content-Range à analyser.
     *
     * @return array|false [unité, premier, dernier, longueur] ; renvoie faux si non
     *                     une plage de contenu ou une plage de contenu non valide est fournie
     */
    protected function parseContentRange(string $header)
    {
        if (preg_match('/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/', $header, $matches)) {
            return [
                $matches['unit'],
                (int) $matches['first'],
                (int) $matches['last'],
                $matches['length'] === '*' ? '*' : (int) $matches['length'],
            ];
        }

        return false;
    }
}
