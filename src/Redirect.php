<?php

namespace App;

use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;

function redirect(
    ResponseInterface $response,
    RouteParserInterface $router,
    string $routeName,
    array $params = []
): ResponseInterface {
    return $response
        ->withHeader('Location', $router->urlFor($routeName, $params))
        ->withStatus(302);
}
