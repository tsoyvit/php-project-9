<?php

namespace App\Services;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class ParseSite
{
    private Client $client;
    private string $url;
    private string $html = '';
    private int $statusCode = 0;
    private ?Document $doc = null;

    public function __construct(Client $client, string $url)
    {
        $this->client = $client;
        $this->url = $url;
    }

    public function parse(): array
    {
        try {
            $this->fetch();
            return [
                'status_code' => $this->statusCode,
                'h1' => $this->parseH1(),
                'title' => $this->parseTitle(),
                'description' => $this->parseDescription(),
            ];
        } catch (ConnectException $e) {
            return ['error' => $e->getMessage()];
        } catch (RequestException $e) {
            $result = ['error' => $e->getMessage()];

            if ($e->hasResponse()) {
                $this->html = (string)$e->getResponse()->getBody();
                $this->doc = new Document($this->html);
                $result['status_code'] = $e->getResponse()->getStatusCode();
                $result['h1'] = $this->parseH1();
                $result['title'] = $this->parseTitle();
                $result['description'] = $this->parseDescription();
            }

            return $result;
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
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
        $this->doc = new Document($this->html);
    }

    /**
     * @throws InvalidSelectorException
     */
    private function parseTitle(): ?string
    {
        return optional($this->doc->first('title'))->text();
    }

    /**
     * @throws InvalidSelectorException
     */
    private function parseDescription(): ?string
    {
        return optional($this->doc->first('meta[name=description]'))->attr('content');
    }

    /**
     * @throws InvalidSelectorException
     */
    private function parseH1(): ?string
    {
        return optional($this->doc->first('h1'))->text();
    }
}
