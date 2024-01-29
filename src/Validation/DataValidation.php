<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Validation;

use BlitzPHP\Http\Request;

abstract class DataValidation
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
     * Parametres supplementaires transmis pour aider à la validation des données
     *
     * Par exemple, on peut spécifier (au niveau du contrôleur) l'ID à ignorer pour la règle `unique`.
     *
     * @var array<string,mixed>
     *
     * @internal N'est pas destiné à être utilisé ou modifié par le développeur
     */
    protected array $params = [];

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
            $data += match ($source) {
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
     *
     * @param array<string,mixed> $params Parametres supplementaires transmis pour aider à la validation des données
     */
    public function process(Request $request, array $params = []): Validation
    {
        $this->params = $params;

        $validation = new Validation();

        $validation->data($this->data($request));
        $validation->rules($this->rules());
        $validation->messages($this->messages());
        $validation->alias($this->attributes());

        return $validation;
    }

    /**
     * Getter magic pour acceder aux paramètres supplementaires de validation
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->params[$name] ?? null;
    }
}
