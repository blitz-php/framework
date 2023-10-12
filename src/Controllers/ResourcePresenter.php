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

use Psr\Http\Message\ResponseInterface;

/**
 * Un contrôleur extensible pour aider à fournir une interface utilisateur pour une ressource.
 */
class ResourcePresenter extends ApplicationController
{
    /**
     * Présenter une vue des objets de ressource
     *
     * @return \Psr\Http\Message\ResponseInterface|string|void
     */
    public function index()
    {
        return lang('Rest.notImplemented', ['index']);
    }

    /**
     * Présenter une vue pour présenter un objet de ressource spécifique
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface|string|void
     */
    public function show($id = null)
    {
        return lang('Rest.notImplemented', ['show']);
    }

    /**
     * Présenter une vue pour présenter un nouvel objet de ressource unique
     *
     * @return ResponseInterface|string|void
     */
    public function new()
    {
        return lang('Rest.notImplemented', ['new']);
    }

    /**
     * Traiter la création/insertion d'un nouvel objet ressource.
     * Cela devrait être un POST.
     *
     * @return ResponseInterface|string|void
     */
    public function create()
    {
        return lang('Rest.notImplemented', ['create']);
    }

    /**
     * Présenter une vue pour modifier les propriétés d'un objet de ressource spécifique
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface|string|void
     */
    public function edit($id = null)
    {
        return lang('Rest.notImplemented', ['edit']);
    }

    /**
     * Traiter la mise à jour, totale ou partielle, d'un objet ressource spécifique.
     * Cela devrait être un POST.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface|string|void
     */
    public function update($id = null)
    {
        return lang('Rest.notImplemented', ['update']);
    }

    /**
     * Présenter une vue pour confirmer la suppression d'un objet de ressource spécifique
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface|string|void
     */
    public function remove($id = null)
    {
        return lang('Rest.notImplemented', ['remove']);
    }

    /**
     * Traiter la suppression d'un objet de ressource spécifique
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface|string|void
     */
    public function delete($id = null)
    {
        return lang('Rest.notImplemented', ['delete']);
    }
}
