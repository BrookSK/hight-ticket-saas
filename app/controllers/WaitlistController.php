<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\ActivityLogService;
use App\Services\RateLimiter;
use App\Services\WaitlistService;

/**
 * Public waitlist controller.
 *
 * Controls flow only: shows the form, validates anti-spam signals, delegates
 * registration to WaitlistService and shows the success page. All business
 * rules (validation, dedupe, UTM/source, activity, email) live in the service.
 */
final class WaitlistController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function show(Request $request, array $params = []): void
    {
        $this->render('site.waitlist', [
            'title'       => __('site.cta.waitlist'),
            'activePath'  => '/lista-de-espera',
            'canonical'   => '/lista-de-espera',
            'formStarted' => time(),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): void
    {
        // Anti-spam: honeypot must be empty and a minimal fill time must pass.
        if ($this->looksLikeSpam($request)) {
            // Silently accept to avoid giving bots feedback; nothing is stored.
            $this->redirect('/lista-de-espera/sucesso');

            return;
        }

        // Rate limit public submissions per IP.
        $rateKey = 'waitlist.' . $request->ip();
        if ($this->rateLimiter()->tooManyAttempts($rateKey, 10)) {
            $this->renderForm($request, ['general' => __('errors.rate_limited')], 429);

            return;
        }
        $this->rateLimiter()->hit($rateKey, 3600);

        $input = [
            'name'           => (string) $request->input('name', ''),
            'email'          => (string) $request->input('email', ''),
            'phone'          => (string) $request->input('phone', ''),
            'company'        => $request->input('company'),
            'sites_quantity' => $request->input('sites_quantity'),
            'main_service'   => $request->input('main_service'),
            'message'        => $request->input('message'),
            'consent'        => $request->input('consent'),
        ];

        $context = $this->captureContext($request);

        $result = $this->waitlist()->register($input, $context);

        if ($result['result'] === WaitlistService::RESULT_INVALID) {
            $this->renderForm($request, $this->translateErrors($result['errors'] ?? []), 422, $input);

            return;
        }

        if ($result['result'] === WaitlistService::RESULT_DUPLICATE) {
            // Friendly message; do not create a duplicate.
            $this->session()->flash('waitlist_message', __('site.waitlist.duplicate'));
            $this->redirect('/lista-de-espera/sucesso');

            return;
        }

        $this->activityLog()->record('waitlist_lead_created', null, [
            'object_type' => 'waitlist_lead',
            'object_id'   => $result['leadId'] ?? null,
        ]);

        $this->redirect('/lista-de-espera/sucesso');
    }

    /**
     * @param array<string, string> $params
     */
    public function success(Request $request, array $params = []): void
    {
        $this->render('site.waitlist-success', [
            'title'     => __('site.waitlist.success_title'),
            'canonical' => '/lista-de-espera/sucesso',
            'message'   => $this->session()->getFlash('waitlist_message'),
        ], 'public');
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    private function renderForm(Request $request, array $errors, int $status, array $old = []): void
    {
        $this->render('site.waitlist', [
            'title'       => __('site.cta.waitlist'),
            'activePath'  => '/lista-de-espera',
            'canonical'   => '/lista-de-espera',
            'errors'      => $errors,
            'old'         => $old,
            'formStarted' => time(),
        ], 'public', $status);
    }

    private function looksLikeSpam(Request $request): bool
    {
        // Honeypot field "website" must remain empty.
        if (trim((string) $request->input('website', '')) !== '') {
            return true;
        }

        // Time-trap: submissions faster than 3 seconds are likely bots.
        $started = (int) $request->input('form_started', '0');
        if ($started > 0 && (time() - $started) < 3) {
            return true;
        }

        return false;
    }

    /**
     * Capture UTM/source/url context from the request.
     *
     * @return array<string, mixed>
     */
    private function captureContext(Request $request): array
    {
        return [
            'source'       => $request->input('utm_source') ? 'campaign' : 'landing_page',
            'source_url'   => $request->input('source_url') ?: $request->header('Referer'),
            'utm_source'   => $request->input('utm_source'),
            'utm_medium'   => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_content'  => $request->input('utm_content'),
            'utm_term'     => $request->input('utm_term'),
        ];
    }

    /**
     * Convert field => translation-key map into field => translated message.
     *
     * @param array<string, string> $errors
     * @return array<string, string>
     */
    private function translateErrors(array $errors): array
    {
        $translated = [];
        foreach ($errors as $field => $key) {
            $translated[$field] = __($key, ['attribute' => __('site.waitlist.field_' . $field)]);
        }

        return $translated;
    }

    private function waitlist(): WaitlistService
    {
        /** @var WaitlistService $service */
        $service = $this->container->get(WaitlistService::class);

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
