<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Exceptions;

use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Http\ResponsableInterface;
use Exception;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * RedirectException
 */
class RedirectException extends Exception implements ResponsableInterface
{
    /**
     * Status code pour les redirections
     *
     * @var int
     */
    protected $code = 302;

    protected ?ResponseInterface $response = null;

    /**
     * @param ResponseInterface|string $message Response object or a string containing a relative URI.
     * @param int                      $code    HTTP status code to redirect if $message is a string.
     */
    public function __construct(ResponseInterface|string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        if ($message instanceof ResponseInterface) {
            $this->response = $message;
            $message        = '';

            if ($this->response->getHeaderLine('Location') === '' && $this->response->getHeaderLine('Refresh') === '') {
                throw new LogicException(
                    'The Response object passed to RedirectException does not contain a redirect address.'
                );
            }

            if ($this->response->getStatusCode() < 301 || $this->response->getStatusCode() > 308) {
                $this->response = $this->response->withStatus($this->code);
            }
        }

        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        if (null === $this->response) {
            $this->response = Services::response()
                ->redirect(base_url($this->getMessage()), 'auto', $this->getCode());
        }

        Services::logger()->info(
            'REDIRECTED ROUTE at '
            . ($this->response->getHeaderLine('Location') ?: substr($this->response->getHeaderLine('Refresh'), 6))
        );

        return $this->response;
    }
}
