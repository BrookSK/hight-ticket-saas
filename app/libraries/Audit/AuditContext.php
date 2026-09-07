<?php

declare(strict_types=1);

namespace App\Libraries\Audit;

/**
 * Mutable data bag shared across analyzers during a single audit run.
 *
 * Holds the crawled pages plus collected artifacts (robots, sitemap, DNS,
 * headers of the homepage). Analyzers read from here and append issues,
 * metrics, technologies and contacts. Keeps collected data separate from
 * processed results (spec requirement).
 */
final class AuditContext
{
    /** @var list<CrawledPage> */
    public array $pages = [];

    /** @var list<Issue> */
    public array $issues = [];

    /** @var list<array{category:string,key:string,value:string|null,unit:string|null,available:bool}> */
    public array $metrics = [];

    /** @var list<array{name:string,category:string|null,version:string|null,confidence:string,evidence:string|null}> */
    public array $technologies = [];

    /** @var list<array{type:string,value:string}> */
    public array $contacts = [];

    /** @var list<array{type:string,url:string|null,size:int|null,attributes:array<string,mixed>}> */
    public array $resources = [];

    /** @var array<string, mixed> Notable positives to highlight in the report. */
    public array $positives = [];

    public ?RobotsTxt $robots = null;

    /** @var array{urls:list<string>,valid:bool}|null */
    public ?array $sitemap = null;

    /** @var array<string,string> Homepage response headers (lower-cased). */
    public array $homepageHeaders = [];

    public string $startUrl = '';

    public string $host = '';

    public function __construct(string $startUrl, string $host)
    {
        $this->startUrl = $startUrl;
        $this->host = $host;
    }

    public function homepage(): ?CrawledPage
    {
        return $this->pages[0] ?? null;
    }

    public function addIssue(Issue $issue): void
    {
        $this->issues[] = $issue;
    }

    public function addMetric(string $category, string $key, ?string $value, ?string $unit = null, bool $available = true): void
    {
        $this->metrics[] = compact('category', 'key', 'value', 'unit', 'available');
    }

    public function addTechnology(string $name, ?string $category, string $confidence, ?string $evidence = null, ?string $version = null): void
    {
        $this->technologies[] = [
            'name'       => $name,
            'category'   => $category,
            'version'    => $version,
            'confidence' => $confidence,
            'evidence'   => $evidence,
        ];
    }

    public function addContact(string $type, string $value): void
    {
        $this->contacts[] = ['type' => $type, 'value' => $value];
    }

    public function addPositive(string $key, string $label): void
    {
        $this->positives[$key] = $label;
    }
}
