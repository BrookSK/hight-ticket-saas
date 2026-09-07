<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\AuthService;

/**
 * Authentication controller.
 *
 * Controls flow only. Authentication logic lives in AuthService.
 */
final class AuthController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function showLogin(Request $request, array $params = []): void
    {
        if ($this->auth()->check()) {
            $this->redirect('/app');

            return;
        }

        $this->render('auth.login', [
            'title' => __('auth.login.title'),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function login(Request $request, array $params = []): void
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '' || !$this->auth()->attempt($email, $password)) {
            $this->render('auth.login', [
                'title'    => __('auth.login.title'),
                'error'    => __('auth.failed'),
                'oldEmail' => $email,
            ], 'public', 422);

            return;
        }

        $this->redirect('/app');
    }

    /**
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): void
    {
        $this->auth()->logout();
        $this->redirect('/');
    }

    private function auth(): AuthService
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth;
    }
}
