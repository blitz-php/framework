<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache\Handlers;

use BlitzPHP\Cache\CacheInterface;
use BlitzPHP\Cache\InvalidArgumentException;
use BlitzPHP\Traits\InstanceConfigTrait;
use BlitzPHP\Utilities\Helpers;
use Closure;
use DateInterval;
use Exception;

abstract class BaseHandler implements CacheInterface
{
    use InstanceConfigTrait;

    /**
     * @var string
     */
    protected const CHECK_KEY = 'key';

    /**
     * @var string
     */
    protected const CHECK_VALUE = 'value';

    /**
     * Caractères réservés qui ne peuvent pas être utilisés dans une clé ou une étiquette. Peut être remplacé par le fichier config.
     * From https://github.com/symfony/cache-contracts/blob/c0446463729b89dd4fa62e9aeecc80287323615d/ItemInterface.php#L43
     */
    protected static string $reservedCharacters = '{}()/\@:';

    /**
     * Préfixe à appliquer aux clés de cache.
     * Ne peut pas être utilisé par tous les gestionnaires.
     */
    protected string $prefix;

    /**
     * La configuration de cache par défaut est remplacée dans la plupart des adaptateurs de cache. Ceux-ci sont
     * les clés communes à tous les adaptateurs. Si elle est remplacée, cette propriété n'est pas utilisée.
     *
     * - `duration` Spécifiez combien de temps durent les éléments de cette configuration de cache.
     * - `groups` Liste des groupes ou 'tags' associés à chaque clé stockée dans cette configuration.
     * 			pratique pour supprimer un groupe complet du cache.
     * - `prefix` Préfixe ajouté à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * 			avec une autre configuration de cache ou une autre application.
     * - `warnOnWriteFailures` Certains moteurs, tels que ApcuEngine, peuvent déclencher des avertissements en cas d'échecs d'écriture.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'duration'            => 3600,
        'groups'              => [],
        'prefix'              => 'blitz_',
        'warnOnWriteFailures' => true,
    ];

    /**
     * Contient la chaîne compilée avec tous les groupes
     * préfixes à ajouter à chaque clé dans ce moteur de cache
     */
    protected string $_groupPrefix = '';

    /**
     * Initialiser le moteur de cache
     *
     * Appelé automatiquement par le frontal du cache. Fusionner la configuration d'exécution avec les valeurs par défaut
     * Avant utilisation.
     *
     * @param array<string, mixed> $config Tableau associatif de paramètres pour le moteur
     *
     * @return bool Vrai si le moteur a été initialisé avec succès, faux sinon
     */
    public function init(array $config = []): bool
    {
        $this->setConfig($config);

        if (! empty($this->_config['groups'])) {
            sort($this->_config['groups']);
            $this->_groupPrefix = str_repeat('%s_', count($this->_config['groups']));
        }
        if (! is_numeric($this->_config['duration'])) {
            $this->_config['duration'] = strtotime($this->_config['duration']) - time();
        }

        return true;
    }

    /**
     * Modifie les caractères reservés
     */
    public function setReservedCharacters(string $reservedCharacters)
    {
        self::$reservedCharacters = $reservedCharacters;
    }

    /**
     * Assurez-vous de la validité de la clé de cache donnée.
     *
     * @throws InvalidArgumentException Quand la clé n'est pas valide
     */
    public function ensureValidKey(string $key): void
    {
        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('Une clé de cache doit être une chaîne non vide.');
        }

        $reserved = self::$reservedCharacters;
        if ($reserved && strpbrk($key, $reserved) !== false) {
            throw new InvalidArgumentException('La clé de cache contient des caractères réservés ' . $reserved);
        }
    }

    /**
     * Assurez-vous de la validité du type d'argument et des clés de cache.
     *
     * @param iterable $iterable L'itérable à vérifier.
     * @param string   $check    Indique s'il faut vérifier les clés ou les valeurs.
     *
     * @throws InvalidArgumentException
     */
    protected function ensureValidType($iterable, string $check = self::CHECK_VALUE): void
    {
        if (! is_iterable($iterable)) {
            throw new InvalidArgumentException(sprintf(
                'Un cache %s doit être soit un tableau soit un Traversable.',
                $check === self::CHECK_VALUE ? 'key set' : 'set'
            ));
        }

        foreach ($iterable as $key => $value) {
            if ($check === self::CHECK_VALUE) {
                $this->ensureValidKey($value);
            } else {
                $this->ensureValidKey($key);
            }
        }
    }

    /**
     * Obtenez un élément du cache ou exécutez la fermeture donnée et stockez le résultat.
     *
     * @param Closure $callback Valeur de retour du rappel
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $this->set($key, $value = $callback(), $ttl);

        return $value;
    }

    /**
     * Supprime les éléments du magasin de cache correspondant à un modèle donné.
     *
     * @param string $pattern Modèle de style global des éléments du cache
     *
     * @throws Exception
     */
    public function deleteMatching(string $pattern)
    {
        throw new Exception('La méthode deleteMatching n\'est pas implémentée.');
    }

    /**
     * Obtient plusieurs éléments de cache par leurs clés uniques.
     *
     * @param iterable $keys    Une liste de clés pouvant être obtenues en une seule opération.
     * @param mixed    $default Valeur par défaut à renvoyer pour les clés qui n'existent pas.
     *
     * @return iterable Une liste de paires clé-valeur. Les clés de cache qui n'existent pas ou qui sont obsolètes auront $default comme valeur.
     *
     * @throws InvalidArgumentException Si $keys n'est ni un tableau ni un Traversable,
     *                                  ou si l'une des clés n'a pas de valeur légale.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->ensureValidType($keys);

        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * Persiste un ensemble de paires clé => valeur dans le cache, avec un TTL facultatif.
     *
     * @param iterable              $values Une liste de paires clé => valeur pour une opération sur plusieurs ensembles.
     * @param DateInterval|int|null $ttl    Facultatif. La valeur TTL de cet élément. Si aucune valeur n'est envoyée et
     *                                      le pilote prend en charge TTL, la bibliothèque peut définir une valeur par défaut
     *                                      pour cela ou laissez le conducteur s'en occuper.
     *
     * @return bool Vrai en cas de succès et faux en cas d'échec.
     *
     * @throws InvalidArgumentException Si $values n'est ni un tableau ni un Traversable,
     *                                  ou si l'une des valeurs $ n'est pas une valeur légale.
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $this->ensureValidType($values, self::CHECK_KEY);

        if ($ttl !== null) {
            $restore = $this->getConfig('duration');
            $this->setConfig('duration', $ttl);
        }

        try {
            foreach ($values as $key => $value) {
                $success = $this->set($key, $value);
                if ($success === false) {
                    return false;
                }
            }

            return true;
        } finally {
            if (isset($restore)) {
                $this->setConfig('duration', $restore);
            }
        }
    }

    /**
     * Supprime plusieurs éléments du cache sous forme de liste
     *
     * Il s'agit d'une tentative de meilleur effort. Si la suppression d'un élément
     * créer une erreur, elle sera ignorée et tous les éléments seront
     * être tenté.
     *
     * @param iterable $keys Une liste de clés basées sur des chaînes à supprimer.
     *
     * @return bool Vrai si les éléments ont été supprimés avec succès. Faux s'il y a eu une erreur.
     *
     * @throws InvalidArgumentException Si $keys n'est ni un tableau ni un Traversable,
     *                                  ou si l'une des clés $ n'a pas de valeur légale.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->ensureValidType($keys);

        $result = true;

        foreach ($keys as $key) {
            if (! $this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Détermine si un élément est présent dans le cache.
     *
     * REMARQUE : Il est recommandé que has() ne soit utilisé qu'à des fins de type réchauffement du cache
     * et à ne pas utiliser dans vos opérations d'applications en direct pour get/set, car cette méthode
     * est soumis à une condition de concurrence où votre has() renverra vrai et immédiatement après,
     * un autre script peut le supprimer, rendant l'état de votre application obsolète.
     *
     * @param mixed $key
     *
     * @throws InvalidArgumentException Si la chaîne $key n'est pas une valeur légale.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Récupère la valeur d'une clé donnée dans le cache.
     */
    abstract public function get(string $key, mixed $default = null): mixed;

    /**
     * Persiste les données dans le cache, référencées de manière unique par la clé donnée avec un temps TTL d'expiration facultatif.
     *
     * @param DateInterval|int|null $ttl Facultatif. La valeur TTL de cet élément. Si aucune valeur n'est envoyée et
     *                                   le pilote prend en charge TTL, la bibliothèque peut définir une valeur par défaut
     *                                   pour cela ou laissez le conducteur s'en occuper.
     *
     * @return bool Vrai en cas de succès et faux en cas d'échec.
     */
    abstract public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function increment(string $key, int $offset = 1);

    /**
     * {@inheritDoc}
     */
    abstract public function decrement(string $key, int $offset = 1);

    /**
     * {@inheritDoc}
     */
    abstract public function delete(string $key): bool;

    /**
     * {@inheritDoc}
     */
    abstract public function clear(): bool;

    /**
     * {@inheritDoc}
     */
    public function add(string $key, mixed $value): bool
    {
        $cachedValue = $this->get($key);
        if ($cachedValue === null) {
            return $this->set($key, $value);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function clearGroup(string $group): bool;

	/**
	 * {@inheritDoc}
	 */
	public function info()
	{
		return null;
	}

    /**
     * Effectue toute initialisation pour chaque groupe est nécessaire
     * et renvoie la "valeur du groupe" pour chacun d'eux, c'est
     * le jeton représentant chaque groupe dans la clé de cache
     *
     * @return string[]
     */
    public function groups(): array
    {
        return $this->_config['groups'];
    }

    /**
     * Génère une clé pour l'utilisation du backend du cache.
     *
     * Si la clé demandée est valide, la valeur du préfixe de groupe et le préfixe du moteur sont appliqués.
     * Les espaces blancs dans les clés seront remplacés.
     *
     * @param string $key la clé transmise
     *
     * @return string Clé préfixée avec des caractères potentiellement dangereux remplacés.
     *
     * @throws InvalidArgumentException Si la valeur de la clé n'est pas valide.
     */
    protected function _key($key): string
    {
        $this->ensureValidKey($key);

        $prefix = '';
        if ($this->_groupPrefix) {
            $prefix = md5(implode('_', $this->groups()));
        }
        $key = preg_replace('/[\s]+/', '_', $key);

        return $this->_config['prefix'] . $prefix . $key;
    }

    /**
     * Les moteurs de cache peuvent déclencher des avertissements s'ils rencontrent des pannes pendant le fonctionnement,
     * si l'option warnOnWriteFailures est définie sur true.
     */
    protected function warning(string $message): void
    {
        if ($this->getConfig('warnOnWriteFailures') !== true) {
            return;
        }

        Helpers::triggerWarning($message);
    }

    /**
     * Convertir les différentes expressions d'une valeur TTL en durée en secondes
     *
     * @param DateInterval|int|null $ttl La valeur TTL de cet élément. Si null est envoyé,
     *                                   La durée par défaut du conducteur sera utilisée.
     */
    protected function duration(DateInterval|int|null $ttl): int
    {
        if ($ttl === null) {
            return $this->_config['duration'];
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        return (int) $ttl->format('%s');
    }
}
