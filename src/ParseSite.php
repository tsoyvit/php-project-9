<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class ParseSite
{
    private Client $client;
    private string $url;
    private Crawler $crawler;
    private string $html = '';
    private int $statusCode = 0;

    public function __construct(Client $client, string $url)
    {
        $this->client = $client;
        $this->url = $url;
        $this->crawler = new Crawler();
    }

    /**
     * Парсит сайт, предварительно загружая HTML с помощью fetch().
     *
     * @throws GuzzleException
     */
    public function parse(): array
    {
        try {
            $this->fetch();
            return [
                'code' => $this->statusCode,
                'h1' => $this->parseH1(),
                'title' => $this->parseTitle(),
                'description' => $this->parseDescription(),
            ];
        } catch (GuzzleException $e) {
            return [
                'code' => $e->getCode() ?: 500,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @throws GuzzleException
     */
    private function fetch(): void
    {
        $response = $this->client->request('GET', $this->url);
        $this->statusCode = $response->getStatusCode();
        $this->html = $response->getBody()->getContents();
        $this->crawler->addHtmlContent($this->html);
    }

    private function parseTitle(): ?string
    {
        return $this->crawler->filter('title')->count() ? $this->crawler->filter('title')->text(null, false) : null;
    }

    private function parseDescription(): ?string
    {
        return $this->crawler->filterXPath('//meta[@name="description"]')->count()
            ? $this->crawler->filterXPath('//meta[@name="description"]')->attr('content')
            : null;
    }

    private function parseH1(): ?string
    {
        return $this->crawler->filter('h1')->count()
            ? $this->crawler->filter('h1')->first()->text(null, false)
            : null;
    }
}
