<?php

namespace App\Services;

class NormalizerUrl
{
    public static function normalize(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        return $scheme . '://' . $host;
    }
}
