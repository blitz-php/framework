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

use BlitzPHP\Contracts\Event\EventInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

/**
 * Implémentation d'un événement PSR-14
 *
 * Cette classe représente un événement qui peut être déclenché et écouté
 * par le système d'événements. Elle supporte les paramètres, les cibles
 * et le contrôle de propagation.
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
class Event implements EventInterface
{
    /**
     * Nom de l'événement.
     */
    protected string $name = '';

    /**
     * La cible/context de l'événement
     */
    protected mixed $target = null;

    /**
     * Les paramètres de l'événement
     *
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * Indique si la propagation de l'événement a été arrêtée.
     */
    protected bool $isPropagationStopped = false;

    /**
     * Indicateur si l'événement est immuable
     */
    protected bool $immutable = false;

    /**
     * Constructeur de l'événement
     *
     * @param string               $name   Nom de l'événement
     * @param mixed                $target Cible/context de l'événement
     * @param array<string, mixed> $params Paramètres de l'événement
     *
     * @throws InvalidArgumentException si le nom est vide
     */
    public function __construct(string $name, mixed $target = null, array $params = [])
    {
        if ($name === '') {
            throw new InvalidArgumentException('Le nom de l\'événement ne peut pas être vide.');
        }

        $this->name   = $name;
        $this->target = $target;
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException Si l'événement est immuable
     */
    public function setName(string $name): self
    {
        $this->checkImmutable();

		if ($name === '') {
            throw new InvalidArgumentException('Le nom de l\'événement ne peut pas être vide.');
        }

		$this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException Si l'événement est immuable
     */
    public function setTarget(mixed $target): self
    {
        $this->checkImmutable();

        $this->target = $target;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * {@inheritDoc}
     */
    public function getParam(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * Vérifie si un paramètre existe
     *
     * @param string $name Nom du paramètre
	 *
     * @return bool True si le paramètre existe, false sinon
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    /**
     * Définit un paramètre spécifique
     *
     * @param string $name Nom du paramètre
     * @param mixed $value Valeur du paramètre
     *
     * @throws RuntimeException Si l'événement est immuable
     */
    public function setParam(string $name, mixed $value): self
    {
        $this->checkImmutable();

        $this->params[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setParams(array $params): self
    {
        $this->checkImmutable();

		$this->params = $params;

        return $this;
    }

    /**
     * Fusionne des paramètres supplémentaires
     *
     * @param array<string, mixed> $params Paramètres à fusionner
     *
     * @throws RuntimeException Si l'événement est immuable
     */
    public function mergeParams(array $params): self
    {
        $this->checkImmutable();

        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation(bool $flag = true): void
    {
        if ($this->isPropagationStopped && !$flag) {
            throw new LogicException('Impossible de redémarrer la propagation d\'un événement arrêté');
        }

        $this->isPropagationStopped = $flag;
    }

    /**
     * Rend l'événement immuable
     *
     * @param bool $immutable True pour rendre immuable, false pour rendre mutable
     */
    public function setImmutable(bool $immutable = true): self
    {
        $this->immutable = $immutable;

        return $this;
    }

    /**
     * Convertit l'événement en tableau
     *
     * @return array{
     *     name: string|null,
     *     target: mixed,
     *     params: array<string, mixed>,
     *     isPropagationStopped: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'name'                 => $this->name,
            'target'               => $this->target,
            'params'               => $this->params,
            'isPropagationStopped' => $this->isPropagationStopped,
        ];
    }

    /**
     * Crée un événement à partir d'un tableau
     *
     * @param array{
     *     name?: string|null,
     *     target?: mixed,
     *     params?: array<string, mixed>,
     *     isPropagationStopped?: bool
     * } $data Données de l'événement
	 *
     * @return self Instance de l'événement
     */
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['name'] ?? '',
            $data['target'] ?? null,
            $data['params'] ?? []
        );

        if (isset($data['isPropagationStopped']) && $data['isPropagationStopped']) {
            $event->stopPropagation();
        }

        return $event;
    }

    /**
     * Vérifie si l'événement peut être modifié
     *
     * @throws RuntimeException Si l'événement est immuable
     */
    protected function checkImmutable(): void
    {
        if ($this->immutable) {
            throw new RuntimeException('Impossible de modifier un événement immuable');
        }
    }

    /**
     * Clone l'événement en profondeur
     */
    public function __clone()
    {
        // Clone les objets dans les paramètres
        foreach ($this->params as $key => $value) {
            if (is_object($value)) {
                $this->params[$key] = clone $value;
            }
        }

        // Clone la cible si c'est un objet
        if (is_object($this->target)) {
            $this->target = clone $this->target;
        }
    }

    /**
     * Représentation textuelle de l'événement
     */
    public function __toString(): string
    {
        return sprintf(
            'Event[name="%s", target=%s, params=%s]',
            $this->name ?? 'null',
            is_object($this->target) ? get_class($this->target) : gettype($this->target),
            json_encode($this->params, JSON_THROW_ON_ERROR)
        );
    }
}
