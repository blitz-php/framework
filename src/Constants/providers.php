<?php
return [
    BlitzPHP\Router\RouteCollectionInterface::class => service('routes'),
	Psr\Container\ContainerInterface::class => service('container'), 
    Psr\Http\Message\ResponseInterface::class => service('response'),
    Psr\Http\Message\ServerRequestInterface::class => service('request'),
];
