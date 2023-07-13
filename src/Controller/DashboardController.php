<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard/{_locale}')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly JobRepository $jobRepository,
    ) {
    }

    #[Route('/widgets', name: 'dashboard_widgets', methods: ['GET'])]
    public function getDashboardWidgetData(): Response {
        $activeModules = explode(',', $this->getParameter('api.activated_modules'));

        $widgetData = [];
        if (in_array('contact', $activeModules)) {
            $widgetData['contact_counter'] = 0;
            $widgetData['contact_chart'] = [
                'data' => [],
                'labels' => [],
                'current_amount' => 0,
                'current_change' => 0,
            ];
        }
        if (in_array('item', $activeModules)) {
            $widgetData['item_counter'] = 0;
        }
        if (in_array('job', $activeModules)) {
            $jobLast12Months = $this->jobRepository->findByDateRange(
                (new \DateTime())->modify('-12 months'),
                new \DateTime()
            );
            $widgetData['job_counter'] = 0;
            $widgetData['job_test'] = $jobLast12Months;
        }
        return $this->json([
            'widget_data' => $widgetData,
        ]);
    }
}