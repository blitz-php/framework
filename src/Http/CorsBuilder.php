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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @credit CodeIgniter4 Cors <a href="https://github.com/agungsugiarto/codeigniter4-cors">Fluent\Cors\ServiceCors</a>
 */
class CorsBuilder
{
    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $this->normalizeOptions($options);
    }

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


		// Normalize case
		$options['allowedMethods'] = array_map('strtoupper', $options['allowedMethods']);


		// normalize ['*'] to true
        if (in_array('*', $options['allowedOrigins'])) {
            $options['allowedOrigins'] = true;
        }
        if (in_array('*', $options['allowedHeaders'])) {
            $options['allowedHeaders'] = true;
        }
        if (in_array('*', $options['allowedMethods'])) {
            $options['allowedMethods'] = true;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function isCorsRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Origin') && !$this->isSameHost($request);
    }

    /**
     * {@inheritdoc}
     */
    public function isPreflightRequest(ServerRequestInterface $request): bool
    {
		return strtoupper($request->getMethod()) === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    /**
     * {@inheritdoc}
     */
    public function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $response = $response->withStatus(204);

		return $this->addPreflightRequestHeaders($request, $response);
    }

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
     * {@inheritdoc}
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

        if (in_array($origin, $this->options['allowedOrigins'])) {
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function varyHeader(ResponseInterface $response, $header): ResponseInterface
    {
        if (! $response->hasHeader('Vary')) {
            $response = $response->withHeader('Vary', $header);
        } elseif (! in_array($header, explode(', ', $response->getHeaderLine('Vary')))) {
            $response = $response->withHeader('Vary', $response->getHeaderLine('Vary') . ', ' . $header);
        }

        return $response;
    }

    protected function configureAllowedOrigin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedOrigins'] === true && ! $this->options['supportsCredentials']) {
            // Safe+cacheable, allow everything
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } else if ($this->isSingleOriginAllowed()) {
            // Single origins can be safely set
            $response = $response->withHeader('Access-Control-Allow-Origin', array_values($this->options['allowedOrigins'])[0]);
        } else {
            // For dynamic headers, set the requested Origin header when set and allowed
            if ($this->isCorsRequest($request) && $this->isOriginAllowed($request)) {
                $response = $response->withHeader('Access-Control-Allow-Origin', (string) $request->getHeaderLine('Origin'));
            }

            $response = $this->varyHeader($response, 'Origin');
        }

		return $response;
    }

    protected function isSingleOriginAllowed(): bool
    {
        if ($this->options['allowedOrigins'] === true || ! empty($this->options['allowedOriginsPatterns'])) {
            return false;
        }

        return count($this->options['allowedOrigins']) === 1;
    }

    protected function configureAllowedMethods(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedMethods'] === true) {
            $allowMethods = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            $response = $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $allowMethods = implode(', ', $this->options['allowedMethods']);
        }

        return $response->withHeader('Access-Control-Allow-Methods', $allowMethods);
    }

    protected function configureAllowedHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['allowedHeaders'] === true) {
            $allowHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
            $response = $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->options['allowedHeaders']);
        }

        return $response->withHeader('Access-Control-Allow-Headers', $allowHeaders);
    }

    protected function configureAllowCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['supportsCredentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

		return $response;
    }

    protected function configureExposedHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['exposedHeaders']) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }

		return $response;
    }

    protected function configureMaxAge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->options['maxAge'] !== null) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $this->options['maxAge']);
        }

		return $response;
    }

    protected function isSameHost(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine('Origin') === config('app.base_url');
    }
}
