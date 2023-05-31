<?php

namespace App\Controller;

use App\Entity\Job;
use App\Form\Job\JobType;
use App\Repository\ContactRepository;
use App\Repository\JobRepository;
use App\Repository\JobTypeRepository;
use App\Repository\UserSettingRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/job')]
class JobController extends AbstractController
{
    public function __construct(
        private readonly JobType $jobForm,
        private readonly JobRepository $jobRepository,
        private readonly JobTypeRepository $jobTypeRepository,
        private readonly ContactRepository $contactRepository,
        private readonly UserSettingRepository $userSettings,
    )
    {
    }

    #[Route('/add', name: 'job_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'job_add_translated', methods: ['GET'])]
    public function getAddForm(): Response {

        return $this->json([
            'form' => $this->jobForm->getFormFields(),
            'sections' => $this->jobForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'job_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'job_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        $job = new Job();

        $contactId = (int)$data['contact'];
        if ($contactId > 0) {
            $data['contact'] = $this->contactRepository->find($contactId);
        }

        $form = $this->createForm(JobType::class, $job);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        // manual validation
        if ($job->getTitle() === null) {
            return $this->json([
                ['message' => 'You must provide a title']
            ], 400);
        }


        $tempType = $this->jobTypeRepository->find(1);
        $job->setType($tempType);
        $job->setContact($data['contact']);

        // set readonly fields
        $job->setCreatedBy($this->getUser()->getId());
        $job->setCreatedDate(new DateTimeImmutable());

        // save job
        $this->jobRepository->save($job, true);

        return $this->itemResponse($job);
    }

    #[Route('/edit/{id}', name: 'job_edit', methods: ['GET'])]
    #[Route('/edit/{_locale}/{id}', name: 'job_edit_translated', methods: ['GET'])]
    public function getEditForm(Job $job): Response {
        return $this->itemResponse($job);
    }

    #[Route('/edit/{id}', name: 'job_edit_save', methods: ['POST'])]
    #[Route('/edit/{_locale}/{id}', name: 'job_edit_save_translated', methods: ['POST'])]
    public function saveEditForm(
        Request $request,
        Job $job,
    ): Response {
        $body = $request->getContent();
        $data = json_decode($body, true);

        // unset readonly fields
        unset($data['createdBy']);
        unset($data['createdDate']);
        unset($data['id']);

        $form = $this->createForm(JobType::class, $job);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }

        $this->jobRepository->save($job, true);

        return $this->itemResponse($job);
    }

    #[Route('/{id}', name: 'job_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/{_locale}/{id}', name: 'job_view_translated', methods: ['GET'])]
    public function view(
        Job $job,
    ): Response {
        return $this->itemResponse($job);
    }

    #[Route('/{id}', name: 'job_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[Route('/{_locale}/{id}', name: 'job_delete_translated', methods: ['DELETE'])]
    public function delete(
        Job $job,
    ): Response {
        $this->jobRepository->remove($job, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/', name: 'job_index', methods: ['GET'])]
    #[Route('/{_locale}', name: 'job_index_translated', methods: ['GET'])]
    public function list(
        Request $request,
    ): Response {
        $pageSize = $this->userSettings->getUserSetting(
            $this->getUser(),
            'pagination-page-size',
        );
        $page = $request->query->getInt('page', 1);
        $jobs = $this->jobRepository->findBySearchAttributes($page, $pageSize);

        $data = [
            'headers' => $this->jobForm->getIndexHeaders(),
            'items' => $jobs,
            'total_items' => count($jobs),
            'pagination' => [
                'page_size' => $pageSize,
                'page' => $page,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        Job $job,
    ): Response {
        $data = [
            'item' => $job,
            'form' => $this->jobForm->getFormFields(),
            'sections' => $this->jobForm->getFormSections(),
        ];

        return $this->json($data);
    }
}