<?php /** @noinspection PhpUnusedFieldDefaultValueInspection */


namespace App\Utils;


use App\Entity\CalendarEvent;
use App\Entity\User;
use App\Entity\Visit;
use App\Settings;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Google\Service\Exception;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Calendar
{
    public Google_Client $client;
    public Google_Service_Calendar $calendar;
    private array $config = [
        'type' => 'service_account',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs'
    ];

    private const TIMEZONE = 'Europe/Stockholm';
    private const QUOTA_WAIT_TIME = 100_000;

    private string $calendarId;


    public function __construct(
        array $googleSettings,
        private Settings $settings,
        private UrlGeneratorInterface $router
    )
    {
        $this->client = new Google_Client();

        $this->config += $googleSettings;
        $this->calendarId = $this->config['calendar_id'];

        $this->client->setAuthConfig($this->config);
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->calendar = new Google_Service_Calendar($this->client);

    }

    public function createEvent(
        string $id,
        string $title,
        Carbon $start,
        Carbon $end = null,
        string $duration = null,
        array $description = [],
        bool $isAllDay = false,
    ): Google_Service_Calendar_Event
    {
        if ($isAllDay === false && $end === null && empty($duration)) {
            throw new \InvalidArgumentException("End and Duration can't *both* be empty.");
        }

        $event = new Google_Service_Calendar_Event();

        $event->setId($id);
        $event->setSummary($title);
        $event->setDescription(implode("\n", $description));

        $startEDT = new Google_Service_Calendar_EventDateTime();
        $endEDT = clone $startEDT;
        if ($isAllDay) {
            $startEDT->setDate($start->toDateString());  //using setDate() tells Google this is an allDayEvent
            $endEDT->setDate($end->addDays(1)->toDateString());  // due to definition of allDayEnd being the *beginning* of the end day
        } else {
            $startEDT->setDateTime($start->toIso8601String());
            if ($end === null) {
                $dur = CarbonInterval::create($duration);
                $endEDT->setDateTime($start->copy()->add($dur)->toIso8601String());
            } else {
                $endEDT->setDateTime($end->toIso8601String());
            }
        }
        $event->setStart($startEDT);
        $event->setEnd($endEDT);

        return $event;
    }

    public function createEventForEntity(CalendarEvent|Visit $entity): Google_Service_Calendar_Event
    {
        $args = match (true) {
            $entity instanceof Visit => $this->createArgsForVisit($entity),
            $entity instanceof CalendarEvent => $this->createArgsForCalendarEvent($entity),
        };

        return $this->createEvent(...$args);
    }

    private function createArgsForVisit(Visit $visit): array
    {
        [$start, $end] = $this->getDateTimesForVisit($visit);

        return [
            'id' => $this->createEventId($visit),
            'title' => $this->createTitleForVisit($visit),
            'description' => $this->createDescriptionForVisit($visit),
            'start' => $start,
            'end' => $end,
            'isAllDay' => false
        ];
    }

    private function createArgsForCalendarEvent(CalendarEvent $calendarEvent): array
    {
        [$start, $end, $changeCount] = $this->getDateTimesForCalendarEvent($calendarEvent);

        return [
            'id' => $this->createEventId($calendarEvent),
            'title' => $calendarEvent->getTitle(),
            'description' => [$calendarEvent->getDescription() ?? ''],
            'start' => $start,
            'end' => $end,
            'isAllDay' => $changeCount === 0, // no time string was given
        ];
    }

    public function insertEventForEntity(CalendarEvent|Visit $entity): void
    {
        $event = $this->createEventForEntity($entity);
        try {
            $this->calendar->events->insert($this->calendarId, $event);
        } catch (Exception $e) {
            if ($this->errorReasonIs($e, 'duplicate')) {
                $this->calendar->events->update($this->calendarId, $event->getId(), $event);
            }
        }
        $this->waitDueToQuota();
    }

    public function updateEventForEntity(CalendarEvent|Visit $entity): void
    {
        $event = $this->createEventForEntity($entity);
        $this->calendar->events->update($this->calendarId, $event->getId(), $event);
        $this->waitDueToQuota();
    }

    public function deleteEventForVisit(Visit $visit): void
    {
        $eventId = $this->createEventId($visit);
        try {
            $this->calendar->events->delete($this->calendarId, $eventId);
        } catch (Exception $e) {
            if (!$this->errorReasonIs($e, 'deleted')) {
                throw $e;
            }
        }
        $this->waitDueToQuota();

    }

    public function listAllEvents()
    {
        $events = $this->calendar->events->listEvents($this->calendarId, ['showDeleted' => true]);
        echo "";
        // TODO: Remove in production
    }


    public function getEvent(string $eventId): Google_Service_Calendar_Event
    {
        return $this->calendar->events->get($this->calendarId, $eventId);
        // TODO: Remove in production
    }


    /**
     * See https://developers.google.com/calendar/api/v3/reference/events/insert
     * for id specs
     */
    public function createEventId(Visit|CalendarEvent $event): string
    {
        $firstLetter = match (true) {
            $event instanceof Visit => 'v',
            $event instanceof CalendarEvent => 'c',
            default => null
        };

        $firstLetterBase32 = base_convert(ord($firstLetter), 10, 32);

        $number = str_pad($event->getId(), 8, '0', STR_PAD_LEFT);

        return $firstLetterBase32 . $number;
    }

    private function createTitleForVisit(Visit $visit): string
    {
        $colleagues = $visit->getColleagues()->toArray();
        $colleagueString = (empty($colleagues)
            ? '?'
            : implode('+', array_map(fn(User $u) => $u->getAcronym(), $colleagues))
        );

        $titleStart = trim(sprintf('[%s] %s',
            $colleagueString,
            $visit->getTopic()->getShortName(),
        ));
        if (!$visit->hasGroup()) {
            return sprintf('%s (Reservtillfälle)', $titleStart);
        }

        return sprintf('%s med %s från %s (%s %s)',
            $titleStart,
            $visit->getGroup()?->getName(),
            $visit->getGroup()?->getSchool()->getName(),
            $visit->getGroup()?->getUser()?->getFirstName(),
            substr($visit->getGroup()?->getUser()?->getLastName(), 0, 1),
        );

    }

    private function createDescriptionForVisit(Visit $visit): array
    {
        $rows = [];

        if ($visit->hasGroup()) {
            $rows = [
                ['Besök %s bekräftat', $visit->isConfirmed() ? 'är' : 'inte'],
                [],
                ['Lärare: %s', $visit->getGroup()?->getUser()?->getFullName()],
                ['Årskurs: %s', $visit->getGroup()?->getSegment()],
                ['Mobil: %s', $visit->getGroup()?->getUser()?->getMobilInPlusFormat()],
                ['Mejl: %s', $visit->getGroup()?->getUser()?->getMail()],
                ['Klass %s med %u elever', $visit->getGroup()?->getName(), $visit->getGroup()?->getNumberStudents()],
                ['Extra info från läraren: %s', $visit->getGroup()?->getInfo()],
                [],
                ['Fler val om besöket: %s', $this->router->generate('visit_overview', ['visit' => $visit->getId()])]
            ];
        }

        return array_map(function (array $row) {
            return sprintf($row[0] ?? '', ...array_slice($row, 1));
        }, $rows);

    }

    private function getDateTimesForVisit(Visit $visit): array
    {
        $start = $visit->getDate()->setTimezone(self::TIMEZONE);
        $end = clone $start;

        [$start, $end, $changeCount] = $this->setStartAndEndTimes($start, $end, $visit->getTime());

        if ($changeCount === 0) {
            $start->setTimeFromTimeString($this->settings->get('defaults.visit_start_time'));
            $end->setTimeFromTimeString($this->settings->get('defaults.visit_end_time'));
        }
        if ($changeCount === 1) {
            $end = $start->copy()->add(CarbonInterval::create($this->settings->get('defaults.visit_duration')));
        }

        return [$start, $end];
    }

    private function getDateTimesForCalendarEvent(CalendarEvent $calendarEvent): array
    {
        $start = Carbon::create($calendarEvent->getStartDate())->setTimezone(self::TIMEZONE);
        if ($calendarEvent->hasEndDate()) {
            $end = Carbon::create($calendarEvent->getEndDate())->setTimezone(self::TIMEZONE);
        } else {
            $end = clone $start;
        }

        [$start, $end, $changeCount] = $this->setStartAndEndTimes($start, $end, $calendarEvent->getTime());

         if ($changeCount === 1) {  // only start given, use default duration
            $end = $start->copy()->add(CarbonInterval::create($this->settings->get('defaults.event_duration')));
        }

        return [$start, $end, $changeCount];
    }

    private function setStartAndEndTimes(Carbon $startDate, Carbon $endDate, string $timeString = null): array
    {
        $parsedTime = $this->parseTime($timeString);
        $count = count($parsedTime);

        if ($count >= 1) {
            $startDate->setHours($parsedTime[0][0])->setMinutes($parsedTime[0][1]);
        }
        if ($count === 2) {
            $endDate->setHours($parsedTime[1][0])->setMinutes($parsedTime[1][1]);
        }

        return [$startDate, $endDate, $count];
    }

    /*
 * will convert a time string like 15:20-17:08 into [[15, 20],[17,8]]
 * an empty string will create an empty array
 *
 * some other strings:
 * ' 2 -3' => [[0,3],[0,3]]   (whitespace is ignored, single digits are parsed as minutes
 * */
    public function parseTime(string $timeString = null): array
    {
        return array_map(function (string $part) {
            $part = filter_var($part, FILTER_SANITIZE_NUMBER_INT);
            return [(int)substr($part, 0, -2), (int)substr($part, -2)];  // hours, minutes
        }, array_filter(explode('-', $timeString), 'strlen'));
    }

    private function errorReasonIs(Exception $e, string $reason): bool
    {
        return $e->getErrors()[0]['reason'] === $reason;
    }

    private function waitDueToQuota(): void
    {
        // to keep google's quota of max 600 requests per user per minute
        usleep(self::QUOTA_WAIT_TIME);
    }


}