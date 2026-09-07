<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

use App\Libraries\Http\HttpResponse;

/**
 * A single crawled page: the request outcome plus the parsed HTML document
 * (when the response was HTML). Value object passed to analyzers.
 */
final class CrawledPage
{
    private ?HtmlDocument $document = null;

    public function __construct(
        public readonly string $url,
        public readonly int $depth,
        public readonly HttpResponse $response
    ) {
    }

    public function isHtml(): bool
    {
        return $this->response->ok() && $this->response->isHtml() && $this->response->body !== '';
    }

    public function document(): ?HtmlDocument
    {
        if (!$this->isHtml()) {
            return null;
        }

        if ($this->document === null) {
            $this->document = new HtmlDocument($this->response->body);
        }

        return $this->document;
    }
}
