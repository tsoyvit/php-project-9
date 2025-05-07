<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Domain\Url;
use App\Domain\UrlCheck;
use App\Repositories\UrlCheckRepository;
use App\Repositories\UrlRepository;
use App\Services\NormalizerUrl;
use App\Services\Parser;
use App\Validators\UrlValidator;
use DI\Container;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

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

$container->set(UrlRepository::class, function ($c) {
    return new UrlRepository($c->get(\PDO::class));
});

$container->set(UrlCheckRepository::class, function ($c) {
    return new UrlCheckRepository($c->get(\PDO::class));
});

$container->set(Client::class, function () {
    return new Client([
        'timeout' => 10,
        'verify' => true,
    ]);
});

$container->set(Parser::class, function () {
    return new Parser();
});

$container->set(PageAnalyzer::class, function ($c) {
    return new PageAnalyzer($c->get(Client::class), $c->get(Parser::class));
});

$container->set('renderer', function () {
    $renderer = new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');
    return $renderer;
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages,
    ];
    return $this->get('renderer')->render($response, 'home.phtml', $params);
})->setName('home');

$app->get('/urls', function ($request, $response) {
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

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepo = $this->get(UrlRepository::class);
    $urlData = (array)$request->getParsedBody();
    $urlName = $urlData['url']['name'] ?? null;
    $normalizeUrl = NormalizerUrl::normalize($urlName);
    $errors = UrlValidator::validate($normalizeUrl);

    if (count($errors) > 0) {
        $params = [
            'errors' => $errors,
            'url' => $urlName,
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'home.phtml', $params);
    }

    if ($url = $urlRepo->existsByName($normalizeUrl)) {
        $this->get('flash')->addMessage('success', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $url->getId()]), 302);
    }

    $url = Url::fromArray(['name' => $normalizeUrl]);
    try {
        $urlRepo->createUrl($url);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    } catch (\RuntimeException $e) {
        $this->get('flash')->addMessage('error', 'Ошибка при сохранении.');
        return $response->withRedirect($router->urlFor('home'), 302);
    }

    return $response->withRedirect($router->urlFor('urls.show', ['id' => (string)$url->getId()]), 302);
})->setName('urls.store');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $urlRepo = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepo->getUrlById($id);
    $checkRepo = $this->get(UrlCheckRepository::class);
    $checks = $checkRepo->getChecks($id);

    if (is_null($url)) {
        return $this->get('renderer')->render($response->withStatus(404), '404.phtml', []);
    }

    $messages = $this->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'flash' => $messages,
        'checks' => $checks,
    ];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls/{id}/checks', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    $urlRepo = $this->get(UrlRepository::class);
    $checkRepo = $this->get(UrlCheckRepository::class);

    $url = $urlRepo->getUrlById($id);

    if (is_null($url)) {
        $this->get('flash')->addMessage('error', 'URL не найден');
        return $response->withRedirect($router->urlFor('urls.index'), 302);
    }

    $analyzer = $this->get(PageAnalyzer::class);
    $checkData = $analyzer->analyze($url->getName());

    if ($checkData->hasError() && $checkData->getStatusCode() === null) {
        $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]), 302);
    }

    $check = new UrlCheck(
        urlId: $url->getId(),
        statusCode: $checkData->getStatusCode(),
        h1: $checkData->getH1(),
        title: $checkData->getTitle(),
        description: $checkData->getDescription()
    );

    try {
        $checkRepo->createCheck($check);
    } catch (\RuntimeException $e) {
        $this->get('flash')->addMessage('error', 'Ошибка при сохранении.');
        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]), 302);
    }

    if ($checkData->hasError()) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена, но сервер ответил с ошибкой');
    } else {
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    }
    return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]), 302);
})->setName('checks.store');

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    return $this->get('renderer')->render($response->withStatus(404), '404.phtml', []);
});

$app->run();
