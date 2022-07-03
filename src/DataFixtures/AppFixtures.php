<?php /** @noinspection PhpUnusedPrivateMethodInspection */

namespace App\DataFixtures;

use App\Entity\CalendarEvent;
use App\Entity\Group;
use App\Entity\Location;
use App\Entity\Note;
use App\Entity\Record;
use App\Entity\School;
use App\Entity\Topic;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\UserRepository;
use App\Repository\VisitRepository;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use ReflectionClass;

class AppFixtures extends Fixture
{
    private ObjectManager $om;

    private static array $specialCases = [
        'CalendarEvent' => ['_Location'],
        'User' => ['Roles'],
        'Visit' => ['Colleagues']
    ];

    private static array $convertToEntity = [
        School::class,
        Location::class,
        User::class,
        Group::class,
        Topic::class,
        Visit::class
    ];

    private static array $manyToMany = [
        'colleagues_visits'
    ];

    private static array $convertToDate = [
        'Date', 'LastUpdate', 'Created'
    ];

    private array $shortToLong = [];


    public function load(ObjectManager $manager): void

    {
        $this->om = $manager;

        $reader = IOFactory::createReader('Ods');
        $reader->setReadDataOnly(true);
        $workbook = $reader->load(__DIR__ . '/test_data_ndbsymfony.ods');
        $sheets = $workbook->getAllSheets();

        /** @var EntityManagerInterface $em */
        $em = $this->om;
        $conn = $em->getConnection();
        $conn->setAutoCommit(false);

        try {
            @$conn->executeQuery('TRUNCATE recordings;');
        } catch (\Exception $e) {

        }

        foreach ($sheets as $sheet) {
            $title = $sheet->getTitle();

            if (self::isIgnored($title)) {
                continue;
            }
            $rows = $sheet->toArray();
            $headers = array_shift($rows);
            $rows = array_map(fn($r) => array_combine($headers, $r), $rows);

            foreach ($rows as $row) {
                if (in_array($title, self::$manyToMany, true)) {
                    $this->combineManyToMany($row, $title);
                    continue;
                }

                $createMethod = 'create' . $title;
                $entity = $this->$createMethod($row);

                $specialCases = self::$specialCases[$title] ?? [];
                $this->setStandardValues($entity, $row, $specialCases);

                $this->om->persist($entity);

                $metadata = $this->om->getClassMetaData(get_class($entity));
                $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_NONE);
            }
            $this->om->flush();
        }

    }


    private function createCalendarEvent(array $row): CalendarEvent
    {
        $c = new CalendarEvent();
        $c->setLocation($row['_Location']);

        return $c;
    }

    private function createSchool(): School
    {
        return new School();
    }

    private function createLocation(): Location
    {
        return new Location();
    }

    private function createUser(array $row): User
    {
        $u = new User();
        $roles = $row['Roles'];
        if (!empty($roles)) {
            $u->addRoles(explode(',', $roles));
        }

        return $u;
    }

    private function createTopic(): Topic
    {
        return new Topic();
    }

    private function createGroup(): Group
    {
        return new Group();
    }

    private function createVisit(): Visit
    {
        return new Visit();
    }

    private function createNote(): Note
    {
        return new Note();
    }

    private function createRecord(): Record
    {
        return new Record();
    }

    private function combineManyToMany(array $row, string $table): void
    {
        switch ($table) {
            case 'colleagues_visits':
                /** @var VisitRepository $visitRepo */
                $visitRepo = $this->om->getRepository(Visit::class);
                /** @var UserRepository $userRepo */
                $userRepo = $this->om->getRepository(User::class);
                $v = $visitRepo->find($row['Visit']);
                $u = $userRepo->find($row['User']);
                if ($u !== null) {
                    $v->addColleague($u);
                }
                break;
        }
    }

    private function setStandardValues($object, $row, $exceptions = []): void
    {
        $this->setShortToLongArray();
        $shortNames = array_keys($this->shortToLong);

        foreach ($row as $header => $value) {
            if (self::isIgnored($header) || in_array($header, $exceptions, true)) {
                continue;
            }
            if (in_array($header, $shortNames, true)) {
                $repo = $this->om->getRepository($this->shortToLong[$header]);
                if ($value !== null) {
                    $value = $repo->find($value);
                }
            }
            if (in_array($header, self::$convertToDate, true)) {
                $value = empty($value) ? null : new DateTime($value);
            }
            $setterMethod = 'set' . ucfirst($header);
            $object->$setterMethod($value);
        }
    }

    private static function isIgnored(string $fieldOrTitle): bool
    {
        return str_contains($fieldOrTitle, '_ignore');
    }

    private function setShortToLongArray(): void
    {
        if (!empty($this->shortToLong)) {
            return;
        }
        foreach (self::$convertToEntity as $val) {
            $key = (new ReflectionClass($val))->getShortName();
            $this->shortToLong[$key] = $val;
        }
    }


}
