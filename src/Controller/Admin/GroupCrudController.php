<?php

namespace App\Controller\Admin;

use App\Entity\Group;
use App\Entity\User;
use App\Enums\Segment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
            ChoiceField::new('SegmentString', 'Segment')->setChoices(array_flip(Segment::getLabels())),
            IntegerField::new('StartYear'),
            IntegerField::new('NumberStudents'),
            TextareaField::new('ShortInfo')->hideOnForm(),
            TextareaField::new('Info')->hideOnIndex(),
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
            ->add('StartYear')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

}
