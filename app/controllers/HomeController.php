<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;

/**
 * Institutional home controller.
 *
 * Controls flow only: renders the public home page.
 */
final class HomeController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $this->render('home.index', [
            'title'    => __('common.app_name'),
            'canonical' => '/',
        ], 'public');
    }
}
