<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Repositories\UserRepository;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\ConfigService;
use App\Services\RateLimiter;
use App\Services\UserService;

/**
 * Authentication controller.
 *
 * Controls flow only. Authentication and recovery logic live in services.
 * Applies rate limiting to login and recovery, and records audit activity.
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

        $rateKey = 'login.' . $request->ip();
        [$maxAttempts, $decay] = $this->loginThrottle();

        if ($this->rateLimiter()->tooManyAttempts($rateKey, $maxAttempts)) {
            $this->render('auth.login', [
                'title'    => __('auth.login.title'),
                'error'    => __('errors.rate_limited'),
                'oldEmail' => $email,
            ], 'public', 429);

            return;
        }

        if ($email === '' || $password === '' || !$this->auth()->attempt($email, $password)) {
            $this->rateLimiter()->hit($rateKey, $decay);
            $this->activityLog()->record('login', null, ['result' => 'failed']);

            $this->render('auth.login', [
                'title'    => __('auth.login.title'),
                'error'    => __('auth.failed'),
                'oldEmail' => $email,
            ], 'public', 422);

            return;
        }

        $this->rateLimiter()->clear($rateKey);

        $userId = $this->auth()->id();
        if ($userId !== null) {
            $this->users()->touchLogin($userId);
            $this->activityLog()->record('login', $userId, ['result' => 'success']);
        }

        $this->redirect('/app');
    }

    /**
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): void
    {
        $userId = $this->auth()->id();
        $this->activityLog()->record('logout', $userId);
        $this->auth()->logout();
        $this->redirect('/');
    }

    // ----------------------------------------------------- password recovery

    /**
     * @param array<string, string> $params
     */
    public function showForgot(Request $request, array $params = []): void
    {
        $this->render('auth.forgot', ['title' => __('auth.forgot.title')], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function sendReset(Request $request, array $params = []): void
    {
        $email = trim((string) $request->input('email', ''));

        $rateKey = 'forgot.' . $request->ip();
        if ($this->rateLimiter()->tooManyAttempts($rateKey, 5)) {
            $this->render('auth.forgot', [
                'title' => __('auth.forgot.title'),
                'error' => __('errors.rate_limited'),
            ], 'public', 429);

            return;
        }
        $this->rateLimiter()->hit($rateKey, 900);

        $token = $this->userService()->createPasswordResetToken($email);
        if ($token !== null) {
            $this->sendResetEmail($email, $token);
            $this->activityLog()->record('password_reset_requested', null, [
                'object_type' => 'user',
            ]);
        }

        // Always show the same message (avoid revealing whether the email exists).
        $this->render('auth.forgot', [
            'title'   => __('auth.forgot.title'),
            'success' => __('auth.forgot.sent'),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function showReset(Request $request, array $params = []): void
    {
        $this->render('auth.reset', [
            'title' => __('auth.reset.title'),
            'email' => (string) $request->query('email', ''),
            'token' => (string) $request->query('token', ''),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function reset(Request $request, array $params = []): void
    {
        $email = trim((string) $request->input('email', ''));
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        if ($password !== $confirm) {
            $this->render('auth.reset', [
                'title' => __('auth.reset.title'),
                'email' => $email,
                'token' => $token,
                'error' => __('validation.confirmed'),
            ], 'public', 422);

            return;
        }

        if (!$this->userService()->resetPassword($email, $token, $password)) {
            $this->render('auth.reset', [
                'title' => __('auth.reset.title'),
                'email' => $email,
                'token' => $token,
                'error' => __('auth.reset.invalid'),
            ], 'public', 422);

            return;
        }

        $this->activityLog()->record('password_reset', null, ['object_type' => 'user']);
        $this->session()->flash('status', __('auth.reset.success'));
        $this->redirect('/login');
    }

    private function sendResetEmail(string $email, string $token): void
    {
        /** @var \App\Services\EmailTemplateService $templates */
        $templates = $this->container->get(\App\Services\EmailTemplateService::class);
        /** @var \App\Services\MailService $mail */
        $mail = $this->container->get(\App\Services\MailService::class);

        $resetUrl = '/redefinir-senha?email=' . urlencode($email) . '&token=' . urlencode($token);
        $html = $templates->render('password-reset', ['resetUrl' => $resetUrl]);
        $mail->send($email, $email, __('mail.reset.subject'), $html);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function loginThrottle(): array
    {
        /** @var ConfigService $config */
        $config = $this->container->get(ConfigService::class);
        $max = (int) ($config->get('security_login_max_attempts', '5') ?? 5);
        $decay = (int) ($config->get('security_login_decay_seconds', '900') ?? 900);

        return [max(1, $max), max(60, $decay)];
    }

    private function auth(): AuthService
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth;
    }

    private function users(): UserRepository
    {
        /** @var UserRepository $users */
        $users = $this->container->get(UserRepository::class);

        return $users;
    }

    private function userService(): UserService
    {
        /** @var UserService $service */
        $service = $this->container->get(UserService::class);

        return $service;
    }

    private function rateLimiter(): RateLimiter
    {
        /** @var RateLimiter $limiter */
        $limiter = $this->container->get(RateLimiter::class);

        return $limiter;
    }

    private function activityLog(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
