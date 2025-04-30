<?php

namespace App;

require '../vendor/autoload.php';

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

function getHttpCode($url): ?int
{
    $client = new Client();
    sleep(1);

    try {
        $response = $client->get($url);  // Метод GET для получения страницы целиком
        return $response->getStatusCode();  // Возвращаем код ответа
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "Ошибка запроса: " . $e->getMessage();
        return null;
    }
}


function getPageTitle($url): string
{
    $client = new Client();
    $response = $client->get($url);  // Получаем HTML страницы

    $html = $response->getBody()->getContents();  // Получаем содержимое

    $crawler = new Crawler($html);
    $title = $crawler->filter('title')->text();  // Извлекаем текст из тега <title>

    return $title ?: 'Без заголовка';
}

function getDescription($url): string
{
    $client = new Client();
    $response = $client->get($url);

    $html = $response->getBody()->getContents();

    $crawler = new Crawler($html);
    $description = $crawler->filter('meta[name="description"]')->attr('content');  // Получаем описание из meta

    return $description ?: 'Описание не найдено';
}

// Пример использования
$url = 'https://www.ozon.ru/';

$httpCode = getHttpCode($url);
$title = getPageTitle($url);
$description = getDescription($url);

echo "Код ответа: $httpCode\n" . '<br>';
echo "Заголовок: $title\n" . '<br>';
echo "Описание: $description\n" . '<br>';
