<?php


namespace App\Utils;


use App\Entity\CalendarEvent;
use App\Entity\Group;
use App\Entity\Location;
use App\Entity\Note;
use App\Entity\Record;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\CalendarEventRepository;
use App\Repository\GroupRepository;
use App\Repository\LocationRepository;
use App\Repository\NoteRepository;
use App\Repository\RecordRepository;
use App\Repository\SchoolRepository;
use App\Repository\TopicRepository;
use App\Repository\UserRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;

class RepoContainer
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getVisitRepo(): VisitRepository
    {
        return $this->em->getRepository(Visit::class);
    }

    public function getSchoolRepo(): SchoolRepository
    {
        return $this->em->getRepository(School::class);
    }

    public function getUserRepo(): UserRepository
    {
        return $this->em->getRepository(User::class);
    }

    public function getLocationRepo(): LocationRepository
    {
        return $this->em->getRepository(Location::class);
    }

    public function getNoteRepo(): NoteRepository
    {
        return $this->em->getRepository(Note::class);
    }

    public function getGroupRepo(): GroupRepository
    {
        return $this->em->getRepository(Group::class);
    }

    public function getRecordRepo(): RecordRepository
    {
        return $this->em->getRepository(Record::class);
    }

    public function getTopicRepo(): TopicRepository
    {
        return $this->em->getRepository(Topic::class);
    }

    public function getCalendarEventRepo(): CalendarEventRepository
    {
        return $this->em->getRepository(CalendarEvent::class);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

}