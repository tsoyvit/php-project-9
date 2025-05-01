<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

session_start();

use App\Domain\Url;
use App\Domain\UrlCheck;
use App\Repositories\UrlCheckRepository;
use App\Repositories\UrlRepository;
use App\Services\NormalizeUrl;
use App\Services\ParseResultAnalyzer;
use App\Services\ParseSite;
use App\Validators\UrlValidator;
use DI\Container;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
        'verify' => true,
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

$app->get('/', function (Request $request, Response $response) {
    return $this->get('renderer')->render($response, 'home.phtml', []);
})->setName('home');

$app->get('/urls', function (Request $request, Response $response) {
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
    $urlData = (array) $request->getParsedBody();
    $urlName = $urlData['url']['name'] ?? null;
    $normalizeUrl = NormalizeUrl::normalize($urlName);
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
    } else {
        $url = Url::fromArray(['name' => $normalizeUrl]);
        $urlRepo->saveUrl($url);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
    }

    return $response
        ->withHeader('Location', $router->urlFor('urls.show', ['id' => $url->getId()]))
        ->withStatus(302);
})->setName('urls.store');

$app->get('/urls/{id}', function (Request $request, Response $response, $args) {
    $urlRepo = $this->get(UrlRepository::class);
    $id = $args['id'];
    $url = $urlRepo->getUrlById($id);
    $checkRepo = $this->get(UrlCheckRepository::class);
    $checks = $checkRepo->getChecks($id);

    if (is_null($url)) {
        return $this->get('renderer')->render($response->withStatus(404), 'urls/404.phtml', []);
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

    $urlRepo = $this->get(UrlRepository::class);
    $checkRepo = $this->get(UrlCheckRepository::class);
    $client = $this->get(Client::class);

    $url = $urlRepo->getUrlById($id);

    if (is_null($url)) {
        $this->get('flash')->addMessage('error', 'URL не найден');
        return $response
            ->withHeader('Location', $router->urlFor('urls.index'))
            ->withStatus(302);
    }

    $parser = new ParseSite($client, $url->getName());
    $result = $parser->parse();
    $analysis = ParseResultAnalyzer::getAnalysis($result);

    if ($analysis['check'] === 'danger') {
        $this->get('flash')->addMessage('error', $analysis['message']);
        return $response
            ->withHeader('Location', $router->urlFor('urls.show', ['id' => $id]))
            ->withStatus(302);
    }

    $check = UrlCheck::fromArray($result, $url->getId());
    $checkRepo->saveCheck($check);
    if ($analysis['check'] === 'warning') {
        $this->get('flash')->addMessage('warning', $analysis['message']);
    } else {
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    }

    return $response
        ->withHeader('Location', $router->urlFor('urls.show', ['id' => $id]))
        ->withStatus(302);
})->setName('checks.store');

$app->run();
