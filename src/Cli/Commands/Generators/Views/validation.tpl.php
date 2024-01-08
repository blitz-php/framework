<@php

namespace {namespace};

use BlitzPHP\Validation\DataValidation;

class {class} extends DataValidation
{
    /**
     * Source à utiliser pour avoir les données à valider
     *
     * @example
     * 	`all` toutes les sources (GET, POST, COOKIE)
     *  `post` données issues de la soumission de formulaire ou d'API
     *  `get` données issues de la chaîne de requête
     *  `cookie` données issues des cookies
     * 	`post|get` données issues de $_POST et $_GET respectivement. Si on a la même clé dans plusieurs sources, seule la clé de la première source sera considérée
     */
    protected string $source = 'all';

    /**
     * Regles de validation qui s'appliquent à la requête.
	 *
	 * @return array<string, \BlitzPHP\Validation\Rules\AbstractRule|array|string>
     */
    protected function rules(): array
	{
		return [];
	}
}
