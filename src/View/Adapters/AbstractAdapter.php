<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View\Adapters;

use BlitzPHP\View\RendererInterface;

abstract class AbstractAdapter implements RendererInterface
{
    /**
     * Données mises à la disposition des vues.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Les variables de rendu
     *
     * @var array
     */
    protected $renderVars = [];

    /**
     * Le répertoire de base dans lequel rechercher nos vues.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Configuration actuelle de l'adapter
     *
     * @var array
     */
    protected $config;

    /**
     * Le nom de la mise en page utilisée, le cas échéant.
     * Défini par la méthode "extend" utilisée dans les vues.
     *
     * @var string|null
     */
    protected $layout = null;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        $this->config   = $config;
        $this->viewPath = rtrim($viewPath, '\\/ ') . DS;
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            // $data = \esc($data, $context);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            // $data = \esc($data, $context);
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVar(string $name, $value = null, ?string $context = null): self
    {
        if ($context) {
            // $value = esc($value, $context);
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resetData(): self
    {
        $this->data = [];

        return $this;
    }

    /**
	 * {@inheritDoc}
	 */
	public function setLayout(?string $layout): self
	{
		$this->layout = $layout;

		return $this;
	}
}
