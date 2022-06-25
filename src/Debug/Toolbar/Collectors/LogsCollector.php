<?php

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Loader\Services;

/**
 * Collecteur de logs pour la barre d'outils de débogage
 * 
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Logs</a>
 */
class LogsCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected $hasTimeline = false;

    /**
     * {@inheritDoc}
     */
    protected $hasTabContent = true;

    /**
     * {@inheritDoc}
     */
    protected $title = 'Logs';

    /**
     * Nos données collectées
     *
     * @var array
     */
    protected $data;

    /**
     * {@inheritDoc}
     */
    public function display(): array
    {
        return [
            'logs' => $this->collectLogs(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        $this->collectLogs();

        return empty($this->data);
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAACYSURBVEhLYxgFJIHU1FSjtLS0i0D8AYj7gEKMEBkqAaAFF4D4ERCvAFrwH4gDoFIMKSkpFkB+OTEYqgUTACXfA/GqjIwMQyD9H2hRHlQKJFcBEiMGQ7VgAqCBvUgK32dmZspCpagGGNPT0/1BLqeF4bQHQJePpiIwhmrBBEADR1MRfgB0+WgqAmOoFkwANHA0FY0CUgEDAwCQ0PUpNB3kqwAAAABJRU5ErkJggg==';
    }

    /**
     * S'assure que les données ont été collectées.
     */
    protected function collectLogs()
    {
        if (! empty($this->data)) {
            return $this->data;
        }

        return $this->data = Services::logger(true)->logCache ?? [];
    }
}
