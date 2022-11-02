<?php

namespace App\Message;

use App\Message\MessageDetails as MD;
use App\Settings;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Symfony\Component\HttpKernel\KernelInterface;

class MessageDetails
{
    public const SUBJECT_VISIT_CONFIRMATION = 'visit_confirmation';
    public const SUBJECT_INCOMPLETE_PROFILE = 'incomplete_profile';
    public const SUBJECT_FIRST_VISIT = 'first_visit';
    public const SUBJECT_VISITS_ADDED = 'visits_added';
    public const SUBJECT_SCHOOL_ADMIN_REQUEST = 'school_admin_request';

    public const TYPES = [
        self::SUBJECT_VISIT_CONFIRMATION,
        self::SUBJECT_INCOMPLETE_PROFILE,
        self::SUBJECT_FIRST_VISIT,
        self::SUBJECT_VISITS_ADDED,
        self::SUBJECT_SCHOOL_ADMIN_REQUEST
    ];

    public function __construct(
        private Settings $settings,
        private KernelInterface $kernel
    )
    {
    }

    public function getTempRecordsPath(string $name): string
    {
        return $this->kernel->getProjectDir() . '/var/temp_records/' . $name . '.txt';
    }

    public function getImmunityTresholdDate(): Carbon
    {
        return Carbon::today()->sub(CarbonInterval::create(
            $this->settings->get('user_reminder.immunity_time'))
        );
    }

    public function getAnnoyanceTresholdDate(string $subject): Carbon
    {
        return Carbon::today()->sub(CarbonInterval::create(
            $this->settings->get('user_reminder.annoyance_interval.' . $subject))
        );
    }

    public static function getVarNameForExtraInfo(string $subject): string
    {
        return match ($subject) {
            self::SUBJECT_FIRST_VISIT, self::SUBJECT_VISITS_ADDED, self::SUBJECT_VISIT_CONFIRMATION => 'visits',
            default => 'extra'
        };
    }

    public function translateSubject(string $original, bool $verbalize = true): string
    {
        $subjects = $this->settings->get('mail_subjects');
        if (!$verbalize) {
            $subjects = array_flip($subjects);
        }

        return $subjects[$original];
    }


}