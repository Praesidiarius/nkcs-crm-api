<?php

namespace App\Service\WorkTime;

use App\Repository\UserSettingRepository;
use App\Repository\WorktimeRepository;
use Symfony\Component\Security\Core\User\UserInterface;

class WorkTimeManager
{
    public function __construct(
        private readonly UserSettingRepository $userSettings,
        private readonly WorktimeRepository    $worktimeRepository,
    )
    {
    }

    public function getWorkTimeWeeklyWidgetData(UserInterface $user): array
    {
        $workTimePerDay = $this->userSettings->findOneBy([
            'user' => $user,
            'settingKey' => 'work-time-per-day',
        ])?->getSettingValue();
        $workTimePerWeek = $workTimePerDay * 5;
        $workTimeThisWeek = $this->worktimeRepository->getWorkTimeCurrentWeek($user);

        $workTimeThisWeekCount = 0;
        foreach ($workTimeThisWeek as $workTime) {
            if ($workTime->getStart() && $workTime->getEnd()) {
                $workTimeThisWeekCount += ($workTime->getEnd()->getTimestamp() - $workTime->getStart()->getTimestamp()) / 3600;
            }
        }

        return [
            'max' => $workTimePerWeek,
            'current' => round($workTimeThisWeekCount, 1)
        ];
    }
}
