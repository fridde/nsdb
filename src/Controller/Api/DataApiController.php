<?php

namespace App\Controller\Api;

use App\Entity\Group;
use App\Entity\Note;
use App\Entity\User;
use App\Entity\Visit;
use App\Repository\Filterable;
use App\Security\Role;
use App\Security\Voter\SameSchoolVoter;
use App\Utils\Attributes\ConvertToEntityFirst;
use App\Utils\RepoContainer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ReflectionNamedType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;


class DataApiController extends AbstractController
{
    private ?Request $request;
    private EntityManagerInterface $em;

    public function __construct(
        RequestStack $requestStack,
        private RepoContainer $rc,
        private Security $security
    )
    {
        $this->em = $rc->getEntityManager();
        $this->request = $requestStack->getCurrentRequest();
    }

    #[Route(
        '/api/user/{user}',
        methods: ['POST']
    )]
    #[IsGranted(SameSchoolVoter::EDIT, 'user')]
    public function updateUser(User $user): JsonResponse
    {
        $this->updateEntityData($user);
        return $this->asJson(['success' => true]);
    }

    #[Route(
        '/api/create/user',
        methods: ['POST']
    )]
    #[IsGranted(Role::ACTIVE_USER)]
    public function createUser(): JsonResponse
    {
        /** @var User $requestingUser  */
        $requestingUser = $this->security->getUser();

        $data = $this->request->get('user');
        $tempId = $data['id'];
        unset($data['id']);
        $user = new User();
        $this->updateEntityData($user, $data);

        if(!($this->security->isGranted(Role::SUPER_ADMIN) || $requestingUser->hasSameSchoolAs($user))){
            throw new AccessDeniedException();
        }
        $user->addRole(Role::ACTIVE_USER);
        $this->em->flush();

        return $this->asJson(['success' => true, 'temp_id' => $tempId, 'user_id' => $user->getId()]);
    }

    #[Route(
        '/api/delete/user/{user}',
        methods: ['POST']
    )]
    #[IsGranted(SameSchoolVoter::DELETE , 'user')]
    public function deleteUser(User $user): JsonResponse
    {
        if($user->hasGroupWithFutureVisit()){
            return $this->asJson([
                'success' => false,
                'user_id' => $user->getId(),
                'error' => 'Användaren har fortfarande besök kvar.' // TODO: Make language agnostic
            ]);
        }
        $user->setStatus(false);
        $this->em->flush();

        return $this->asJson([
            'success' => true,
            'user_id' => $user->getId()
        ]);
    }

    #[Route(
        '/api/visit/{visit}',
        methods: ['POST']
    )]
    #[IsGranted('edit', subject: 'visit')]
    public function updateVisit(Visit $visit): JsonResponse
    {
        $this->updateEntityData($visit);
        return $this->asJson(['success' => true]);
    }

    #[Route(
        '/api/rate-visit/{visit}',
        methods: ['POST']
    )]
    #[IsGranted('confirm', subject: 'visit')]
    public function rateVisit(Visit $visit): JsonResponse
    {
        $this->updateEntityData($visit);
        return $this->asJson(['success' => true]);
    }

    #[Route(
        '/api/note/{note}',
        methods: ['GET', 'POST']
    )]
    #[IsGranted(Role::SUPER_ADMIN)]
    public function updateNoteForVisit(?Note $note): Response
    {
        $thisNote = $note;
        if(!($note instanceof Note)){
            $visit = $this->rc->getVisitRepo()->find($this->request->get('visit'));
            $user = $this->getUser();
            $crit = ['Visit' => $visit, 'User' => $user];
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $note = $this->rc->getNoteRepo()->findOneBy($crit);
            if(!($note instanceof Note) && ($user instanceof User)){
                $note = new Note();
                $note->setUser($user);
                $note->setVisit($visit);
                $this->em->persist($note);
            }
        }
        $note->setText($this->request->get('text'));
        $this->em->flush();

        return $this->json(['success' => true, 'note_id' => $note->getId()]);
    }


    #[Route(
        '/api/group/{group}',
        methods: ['POST']
    )]
    #[IsGranted(SameSchoolVoter::EDIT, 'group')]
    public function updateGroup(Group $group): JsonResponse
    {
        $this->updateEntityData($group);
        return $this->asJson(['success' => true]);
    }

    public function updateMultipleEntities(string $className, array $entities = null): void
    {
        $entities ??= $this->request->get('updates', []);
        $repo = $this->em->getRepository($className);

        foreach ($entities as $id => $entityData) {
            $entity = $repo->find($id);
            $this->updateSingleEntity($entity, $entityData);
        }
        $this->em->flush();
    }

    public function updateSingleEntity($e, array $data = []): void
    {
        foreach ($data as $attribute => $newValue) {
            $setMethod = 'set' . ucfirst($attribute);
            $newValue = $this->convertToEntityIfNecessary($e, $setMethod, $newValue);
            $e->$setMethod($newValue);
        }
        $this->em->persist($e);
    }

    private function updateEntityData($e, array $data = null): void
    {
        $data ??= $this->request->get('updates', []);
        $this->updateSingleEntity($e, $data);
        $this->em->flush();
    }



    private function asJson($data): JsonResponse
    {
        return new JsonResponse((array) $data);
    }

    private function convertToEntityIfNecessary($e, string $setMethod, $value): mixed
    {
        $reflector = new \ReflectionClass($e);
        $method = $reflector->getMethod($setMethod);
        $attributes = $method->getAttributes(ConvertToEntityFirst::class);
        if (empty($attributes)) {
            return $value;
        }
        $parameter = $method->getParameters()[0];
        /** @var ReflectionNamedType $type */
        $type = $parameter->getType();

        if (is_object($value) && \str_ends_with(get_class($value), $type->getName())) {  // this covers both the FQCN and the short version
            return $value;
        }

        $entity = $this->em->find($type->getName(), $value);
        if (empty($entity)) {
            throw new \RuntimeException('An entity of the type ' . $type->getName() . ' and id ' . $value . ' does not exist');
        }

        return $entity;
    }

    private function getFiltered(string $fqcn): array
    {
        /** @var Filterable $repo */
        $repo = $this->em->getRepository($fqcn);

        $filter = $repo->translateFilterFromRequest($this->request);

        $criteria = $repo->applyFilterFunctions($filter);
        /** @var EntityRepository $repo */

        return $repo->matching($criteria)->toArray();
    }


}