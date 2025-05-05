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

        return [
            'title' => $this->extractText($doc, 'title'),
            'h1' => $this->extractText($doc, 'h1'),
            'description' => $this->extractAttribute($doc, 'meta[name=description]', 'content')
        ];
    }

    /**
     * @throws InvalidSelectorException
     */
    private function extractText(Document $doc, string $selector): ?string
    {
        $element = $doc->first($selector);
        return $element instanceof Element ? $element->text() : null;
    }

    /**
     * @throws InvalidSelectorException
     */
    private function extractAttribute(Document $doc, string $selector, string $attr): ?string
    {
        $element = $doc->first($selector);
        return $element instanceof Element ? $element->attr($attr) : null;
    }
}
