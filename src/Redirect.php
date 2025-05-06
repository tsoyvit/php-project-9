<?php

namespace App;

use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteParser;

function redirect(
    ResponseInterface $response,
    RouteParser $router,
    string $routeName,
    array $params = []
): ResponseInterface {
    return $response
        ->withHeader('Location', $router->urlFor($routeName, $params))
        ->withStatus(302);
}
