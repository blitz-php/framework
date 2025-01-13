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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PerformanceMetrics implements MiddlewareInterface
{
    /**
     * Remplace les balises de mesures de performance
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $body     = $response->getBody()->getContents();

        if ($body !== '' && $body !== '0') {
            $benchmark = service('timer');

            $output = str_replace(
                [
                    '{elapsed_time}',
                    '{memory_usage}',
                ],
                [
                    (string) $benchmark->getElapsedTime('total_execution'),
                    number_format(memory_get_peak_usage() / 1024 / 1024, 3),
                ],
                $body
            );

            $response = $response->withBody(to_stream($output));
        }

        return $response;
    }
}
