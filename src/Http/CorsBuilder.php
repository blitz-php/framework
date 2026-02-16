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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Constructeur CORS (Cross-Origin Resource Sharing)
 *
 * Cette classe gère la configuration et l'application des en-têtes CORS
 * pour les requêtes cross-origin selon les spécifications W3C.
 *
 * @credit CodeIgniter4 Cors <a href="https://github.com/agungsugiarto/codeigniter4-cors">Fluent\Cors\ServiceCors</a>
 */
class CorsBuilder
{
    /**
     * Options de configuration CORS
     */
    protected array $options = [];

    /**
     * Constructeur de la classe CorsBuilder
     *
     * @param array $options Options de configuration CORS
     */
    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

    /**
     * Normalise les options de configuration CORS
     */
    protected function normalizeOptions(array $options = []): array
    {
        $options = array_merge([
            'allowedOrigins'         => [],
            'allowedOriginsPatterns' => [],
            'supportsCredentials'    => false,
            'allowedHeaders'         => [],
            'exposedHeaders'         => [],
            'allowedMethods'         => [],
            'maxAge'                 => 0,
        ], $options);

        // Normaliser la casse des méthodes
        $options['allowedMethods'] = array_map(strtoupper(...), $options['allowedMethods']);

        // Normaliser ['*'] en true pour les origines, en-têtes et méthodes
        if (in_array('*', $options['allowedOrigins'], true)) {
            $options['allowedOrigins'] = true;
        }
        if (in_array('*', $options['allowedHeaders'], true)) {
            $options['allowedHeaders'] = true;
        }
        if (in_array('*', $options['allowedMethods'], true)) {
            $options['allowedMethods'] = true;
        }

        return $options;
    }

    /**
     * Vérifie si la requête est une requête CORS
     *
     * Une requête est considérée comme CORS si elle contient un en-tête Origin
     * et que l'origine est différente de l'hôte du serveur
     *
     * @return bool true si c'est une requête CORS, false sinon
     */
    public function isCorsRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Origin') && ! $this->isSameHost($request);
    }

    /**
     * Vérifie si la requête est une requête préflight (OPTIONS)
     *
     * Une requête préflight est une requête OPTIONS envoyée par le navigateur
     * pour vérifier si la requête CORS est autorisée
     *
     * @return bool true si c'est une requête préflight, false sinon
     */
    public function isPreflightRequest(ServerRequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * Traite une requête préflight et retourne une réponse appropriée
     *
     * @param ServerRequestInterface $request Requête préflight
     *
     * @return ResponseInterface Réponse avec les en-têtes CORS appropriés
     */
    public function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $response = $response->withStatus(204);

        return $this->addPreflightRequestHeaders($request, $response);
    }

    /**
     * Ajoute les en-têtes appropriés pour une réponse à une requête préflight
     *
     * @param ServerRequestInterface $request  Requête préflight
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec les en-têtes CORS ajoutés
     */
    public function addPreflightRequestHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowCredentials($request, $response);
            $response = $this->configureAllowedMethods($request, $response);
            $response = $this->configureAllowedHeaders($request, $response);
            $response = $this->configureMaxAge($request, $response);
        }

        return $response;
    }

    /**
     * Vérifie si l'origine de la requête est autorisée
     *
     * @return bool true si l'origine est autorisée, false sinon
     */
    public function isOriginAllowed(ServerRequestInterface $request): bool
    {
        if ($this->options['allowedOrigins'] === true) {
            return true;
        }

        if (! $request->hasHeader('Origin')) {
            return false;
        }

        $origin = $request->getHeaderLine('Origin');

        if (in_array($origin, $this->options['allowedOrigins'], true)) {
            return true;
        }

        foreach ($this->options['allowedOriginsPatterns'] as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ajoute les en-têtes CORS appropriés pour une requête actuelle (non préflight)
     *
     * @param ServerRequestInterface $request  Requête actuelle
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec les en-têtes CORS ajoutés
     */
    public function addActualRequestHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowCredentials($request, $response);
            $response = $this->configureExposedHeaders($request, $response);
        }

        return $response;
    }

    /**
     * Ajoute un en-tête à l'en-tête Vary de la réponse
     *
     * L'en-tête Vary indique au cache quelles parties de la requête
     * peuvent affecter la réponse
     *
     * @param ResponseInterface $response Réponse à modifier
     * @param string            $header   En-tête à ajouter à Vary
     *
     * @return ResponseInterface Réponse avec l'en-tête Vary mis à jour
     */
    public function varyHeader(ResponseInterface $response, $header): ResponseInterface
    {
        if (! $response->hasHeader('Vary')) {
            $response = $response->withHeader('Vary', $header);
        } elseif (! in_array($header, explode(', ', $response->getHeaderLine('Vary')), true)) {
            $response = $response->withHeader('Vary', $response->getHeaderLine('Vary') . ', ' . $header);
        }

        return $response;
    }

    /**
     * Configure l'en-tête Access-Control-Allow-Origin
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Allow-Origin configuré
     */
    protected function configureAllowedOrigin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedOrigins'] === true && ! $this->options['supportsCredentials']) {
            // Sûr et pouvant être mis en cache, autoriser toutes les origines
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            // Les origines uniques peuvent être définies en toute sécurité
            $response = $response->withHeader('Access-Control-Allow-Origin', array_values($this->options['allowedOrigins'])[0]);
        } else {
            // Pour les en-têtes dynamiques, définir l'en-tête Origin demandé quand il est défini et autorisé
            if ($this->isCorsRequest($request) && $this->isOriginAllowed($request)) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin'));
            }

            $response = $this->varyHeader($response, 'Origin');
        }

        return $response;
    }

    /**
     * Vérifie si une seule origine est autorisée
     *
     * @return bool true si une seule origine est autorisée, false sinon
     */
    protected function isSingleOriginAllowed(): bool
    {
        if ($this->options['allowedOrigins'] === true || ! empty($this->options['allowedOriginsPatterns'])) {
            return false;
        }

        return count($this->options['allowedOrigins']) === 1;
    }

    /**
     * Configure l'en-tête Access-Control-Allow-Methods
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Allow-Methods configuré
     */
    protected function configureAllowedMethods(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedMethods'] === true) {
            $allowMethods = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            $response     = $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->options['allowedMethods']);
        }

        return $response->withHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    /**
     * Configure l'en-tête Access-Control-Allow-Headers
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Allow-Headers configuré
     */
    protected function configureAllowedHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedHeaders'] === true) {
            $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $response     = $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->options['allowedHeaders']);
        }

        return $response->withHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    /**
     * Configure l'en-tête Access-Control-Allow-Credentials
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Allow-Credentials configuré
     */
    protected function configureAllowCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['supportsCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    /**
     * Configure l'en-tête Access-Control-Expose-Headers
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Expose-Headers configuré
     */
    protected function configureExposedHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['exposedHeaders']) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }

        return $response;
    }

    /**
     * Configure l'en-tête Access-Control-Max-Age
     *
     * @param ServerRequestInterface $request  Requête courante
     * @param ResponseInterface      $response Réponse à modifier
     *
     * @return ResponseInterface Réponse avec l'en-tête Access-Control-Max-Age configuré
     */
    protected function configureMaxAge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['maxAge'] !== null) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $this->options['maxAge']);
        }

        return $response;
    }

    /**
     * Vérifie si la requête provient du même hôte
     *
     * @return bool true si la requête provient du même hôte, false sinon
     */
    protected function isSameHost(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('Origin') === config('app.base_url');
    }
}
