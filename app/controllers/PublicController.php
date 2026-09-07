<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\Request;
use App\Services\PlanService;

/**
 * Institutional site controller.
 *
 * Controls flow only: renders public marketing/legal pages. Plan data comes
 * from PlanService (database), never hardcoded.
 */
final class PublicController extends Controller
{
    /**
     * @param array<string, string> $params
     */
    public function home(Request $request, array $params = []): void
    {
        $this->render('site.home', [
            'title'      => '',
            'activePath' => '/',
            'canonical'  => '/',
            'plans'      => $this->plans()->publicPlans(),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function features(Request $request, array $params = []): void
    {
        $this->render('site.features', [
            'title'      => __('site.nav.features'),
            'activePath' => '/recursos',
            'canonical'  => '/recursos',
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function howItWorks(Request $request, array $params = []): void
    {
        $this->render('site.how', [
            'title'      => __('site.nav.how'),
            'activePath' => '/como-funciona',
            'canonical'  => '/como-funciona',
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function plansPage(Request $request, array $params = []): void
    {
        $this->render('site.plans', [
            'title'      => __('site.nav.plans'),
            'activePath' => '/planos',
            'canonical'  => '/planos',
            'plans'      => $this->plans()->publicPlans(),
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function faq(Request $request, array $params = []): void
    {
        $this->render('site.faq', [
            'title'      => __('site.nav.faq'),
            'activePath' => '/faq',
            'canonical'  => '/faq',
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function contact(Request $request, array $params = []): void
    {
        $this->render('site.contact', [
            'title'      => __('site.nav.contact'),
            'activePath' => '/contato',
            'canonical'  => '/contato',
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function terms(Request $request, array $params = []): void
    {
        $this->render('site.terms', [
            'title'     => __('site.footer.terms'),
            'canonical' => '/termos-de-uso',
        ], 'public');
    }

    /**
     * @param array<string, string> $params
     */
    public function privacy(Request $request, array $params = []): void
    {
        $this->render('site.privacy', [
            'title'     => __('site.footer.privacy'),
            'canonical' => '/politica-de-privacidade',
        ], 'public');
    }

    private function plans(): PlanService
    {
        /** @var PlanService $plans */
        $plans = $this->container->get(PlanService::class);

        return $plans;
    }
}
