<?php

namespace App;

use App\Services\Parser;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class PageAnalyzer
{
    private Fetcher $fetcher;
    private Parser $parse;

    public function __construct(Fetcher $fetcher, Parser $parse)
    {
        $this->fetcher = $fetcher;
        $this->parse = $parse;
    }

    public function analyze(string $url): PageCheckResult
    {
        try {
            $response = $this->fetcher->fetch($url);
            $html = (string)$response->getBody();
            $parsed = $this->parse->parse($html);

            return new PageCheckResult(
                statusCode: $response->getStatusCode(),
                h1: $parsed['h1'],
                title: $parsed['title'],
                description: $parsed['description']
            );
        } catch (ConnectException $e) {
            return new PageCheckResult(null, null, null, null, $e->getMessage());
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response !== null) {
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
            return new PageCheckResult(null, null, null, null, $e->getMessage());
        } catch (GuzzleException $e) {
            return new PageCheckResult(null, null, null, null, $e->getMessage());
        }
    }
}
