<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

session_start();

use Dotenv\Dotenv;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Symfony\Component\VarDumper\VarDumper;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use Illuminate\Support\Collection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$container = new Container();

$container->set(\PDO::class, function () {
    $url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
    if (!$url) {
        throw new \RuntimeException('DATABASE_URL не задан в .env или переменных окружения');
    }

    $url = str_replace('postgres://', 'postgresql://', $url);

    $parts = parse_url($url);
    if ($parts === false) {
        throw new \InvalidArgumentException('Неверный формат DATABASE_URL');
    }

    $port = $parts['port'] ?? '5432';
    $host = $parts['host'] ?? '';
    $user = $parts['user'] ?? '';
    $pass = $parts['pass'] ?? '';
    $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_PERSISTENT => false,
    ]);
});


$container->set(Logger::class, function () {
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Level::Warning));
    return $logger;
});

$container->set(UrlRepository::class, function ($c) {
    return new UrlRepository($c->get(\PDO::class), $c->get(Logger::class));
});

$container->set(UrlCheckRepository::class, function ($c) {
    return new UrlCheckRepository($c->get(\PDO::class), $c->get(Logger::class));
});

$container->set(Client::class, function () {
    return new Client([
        'timeout' => 10,
        'verify' => false,
    ]);
});

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true, $container->get(Logger::class));

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function (Request $request, Response $response) use ($router) {
    return $this->get('renderer')->render($response, 'home.phtml', []);
})->setName('home');

$app->get('/urls', function (Request $request, Response $response) use ($router) {
    $urlRepo = $this->get(UrlRepository::class);
    $urls = $urlRepo->getUrls();
    $messages = $this->get('flash')->getMessages();
    $checkRepo = $this->get(UrlCheckRepository::class);
    $lastChecks = $checkRepo->getLastChecks();
    $params = [
        'urls' => $urls,
        'lastChecks' => $lastChecks,
        'flash' => $messages,
    ];

    return $this->get('renderer')->render($response, 'urls/urls.phtml', $params);
})->setName('urls.index');

$app->post('/urls', function (Request $request, Response $response) use ($router) {
    $urlRepo = $this->get(UrlRepository::class);
    $urlData = $request->getParsedBodyParam('url');
    $normalizeUrl = NormalizeUrl::normalize($urlData['name']);
    $errors = UrlValidator::validate($normalizeUrl);

    if ($url = $urlRepo->existsByName($normalizeUrl)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]));
    }

    if (count($errors) === 0) {
        $url = Url::fromArray(['name' => $normalizeUrl]);
        $urlRepo->saveUrl($url);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]));
    }

    $params = [
        'errors' => $errors,
        'url' => $urlData['name'],
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'home.phtml', $params);
})->setName('urls.store');

$app->get('/urls/{id}', function (Request $request, Response $response, $args) use ($router) {
    $urlRepo = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepo->getUrlById($id);
    $checkRepo = $this->get(UrlCheckRepository::class);
    $checks = $checkRepo->getChecks($id);

    if (is_null($url)) {
        return $response->withStatus(404)->write("Страница не найдена!");
    }

    $messages = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'flash' => $messages,
        'checks' => $checks,
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls/{id}/checks', function (Request $request, Response $response, $args) use ($router) {
    $id = $args['id'];
    $client = $this->get(Client::class);
    $checkRepo = $this->get(UrlCheckRepository::class);
    $urlRepo = $this->get(UrlRepository::class);
    $url = $urlRepo->getUrlById($id);
    $parse = new ParseSite($client, $url->getName());
    $check = UrlCheck::fromArray($parse->parse(), $url->getId());
    $checkRepo->saveCheck($check);
    $this->get('flash')->addMessage('success', 'Проверка успешно добавлена');
    return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]));
})->setName('checks.store');

$app->run();
