<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

/*
 * Règles de réécriture du serveur de développement PHP de BlitzPHP
 *
 * Ce script fonctionne avec la commande CLI serve pour aider à exécuter un serveur de développement transparent basé sur le serveur de développement intégré de PHP.
 * Ce fichier essaie simplement d’imiter la fonctionnalité mod_rewrite d’Apache afin que le site fonctionne normalement.
 *
 * @credit <a href="https://codeigniter.com">CodeIgniter</a>
 */

// @codeCoverageIgnoreStart
// Éviter l’exécution de ce fichier lors de la liste des commandes
if (PHP_SAPI === 'cli') {
    return;
}

$uri = urldecode(
    parse_url('https://blitz-php.com' . $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Toutes les requêtes sont gérées par index.php fichier.
$_SERVER['SCRIPT_NAME'] = '/index.php';

// Chemin d’accès complet
$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . ltrim($uri, '/');

// Si $path est un fichier ou un dossier existant dans le dossier public, laissez la demande le gérer normalement.
if ($uri !== '/' && (is_file($path) || is_dir($path))) {
    return false;
}

unset($uri, $path);

// Sinon, nous chargerons le fichier d’index et laisserons le framework gérer la demande à partir d’ici.
require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
// @codeCoverageIgnoreEnd
