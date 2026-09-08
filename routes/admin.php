<?php

declare(strict_types=1);

use App\Controllers\Admin\ActivityController;
use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\CompanyController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\CrmDashboardController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\CampaignController;
use App\Controllers\Admin\ConversationController;
use App\Controllers\Admin\LeadController;
use App\Controllers\Admin\LogController;
use App\Controllers\Admin\OpportunityReviewController;
use App\Controllers\Admin\OutreachController;
use App\Controllers\Admin\OutreachDashboardController;
use App\Controllers\Admin\OutreachSequenceController;
use App\Controllers\Admin\OutreachTemplateController;
use App\Controllers\Admin\PipelineController;
use App\Controllers\Admin\ProspectingDashboardController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\PlanController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\WaitlistController;
use App\Core\Router;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CsrfMiddleware;
use App\Middlewares\PermissionMiddleware;

/**
 * Admin (Super Admin panel) routes.
 *
 * The whole /app group requires authentication. Each route additionally
 * enforces its granular permission via PermissionMiddleware. State-changing
 * routes go through CsrfMiddleware. Super Admin bypasses permission checks.
 */

return static function (Router $router): void {
    $router->group('/app', static function (Router $router): void {
        // Dashboard.
        $router->get('/', [DashboardController::class, 'index'], [
            PermissionMiddleware::for('dashboard.view'),
        ]);

        // Waitlist management.
        $router->get('/waitlist', [WaitlistController::class, 'index'], [PermissionMiddleware::for('waitlist.view')]);
        $router->get('/waitlist/export', [WaitlistController::class, 'export'], [PermissionMiddleware::for('waitlist.export')]);
        $router->get('/waitlist/{id}', [WaitlistController::class, 'show'], [PermissionMiddleware::for('waitlist.view')]);
        $router->post('/waitlist/{id}/status', [WaitlistController::class, 'updateStatus'], [PermissionMiddleware::for('waitlist.edit'), CsrfMiddleware::class]);
        $router->post('/waitlist/{id}/notes', [WaitlistController::class, 'updateNotes'], [PermissionMiddleware::for('waitlist.edit'), CsrfMiddleware::class]);
        $router->post('/waitlist/{id}/delete', [WaitlistController::class, 'delete'], [PermissionMiddleware::for('waitlist.delete'), CsrfMiddleware::class]);

        // Audits (Fase 2).
        $router->get('/audits', [AuditController::class, 'index'], [PermissionMiddleware::for('audits.view')]);
        $router->get('/audits/create', [AuditController::class, 'create'], [PermissionMiddleware::for('audits.create')]);
        $router->post('/audits', [AuditController::class, 'store'], [PermissionMiddleware::for('audits.create'), CsrfMiddleware::class]);
        $router->get('/audits/{id}', [AuditController::class, 'show'], [PermissionMiddleware::for('audits.view')]);
        $router->get('/audits/{id}/progress', [AuditController::class, 'progress'], [PermissionMiddleware::for('audits.view')]);
        $router->get('/audits/{id}/report', [AuditController::class, 'report'], [PermissionMiddleware::for('audits.export')]);
        $router->post('/audits/{id}/delete', [AuditController::class, 'delete'], [PermissionMiddleware::for('audits.delete'), CsrfMiddleware::class]);
        $router->post('/audits/{id}/to-lead', [AuditController::class, 'transformToLead'], [PermissionMiddleware::for('leads.create'), CsrfMiddleware::class]);

        // CRM — Dashboard comercial.
        $router->get('/crm', [CrmDashboardController::class, 'index'], [PermissionMiddleware::for('leads.view')]);

        // CRM — Empresas.
        $router->get('/companies', [CompanyController::class, 'index'], [PermissionMiddleware::for('companies.view')]);
        $router->get('/companies/create', [CompanyController::class, 'create'], [PermissionMiddleware::for('companies.create')]);
        $router->post('/companies', [CompanyController::class, 'store'], [PermissionMiddleware::for('companies.create'), CsrfMiddleware::class]);
        $router->get('/companies/{id}', [CompanyController::class, 'show'], [PermissionMiddleware::for('companies.view')]);
        $router->get('/companies/{id}/edit', [CompanyController::class, 'edit'], [PermissionMiddleware::for('companies.update')]);
        $router->put('/companies/{id}', [CompanyController::class, 'update'], [PermissionMiddleware::for('companies.update'), CsrfMiddleware::class]);
        $router->post('/companies/{id}/archive', [CompanyController::class, 'archive'], [PermissionMiddleware::for('companies.update'), CsrfMiddleware::class]);
        $router->post('/companies/{id}/delete', [CompanyController::class, 'delete'], [PermissionMiddleware::for('companies.delete'), CsrfMiddleware::class]);

        // CRM — Contatos (aninhados à empresa).
        $router->post('/companies/{company}/contacts', [ContactController::class, 'store'], [PermissionMiddleware::for('contacts.create'), CsrfMiddleware::class]);
        $router->put('/contacts/{id}', [ContactController::class, 'update'], [PermissionMiddleware::for('contacts.update'), CsrfMiddleware::class]);
        $router->post('/contacts/{id}/delete', [ContactController::class, 'delete'], [PermissionMiddleware::for('contacts.delete'), CsrfMiddleware::class]);

        // CRM — Leads.
        $router->get('/leads', [LeadController::class, 'index'], [PermissionMiddleware::for('leads.view')]);
        $router->get('/leads/create', [LeadController::class, 'create'], [PermissionMiddleware::for('leads.create')]);
        $router->post('/leads', [LeadController::class, 'store'], [PermissionMiddleware::for('leads.create'), CsrfMiddleware::class]);
        $router->get('/leads/{id}', [LeadController::class, 'show'], [PermissionMiddleware::for('leads.view')]);
        $router->get('/leads/{id}/edit', [LeadController::class, 'edit'], [PermissionMiddleware::for('leads.update')]);
        $router->put('/leads/{id}', [LeadController::class, 'update'], [PermissionMiddleware::for('leads.update'), CsrfMiddleware::class]);
        $router->post('/leads/{id}/status', [LeadController::class, 'changeStatus'], [PermissionMiddleware::for('leads.update'), CsrfMiddleware::class]);
        $router->post('/leads/{id}/assign', [LeadController::class, 'assign'], [PermissionMiddleware::for('leads.assign'), CsrfMiddleware::class]);
        $router->post('/leads/{id}/win', [LeadController::class, 'win'], [PermissionMiddleware::for('leads.update'), CsrfMiddleware::class]);
        $router->post('/leads/{id}/lose', [LeadController::class, 'lose'], [PermissionMiddleware::for('leads.update'), CsrfMiddleware::class]);
        $router->post('/leads/{id}/delete', [LeadController::class, 'delete'], [PermissionMiddleware::for('leads.delete'), CsrfMiddleware::class]);

        // CRM — Pipeline (Kanban).
        $router->get('/pipeline', [PipelineController::class, 'index'], [PermissionMiddleware::for('pipeline.view')]);
        $router->post('/pipeline/{id}/move', [PipelineController::class, 'move'], [PermissionMiddleware::for('pipeline.update'), CsrfMiddleware::class]);

        // CRM — Atividades / tarefas.
        $router->post('/activities', [ActivityController::class, 'store'], [PermissionMiddleware::for('activities.create'), CsrfMiddleware::class]);
        $router->post('/activities/{id}/complete', [ActivityController::class, 'complete'], [PermissionMiddleware::for('activities.update'), CsrfMiddleware::class]);
        $router->post('/activities/{id}/delete', [ActivityController::class, 'delete'], [PermissionMiddleware::for('activities.delete'), CsrfMiddleware::class]);

        // Prospecção (Fase 4).
        $router->get('/prospecting', [ProspectingDashboardController::class, 'index'], [PermissionMiddleware::for('prospecting.view')]);
        $router->get('/prospecting/exclusions', [ProspectingDashboardController::class, 'exclusions'], [PermissionMiddleware::for('prospecting.view')]);
        $router->post('/prospecting/exclusions', [ProspectingDashboardController::class, 'addExclusion'], [PermissionMiddleware::for('prospecting.update'), CsrfMiddleware::class]);
        $router->post('/prospecting/exclusions/{id}/delete', [ProspectingDashboardController::class, 'removeExclusion'], [PermissionMiddleware::for('prospecting.update'), CsrfMiddleware::class]);

        // Prospecção — campanhas.
        $router->get('/prospecting/campaigns', [CampaignController::class, 'index'], [PermissionMiddleware::for('prospecting.view')]);
        $router->get('/prospecting/campaigns/create', [CampaignController::class, 'create'], [PermissionMiddleware::for('prospecting.create')]);
        $router->post('/prospecting/campaigns', [CampaignController::class, 'store'], [PermissionMiddleware::for('prospecting.create'), CsrfMiddleware::class]);
        $router->get('/prospecting/campaigns/{id}', [CampaignController::class, 'show'], [PermissionMiddleware::for('prospecting.view')]);
        $router->get('/prospecting/campaigns/{id}/progress', [CampaignController::class, 'progress'], [PermissionMiddleware::for('prospecting.view')]);
        $router->post('/prospecting/campaigns/{id}/run', [CampaignController::class, 'run'], [PermissionMiddleware::for('prospecting.run'), CsrfMiddleware::class]);
        $router->post('/prospecting/campaigns/{id}/pause', [CampaignController::class, 'pause'], [PermissionMiddleware::for('prospecting.pause'), CsrfMiddleware::class]);
        $router->post('/prospecting/campaigns/{id}/cancel', [CampaignController::class, 'cancel'], [PermissionMiddleware::for('prospecting.cancel'), CsrfMiddleware::class]);
        $router->post('/prospecting/campaigns/{id}/delete', [CampaignController::class, 'delete'], [PermissionMiddleware::for('prospecting.delete'), CsrfMiddleware::class]);

        // Prospecção — revisão de oportunidades.
        $router->get('/prospecting/review', [OpportunityReviewController::class, 'index'], [PermissionMiddleware::for('prospecting.review')]);
        $router->post('/prospecting/review/convert-batch', [OpportunityReviewController::class, 'convertBatch'], [PermissionMiddleware::for('prospecting.convert'), CsrfMiddleware::class]);
        $router->get('/prospecting/review/{id}', [OpportunityReviewController::class, 'show'], [PermissionMiddleware::for('prospecting.review')]);
        $router->post('/prospecting/review/{id}/convert', [OpportunityReviewController::class, 'convert'], [PermissionMiddleware::for('prospecting.convert'), CsrfMiddleware::class]);
        $router->post('/prospecting/review/{id}/discard', [OpportunityReviewController::class, 'discard'], [PermissionMiddleware::for('prospecting.review'), CsrfMiddleware::class]);
        $router->post('/prospecting/review/{id}/ignore', [OpportunityReviewController::class, 'ignore'], [PermissionMiddleware::for('prospecting.review'), CsrfMiddleware::class]);

        // Outreach (Fase 5) — dashboard comercial.
        $router->get('/outreach', [OutreachDashboardController::class, 'index'], [PermissionMiddleware::for('outreach.view')]);

        // Outreach — templates.
        $router->get('/outreach/templates', [OutreachTemplateController::class, 'index'], [PermissionMiddleware::for('outreach.manage_templates')]);
        $router->get('/outreach/templates/create', [OutreachTemplateController::class, 'create'], [PermissionMiddleware::for('outreach.manage_templates')]);
        $router->post('/outreach/templates', [OutreachTemplateController::class, 'store'], [PermissionMiddleware::for('outreach.manage_templates'), CsrfMiddleware::class]);
        $router->get('/outreach/templates/{id}/edit', [OutreachTemplateController::class, 'edit'], [PermissionMiddleware::for('outreach.manage_templates')]);
        $router->put('/outreach/templates/{id}', [OutreachTemplateController::class, 'update'], [PermissionMiddleware::for('outreach.manage_templates'), CsrfMiddleware::class]);
        $router->post('/outreach/templates/{id}/delete', [OutreachTemplateController::class, 'delete'], [PermissionMiddleware::for('outreach.manage_templates'), CsrfMiddleware::class]);

        // Outreach — sequences (follow-up).
        $router->get('/outreach/sequences', [OutreachSequenceController::class, 'index'], [PermissionMiddleware::for('outreach.manage_sequences')]);
        $router->get('/outreach/sequences/create', [OutreachSequenceController::class, 'create'], [PermissionMiddleware::for('outreach.manage_sequences')]);
        $router->post('/outreach/sequences', [OutreachSequenceController::class, 'store'], [PermissionMiddleware::for('outreach.manage_sequences'), CsrfMiddleware::class]);
        $router->get('/outreach/sequences/{id}/edit', [OutreachSequenceController::class, 'edit'], [PermissionMiddleware::for('outreach.manage_sequences')]);
        $router->put('/outreach/sequences/{id}', [OutreachSequenceController::class, 'update'], [PermissionMiddleware::for('outreach.manage_sequences'), CsrfMiddleware::class]);
        $router->post('/outreach/sequences/{id}/delete', [OutreachSequenceController::class, 'delete'], [PermissionMiddleware::for('outreach.manage_sequences'), CsrfMiddleware::class]);

        // Outreach — conversations.
        $router->get('/outreach/conversations', [ConversationController::class, 'index'], [PermissionMiddleware::for('outreach.view')]);
        $router->get('/outreach/conversations/{id}', [ConversationController::class, 'show'], [PermissionMiddleware::for('outreach.view')]);
        $router->post('/outreach/conversations/{id}/close', [ConversationController::class, 'close'], [PermissionMiddleware::for('outreach.update'), CsrfMiddleware::class]);

        // Outreach — suppressions (opt-out).
        $router->get('/outreach/suppressions', [OutreachController::class, 'suppressions'], [PermissionMiddleware::for('outreach.view')]);
        $router->post('/outreach/suppressions', [OutreachController::class, 'addSuppression'], [PermissionMiddleware::for('outreach.update'), CsrfMiddleware::class]);
        $router->post('/outreach/suppressions/{id}/delete', [OutreachController::class, 'removeSuppression'], [PermissionMiddleware::for('outreach.update'), CsrfMiddleware::class]);

        // Outreach — reports (shareable).
        $router->get('/outreach/reports', [ReportController::class, 'index'], [PermissionMiddleware::for('outreach.view')]);
        $router->post('/outreach/reports', [ReportController::class, 'store'], [PermissionMiddleware::for('outreach.create'), CsrfMiddleware::class]);
        $router->get('/outreach/reports/{id}', [ReportController::class, 'show'], [PermissionMiddleware::for('outreach.view')]);
        $router->post('/outreach/reports/{id}/revoke', [ReportController::class, 'revoke'], [PermissionMiddleware::for('outreach.update'), CsrfMiddleware::class]);

        // Outreach — outbox + prepare contact + approvals + enrollment.
        $router->get('/outreach/outbox', [OutreachController::class, 'outbox'], [PermissionMiddleware::for('outreach.view')]);
        $router->post('/outreach/messages', [OutreachController::class, 'store'], [PermissionMiddleware::for('outreach.create'), CsrfMiddleware::class]);
        $router->post('/outreach/messages/{id}/approve', [OutreachController::class, 'approve'], [PermissionMiddleware::for('outreach.approve'), CsrfMiddleware::class]);
        $router->post('/outreach/messages/{id}/cancel', [OutreachController::class, 'cancel'], [PermissionMiddleware::for('outreach.cancel'), CsrfMiddleware::class]);
        $router->post('/outreach/enroll', [OutreachController::class, 'enroll'], [PermissionMiddleware::for('outreach.schedule'), CsrfMiddleware::class]);
        $router->get('/outreach/prepare/{lead}', [OutreachController::class, 'prepare'], [PermissionMiddleware::for('outreach.create')]);

        // Plans.
        $router->get('/plans', [PlanController::class, 'index'], [PermissionMiddleware::for('plans.view')]);
        $router->get('/plans/create', [PlanController::class, 'create'], [PermissionMiddleware::for('plans.create')]);
        $router->post('/plans', [PlanController::class, 'store'], [PermissionMiddleware::for('plans.create'), CsrfMiddleware::class]);
        $router->get('/plans/{id}/edit', [PlanController::class, 'edit'], [PermissionMiddleware::for('plans.edit')]);
        $router->put('/plans/{id}', [PlanController::class, 'update'], [PermissionMiddleware::for('plans.edit'), CsrfMiddleware::class]);
        $router->post('/plans/{id}/delete', [PlanController::class, 'delete'], [PermissionMiddleware::for('plans.delete'), CsrfMiddleware::class]);

        // Users.
        $router->get('/users', [UserController::class, 'index'], [PermissionMiddleware::for('users.view')]);
        $router->get('/users/create', [UserController::class, 'create'], [PermissionMiddleware::for('users.create')]);
        $router->post('/users', [UserController::class, 'store'], [PermissionMiddleware::for('users.create'), CsrfMiddleware::class]);
        $router->get('/users/{id}/edit', [UserController::class, 'edit'], [PermissionMiddleware::for('users.edit')]);
        $router->put('/users/{id}', [UserController::class, 'update'], [PermissionMiddleware::for('users.edit'), CsrfMiddleware::class]);
        $router->post('/users/{id}/delete', [UserController::class, 'delete'], [PermissionMiddleware::for('users.delete'), CsrfMiddleware::class]);
        $router->post('/users/{id}/impersonate', [UserController::class, 'impersonate'], [CsrfMiddleware::class]);
        $router->post('/impersonate/stop', [UserController::class, 'stopImpersonation'], [CsrfMiddleware::class]);

        // Roles & permissions.
        $router->get('/roles', [RoleController::class, 'index'], [PermissionMiddleware::for('roles.view')]);
        $router->get('/roles/{id}/edit', [RoleController::class, 'edit'], [PermissionMiddleware::for('roles.edit')]);
        $router->put('/roles/{id}', [RoleController::class, 'update'], [PermissionMiddleware::for('roles.edit'), CsrfMiddleware::class]);

        // Settings (Configurações Gerais).
        $router->get('/settings', [SettingsController::class, 'index'], [PermissionMiddleware::for('settings.view')]);
        $router->get('/settings/{group}', [SettingsController::class, 'index'], [PermissionMiddleware::for('settings.view')]);
        $router->put('/settings/{group}', [SettingsController::class, 'update'], [PermissionMiddleware::for('settings.edit'), CsrfMiddleware::class]);

        // Logs.
        $router->get('/logs', [LogController::class, 'index'], [PermissionMiddleware::for('logs.view')]);
    }, [AuthMiddleware::class]);
};
