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

/**
 * Classe de négociation de contenu HTTP
 *
 * Cette classe gère la négociation de contenu HTTP selon les spécifications RFC 7231.
 * Elle permet de déterminer le meilleur type de contenu, jeu de caractères, encodage
 * ou langue en fonction des préférences du client et des capacités du serveur.
 */
class Negotiator
{
    /**
     * Instance de la requête serveur
     *
     * @var ServerRequest
     */
    protected $request;

    /**
     * Constructeur de la classe Negotiator
     *
     * @param ServerRequest|null $request Instance optionnelle de la requête serveur
     */
    public function __construct(?ServerRequest $request = null)
    {
        if (null !== $request) {
            $this->request = $request;
        }
    }

    /**
     * Définit l'instance de requête pour récupérer les en-têtes
     *
     * @param ServerRequest $request Instance de la requête serveur
     */
    public function setRequest(ServerRequest $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Détermine le meilleur type de média (Content-Type) à utiliser
     *
     * Compare les types supportés par l'application avec les types demandés
     * par le client dans l'en-tête Accept.
     *
     * @param array $supported Tableau des types de média supportés par l'application
     * @param bool $strictMatch Si true, retourne une chaîne vide quand aucun match n'est trouvé.
     *                         Si false, retourne le premier élément supporté.
	 *
     * @return string Le meilleur type de média correspondant
     */
    public function media(array $supported, bool $strictMatch = false): string
    {
        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept'), true, $strictMatch);
    }

    /**
     * Détermine le meilleur jeu de caractères à utiliser
     *
     * Compare les jeux de caractères supportés par l'application avec ceux demandés
     * par le client dans l'en-tête Accept-Charset.
     *
     * @param array $supported Tableau des jeux de caractères supportés par l'application
	 *
     * @return string Le meilleur jeu de caractères correspondant
     */
    public function charset(array $supported): string
    {
        $match = $this->getBestMatch($supported, $this->request->getHeaderLine('accept-charset'), false, true);

        // Si aucun jeu de caractères n'est trouvé, on utilise la valeur par défaut utf-8
        // comme autorisé par la RFC
        if ($match === '' || $match === '0') {
            return 'utf-8';
        }

        return $match;
    }

    /**
     * Détermine le meilleur type d'encodage à utiliser
     *
     * Compare les encodages supportés par l'application avec ceux demandés
     * par le client dans l'en-tête Accept-Encoding.
     *
     * @param array $supported Tableau des encodages supportés par l'application
	 *
     * @return string Le meilleur encodage correspondant
     */
    public function encoding(array $supported = []): string
    {
        $supported[] = 'identity';

        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-encoding'));
    }

    /**
     * Détermine la meilleure langue à utiliser
     *
     * Compare les langues supportées par l'application avec celles demandées
     * par le client dans l'en-tête Accept-Language.
     *
     * @param array $supported Tableau des langues supportées par l'application
	 *
     * @return string La meilleure langue correspondante
     */
    public function language(array $supported): string
    {
        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-language'));
    }

    // --------------------------------------------------------------------
    // Méthodes utilitaires
    // --------------------------------------------------------------------

    /**
     * Effectue la comparaison entre les valeurs supportées par l'application
     * et les valeurs demandées dans un en-tête Accept* spécifique
     *
     * Portions de ce code basées sur la bibliothèque Aura.Accept.
     *
     * @param array $supported Tableau des valeurs supportées par l'application
     * @param string|null $header Chaîne de l'en-tête Accept* à analyser
     * @param bool $enforceTypes Si true, compare les types et sous-types de média
     * @param bool $strictMatch Si true, retourne une chaîne vide si aucun match n'est trouvé.
     *                         Si false, retourne le premier élément supporté.
	 *
     * @return string La meilleure correspondance
	 *
     * @throws HttpException Si le tableau des valeurs supportées est vide
     */
    protected function getBestMatch(array $supported, ?string $header = null, bool $enforceTypes = false, bool $strictMatch = false): string
    {
        if ($supported === []) {
            throw new HttpException('Vous devez fournir un tableau de valeurs supportées pour toutes les négociations.');
        }

        if ($header === null || $header === '' || $header === '0') {
            return $strictMatch ? '' : $supported[0];
        }

        $acceptable = $this->parseHeader($header);

        foreach ($acceptable as $accept) {
            // Si la qualité acceptable est zéro, on passe
            if ($accept['q'] === 0.0) {
                continue;
            }

            // Si la valeur acceptable est "n'importe quoi", on retourne le premier disponible
            if ($accept['value'] === '*' || $accept['value'] === '*/*') {
                return $supported[0];
            }

            // Si une valeur acceptable est supportée, on la retourne
            foreach ($supported as $available) {
                if ($this->match($accept, $available, $enforceTypes)) {
                    return $available;
                }
            }
        }

        // Aucune correspondance ? On retourne le premier élément supporté
        return $strictMatch ? '' : $supported[0];
    }

    /**
     * Analyse un en-tête Accept* en ses multiples valeurs
     *
     * Ce code est basé sur la bibliothèque Aura.Accept.
     *
     * @param string $header Chaîne de l'en-tête à analyser
	 *
     * @return array Tableau structuré des valeurs acceptables avec leurs paramètres
     */
    public function parseHeader(string $header): array
    {
        $results    = [];
        $acceptable = explode(',', $header);

        foreach ($acceptable as $value) {
            $pairs = explode(';', $value);

            $value = $pairs[0];

            unset($pairs[0]);

            $parameters = [];

            foreach ($pairs as $pair) {
                $param = [];
                preg_match(
                    '/^(?P<name>.+?)=(?P<quoted>"|\')?(?P<value>.*?)(?:\k<quoted>)?$/',
                    $pair,
                    $param
                );
                $parameters[trim($param['name'])] = trim($param['value']);
            }

            $quality = 1.0;

            if (array_key_exists('q', $parameters)) {
                $quality = $parameters['q'];
                unset($parameters['q']);
            }

            $results[] = [
                'value'  => trim($value),
                'q'      => (float) $quality,
                'params' => $parameters,
            ];
        }

        // Tri pour obtenir les résultats de plus haute qualité en premier
        usort($results, static function ($a, $b): int {
            if ($a['q'] === $b['q']) {
                $a_ast = substr_count($a['value'], '*');
                $b_ast = substr_count($b['value'], '*');

                // '*/*' a une priorité inférieure à 'text/*',
                // et 'text/*' a une priorité inférieure à 'text/plain'
                //
                // Cela semble inversé, mais nécessaire en raison de la façon
                // dont PHP7 gère l'ordonnancement des éléments de tableau
                // créés par référence.
                if ($a_ast > $b_ast) {
                    return 1;
                }

                // Si les comptes sont identiques, mais qu'un élément
                // a plus de paramètres qu'un autre, il a une priorité supérieure.
                //
                // Cela semble inversé, mais nécessaire en raison de la façon
                // dont PHP7 gère l'ordonnancement des éléments de tableau
                // créés par référence.
                if ($a_ast === $b_ast) {
                    return count($b['params']) - count($a['params']);
                }

                return 0;
            }

            // Toujours là ? Les valeurs q plus élevées ont la priorité.
            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        return $results;
    }

    /**
     * Compare une valeur acceptable avec une valeur supportée
     *
     * @param bool $enforceTypes Si true, compare les types et sous-types
     */
    protected function match(array $acceptable, string $supported, bool $enforceTypes = false): bool
    {
        $supported = $this->parseHeader($supported);
        if (count($supported) === 1) {
            $supported = $supported[0];
        }

        // Correspondance exacte ?
        if ($acceptable['value'] === $supported['value']) {
            return $this->matchParameters($acceptable, $supported);
        }

        // Doit-on comparer les types/sous-types ? Utilisé uniquement
        // par negotiateMedia().
        if ($enforceTypes) {
            return $this->matchTypes($acceptable, $supported);
        }

        return false;
    }

    /**
     * Vérifie si deux valeurs Accept avec des 'values' correspondantes
     * ont les mêmes paramètres
     */
    protected function matchParameters(array $acceptable, array $supported): bool
    {
        if (count($acceptable['params']) !== count($supported['params'])) {
            return false;
        }

        foreach ($supported['params'] as $label => $value) {
            if (! isset($acceptable['params'][$label]) || $acceptable['params'][$label] !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare les types/sous-types d'un type de média acceptable
     * avec la chaîne supportée
     */
    public function matchTypes(array $acceptable, array $supported): bool
    {
        [$aType, $aSubType] = explode('/', $acceptable['value']);
        [$sType, $sSubType] = explode('/', $supported['value']);

        // Si les types ne correspondent pas, on s'arrête
        if ($aType !== $sType) {
            return false;
        }

        // S'il y a un astérisque, c'est bon
        if ($aSubType === '*') {
            return true;
        }

        // Sinon, les sous-types doivent correspondre aussi
        return $aSubType === $sSubType;
    }
}
