<?php

namespace App\Controller;

use App\Entity\Worktime;
use App\Form\WorkTime\WorkTimeType;
use App\Repository\WorktimeRepository;
use App\Service\WorkTime\WorkTimeManager;
use DateTime;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/work-time')]
class WorkTimeController extends AbstractApiController
{
    public function __construct(
        private readonly WorktimeRepository $worktimeRepository,
        private readonly WorkTimeType $workTimeForm,
        private readonly WorkTimeManager $workTimeManager,
        private readonly HttpClientInterface $httpClient,
    )
    {
        parent::__construct($this->httpClient);
    }

    #[Route('/add', name: 'work_time_add', methods: ['GET'])]
    #[Route('/add/{_locale}', name: 'work_time_translated', methods: ['GET'])]
    public function getAddForm(): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->json([
            'form' => $this->workTimeForm->getFormFields(),
            'sections' => $this->workTimeForm->getFormSections(),
        ]);
    }

    #[Route('/add', name: 'work_time_add_save', methods: ['POST'])]
    #[Route('/add/{_locale}', name: 'work_time_add_save_translated', methods: ['POST'])]
    public function saveAddForm(Request $request): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $body = $request->getContent();
        $data = json_decode($body, true);

        // fix datetime
        //$data['date'] = $data['date'] ? new DateTime(explode('T', $data['date'])[0]) : null;

        $workTime = new Worktime();

        $form = $this->createForm(WorkTimeType::class, $workTime);
        $form->submit($data);

        if (!$form->isValid()) {
            if (count($form->getErrors()) > 0) {
                return $this->json(
                    $form->getErrors(),
                    400
                );
            }
        }
        // set date and time fields manually
        // todo: make createForm work for date and time fields check transformers
        $workTime->setDate($data['date'] ? new DateTime(explode('T', $data['date'])[0]) : null);
        $workTime->setStart($data['start'] ? new DateTime(explode('T', $data['start'])[0]) : null);
        $workTime->setEnd($data['end'] ? new DateTime(explode('T', $data['end'])[0]) : null);

        // set readonly fields
        $workTime->setUser($this->getUser());
        $workTime->setCreatedBy($this->getUser()->getId());
        $workTime->setCreatedDate(new DateTimeImmutable());

        // save contact
        $this->worktimeRepository->save($workTime, true);

        return $this->itemResponse($workTime);
    }

    #[Route('/{id}', name: 'work_time_view', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[Route('/{_locale}/{id}', name: 'work_time_view_translated', methods: ['GET'])]
    public function view(
        Worktime $workTime,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        return $this->itemResponse($workTime);
    }

    #[Route('/{id}', name: 'work_time_delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[Route('/{_locale}/{id}', name: 'work_time_delete_translated', methods: ['DELETE'])]
    public function delete(
        Worktime $workTime,
    ): Response {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $this->worktimeRepository->remove($workTime, true);

        return $this->json(['state' => 'success']);
    }

    #[Route('/', name: 'work_time_index', methods: ['GET'])]
    #[Route('/{_locale}', name: 'work_time_index_translated', methods: ['GET'])]
    public function list(): Response
    {
        if (!$this->checkLicense()) {
            throw new HttpException(402, 'no valid license found');
        }

        $workTimes = $this->worktimeRepository->findBy(['user' => $this->getUser()]);

        $data = [
            'headers' => $this->workTimeForm->getIndexHeaders(),
            'items' => $workTimes,
            'widgets' => [
                'week' => $this->workTimeManager->getWorkTimeWeeklyWidgetData($this->getUser())
            ],
            'total_items' => count($workTimes),
            'pagination' => [
                'pages' => 1,
                'page_size' => 35,
                'page' => 1,
            ],
        ];

        return $this->json($data, 200);
    }

    private function itemResponse(
        Worktime $workTime,
    ): Response {
        $data = [
            'item' => $workTime,
            'form' => $this->workTimeForm->getFormFields(),
            'sections' => $this->workTimeForm->getFormSections(),
        ];

        return $this->json($data);
    }
}