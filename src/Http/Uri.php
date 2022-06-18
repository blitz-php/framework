<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BlitzPHP\Exceptions\FrameworkException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

/**
 * Abstraction pour un identificateur de ressource uniforme (URI).
 *
 * @credit CodeIgniter 4 <a href="https://codeigniter.com">CodeIgniter\HTTP\URI</a>
 */
class Uri implements UriInterface
{
    /**
     * Sous-délimiteurs utilisés dans les chaînes de requête et les fragments.
     */
    public const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * Caractères non réservés utilisés dans les chemins, les chaînes de requête et les fragments.
     */
    public const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    /**
     * Chaîne d'URI actuelle
     *
     * @var string
     */
    protected $uriString;

    /**
     * Liste des segments d'URI.
     *
     * Commence à 1 au lieu de 0
     *
     * @var array
     */
    protected $segments = [];

    /**
     * Schéma
     *
     * @var string
     */
    protected $scheme = 'http';

    /**
     * Informations utilisateur
     *
     * @var string
     */
    protected $user = '';

    /**
     * Mot de passe
     *
     * @var string
     */
    protected $password = '';

    /**
     * Hôte
     *
     * @var string
     */
    protected $host = '';

    /**
     * Port
     *
     * @var int
     */
    protected $port = 80;

    /**
     * Chemin.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Le nom de n'importe quel fragment.
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * La chaîne de requête.
     *
     * @var array
     */
    protected $query = [];

    /**
     * Default schemes/ports.
     *
     * @var array
     */
    protected $defaultPorts = [
        'http'  => 80,
        'https' => 443,
        'ftp'   => 21,
        'sftp'  => 22,
    ];

    /**
     * Indique si les mots de passe doivent être affichés dans les appels userInfo/authority.
     * La valeur par défaut est false car les URI apparaissent souvent dans les journaux
     *
     * @var bool
     */
    protected $showPassword = false;

    /**
     * Constructeur.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(?string $uri = null)
    {
        $this->setURI($uri);
        $this->port = $_SERVER['SERVER_PORT'] ?? 80;
    }

    /**
     * Définit et écrase toute information URI actuelle.
     */
    public function setURI(?string $uri = null): self
    {
        if (null !== $uri) {
            $parts = parse_url($uri);

            if ($parts === false) {
                throw new FrameworkException('Impossible de parser l\'URI "' . $uri . '"');
            }

            $this->applyParts($parts);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthority(bool $ignorePort = false): string
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;

        if (! empty($this->getUserInfo())) {
            $authority = $this->getUserInfo() . '@' . $authority;
        }

        if (! empty($this->port) && ! $ignorePort) {
            // N'ajoute pas de port s'il s'agit d'un port standard pour ce schéma
            if ($this->port !== $this->defaultPorts[$this->scheme]) {
                $authority .= ':' . $this->port;
            }
        }

        $this->showPassword = false;

        return $authority;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo()
    {
        $userInfo = $this->user;

        if ($this->showPassword === true && ! empty($this->password)) {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * Définit temporairement l'URI pour afficher un mot de passe dans userInfo.
     * Se réinitialisera après le premier appel à l'autorité().
     */
    public function showPassword(bool $val = true): self
    {
        $this->showPassword = $val;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritDoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return (null === $this->path) ? '' : $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(array $options = []): string
    {
        $vars = $this->query;

        if (array_key_exists('except', $options)) {
            if (! is_array($options['except'])) {
                $options['except'] = [$options['except']];
            }

            foreach ($options['except'] as $var) {
                unset($vars[$var]);
            }
        } elseif (array_key_exists('only', $options)) {
            $temp = [];

            if (! is_array($options['only'])) {
                $options['only'] = [$options['only']];
            }

            foreach ($options['only'] as $var) {
                if (array_key_exists($var, $vars)) {
                    $temp[$var] = $vars[$var];
                }
            }

            $vars = $temp;
        }

        return empty($vars) ? '' : http_build_query($vars);
    }

    /**
     * {@inheritDoc}
     */
    public function getFragment(): string
    {
        return null === $this->fragment ? '' : $this->fragment;
    }

    /**
     * Renvoie les segments du chemin sous forme de tableau.
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * Renvoie la valeur d'un segment spécifique du chemin URI.
     *
     * @return string La valeur du segment. Si aucun segment n'est trouvé, lance InvalidArgumentError
     */
    public function getSegment(int $number): string
    {
        // Le segment doit traiter le tableau comme basé sur 1 pour l'utilisateur
        // mais nous devons encore gérer un tableau de base zéro.
        $number--;

        if ($number > count($this->segments)) {
            throw new FrameworkException('Le segment "' . $number . '" n\'est pas dans l\'interval de segment disponible');
        }

        return $this->segments[$number] ?? '';
    }

    /**
     * Définissez la valeur d'un segment spécifique du chemin URI.
     * Permet de définir uniquement des segments existants ou d'en ajouter un nouveau.
     *
     * @param mixed $value (string ou int)
     */
    public function setSegment(int $number, $value)
    {
        // Le segment doit traiter le tableau comme basé sur 1 pour l'utilisateur
        // mais nous devons encore gérer un tableau de base zéro.
        $number--;

        if ($number > count($this->segments) + 1) {
            throw new FrameworkException('Le segment "' . $number . '" n\'est pas dans l\'interval de segment disponible');
        }

        $this->segments[$number] = $value;
        $this->refreshPath();

        return $this;
    }

    /**
     * Renvoie le nombre total de segments.
     */
    public function getTotalSegments(): int
    {
        return count($this->segments);
    }

    /**
     * Autoriser la sortie de l'URI sous forme de chaîne en le convertissant simplement en chaîne
     * ou en écho.
     */
    public function __toString(): string
    {
        return static::createURIString(
            $this->getScheme(),
            $this->getAuthority(),
            $this->getPath(), // Les URI absolus doivent utiliser un "/" pour un chemin vide
            $this->getQuery(),
            $this->getFragment()
        );
    }

    /**
     * Construit une représentation de la chaîne à partir des parties du composant.
     */
    public static function createURIString(?string $scheme = null, ?string $authority = null, ?string $path = null, ?string $query = null, ?string $fragment = null): string
    {
        $uri = '';
        if (! empty($scheme)) {
            $uri .= $scheme . '://';
        }

        if (! empty($authority)) {
            $uri .= $authority;
        }

        if ($path) {
            $uri .= substr($uri, -1, 1) !== '/' ? '/' . ltrim($path, '/') : $path;
        }

        if ($query) {
            $uri .= '?' . $query;
        }

        if ($fragment) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Analyse la chaîne donnée et enregistre les pièces d'autorité appropriées.
     */
    public function setAuthority(string $str): self
    {
        $parts = parse_url($str);

        if (empty($parts['host']) && ! empty($parts['path'])) {
            $parts['host'] = $parts['path'];
            unset($parts['path']);
        }

        $this->applyParts($parts);

        return $this;
    }

    /**
     * Définit le schéma pour cet URI.
     *
     * En raison du grand nombre de schémas valides, nous ne pouvons pas limiter ce
     * uniquement sur http ou https.
     *
     * @see https://www.iana.org/assignments/uri-schemes/uri-schemes.xhtml
     */
    public function setScheme(string $str): self
    {
        $str = strtolower($str);
        $str = preg_replace('#:(//)?$#', '', $str);

        $this->scheme = $str;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withScheme($scheme)
    {
        return $this->setScheme($scheme);
    }

    /**
     * Définit la partie userInfo/Authority de l'URI.
     *
     * @param string $user Le nom d'utilisateur de l'utilisateur
     * @param string $pass Le mot de passe de l'utilisateur
     */
    public function setUserInfo(string $user, string $pass): self
    {
        $this->user     = trim($user);
        $this->password = trim($pass);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withUserInfo($user, $password = null)
    {
        return $this->setUserInfo($user, $password);
    }

    /**
     * Définit le nom d'hôte à utiliser.
     */
    public function setHost(string $str): self
    {
        $this->host = trim($str);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withHost($host)
    {
        return $this->setHost($host);
    }

    /**
     * Définit la partie port de l'URI.
     */
    public function setPort(?int $port = null): self
    {
        if (null === $port) {
            return $this;
        }

        if ($port <= 0 || $port > 65535) {
            throw new FrameworkException('Le port "' . $port . '" est invalide');
        }

        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPort($port)
    {
        return $this->setPort($port);
    }

    /**
     * Définit la partie chemin de l'URI.
     */
    public function setPath(string $path): self
    {
        $this->path = $this->filterPath($path);

        $this->segments = explode('/', $this->path);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath($path)
    {
        return $this->setPath($path);
    }

    /**
     * Définit la partie chemin de l'URI en fonction des segments.
     */
    public function refreshPath(): self
    {
        $this->path = $this->filterPath(implode('/', $this->segments));

        $this->segments = explode('/', $this->path);

        return $this;
    }

    /**
     * Définit la partie requête de l'URI, tout en essayant
     * de nettoyer les différentes parties des clés et des valeurs de la requête.
     */
    public function setQuery(string $query): self
    {
        if (strpos($query, '#') !== false) {
            throw new FrameworkException('La chaine de requete est mal formée');
        }

        // Ne peut pas avoir de début ?
        if (! empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        parse_str($query, $this->query);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withQuery($query)
    {
        return $this->setQuery($query);
    }

    /**
     * Une méthode pratique pour transmettre un tableau d'éléments en tant que requête
     * partie de l'URI.
     */
    public function setQueryArray(array $query): self
    {
        $query = http_build_query($query);

        return $this->setQuery($query);
    }

    /**
     * Ajoute un seul nouvel élément à la requête vars.
     *
     * @param mixed $value
     */
    public function addQuery(string $key, $value = null): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    /**
     * Supprime une ou plusieurs variables de requête de l'URI.
     *
     * @param array ...$params
     */
    public function stripQuery(...$params): self
    {
        foreach ($params as $param) {
            unset($this->query[$param]);
        }

        return $this;
    }

    /**
     * Filtre les variables de requête afin que seules les clés transmises
     * sont gardés. Le reste est supprimé de l'objet.
     *
     * @param array ...$params
     */
    public function keepQuery(...$params): self
    {
        $temp = [];

        foreach ($this->query as $key => $value) {
            if (! in_array($key, $params, true)) {
                continue;
            }

            $temp[$key] = $value;
        }

        $this->query = $temp;

        return $this;
    }

    /**
     * Définit la partie fragment de l'URI.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public function setFragment(string $string): self
    {
        $this->fragment = trim($string, '# ');

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withFragment($fragment)
    {
        return $this->setFragment($fragment);
    }

    /**
     * Encode tous les caractères dangereux et supprime les segments de points.
     * Bien que les segments de points aient des utilisations valides selon la spécification,
     * cette classe ne les autorise pas.
     */
    protected function filterPath(?string $path = null): string
    {
        $orig = $path;

        // Décode/normalise les caractères codés en pourcentage afin que
        // nous pouissions toujours avoir une correspondance pour les routes, etc.
        $path = urldecode($path);

        // Supprimer les segments de points
        $path = $this->removeDotSegments($path);

        // Correction de certains cas de bord de barre oblique...
        if (strpos($orig, './') === 0) {
            $path = '/' . $path;
        }
        if (strpos($orig, '../') === 0) {
            $path = '/' . $path;
        }

        // Encode les caractères
        $path = preg_replace_callback(
            '/(?:[^' . static::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            static fn (array $matches) => rawurlencode($matches[0]),
            $path
        );

        return $path;
    }

    /**
     * Enregistre nos pièces à partir d'un appel parse_url.
     */
    protected function applyParts(array $parts)
    {
        if (! empty($parts['host'])) {
            $this->host = $parts['host'];
        }
        if (! empty($parts['user'])) {
            $this->user = $parts['user'];
        }
        if (! empty($parts['path'])) {
            $this->path = $this->filterPath($parts['path']);
        }
        if (! empty($parts['query'])) {
            $this->setQuery($parts['query']);
        }
        if (! empty($parts['fragment'])) {
            $this->fragment = $parts['fragment'];
        }

        if (isset($parts['scheme'])) {
            $this->setScheme(rtrim($parts['scheme'], ':/'));
        } else {
            $this->setScheme('http');
        }

        if (isset($parts['port'])) {
            if (null !== $parts['port']) {
                // Les numéros de port valides sont appliqués par les précédents parse_url ou setPort()
                $port       = $parts['port'];
                $this->port = $port;
            }
        }

        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }

        if (! empty($parts['path'])) {
            $this->segments = explode('/', trim($parts['path'], '/'));
        }
    }

    /**
     * Combine une chaîne d'URI avec celle-ci en fonction des règles définies dans
     * RFC 3986 Section 2
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2
     */
    public function resolveRelativeURI(string $uri): self
    {
        /*
         * REMARQUE : Nous n'utilisons pas removeDotSegments dans cet
         * algorithme puisque c'est déjà fait par cette ligne !
         */
        $relative = new self();
        $relative->setURI($uri);

        if ($relative->getScheme() === $this->getScheme()) {
            $relative->setScheme('');
        }

        $transformed = clone $relative;

        // 5.2.2 Transformer les références dans une méthode non stricte (pas de schéma)
        if (! empty($relative->getAuthority())) {
            $transformed->setAuthority($relative->getAuthority())
                ->setPath($relative->getPath())
                ->setQuery($relative->getQuery());
        } else {
            if ($relative->getPath() === '') {
                $transformed->setPath($this->getPath());

                if ($relative->getQuery()) {
                    $transformed->setQuery($relative->getQuery());
                } else {
                    $transformed->setQuery($this->getQuery());
                }
            } else {
                if (strpos($relative->getPath(), '/') === 0) {
                    $transformed->setPath($relative->getPath());
                } else {
                    $transformed->setPath($this->mergePaths($this, $relative));
                }

                $transformed->setQuery($relative->getQuery());
            }

            $transformed->setAuthority($this->getAuthority());
        }

        $transformed->setScheme($this->getScheme());

        $transformed->setFragment($relative->getFragment());

        return $transformed;
    }

    /**
     * Étant donné 2 chemins, les fusionnera conformément aux règles énoncées dans RFC 2986, section 5.2
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.3
     */
    protected function mergePaths(self $base, self $reference): string
    {
        if (! empty($base->getAuthority()) && empty($base->getPath())) {
            return '/' . ltrim($reference->getPath(), '/ ');
        }

        $path = explode('/', $base->getPath());

        if (empty($path[0])) {
            unset($path[0]);
        }

        array_pop($path);
        $path[] = $reference->getPath();

        return implode('/', $path);
    }

    /**
     * Utilisé lors de la résolution et de la fusion de chemins pour interpréter et
     * supprimer correctement les segments à un ou deux points du chemin selon RFC 3986 Section 5.2.4
     *
     * @see http://tools.ietf.org/html/rfc3986#section-5.2.4
     */
    public static function removeDotSegments(string $path): string
    {
        if (empty($path) || $path === '/') {
            return $path;
        }

        $output = [];

        $input = explode('/', $path);

        if (empty($input[0])) {
            unset($input[0]);
            $input = array_values($input);
        }

        // Ce n'est pas une représentation parfaite de la
        // RFC, mais correspond à la plupart des cas et est joli
        // beaucoup ce que Guzzle utilise. Devrait être assez bon
        // pour presque tous les cas d'utilisation réels.
        foreach ($input as $segment) {
            if ($segment === '..') {
                array_pop($output);
            } elseif ($segment !== '.' && $segment !== '') {
                $output[] = $segment;
            }
        }

        $output = implode('/', $output);
        $output = ltrim($output, '/ ');

        if ($output !== '/') {
            // Ajouter une barre oblique au début si nécessaire
            if (strpos($path, '/') === 0) {
                $output = '/' . $output;
            }

            // Ajouter une barre oblique à la fin si nécessaire
            if (substr($path, -1, 1) === '/') {
                $output .= '/';
            }
        }

        return $output;
    }
}
