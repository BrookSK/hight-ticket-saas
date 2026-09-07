<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Events\EventDispatcher;
use App\Repositories\WaitlistActivityRepository;
use App\Repositories\WaitlistLeadRepository;

/**
 * Waitlist business logic.
 *
 * Validates and stores leads, prevents duplicate e-mails, captures origin/UTM,
 * records a timeline activity, dispatches a domain event and triggers the
 * confirmation e-mail (when SMTP is configured). All rules live here — never in
 * controllers or views.
 */
final class WaitlistService extends Service
{
    /** Allowed statuses for waitlist leads. */
    public const STATUSES = [
        'new', 'contacted', 'interested', 'invited', 'converted', 'not_interested', 'discarded',
    ];

    public const RESULT_CREATED = 'created';
    public const RESULT_DUPLICATE = 'duplicate';
    public const RESULT_INVALID = 'invalid';

    /**
     * Register a new lead.
     *
     * @param array<string, mixed> $input Raw form input.
     * @param array<string, mixed> $context UTM/source/url captured from request.
     * @return array{result:string, errors?:array<string,string>, leadId?:int}
     */
    public function register(array $input, array $context = []): array
    {
        $errors = $this->validate($input);
        if ($errors !== []) {
            return ['result' => self::RESULT_INVALID, 'errors' => $errors];
        }

        $email = strtolower(trim((string) ($input['email'] ?? '')));

        if ($this->leads()->findByEmail($email) !== null) {
            return ['result' => self::RESULT_DUPLICATE];
        }

        $leadId = $this->leads()->create([
            'name'           => trim((string) $input['name']),
            'email'          => $email,
            'phone'          => trim((string) $input['phone']),
            'company'        => $this->nullableTrim($input['company'] ?? null),
            'sites_quantity' => $this->nullableTrim($input['sites_quantity'] ?? null),
            'main_service'   => $this->nullableTrim($input['main_service'] ?? null),
            'message'        => $this->nullableTrim($input['message'] ?? null),
            'status'         => 'new',
            'source'         => (string) ($context['source'] ?? 'landing_page'),
            'source_url'     => $this->nullableTrim($context['source_url'] ?? null),
            'utm_source'     => $this->nullableTrim($context['utm_source'] ?? null),
            'utm_medium'     => $this->nullableTrim($context['utm_medium'] ?? null),
            'utm_campaign'   => $this->nullableTrim($context['utm_campaign'] ?? null),
            'utm_content'    => $this->nullableTrim($context['utm_content'] ?? null),
            'utm_term'       => $this->nullableTrim($context['utm_term'] ?? null),
            'consent'        => !empty($input['consent']) ? 1 : 0,
        ]);

        $this->activities()->create($leadId, null, 'created', 'Lead cadastrado na lista de espera.');

        $this->events()->dispatch('waitlist.lead.created', [
            'leadId' => $leadId,
            'name'   => trim((string) $input['name']),
            'email'  => $email,
        ]);

        $this->sendConfirmationEmail(trim((string) $input['name']), $email);

        return ['result' => self::RESULT_CREATED, 'leadId' => $leadId];
    }

    /**
     * Change a lead's status and record the activity.
     */
    public function changeStatus(int $leadId, string $status, ?int $actingUserId): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $lead = $this->leads()->findById($leadId);
        if ($lead === null) {
            return false;
        }

        $this->leads()->updateStatus($leadId, $status);
        $this->leads()->touchContact($leadId);
        $this->activities()->create(
            $leadId,
            $actingUserId,
            'status_changed',
            'Status alterado para: ' . $status
        );

        return true;
    }

    /**
     * Add/replace internal notes and record the activity.
     */
    public function updateNotes(int $leadId, ?string $notes, ?int $actingUserId): bool
    {
        $lead = $this->leads()->findById($leadId);
        if ($lead === null) {
            return false;
        }

        $this->leads()->updateNotes($leadId, $notes !== null ? trim($notes) : null);
        $this->activities()->create($leadId, $actingUserId, 'note_added', 'Nota interna atualizada.');

        return true;
    }

    /**
     * Validate lead input. Returns a map of field => translation key.
     *
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function validate(array $input): array
    {
        $errors = [];

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'validation.required';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email === '') {
            $errors['email'] = 'validation.required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'validation.email';
        }

        $phone = trim((string) ($input['phone'] ?? ''));
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($phone === '') {
            $errors['phone'] = 'validation.required';
        } elseif (strlen($digits) < 10 || strlen($digits) > 13) {
            $errors['phone'] = 'validation.phone';
        }

        return $errors;
    }

    private function sendConfirmationEmail(string $name, string $email): void
    {
        /** @var EmailTemplateService $templates */
        $templates = $this->container->get(EmailTemplateService::class);
        /** @var MailService $mail */
        $mail = $this->container->get(MailService::class);

        $html = $templates->render('waitlist-confirmation', [
            'name'    => $name,
            'siteUrl' => '/',
        ]);

        $mail->send($email, $name, __('mail.waitlist.subject'), $html);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function leads(): WaitlistLeadRepository
    {
        /** @var WaitlistLeadRepository $repository */
        $repository = $this->container->get(WaitlistLeadRepository::class);

        return $repository;
    }

    private function activities(): WaitlistActivityRepository
    {
        /** @var WaitlistActivityRepository $repository */
        $repository = $this->container->get(WaitlistActivityRepository::class);

        return $repository;
    }

    private function events(): EventDispatcher
    {
        /** @var EventDispatcher $events */
        $events = $this->container->get('events');

        return $events;
    }
}
