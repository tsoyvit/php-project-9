<?php

namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use PDO;
use Symfony\Component\VarDumper\VarDumper;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Illuminate\Support\Collection;


require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();


$app->get('/', function (Request $request, Response $response) use ($router) {
    return $this->get('renderer')->render($response, 'home.phtml', []);
})->setName('home');

$app->run();
