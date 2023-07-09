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

/**
 * Un contrôleur extensible pour fournir une API RESTful pour une ressource.
 */
class ResourceController extends RestController
{
    /**
     * Renvoie un tableau d'objets ressources, eux-mêmes au format tableau
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function index()
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Renvoyer les propriétés d'un objet ressource
     *
     * @param int|string|null $id
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function show($id = null)
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Renvoie un nouvel objet ressource, avec les propriétés par défaut
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function new()
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Créer un nouvel objet ressource, à partir des données envoyées
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function create()
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Renvoyer les propriétés modifiables d'un objet ressource
     *
     * @param int|string|null $id
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function edit($id = null)
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Ajouter ou mettre à jour une ressource de modèle, à partir de données envoyées
     *
     * @param int|string|null $id
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function update($id = null)
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Supprimer l'objet ressource désigné du modèle
     *
     * @param int|string|null $id
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function delete($id = null)
    {
        return $this->respondNotImplemented($this->_translate('notImplemented', [__METHOD__]));
    }

    /**
     * Définir/modifier la représentation de réponse attendue pour les objets renvoyés
     *
     * @param string $format json/xml
     *
     * @return void
     */
    public function setFormat(string $format = 'json')
    {
        if (in_array($format, ['json', 'xml'], true)) {
            $this->returnFormat($format);
        }
    }
}
