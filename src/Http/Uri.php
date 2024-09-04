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

use BlitzPHP\Exceptions\HttpException;
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
     */
    protected array $segments = [];

    /**
     * Schéma
     */
    protected string $scheme = 'http';

    /**
     * Informations utilisateur
     */
    protected ?string $user = null;

    /**
     * Mot de passe
     */
    protected ?string $password = null;

    /**
     * Hôte
     */
    protected ?string $host = null;

    /**
     * Port
     */
    protected ?int $port = null;

    /**
     * Chemin.
     */
    protected ?string $path = null;

    /**
     * Le nom de n'importe quel fragment.
     */
    protected string $fragment = '';

    /**
     * La chaîne de requête.
     */
    protected array $query = [];

    /**
     * Default schemes/ports.
     */
    protected array $defaultPorts = [
        'http'  => 80,
        'https' => 443,
        'ftp'   => 21,
        'sftp'  => 22,
    ];

    /**
     * Indique si les mots de passe doivent être affichés dans les appels userInfo/authority.
     * La valeur par défaut est false car les URI apparaissent souvent dans les journaux
     */
    protected bool $showPassword = false;

    /**
     * Constructeur.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(?string $uri = null)
    {
        $this->setURI($uri);
    }

    /**
     * Définit et écrase toute information URI actuelle.
     */
    public function setURI(?string $uri = null): self
    {
        if (null !== $uri) {
            $parts = parse_url($uri);

            if ($parts === false) {
                throw HttpException::unableToParseURI($uri);
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
        if (empty($authority = $this->host)) {
            return '';
        }

        if (! empty($userInfo = $this->getUserInfo())) {
            $authority = $userInfo . '@' . $authority;
        }

        // N'ajoute pas de port s'il s'agit d'un port standard pour ce schéma
        if ($this->port !== null && $this->port !== 0 && ! $ignorePort && $this->port !== $this->defaultPorts[$this->scheme]) {
            $authority .= ':' . $this->port;
        }

        $this->showPassword = false;

        return $authority;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo(): string
    {
        $userInfo = $this->user ?: '';

        if ($this->showPassword === true && ($this->password !== null && $this->password !== '' && $this->password !== '0')) {
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
        return $this->host ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return $this->path ?? '';
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

        return $vars === [] ? '' : http_build_query($vars);
    }

    /**
     * {@inheritDoc}
     */
    public function getFragment(): string
    {
        return $this->fragment ?? '';
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
    public function getSegment(int $number, string $default = ''): string
    {
        if ($number < 1) {
            throw HttpException::uriSegmentOutOfRange($number);
        }
        if ($number > count($this->segments) + 1) {
            throw HttpException::uriSegmentOutOfRange($number);
        }

        // Le segment doit traiter le tableau comme basé sur 1 pour l'utilisateur
        // mais nous devons encore gérer un tableau de base zéro.
        $number--;

        return $this->segments[$number] ?? $default;
    }

    /**
     * Définissez la valeur d'un segment spécifique du chemin URI.
     * Permet de définir uniquement des segments existants ou d'en ajouter un nouveau.
     *
     * @param mixed $value (string ou int)
     */
    public function setSegment(int $number, $value)
    {
        if ($number < 1) {
            throw HttpException::uriSegmentOutOfRange($number);
        }

        if ($number > count($this->segments) + 1) {
            throw HttpException::uriSegmentOutOfRange($number);
        }

        // Le segment doit traiter le tableau comme basé sur 1 pour l'utilisateur
        // mais nous devons encore gérer un tableau de base zéro.
        $number--;

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
        $path   = $this->getPath();
        $scheme = $this->getScheme();

        // Si les hôtes correspondent, il faut supposer que l'URL est relative à l'URL de base.
        [$scheme, $path] = $this->changeSchemeAndPath($scheme, $path);

        return static::createURIString(
            $scheme,
            $this->getAuthority(),
            $path, // Les URI absolus doivent utiliser un "/" pour un chemin vide
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
        if ($scheme !== null && $scheme !== '' && $scheme !== '0') {
            $uri .= $scheme . '://';
        }

        if ($authority !== null && $authority !== '' && $authority !== '0') {
            $uri .= $authority;
        }

        if (isset($path) && $path !== '') {
            $uri .= ! str_ends_with($uri, '/')
                ? '/' . ltrim($path, '/')
                : ltrim($path, '/');
        }

        if ($query !== '' && $query !== null) {
            $uri .= '?' . $query;
        }

        if ($fragment !== '' && $fragment !== null) {
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

        if (! isset($parts['path'])) {
            $parts['path'] = $this->getPath();
        }

        if (empty($parts['host']) && $parts['path'] !== '') {
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
        $str          = strtolower($str);
        $this->scheme = preg_replace('#:(//)?$#', '', $str);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withScheme(string $scheme): static
    {
        $uri = clone $this;

        $scheme = strtolower($scheme);

        $uri->scheme = preg_replace('#:(//)?$#', '', $scheme);

        return $uri;
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
    public function withUserInfo(string $user, ?string $password = null): static
    {
        $new = clone $this;

        $new->setUserInfo($user, $password);

        return $new;
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
    public function withHost(string $host): static
    {
        $new = clone $this;

        $new->setHost($host);

        return $new;
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
            throw HttpException::invalidPort($port);
        }

        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPort(?int $port): static
    {
        $new = clone $this;

        $new->setPort($port);

        return $new;
    }

    /**
     * Définit la partie chemin de l'URI.
     */
    public function setPath(string $path): self
    {
        $this->path = $this->filterPath($path);

        $tempPath = trim($this->path, '/');

        $this->segments = ($tempPath === '') ? [] : explode('/', $tempPath);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath(string $path): static
    {
        $new = clone $this;

        $new->setPath($path);

        return $new;
    }

    /**
     * Définit la partie chemin de l'URI en fonction des segments.
     */
    private function refreshPath(): self
    {
        $this->path = $this->filterPath(implode('/', $this->segments));

        $tempPath = trim($this->path, '/');

        $this->segments = ($tempPath === '') ? [] : explode('/', $tempPath);

        return $this;
    }

    /**
     * Définit la partie requête de l'URI, tout en essayant
     * de nettoyer les différentes parties des clés et des valeurs de la requête.
     */
    public function setQuery(string $query): self
    {
        if (str_contains($query, '#')) {
            throw HttpException::malformedQueryString();
        }

        // Ne peut pas avoir de début ?
        if ($query !== '' && str_starts_with($query, '?')) {
            $query = substr($query, 1);
        }

        parse_str($query, $this->query);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withQuery(string $query): static
    {
        $new = clone $this;

        $new->setQuery($query);

        return $new;
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
     * Une méthode pratique pour transmettre un tableau d'éléments en tant que requête
     * partie de l'URI.
     */
    public function withQueryParams(array $query): static
    {
        $uri = clone $this;

        $uri->setQueryArray($query);

        return $uri;
    }

    /**
     * Ajoute un seul nouvel élément à la requête vars.
     */
    public function addQuery(string $key, mixed $value = null): self
    {
        $this->query[$key] = $value;

        return $this;
    }

    /**
     * Supprime une ou plusieurs variables de requête de l'URI.
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
    public function withFragment(string $fragment): static
    {
        $new = clone $this;

        $new->setFragment($fragment);

        return $new;
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
        $path = self::removeDotSegments($path);

        // Correction de certains cas de bord de barre oblique...
        if (str_starts_with($orig, './')) {
            $path = '/' . $path;
        }
        if (str_starts_with($orig, '../')) {
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
            // Les numéros de port valides sont appliqués par les précédents parse_url ou setPort()
            $this->port = $parts['port'];
        }

        if (isset($parts['pass'])) {
            $this->password = $parts['pass'];
        }

        if (isset($parts['path']) && $parts['path'] !== '') {
            $tempPath = trim($parts['path'], '/');

            $this->segments = ($tempPath === '') ? [] : explode('/', $tempPath);
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
        if ($relative->getAuthority() !== '' && $relative->getAuthority() !== '0') {
            $transformed->setAuthority($relative->getAuthority())
                ->setPath($relative->getPath())
                ->setQuery($relative->getQuery());
        } else {
            if ($relative->getPath() === '') {
                $transformed->setPath($this->getPath());

                if ($relative->getQuery() !== '') {
                    $transformed->setQuery($relative->getQuery());
                } else {
                    $transformed->setQuery($this->getQuery());
                }
            } else {
                if (str_starts_with($relative->getPath(), '/')) {
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
        if ($base->getAuthority() !== '' && '' === $base->getPath()) {
            return '/' . ltrim($reference->getPath(), '/ ');
        }

        $path = explode('/', $base->getPath());

        if ('' === $path[0]) {
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
        if ($path === '' || $path === '/') {
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
            if (str_starts_with($path, '/')) {
                $output = '/' . $output;
            }

            // Ajouter une barre oblique à la fin si nécessaire
            if (str_ends_with($path, '/')) {
                $output .= '/';
            }
        }

        return $output;
    }

    /**
     * Modifier le chemin (et le schéma) en supposant que les URI ayant le même hôte que baseURL doivent être relatifs à la configuration du projet.
     *
     * @deprecated Cette methode pourrait etre supprimer
     */
    private function changeSchemeAndPath(string $scheme, string $path): array
    {
        // Vérifier s'il s'agit d'un URI interne
        $config  = (object) config('app');
        $baseUri = new self($config->base_url);

        if (str_starts_with($this->getScheme(), 'http') && $this->getHost() === $baseUri->getHost()) {
            // Vérifier la présence de segments supplémentaires
            $basePath = trim($baseUri->getPath(), '/') . '/';
            $trimPath = ltrim($path, '/');

            if ($basePath !== '/' && ! str_starts_with($trimPath, $basePath)) {
                $path = $basePath . $trimPath;
            }

            // Vérifier si le protocole HTTPS est forcé
            if ($config->force_global_secure_requests) {
                $scheme = 'https';
            }
        }

        return [$scheme, $path];
    }
}
