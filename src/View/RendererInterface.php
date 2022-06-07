<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View;

/**
 * Interface de rendu d'interface
 *
 * L'interface utilisée pour afficher les vues et/ou les fichiers de thème.
 */
interface RendererInterface
{
    /**
     * Constructeur
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH);

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function addData(array $data = [], ?string $context = null): self;

    /**
     * Construit la sortie en fonction d'un nom de fichier et de tout données déjà définies.
     *
     * Options valides :
     * - cache Nombre de secondes à mettre en cache pour
     * - cache_name Nom à utiliser pour le cache
     *
     * @param string     $view     Nom de fichier de la source de la vue
     * @param array|null $options  Réservé à des utilisations tierces car
     *                             il peut être nécessaire de transmettre des
     *                             informations supplémentaires à d'autres moteurs de modèles.
     * @param bool|null  $saveData Si vrai, enregistre les données pour les appels suivants,
     *                             si faux, nettoie les données après affichage,
     *                             si null, utilise le paramètre de configuration.
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string;

    /**
     * Construit la sortie en fonction d'une chaîne et de tout
     * données déjà définies.
     *
     * @param string $view     Le contenu de la vue
     * @param array  $options  Réservé à des utilisations tierces depuis
     *                         il peut être nécessaire de transmettre des informations supplémentaires
     *                         vers d'autres moteurs de modèles.
     * @param bool   $saveData Indique s'il faut enregistrer les données pour les appels suivants
     */
    public function renderString(string $view, ?array $options = null, bool $saveData = false): string;

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     *
     * @param string $context Le contexte d'échappement pour : html, css, js, url
     *                        Si 'raw', aucun echappement ne se produira
     */
    public function setData(array $data = [], ?string $context = null): self;

    /**
     * Définit une seule donnée de vue.
     *
     * @param mixed  $value
     * @param string $context Le contexte d'échappement pour : html, css, js, url
     *                        Si 'raw', aucun echappement ne se produira
     */
    public function setVar(string $name, $value = null, ?string $context = null): self;

    /**
     * Supprime toutes les données de vue du système.
     */
    public function resetData(): self;
}
