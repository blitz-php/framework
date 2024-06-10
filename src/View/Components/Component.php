<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View\Components;

use BlitzPHP\Traits\PropertiesTrait;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use LogicException;
use ReflectionClass;
use Stringable;

/**
 * La classe de base dont etendra tous les composants de vues
 * Fournie des fonctionnalites extensible pour gerer/rendre le contenu d'un composant simple.
 *
 * @function mount()
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.5 - CodeIgniter\View\Cells\Cell</a>
 */
class Component implements Stringable
{
    use PropertiesTrait;

    /**
     * Nom de la vue a rendre.
     * Si vide, il sera determiné en fonction du nom de la classe de composant.
     */
    protected string $view = '';

    /**
     * Responsable de la conversion de la vue en HTML.
     * Peut etre modifier par les classes filles dans certains cas, mais pas tous.
     */
    public function render(): string
    {
        return $this->view($this->view);
    }

    /**
     * Defini la vue à utiliser lors du rendu.
     */
    public function setView(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * rend actuellement la vue et renvoie le code HTML.
     * Afin de permettre l'accès aux propriétés et méthodes publiques à partir de la vue,
     * cette méthode extrait $data dans le champ d'application actuel et capture le tampon de
     * sortie au lieu de s'appuyer sur le service de vue.
     *
     * @throws LogicException
     */
    final protected function view(?string $view, array $data = []): string
    {
        $properties = $this->getPublicProperties();
        $properties = $this->includeComputedProperties($properties);
        $properties = array_merge($properties, $data);

        $view = (string) $view;

        if ($view === '') {
            $viewName  = Text::convertTo(Helpers::classBasename(static::class), 'toKebab');
            $directory = dirname((new ReflectionClass($this))->getFileName()) . DIRECTORY_SEPARATOR;

            $possibleView1 = $directory . substr($viewName, 0, strrpos($viewName, '-component')) . '.php';
            $possibleView2 = $directory . $viewName . '.php';
        }

        if ($view !== '' && ! is_file($view)) {
            $directory = dirname((new ReflectionClass($this))->getFileName()) . DIRECTORY_SEPARATOR;

            $view = $directory . $view . '.php';
        }

        $candidateViews = array_filter(
            [$view, $possibleView1 ?? '', $possibleView2 ?? ''],
            static fn (string $path): bool => $path !== '' && is_file($path)
        );

        if ($candidateViews === []) {
            throw new LogicException(sprintf(
                'Impossible de localiser le fichier de vue pour le composant "%s".',
                static::class
            ));
        }

        $foundView = current($candidateViews);

        return (function () use ($properties, $foundView): string {
            extract($properties);
            ob_start();
            include $foundView;

            return ob_get_clean();
        })();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Permet au développeur de définir les propriétés calculées comme des méthodes
     * avec `get` préfixé au nom de la propriété protégée/privée.
     */
    private function includeComputedProperties(array $properties): array
    {
        $reservedProperties = ['data', 'view'];
        $privateProperties  = $this->getNonPublicProperties();

        foreach ($privateProperties as $property) {
            $name = $property->getName();

            //  ne pas inclure de méthodes dans la classe de base
            if (in_array($name, $reservedProperties, true)) {
                continue;
            }

            $computedMethod = 'get' . ucfirst($name) . 'Property';

            if (method_exists($this, $computedMethod)) {
                $properties[$name] = $this->{$computedMethod}();
            }
        }

        return $properties;
    }
}
