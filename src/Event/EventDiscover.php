<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Event;

use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Event\EventListenerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Découvre et inclut tous les écouteurs d'événements
 *
 * Cette classe scanne les répertoires d'écouteurs d'événements
 * et les enregistre automatiquement dans le gestionnaire d'événements.
 */
class EventDiscover
{
    /**
     * Le localisateur de fichiers
     */
    protected LocatorInterface $locator;

    /**
     * Le conteneur d'injection
     */
    protected ContainerInterface $container;

    /**
     * Les classes déjà découvertes
     *
     * @var array<string, bool>
     */
    protected array $discoveredClasses = [];

    /**
     * Les chemins à scanner
     *
     * @var list<string>
     */
    protected array $paths = ['Listeners'];

    /**
     * Constructeur du découvreur d'événements
     *
     * @param EventManagerInterface   $manager   Le gestionnaire d'événements
     * @param LocatorInterface|null   $locator   Le localisateur de fichiers
     * @param ContainerInterface|null $container Le conteneur d'injection
     */
    public function __construct(protected EventManagerInterface $manager, ?LocatorInterface $locator = null, ?ContainerInterface $container = null)
    {
        $this->locator   = $locator ?? service('locator');
        $this->container = $container ?? service('container');

        $this->addPath('Events'); // Ajoute le chemin déprécié pour la compatibilité
    }

    /**
     * Découvre et enregistre tous les écouteurs d'événements
     *
     * @return int Nombre d'écouteurs découverts et enregistrés
     *
     * @throws RuntimeException Si la découverte échoue
     */
    public function discover(): int
    {
        if ($this->discoveredClasses !== []) {
            return count($this->discoveredClasses);
        }

        $count = 0;
        $files = [];

        foreach ($this->paths as $path) {
            try {
                $pathFiles = $this->locator->listFiles($path);
                $files     = array_merge($files, $pathFiles);
            } catch (Throwable) {
                // Ignore les chemins qui n'existent pas
                continue;
            }
        }

        $files = array_unique($files);

        foreach ($files as $file) {
            if ($this->registerListener($file)) {
                $count++;
            }
        }

        $this->discoveredClasses = array_fill_keys(array_keys($this->discoveredClasses), true);

        return $count;
    }

    /**
     * Ajoute un chemin à scanner
     *
     * @param string $path Chemin à ajouter
     *
     * @throws InvalidArgumentException Si le chemin est invalide
     */
    public function addPath(string $path): self
    {
        if ($path === '' || $path === '0') {
            throw new InvalidArgumentException('Le chemin ne peut pas être vide.');
        }

        if (! in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * Définit les chemins à scanner
     *
     * @param list<string> $paths Chemins à scanner
     */
    public function setPaths(array $paths): self
    {
        $this->paths = [];

        foreach ($paths as $path) {
            $this->addPath($path);
        }

        return $this;
    }

    /**
     * Récupère les chemins scannés
     *
     * @return list<string> Les chemins scannés
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Efface le cache des classes découvertes
     */
    public function clearCache(): self
    {
        $this->discoveredClasses = [];

        return $this;
    }

    /**
     * Enregistre un écouteur depuis un fichier
     *
     * @param string $file Chemin du fichier
     *
     * @return bool True si l'écouteur a été enregistré
     *
     * @throws RuntimeException Si l'enregistrement échoue
     */
    protected function registerListener(string $file): bool
    {
        try {
            $className = $this->locator->getClassname($file);

            if (! $this->shouldRegisterClass($className)) {
                return false;
            }

            $this->container->make($className)->listen($this->manager);

            $this->discoveredClasses[$className] = true;

            return true;
        } catch (Throwable $e) {
            // Log l'erreur mais continue avec les autres écouteurs
            logger()->error(sprintf(
                "Échec de l'enregistrement de l'écouteur d'événement %s : %s",
                $file,
                $e->getMessage()
            ));

            return false;
        }
    }

    /**
     * Vérifie si une classe doit être enregistrée
     *
     * @param string $className Nom de la classe
     *
     * @return bool True si la classe doit être enregistrée
     */
    protected function shouldRegisterClass(string $className): bool
    {
        if (isset($this->discoveredClasses[$className])) {
            return false;
        }

        return ! (! class_exists($className) || ! is_subclass_of($className, EventListenerInterface::class));
    }

    /**
     * Récupère les classes découvertes
     *
     * @return list<string> Les noms de classes découvertes
     */
    public function getDiscoveredClasses(): array
    {
        return array_keys($this->discoveredClasses);
    }

    /**
     * Vérifie si une classe a été découverte
     */
    public function isDiscovered(string $className): bool
    {
        return isset($this->discoveredClasses[$className]);
    }
}
