<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Session\Handlers;

use BlitzPHP\Traits\InstanceConfigTrait;
use Psr\Log\LoggerAwareTrait;
use SessionHandlerInterface;

abstract class BaseHandler implements SessionHandlerInterface
{
    use InstanceConfigTrait;
    use LoggerAwareTrait;

    /**
     * L'empreinte Data.
     */
    protected string $fingerprint = '';

    /**
     * Verrouiller l'espace réservé.
     */
    protected bool $lock = false;

    /**
     * L'ID de la session courante
     */
    protected ?string $sessionID = null;

    /**
     * L'adresse IP de l'utilisateur.
     */
    protected string $ipAddress;

    /**
     * La configuration de session par défaut est remplacée dans la plupart des adaptateurs. Ceux-ci sont
     * les clés communes à tous les adaptateurs. Si elle est remplacée, cette propriété n'est pas utilisée.
     *
     * - `cookie_prefix` @var string
     * 			Préfixe ajouté à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * 			avec une autre configuration de session ou une autre application.
     * - `cookie_domain` @var string Domaine des Cookies.
     * - `cookie_name` @var string Nom du cookie à utiliser.
     * - `cookie_path` @var string Chemin des Cookies.
     * - `cookie_secure` @var bool Cookie sécurisé ?
     * - `matchIP` @var bool Faire correspondre les adresses IP pour les cookies ?
     * - `keyPrefix` @var string prefixe de la cle de session (memcached, redis, database)
     * - `savePath` @var array|string Le "chemin d'enregistrement" de la session varie entre
     * - `expiration` @var int Nombre de secondes jusqu'à la fin de la session.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'savePath'      => [],
        'keyPrefix'     => 'blitz_session:',
        'cookie_prefix' => 'blitz_',
        'cookie_path'   => '/',
        'cookie_domain' => '',
        'cookie_name'   => '',
        'secure'        => false,
        'matchIP'       => false,
        'expiration'    => 7200,
    ];

    /**
     * Initialiser le moteur de session
     *
     * Appelé automatiquement par le frontal de session. Fusionner la configuration d'exécution avec les valeurs par défaut
     * Avant utilisation.
     *
     * @param array<string, mixed> $config Tableau associatif de paramètres pour le moteur
     *
     * @return bool Vrai si le moteur a été initialisé avec succès, faux sinon
     */
    public function init(array $config, string $ipAddress): bool
    {
        $this->setConfig($config);
        $this->ipAddress = $ipAddress;

        return true;
    }

    /**
     * Méthode interne pour forcer la suppression d'un cookie par le client lorsque session_destroy() est appelée.
     */
    protected function destroyCookie(): bool
    {
        return setcookie($this->_config['cookie_name'], '', [
            'expires'  => 1,
            'path'     => $this->_config['cookie_path'],
            'domain'   => $this->_config['cookie_domain'],
            'secure'   => $this->_config['cookie_secure'],
            'httponly' => true,
        ]);
    }

    /**
     * Une méthode factice permettant aux pilotes sans fonctionnalité de verrouillage
     * (bases de données autres que PostgreSQL et MySQL) d'agir comme s'ils acquéraient un verrou.
     */
    protected function lockSession(string $sessionID): bool
    {
        $this->lock = true;

        return true;
    }

    /**
     * Libère le verrou, le cas échéant.
     */
    protected function releaseLock(): bool
    {
        $this->lock = false;

        return true;
    }

    /**
     * Les pilotes autres que celui des "fichiers" n'utilisent pas (n'ont pas besoin d'utiliser)
     * le paramètre INI session.save_path, mais cela conduit à des messages d'erreur déroutants
     * émis par PHP lorsque open() ou write() échoue, car le message contient session.save_path ...
     *
     * Pour contourner le problème, les pilotes appellent cette méthode
     * afin que l'INI soit défini juste à temps pour que le message d'erreur soit correctement généré.
     */
    protected function fail(): bool
    {
        ini_set('session.save_path', $this->_config['savePath']);

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function gc(int $max_lifetime): int|false
    {
        return 1;
    }

    public function logMessage(string $message, $level = 'error')
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }
}
