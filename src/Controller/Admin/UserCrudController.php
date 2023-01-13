<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;

class UserCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return User::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('FirstName'),
            TextField::new('LastName'),
            TelephoneField::new('Mobil'),
            EmailField::new('Mail'),
            ArrayField::new('Roles'),  //->setChoices($this->getUserRoleLabels()),
            BooleanField::new('Status')->renderAsSwitch(),
            AssociationField::new('School'),
            TextField::new('Acronym')->setMaxLength(3),

        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('FirstName')
            ->add('School')
            ->add(ArrayFilter::new('Roles'))
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

//    private function getUserRoleLabels(): array
//    {
//        $roles = User::getRoleLabels();
//        $labels = array_map(
//            fn($label) => mb_strtolower(str_replace('ROLE_', '', $label)),
//            $roles
//        );
//
//        return array_combine($labels, $roles);
//    }

}
