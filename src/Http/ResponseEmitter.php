<?php
namespace BlitzPHP\Http;

use GuzzleHttp\Psr7\LimitStream;

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
     * {@inheritDoc}
     */
    public function emit(Response $response, int $maxBufferLength = 8192)
    {
        $file = $line = null;
        if (headers_sent($file, $line)) {
            $message = "Unable to emit headers. Headers sent in file=$file line=$line";
            if (on_dev()) {
                trigger_error($message, E_USER_WARNING);
            }
            else {
                // Logger::warning($message, __FILE__, __LINE__);
            }
        }

        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->flush();

        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));
        if (is_array($range)) {
            $this->emitBodyRange($range, $response, $maxBufferLength);
        }
        else {
            $this->emitBody($response, $maxBufferLength);
        }

        if (function_exists('fastcgi_finish_request')) {
            session_write_close();
            fastcgi_finish_request();
        }
    }

    /**
     * Emet le corps de la requête
     *
     * @param int $maxBufferLength La taille du bloc à émettre
     * @return void
     */
    protected function emitBody(Response $response, int $maxBufferLength)
    {
        if (in_array($response->getStatusCode(), [204, 304])) {
            return;
        }
        $body = $response->getBody();

        if (!$body->isSeekable()) {
            echo $body;

            return;
        }

        $body->rewind();
        while (!$body->eof()) {
            echo $body->read($maxBufferLength);
        }
    }

    /**
     * Émettre une plage du corps du message.
     *
     * @param array $range La plage de données à émettre
     * @param int $maxBufferLength La taille du bloc à émettre
     * @return void
     */
    protected function emitBodyRange(array $range, Response $response, int $maxBufferLength)
    {
        list($unit, $first, $last, $length) = $range;

        $body = $response->getBody();

        if (!$body->isSeekable()) {
            $contents = $body->getContents();
            echo substr($contents, $first, $last - $first + 1);

            return;
        }

        $body = new LimitStream($body, -1, $first);
        $body->rewind();
        $pos = 0;
        $length = $last - $first + 1;
        while (!$body->eof() AND $pos < $length) {
            if (($pos + $maxBufferLength) > $length) {
                echo $body->read($length - $pos);
                break;
            }

            echo $body->read($maxBufferLength);
            $pos = $body->tell();
        }
    }

    /**
     * Émettre la ligne d'état.
     *
     * Émet la ligne d'état en utilisant la version du protocole et le code d'état de
     * la réponse; si une expression de raison est disponible, elle est également émise.
     *
     * @return void
     */
    protected function emitStatusLine(Response $response)
    {
        $reasonPhrase = $response->getReasonPhrase();
        header(sprintf(
            'HTTP/%s %d%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            ($reasonPhrase ? ' ' . $reasonPhrase : '')
        ));
    }

    /**
     * Émettre des en-têtes de réponse.
     *
     * Boucle à travers chaque en-tête, émettant chacun ; si la valeur d'en-tête
     * est un tableau avec plusieurs valeurs, garantit que chacune est envoyée
     * de manière à créer des en-têtes agrégés (au lieu de remplacer
     * la précédente).
     *
     * @return void
     */
    protected function emitHeaders(Response $response)
    {
        $cookies = [];
        if (method_exists($response, 'getCookies')) {
			$cookies = $response->getCookies();
        }

        foreach ($response->getHeaders() As $name => $values)
        {
            if (strtolower($name) === 'set-cookie')
            {
                $cookies = array_merge($cookies, $values);
                continue;
            }
            $first = true;
            foreach ($values As $value)
            {
                header(sprintf(
                    '%s: %s',
                    $name,
                    $value
                ), $first);
                $first = false;
            }
        }

        $this->emitCookies($cookies);
    }

    /**
     * émettre des cookies en utilisant setcookie()
     *
     * @param array $cookies Un tableau d'en-têtes Set-Cookie.
     * @return void
     */
    protected function emitCookies(array $cookies)
    {
        foreach ($cookies As $cookie)
        {
            if (is_array($cookie))
			{
                setcookie(
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['expire'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure'],
                    $cookie['httpOnly']
                );
                continue;
            }

            if (strpos($cookie, '";"') !== false)
            {
                $cookie = str_replace('";"', '{__cookie_replace__}', $cookie);
                $parts = str_replace('{__cookie_replace__}', '";"', explode(';', $cookie));
            }
            else
            {
                $parts = preg_split('/\;[ \t]*/', $cookie);
            }

            list($name, $value) = explode('=', array_shift($parts), 2);
            $data = [
                'name'     => urldecode($name),
                'value'    => urldecode($value),
                'expires'  => 0,
                'path'     => '',
                'domain'   => '',
                'secure'   => false,
                'httponly' => false,
            ];

            foreach ($parts As $part)
            {
                if (strpos($part, '=') !== false)
                {
                    list($key, $value) = explode('=', $part);
                }
                else
                {
                    $key = $part;
                    $value = true;
                }

                $key = strtolower($key);
                $data[$key] = $value;
            }
            if (!empty($data['expires']))
            {
                $data['expires'] = strtotime($data['expires']);
            }
            setcookie(
                $data['name'],
                $data['value'],
                $data['expires'],
                $data['path'],
                $data['domain'],
                $data['secure'],
                $data['httponly']
            );
        }
    }

    /**
     * Boucle à travers le tampon de sortie, en vidant chacun, avant d'émettre
     * la réponse.
     *
     * @param int|null $maxBufferLevel Vide jusqu'à ce niveau de tampon.
     * @return void
     */
    protected function flush(?int $maxBufferLevel = null)
    {
        if (null === $maxBufferLevel)
        {
            $maxBufferLevel = ob_get_level();
        }

        while (ob_get_level() > $maxBufferLevel)
        {
            ob_end_flush();
        }
    }

    /**
     * Analyser l'en-tête de la plage de contenu
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param string $header L'en-tête Content-Range à analyser.
     * @return array|false [unité, premier, dernier, longueur] ; renvoie faux si non
     * une plage de contenu ou une plage de contenu non valide est fournie
     */
    protected function parseContentRange(string $header)
    {
        if (preg_match('/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/', $header, $matches))
        {
            return [
                $matches['unit'],
                (int)$matches['first'],
                (int)$matches['last'],
                $matches['length'] === '*' ? '*' : (int)$matches['length'],
            ];
        }

        return false;
    }
}
