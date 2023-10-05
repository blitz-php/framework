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

use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Container\Services;
use BlitzPHP\Validation\Rules\AbstractRule;
use Dimtrovich\Validation\Validation as BaseValidation;

class Validation extends BaseValidation
{
    public function __construct()
    {
        parent::__construct(config('app.language'));

        $this->discoverRules();
    }

    /**
     * {@inheritDoc}
     *
     * @param array<AbstractRule> $rules
     */
    protected function registerRules(array $rules): void
    {
        foreach ($rules as $key => $value) {
            if (is_int($key)) {
                $name = $value::name();
                $rule = $value;
            } else {
                $name = $value;
                $rule = $key;
            }

            $this->addValidator($name, Services::container()->get($rule));
        }
    }

    /**
     * Definie les fichiers qui pourront etre considerer comme regles de validations
     *
     * @return string[] Chemins absolus des fichiers
     */
    protected function files(Locator $locator): array
    {
        $files = array_merge(
            $locator->listFiles('Rules/'), // Regles de l'application ou des fournisseurs
            $locator->listFiles('Validation/Rules/') // Regles internes du framework
        );

        return array_unique($files);
    }

    /**
     * Recherche toutes les regles de validation dans le framework et dans le code de l'utilisateur
     * et collecte leurs instances pour fonctionner avec eux.
     */
    private function discoverRules()
    {
        $files = $this->files($locator = Services::locator());
        $rules = [];

        foreach ($files as $file) {
            $className = $locator->getClassname($file);

            if ($className !== '' && class_exists($className) && is_subclass_of($className, AbstractRule::class)) {
                $rules[] = $className;
            }
        }

        $this->registerRules($rules);
    }
}
