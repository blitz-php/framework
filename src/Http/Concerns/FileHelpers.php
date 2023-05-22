<?php

namespace BlitzPHP\Http\Concerns;

use BlitzPHP\Utilities\String\Text;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     */
    protected ?string $hashName = null;

    /**
     * Get the fully qualified path to the file.
     */
    public function path(): string
    {
        return $this->getPathname();
    }

    /**
     * Get the file's extension.
     */
    public function extension(): string
    {
        return $this->clientExtension();
    }

    /**
     * Obtenez un nom pour le fichier.
     */
    public function hashName(?string $path = null): string
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = $this->hashName ?: $this->hashName = Text::random(40);

        if ($extension = $this->clientExtension()) {
            $extension = '.'.$extension;
        }

        return $path.$hash.$extension;
    }
}
