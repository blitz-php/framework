<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Controllers;

use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Validation\Validation;
use Dimtrovich\Validation\ValidatedInput;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * Contrôleur de base pour toute application BlitzPHP
 */
abstract class BaseController
{
    /**
     * Helpers qui seront automatiquement chargés lors de l'instanciation de la classe.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Le modèle qui contient les données de cette ressource
     *
     * @var string|null
     */
    protected $modelName;

    /**
     * Le modèle qui contient les données de cette ressource
     *
     * @var object|null
     */
    protected $model;

    /**
     * Instance de l'objet Request principal.
     *
     * @var Request
     */
    protected $request;

    /**
     * Instance de l'objet de Response principal.
     *
     * @var Response
     */
    protected $response;

    /**
     * Instance de logger à utiliser.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Devrait appliquer l'accès HTTPS pour toutes les méthodes de ce contrôleur.
     *
     * @var int Nombre de secondes pour définir l'en-tête HSTS
     */
    protected $forceHTTPS = 0;

    /**
     * Constructor.
     *
     * @throws HttpException
     */
    public function initialize(ServerRequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->request  = $request; // @phpstan-ignore-line
        $this->response = $response; // @phpstan-ignore-line
        $this->logger   = $logger;

        Services::container()->set(Request::class, $request);
        Services::container()->set(Response::class, $response);

        if ($this->forceHTTPS > 0) {
            $this->forceHTTPS($this->forceHTTPS);
        }

        $this->getModel();

        if (! empty($this->helpers)) {
            helper($this->helpers);
        }
    }

    /**
     * Validation des donnees de la requete actuelle
     */
    protected function validate(array $rules, array $messages = []): ValidatedInput
    {
        return $this->request->validate($rules, $messages);
    }

    /**
     * Cree un validateur avec les donnees de la requete actuelle
     */
    protected function validation(array $rules, array $messages = []): Validation
    {
        return $this->request->validation($rules, $messages);
    }

    /**
     * Définissez ou modifiez le modèle auquel ce contrôleur est lié.
     * Étant donné le nom ou l'objet, déterminer l'autre.
     *
     * @param object|string|null $which
     */
    protected function setModel($which = null)
    {
        if ($which) {
            $this->model     = is_object($which) ? $which : null;
            $this->modelName = is_object($which) ? null : $which;
        }

        if (empty($this->model) && ! empty($this->modelName) && class_exists($this->modelName)) {
            $this->model = model($this->modelName);
        }

        if (! empty($this->model) && empty($this->modelName)) {
            $this->modelName = get_class($this->model);
        }
    }

    /**
     * Une méthode pratique à utiliser lorsque vous devez vous assurer qu'un seul
     * La méthode est accessible uniquement via HTTPS. Si ce n'est pas le cas, alors une redirection
     * reviendra à cette méthode et l'en-tête HSTS sera envoyé
     * pour que les navigateurs modernes transforment automatiquement les requêtes.
     *
     * @param int $duration Le nombre de secondes pendant lesquelles ce lien doit être
     *                      considéré comme sûr pour. Uniquement avec en-tête HSTS.
     *                      La valeur par défaut est 1 an.
     *
     * @throws HttpException
     */
    protected function forceHTTPS(int $duration = 31536000)
    {
        force_https($duration, $this->request, $this->response);
    }

    /**
     * Fournit un moyen simple de se lier à la classe principale de BlitzPHP
     * et de lui indiquer la durée de mise en cache de la page actuelle.
     */
    protected function cachePage(int $time)
    {
        Services::responsecache()->setTtl($time);
    }

    /**
     * Recherche le model par defaut (à base de son nom) du controleur
     *
     * @throws ReflectionException
     */
    private function getModel()
    {
        if (! empty($this->modelName)) {
            $model = $this->modelName;
        } else {
            $model = str_replace('Controller', 'Model', static::class);
        }

        if (class_exists($model)) {
            $this->setModel($model);
        }
    }
}
