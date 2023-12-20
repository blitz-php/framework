<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Http\CorsBuilder;
use BlitzPHP\Utilities\String\Text;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware cors pour gerer les requetes d'origine croisees
 *
 * @credit <a href="https://github.com/agungsugiarto/codeigniter4-cors">CodeIgniter4 Cors</a>
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors implements MiddlewareInterface
{
	/**
     * --------------------------------------------------------------------------
     * En-têtes HTTP autorisés
     * --------------------------------------------------------------------------
     *
     * Indique les en-têtes HTTP autorisés.
     */
    public array $allowedHeaders = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Méthodes HTTP autorisées
     * --------------------------------------------------------------------------
     *
     * Indique les méthodes HTTP autorisées.
     */
    public array $allowedMethods = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Origine des requêtes autorisées
     * --------------------------------------------------------------------------
     *
     * Indique quelles origines sont autorisées à effectuer des demandes.
     * Les motifs sont également acceptés, par exemple *.foo.com
     */
    public array $allowedOrigins = ['*'];

    /**
     * --------------------------------------------------------------------------
     * Modèles d'origines autorisés
     * --------------------------------------------------------------------------
     *
     * Les motifs qui peuvent être utilisés avec `preg_match` pour correspondre à l'origine.
     */
    public array $allowedOriginsPatterns = [];

    /**
     * --------------------------------------------------------------------------
     * En-têtes exposés
     * --------------------------------------------------------------------------
     *
     * En-têtes qui sont autorisés à être exposés au serveur web.
     */
    public array $exposedHeaders = [];

    /**
     * --------------------------------------------------------------------------
     * Âge maximum
     * --------------------------------------------------------------------------
     *
     * Indique la durée pendant laquelle les résultats d'une demande de contrôle en amont peuvent être mis en cache.
     */
    public int $maxAge = 0;

    /**
     * --------------------------------------------------------------------------
     * Si la réponse peut être exposée ou non lorsque des informations d'identification sont présentes
     * --------------------------------------------------------------------------
     *
     * Indique si la réponse à la demande peut être exposée lorsque l'indicateur d'informations d'identification est vrai.
	 * Lorsqu'il est utilisé dans le cadre d'une réponse à une demande de contrôle en amont, il indique si la demande proprement dite peut être effectuée en utilisant des informations d'identification.
	 * Notez que les requêtes GET simples ne sont pas contrôlées au préalable, et donc si une requête est faite pour une ressource avec des informations d'identification, si cet en-tête n'est pas renvoyé avec la ressource, la réponse est ignorée par le navigateur et n'est pas renvoyée au contenu web.
     */
    public bool $supportsCredentials = false;

	protected CorsBuilder $cors;

	/**
     * Constructor.
     */
    public function __construct(array $config = [])
    {
		$params = (array) config('cors', []);
		$config = array_merge($params, $config);

		foreach ($config as $key => $value) {
			$key = Text::camel($key);

			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}

        $this->cors = new CorsBuilder([
            'allowedHeaders'         => $this->allowedHeaders,
            'allowedMethods'         => $this->allowedMethods,
            'allowedOrigins'         => $this->allowedOrigins,
            'allowedOriginsPatterns' => $this->allowedOriginsPatterns,
            'exposedHeaders'         => $this->exposedHeaders,
            'maxAge'                 => $this->maxAge,
            'supportsCredentials'    => $this->supportsCredentials,
        ]);
    }

	/**
     * Execution du middleware
     */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->cors->isPreflightRequest($request)) {
            $response = $this->cors->handlePreflightRequest($request);

            return $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

		$response = $handler->handle($request);

		if ($request->getMethod() === 'OPTIONS') {
            $response = $this->cors->varyHeader($response, 'Access-Control-Request-Method');
        }

		if (! $response->hasHeader('Access-Control-Allow-Origin')) {
            $response =  $this->cors->addActualRequestHeaders($request, $response);
        }

		return $response;
	}
}
