<?php

declare(strict_types=1);

namespace App\Core;

use App\Libraries\Csrf;
use App\Libraries\Request;
use App\Libraries\Response;
use App\Libraries\Session;
use App\Libraries\Translator;

/**
 * Base controller.
 *
 * Controllers only control flow: they read the request, call services, and
 * return a view or JSON response. They never contain business logic, run SQL,
 * or perform complex calculations.
 */
abstract class Controller
{
    public function __construct(protected readonly Container $container)
    {
    }

    protected function response(): Response
    {
        /** @var Response $response */
        $response = $this->container->get('response');

        return $response;
    }

    protected function view(): View
    {
        /** @var View $view */
        $view = $this->container->get('view');

        return $view;
    }

    protected function session(): Session
    {
        /** @var Session $session */
        $session = $this->container->get('session');

        return $session;
    }

    protected function translator(): Translator
    {
        /** @var Translator $translator */
        $translator = $this->container->get('translator');

        return $translator;
    }

    protected function csrf(): Csrf
    {
        /** @var Csrf $csrf */
        $csrf = $this->container->get('csrf');

        return $csrf;
    }

    /**
     * Render a view within a layout and send it as HTML.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], string $layout = 'app', int $status = 200): void
    {
        $html = $this->view()->render($view, $data, $layout);
        $this->response()->html($html, $status);
    }

    /**
     * Redirect helper.
     */
    protected function redirect(string $location, int $status = 302): void
    {
        $this->response()->redirect($location, $status);
    }
}
