<?php

namespace App;

use App\Services\Parser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class PageAnalyzer
{
    private Client $client;
    private Parser $parse;

    public function __construct(Client $client, Parser $parse)
    {
        $this->client = $client;
        $this->parse = $parse;
    }

    public function analyze(string $url): PageCheckResult
    {
        try {
            $response = $this->client->request('GET', $url);
            $html = (string)$response->getBody();
            $parsed = $this->parse->parse($html);

            return new PageCheckResult(
                statusCode: $response->getStatusCode(),
                h1: $parsed['h1'],
                title: $parsed['title'],
                description: $parsed['description']
            );
        } catch (RequestException | ConnectException | GuzzleException $e) {
            $response = $e instanceof RequestException ? $e->getResponse() : null;

            if ($response) {
                $html = (string)$response->getBody();
                $parsed = $this->parse->parse($html);

                return new PageCheckResult(
                    statusCode: $response->getStatusCode(),
                    h1: $parsed['h1'],
                    title: $parsed['title'],
                    description: $parsed['description'],
                    error: $e->getMessage()
                );
            }

            return new PageCheckResult(error: $e->getMessage());
        }
    }
}
