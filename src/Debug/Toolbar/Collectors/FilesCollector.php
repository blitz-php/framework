<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

/**
 * Collecteur de fichiers pour la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Files</a>
 */
class FilesCollector extends BaseCollector
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
    protected $title = 'Files';

    /**
     * {@inheritDoc}
     */
    public function getTitleDetails(): string
    {
        return '( ' . (int) count(get_included_files()) . ' )';
    }

    /**
     * {@inheritDoc}
     */
    public function display(): array
    {
        $rawFiles  = get_included_files();
        $coreFiles = [];
        $userFiles = [];

        foreach ($rawFiles as $file) {
            $path = clean_path($file);

            if (strpos($path, 'SYST_PATH') !== false) {
                $coreFiles[] = [
                    'name' => basename($file),
                    'path' => $path,
                ];
            } else {
                $userFiles[] = [
                    'name' => basename($file),
                    'path' => $path,
                ];
            }
        }

        sort($userFiles);
        sort($coreFiles);

        return [
            'coreFiles' => $coreFiles,
            'userFiles' => $userFiles,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        return count(get_included_files());
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAGBSURBVEhL7ZQ9S8NQGIVTBQUncfMfCO4uLgoKbuKQOWg+OkXERRE1IAXrIHbVDrqIDuLiJgj+gro7S3dnpfq88b1FMTE3VZx64HBzzvvZWxKnj15QCcPwCD5HUfSWR+JtzgmtsUcQBEva5IIm9SwSu+95CAWbUuy67qBa32ByZEDpIaZYZSZMjjQuPcQUq8yEyYEb8FSerYeQVGbAFzJkX1PyQWLhgCz0BxTCekC1Wp0hsa6yokzhed4oje6Iz6rlJEkyIKfUEFtITVtQdAibn5rMyaYsMS+a5wTv8qeXMhcU16QZbKgl3hbs+L4/pnpdc87MElZgq10p5DxGdq8I7xrvUWUKvG3NbSK7ubngYzdJwSsF7TiOh9VOgfcEz1UayNe3JUPM1RWC5GXYgTfc75B4NBmXJnAtTfpABX0iPvEd9ezALwkplCFXcr9styiNOKc1RRZpaPM9tcqBwlWzGY1qPL9wjqRBgF5BH6j8HWh2S7MHlX8PrmbK+k/8PzjOOzx1D3i1pKTTAAAAAElFTkSuQmCC';
    }
}
