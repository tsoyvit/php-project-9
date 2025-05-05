<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Fetcher
{
    private Client $client;
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    public function fetch(string $url): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('GET', $url);
    }
}
