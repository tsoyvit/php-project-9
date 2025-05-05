<?php

namespace App\Services;

use DiDom\Document;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    /**
     * @throws InvalidSelectorException
     */
    public function parse(string $html): array
    {
        $doc = new Document($html);
        return [
            'title' => $doc->first('title')?->text(),
            'h1' => $doc->first('h1')?->text(),
            'description' => $doc->first('meta[name=description]')?->attr('content')
        ];
    }
}
