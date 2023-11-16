<@php

namespace {namespace};

use {useStatement};

class {class} extends {extends}
{
<?php if (! empty($invokable)): ?>
	/**
	 * Traitement
	 *
	 * @return mixed
	 */
	public function __invoke()
    {
        //
    }
<?php elseif ($type === 'controller'): ?>
    /**
     * Renvoie un tableau d'objets ressources, eux-mêmes au format tableau
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Renvoyer les propriétés d'un objet ressource
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Renvoie un nouvel objet ressource, avec les propriétés par défaut
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Créer un nouvel objet ressource, à partir des données envoyées
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Renvoyer les propriétés modifiables d'un objet ressource
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Ajouter ou mettre à jour une ressource de modèle, à partir de données envoyées"
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Supprimer l'objet ressource désigné du modèle
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
<?php elseif ($type === 'presenter'): ?>
    /**
     * Présenter une vue des objets de ressource
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Présenter une vue pour présenter un objet de ressource spécifique
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Présenter une vue pour présenter un nouvel objet de ressource unique
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Traiter la création/insertion d'un nouvel objet ressource.
     * Cela devrait être un POST.
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Présenter une vue pour modifier les propriétés d'un objet de ressource spécifique
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Traiter la mise à jour, totale ou partielle, d'un objet ressource spécifique.
     * Cela devrait être un POST.
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Présenter une vue pour confirmer la suppression d'un objet de ressource spécifique
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function remove($id = null)
    {
        //
    }

    /**
     * Traiter la suppression d'un objet de ressource spécifique
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
<?php else: ?>
    public function index()
    {
        //
    }
<?php endif ?>
}
