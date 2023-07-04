<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\LegacyContactRepository;
use App\Repository\ItemRepository;
use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/dashboard/{_locale}')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly LegacyContactRepository $contactRepository,
        private readonly JobRepository           $jobRepository,
        private readonly ItemRepository          $itemRepository,
    ) {
    }

    #[Route('/widgets', name: 'dashboard_widgets', methods: ['GET'])]
    public function getDashboardWidgetData(): Response {
        $activeModules = explode(',', $this->getParameter('api.activated_modules'));

        $widgetData = [];
        if (in_array('contact', $activeModules)) {
            $widgetData['contact_counter'] = $this->contactRepository->count([]);
        }
        if (in_array('item', $activeModules)) {
            $widgetData['item_counter'] = $this->itemRepository->count([]);
        }
        if (in_array('job', $activeModules)) {
            $widgetData['job_counter'] = $this->jobRepository->count([]);
        }
        return $this->json([
            'widget_data' => $widgetData,
        ]);
    }
}