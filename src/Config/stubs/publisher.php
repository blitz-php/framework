<?php

/**
 * Configuration du Publisher
 *
 * Définit les restrictions de sécurité de base pour la classe Publisher afin d'éviter les abus en injectant des fichiers malveillants dans un projet.
 */

return [
    /**
     * Une liste de destinations autorisées avec une (pseudo-)regex de fichiers autorisés pour chaque destination.
     * Les tentatives de publication dans des répertoires ne figurant pas dans cette liste entraîneront une PublisherException.
     * Les fichiers qui ne correspondent pas au modèle entraîneront l'échec de la copie/fusion.
     *
     * @var array<string,string>
     */
    'restrictions' => [
        ROOTPATH => '*',
        WEBROOT  => '#\.(s?css|js|map|html?|xml|json|webmanifest|ttf|eot|woff2?|gif|jpe?g|tiff?|png|webp|bmp|ico|svg)$#i',
    ],
];
