<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Middlewares;

use BlitzPHP\Filesystem\Adapters\FilesystemAdapter;
use BlitzPHP\Filesystem\Exceptions\FileNotFoundException;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FileViewer implements MiddlewareInterface
{
    /**
     * Chemin d'accès du fichier qu'on souhaite affiché
     */
    private string $path = '';

    private ?FilesystemAdapter $disk = null;

    public function __construct(private FilesystemManager $filesystem, private Response $response)
    {
    }

    /**
     * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = config('filesystems');

        if ([] === $config['viewable'] ?? []) {
            return $handler->handle($request);
        }

        $path                  = trim(urldecode($request->getPath()), '/');
        [$prefix, $this->path] = explode('/', $path, 2);

        foreach ($config['disks'] as $name => $disk) {
            if (str_ends_with(trim($disk['url'], '/'), trim($prefix, '/'))) {
                $this->disk = $this->filesystem->disk($name);
                break;
            }
        }

        if (null === $this->disk) {
            return $handler->handle($request);
        }

        if (! $this->disk->exists($this->path)) {
            throw FileNotFoundException::fileNotFound($this->path);
        }

        $path = $this->disk->path($this->path);

        if ($request->boolean('download')) {
            return $this->response->download($path);
        }

        return $this->response->file($path);
    }
}
