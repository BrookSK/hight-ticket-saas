<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Translator;
use RuntimeException;

/**
 * Simple PHP template renderer.
 *
 * Views only render output; they contain no SQL and no business logic. Data is
 * passed in from controllers. A layout may wrap the view content via the
 * "layout" mechanism. All output escaping helpers are available in views.
 */
final class View
{
    private string $viewsPath;

    /** @var array<string, mixed> Shared data available to every view. */
    private array $shared = [];

    public function __construct(
        private readonly Translator $translator,
        ?string $viewsPath = null
    ) {
        $this->viewsPath = $viewsPath ?? dirname(__DIR__) . '/views';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function share(array $data): void
    {
        $this->shared = array_merge($this->shared, $data);
    }

    /**
     * Render a view (dot notation, e.g. "auth.login") into a string.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = $this->renderFile($this->resolve($view), array_merge($this->shared, $data));

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile(
            $this->resolve('layouts.' . $layout),
            array_merge($this->shared, $data, ['content' => $content])
        );
    }

    /**
     * Render a partial/component and return its HTML.
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $view, array $data = []): string
    {
        return $this->renderFile($this->resolve($view), array_merge($this->shared, $data));
    }

    private function resolve(string $view): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $file = $this->viewsPath . DIRECTORY_SEPARATOR . $relative . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('View "%s" not found.', $view));
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $file, array $data): string
    {
        // Expose the translator to views as $t for convenience.
        $data['t'] = $this->translator;

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
