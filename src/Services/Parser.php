<?php

namespace App\Services;

use DiDom\Document;
use DiDom\Element;
use DiDom\Exceptions\InvalidSelectorException;

class Parser
{
    /**
     * @throws InvalidSelectorException
     */


    public function parse(string $html): array
    {
        $doc = new Document($html);

        /** @var Element|null $titleElement */
        /** @var Element|null $h1Element */
        /** @var Element|null $metaElement */

        $titleElement = $doc->first('title');
        $h1Element = $doc->first('h1');
        $metaElement = $doc->first('meta[name=description]');

        return [
            'title' => $titleElement?->text(),
            'h1' => $h1Element?->text(),
            'description' => $metaElement?->attr('content')
        ];
    }
}
