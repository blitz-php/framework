<?php

namespace BlitzPHP\Validation;

use BlitzPHP\Http\Request;

abstract class DataValidation
{
	/**
	 * Source à utiliser pour avoir les données à valider
	 *
	 * @var string
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
	 * Regles de validation
	 */
    abstract protected function rules(): array;

	/**
	 * Messages personnalisés pour les erreurs du validateur.
	 */
    protected function messages(): array
    {
        return [];
    }

	/**
	 * Attributs personnalisés pour les erreurs du validateur.
	 */
    protected function attributes(): array
    {
        return [];
    }

	/**
	 * Données à valider à partir de la demande.
	 */
    protected function data(Request $request): array
    {

		if ($this->source === 'all') {
        	return $request->all();
		}

		$sources = explode('|', $this->source);
		$data    = [];

		foreach ($sources as $source) {
			$data += match($source) {
				'post'   => $request->post(),
				'get'    => $request->query(),
				'cookie' => $request->cookie(),
				default  => [],
			};
		}

		return $data;
    }

	/**
	 * @internal
	 */
	public function process(Request $request): Validation
	{
		$validation = new Validation();

        $validation->data($this->data($request));
        $validation->rules($this->rules());
        $validation->messages($this->messages());
		$validation->alias($this->attributes());

		return $validation;
	}
}
