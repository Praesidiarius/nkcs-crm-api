<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard/{_locale}')]
class DashboardController extends AbstractController
{
    public function __construct() {
    }

    #[Route('/widgets', name: 'dashboard_widgets', methods: ['GET'])]
    public function getDashboardWidgetData(): Response {
        $activeModules = explode(',', $this->getParameter('api.activated_modules'));

        $widgetData = [];
        if (in_array('contact', $activeModules)) {
            $widgetData['contact_counter'] = 0;
        }
        if (in_array('item', $activeModules)) {
            $widgetData['item_counter'] = 0;
        }
        if (in_array('job', $activeModules)) {
            $widgetData['job_counter'] = 0;
        }
        return $this->json([
            'widget_data' => $widgetData,
        ]);
    }
}