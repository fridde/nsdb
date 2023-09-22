<?php

namespace App\Controller\Admin;

use App\Entity\Group;
use App\Entity\Visit;
use Carbon\Carbon;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class VisitCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Visit::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('Date')
                ->formatValue(function ($v) {
                    return (new Carbon($v))->toDateString();
                })
                ->setFormTypeOptions(['block_name' => 'date_picker']),
            AssociationField::new('Topic'),
            AssociationField::new('Group'),
            BooleanField::new('Confirmed')->renderAsSwitch(),
            TextField::new('Time'),
            BooleanField::new('Status')->renderAsSwitch()
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
//            ->add(DateTimeFilter::new('Date'))
//            ->add(ComparisonFilter::new('Date')->setFormTypeOptions())
            ->add(EntityFilter::new('Topic'))
            ->add(BooleanFilter::new('Status'));
    }



    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setFormThemes(['admin/form.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setDefaultSort(['Date' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }




}
