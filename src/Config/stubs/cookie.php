<?php

/**
 * ------------------------------------------------- -------------------------
 * Configuration du gestionnaire de cookie
 * ------------------------------------------------- -------------------------
 */

return [
    /**
     * ------------------------------------------------- -------------------------
     * Prefixe des cookies
     * ------------------------------------------------- -------------------------
     *
     * Définissez un préfixe de nom de cookie si vous devez éviter les collisions.
     * 
     * @var string
     */
    'prefix' => env('cookie.prefix', ''),

    /**
     * ------------------------------------------------- -------------------------
     * Horodatage d'expiration du cookie
     * ------------------------------------------------- -------------------------
     *
     * L'horodatage d'expiration par défaut pour les cookies. 
     * Si vous définissez ce paramètre sur "0", le cookie n'aura pas l'attribut "Expire" 
     * et se comportera comme un cookie de session.
     *
     * @var DateTimeInterface|int|string
     */
    'expires' => env('cookie.expires', 0),
    
    /** 
     * --------------------------------------------------------------------------
     * Chemin des cookies
     * --------------------------------------------------------------------------
     *
     * Il s'agira généralement d'une barre oblique.
     * 
     * @var string
     */
    'path' => env('cookie.path', '/'),

    /**
     * --------------------------------------------------------------------------
     * Domaine des cookies
     * --------------------------------------------------------------------------
     *
     * Définissez sur ".votre-domaine.com" pour les cookies à l'échelle du site.
     * 
     * @var string
     */
    'domain' => env('cookie.domain', ''),

    /**
     * --------------------------------------------------------------------------
     * Sécurisé par les cookies
     * --------------------------------------------------------------------------
     *
     * Le cookie ne sera défini que s'il existe une connexion HTTPS sécurisée.
     * 
     * @var bool
     */
    'secure' => env('cookie.secure', false),

    /**
     * --------------------------------------------------------------------------
     * HTTP uniquement
     * --------------------------------------------------------------------------
     *
     * Le cookie ne sera accessible que via HTTP(S) (pas de JavaScript).
     * 
     * @var bool
     */
    'httponly' => env('cookie.httponly', true),

    /**
     * --------------------------------------------------------------------------
     * SameSite
     * --------------------------------------------------------------------------
     *
     * Configurer le paramètre SameSite des cookies. Les valeurs autorisées sont :
     * - None
     * - Lax
     * - Strict
     * - ''
     *
     * Alternativement, vous pouvez utiliser les noms de constante :
     * - `Cookie::SAMESITE_NONE`
     * - `Cookie::SAMESITE_LAX`
     * - `Cookie::SAMESITE_STRICT`
     *
     * La valeur par défaut est "Lax" pour la compatibilité avec les navigateurs modernes. Réglage '''`
     * (chaîne vide) signifie l'attribut SameSite par défaut défini par les navigateurs (`Lax`)
     * sera défini sur les cookies. S'il est défini sur `None`, `$secure` doit également être défini.
     * 
     * @var string
     */
    'samesite' => env('cookie.samesite', 'Lax'),

    /**
     * --------------------------------------------------------------------------
     * Raw
     * --------------------------------------------------------------------------
     *
     * Cet indicateur permet de définir un cookie "brut", c'est-à-dire que son nom et 
     * sa valeur ne sont pas encodés en URL à l'aide de `rawurlencode()`.
     *
     * Si ce paramètre est défini sur "true", les noms de cookies doivent être conformes à 
     * la liste des caractères autorisés de la RFC 2616.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#attributes
     * @see https://tools.ietf.org/html/rfc2616#section-2.2
     * 
     * @var bool
     */
    'raw' => env('cookie.raw', false),
];
