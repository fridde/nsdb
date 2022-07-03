<?php

namespace App\Message;

use App\Entity\Record;
use App\Entity\User;
use App\Entity\Visit;
use App\Utils\ExtendedCollection;
use App\Utils\RepoContainer;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

use App\Message\MessageDetails as MD;

class MessageRecorder
{
    private ?ExtendedCollection $records;
    private ?ExtendedCollection $tempRecords;
    private Carbon $oneYearAgo;

    public function __construct(
        private RepoContainer          $rc,
        private EntityManagerInterface $em,
        private Filesystem             $fs,
        private MD                     $md
    )
    {
        $this->records = new ExtendedCollection();
        $this->tempRecords = new ExtendedCollection();
        $this->oneYearAgo = Carbon::today()->subYear();
    }

    public function getMessageRecords(): ExtendedCollection
    {
        if ($this->records->isEmpty()) {
            $this->records = $this->rc->getRecordRepo()
                ->hasType('message_sent')
                ->byCreationDate()
                ->getRecordsByContent(['subject' => MD::TYPES]);
        }

        return $this->records;
    }

    public function saveTempRecordsToFile(): string
    {
        $serialized = serialize($this->tempRecords->toArray());
        $random = substr(md5(hrtime(true)), 0 , 5);
        $this->fs->dumpFile($this->md->getTempRecordsPath($random), $serialized);

        return $random;
    }

    public function saveTempRecordsToDB(string $token): void
    {
        $records = $this->loadTempRecords($token);
        ExtendedCollection::create($records)
            ->walk(fn(Record $r) => $this->em->persist($r));
        $this->em->flush();
    }

    private function loadTempRecords(string $token): array
    {
        $serialized = file_get_contents($this->md->getTempRecordsPath($token));
        return unserialize($serialized, ['allowed_classes' => true]);
    }

    public function removeTempRecords(string $token): void
    {
        $this->fs->remove($this->md->getTempRecordsPath($token));
    }

    public function addAsTempRecord(User $user, string $subject, ?array $extraInfo): void
    {
        $record = new Record('message_sent');
        $record->setCreated(Carbon::today());
        $record->addToContent('subject', $subject);
        $record->addToContent('user', $user->getId());
        $extra = $this->serializeExtraInfo($extraInfo);
        if ($extra !== null) {
            $record->addToContent(MD::getVarNameForExtraInfo($subject), $extra);
        }

        $this->tempRecords->add($record);
    }

    private function serializeExtraInfo(?array $extraInfo): ?array
    {
        if ($extraInfo === null) {
            return null;
        }
        return ExtendedCollection::create($extraInfo)
            ->map(fn(Visit $v) => $v->getId())
            ->getValues();
    }

    /**
     * @param string $subject
     * @return ExtendedCollection A collection of user-ids that have recently received a mail of this type. "Recently"
     *     means in this context within the annoyance_interval given in app_settings.yaml
     */
    public function getAnnoyedUserIds(string $subject): ExtendedCollection
    {
        $annoyanceImmunityAfter = $this->md->getAnnoyanceTresholdDate($subject);

        return $this->getMessageRecords()
            ->filter(fn(Record $r) => $r->hasInContent('subject', $subject))
            ->filter(fn(Record $r) => $r->getCreated()->gt($annoyanceImmunityAfter))
            ->map(fn(Record $r) => $r->getFromContent('user'));
    }

    private function getLatestRecordForSentMessage(string $subject, User $user, Carbon $after = null): ?Record
    {
        $after ??= Carbon::create('1900-01-01');

        $content = ['subject' => $subject, 'user' => $user->getId()];

        return $this->getMessageRecords()->filter(
            fn(Record $r) => $r->hasInContent($content) && $r->wasCreatedAfter($after)
        )->first();
    }

    public function userGotMessageAfter(User $user, string $subject, Carbon $after): bool
    {
        return $this->getLatestRecordForSentMessage($subject, $user, $after) !== null;
    }

    public function userGotFirstVisitMessage(User $user): bool
    {
        $subject = MD::SUBJECT_FIRST_VISIT;

        return $this->userGotMessageAfter($user, $subject, $this->oneYearAgo);
    }

    public function userHasUnnotifiedFutureVisit(User $user): bool
    {
        return $this->getUnnotifiedFutureVisits($user)->isNotEmpty();
    }

    public function getUnnotifiedFutureVisits(User $user): ExtendedCollection
    {
        $subjects = [MD::SUBJECT_FIRST_VISIT, MD::SUBJECT_VISITS_ADDED];
        $records = ExtendedCollection::create($subjects)
            ->map(fn($t) => $this->getLatestRecordForSentMessage($t, $user, $this->oneYearAgo));

        $notifiedVisitIds = $records
            ->removeNull()
            ->map(fn(Record $r) => $r->getFromContent('visits'))
            ->collapse()->unique();

        return $user->getFutureVisits()
            ->filter(fn(Visit $v) => $notifiedVisitIds->doesNotContain($v->getId()));
    }
}