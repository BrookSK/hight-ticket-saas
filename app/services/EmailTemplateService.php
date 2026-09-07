<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Core\View;

/**
 * Renders email bodies from reusable templates.
 *
 * Emails never build HTML inline — they use view templates wrapped by the base
 * email layout (app/views/emails/layout.php). All text is translatable.
 */
final class EmailTemplateService extends Service
{
    /**
     * Render a named email template into full HTML.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        /** @var View $view */
        $view = $this->container->get('view');

        /** @var ConfigService $config */
        $config = $this->container->get(ConfigService::class);
        $appName = $config->get('system_name', 'LRV Web');

        $data['appName'] = $appName;

        return $view->render('emails.' . $template, $data, 'email');
    }
}
