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
    protected string $path = '';

    /**
     * Specifie si le middleware doit directement renvoyé le fichier au navigateur ou pas.
     * Ceci peut être utile si une classe fille a besoin de faire quelques traitements sur le fichier avant de le renvoyer
     */
    protected bool $render = true;

    protected ?FilesystemAdapter $disk = null;

    public function __construct(protected FilesystemManager $filesystem, protected Response $response)
    {
    }

    /**
     * @param Request $request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $config = config('filesystems');

        if ([] === ($config['viewable'] ?? [])) {
            return $handler->handle($request);
        }

        $path                  = trim(urldecode($request->getPath()), '/');
        [$prefix, $this->path] = explode('/', $path, 2) + [1 => ''];
        $prefix                = trim($prefix, '/');
        $this->path            = trim($this->path, '/');

        foreach ($config['disks'] as $name => $disk) {
            $url = trim($disk['url'] ?? '', '/');

            if (str_ends_with($url, $prefix)) {
                $this->disk = $this->filesystem->disk($name); // @phpstan-ignore-line
                break;
            }
        }

        if (null === $this->disk || '' === $this->path) {
            return $handler->handle($request);
        }

        if (! $this->disk->exists($this->path)) {
            throw FileNotFoundException::fileNotFound($this->path);
        }

        if (! $this->render) {
            return $this->response;
        }

        $path = $this->disk->path($this->path);

        if ($request->boolean('download')) {
            return $this->response->download($path);
        }

        return $this->response->file($path);
    }
}
