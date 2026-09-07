<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\ConfigService;

/**
 * Admin management of Configurações Gerais (grouped by category).
 *
 * All settings are stored in the database and read/written through
 * ConfigService. No .env. Secret values are never echoed back to the form.
 */
final class SettingsController extends Controller
{
    /** Editable keys per category tab. Secret keys are write-only in the UI. */
    private const GROUPS = [
        'site' => [
            'system_name', 'site_description', 'site_logo', 'site_favicon',
            'site_phone', 'site_email', 'site_whatsapp', 'site_address',
            'social_instagram', 'social_facebook', 'social_linkedin', 'social_youtube',
        ],
        'email' => [
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'smtp_from_name', 'smtp_from_email',
        ],
        'seo' => [
            'seo_title', 'seo_description', 'seo_og_image', 'seo_google_verification',
        ],
        'whatsapp' => [
            'whatsapp_message', 'whatsapp_provider', 'whatsapp_api_url',
            'whatsapp_api_key', 'whatsapp_instance', 'whatsapp_status',
        ],
        'ai' => [
            'ai_provider', 'ai_api_key', 'ai_default_model', 'ai_temperature',
            'ai_max_tokens', 'ai_status',
        ],
        'integrations' => [
            'analytics_ga_id', 'analytics_gtm_id', 'analytics_meta_pixel', 'analytics_clarity_id',
        ],
        'security' => [
            'security_login_max_attempts', 'security_login_decay_seconds',
        ],
    ];

    /** Keys that are secret (write-only; not shown pre-filled). */
    private const SECRET_KEYS = ['smtp_password', 'whatsapp_api_key', 'ai_api_key'];

    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): void
    {
        $group = (string) ($params['group'] ?? 'site');
        if (!isset(self::GROUPS[$group])) {
            $group = 'site';
        }

        $values = [];
        foreach (self::GROUPS[$group] as $key) {
            $values[$key] = in_array($key, self::SECRET_KEYS, true)
                ? ''
                : (string) ($this->config()->get($key) ?? '');
        }

        $this->render('admin.settings.index', [
            'title'      => __('admin.settings.title'),
            'activePath' => '/app/settings',
            'group'      => $group,
            'groups'     => array_keys(self::GROUPS),
            'keys'       => self::GROUPS[$group],
            'values'     => $values,
            'secretKeys' => self::SECRET_KEYS,
        ], 'admin');
    }

    /**
     * @param array<string, string> $params
     */
    public function update(Request $request, array $params = []): void
    {
        $group = (string) ($params['group'] ?? 'site');
        if (!isset(self::GROUPS[$group])) {
            $this->response()->html('<h1>' . e(__('errors.not_found')) . '</h1>', 404);

            return;
        }

        foreach (self::GROUPS[$group] as $key) {
            $value = $request->input($key);

            // Skip empty secret fields (do not overwrite stored secrets with blank).
            if (in_array($key, self::SECRET_KEYS, true) && (string) $value === '') {
                continue;
            }

            $this->config()->set($key, $value !== null ? (string) $value : null, $group);
        }

        $this->log()->record('settings_updated', $this->userId(), ['object_type' => 'settings', 'object_id' => $group]);
        $this->session()->flash('status', __('admin.settings.updated'));
        $this->redirect('/app/settings/' . $group);
    }

    private function userId(): ?int
    {
        /** @var AuthService $auth */
        $auth = $this->container->get(AuthService::class);

        return $auth->id();
    }

    private function config(): ConfigService
    {
        /** @var ConfigService $config */
        $config = $this->container->get(ConfigService::class);

        return $config;
    }

    private function log(): ActivityLogService
    {
        /** @var ActivityLogService $log */
        $log = $this->container->get(ActivityLogService::class);

        return $log;
    }
}
