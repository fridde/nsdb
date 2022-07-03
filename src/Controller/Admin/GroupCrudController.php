<?php

namespace App\Controller\Admin;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;

class GroupCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return Group::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('Name'),
            TextField::new('Segment'),
            IntegerField::new('StartYear'),
            IntegerField::new('NumberStudents'),
            TextareaField::new('Info'),
            BooleanField::new('Status')->renderAsSwitch(),
            AssociationField::new('School'),
            AssociationField::new('User'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('School')
            ->add('Segment')
            ;
    }

}
