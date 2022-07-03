<?php

namespace App\Controller\Admin;

use App\Entity\Visit;
use Carbon\Carbon;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
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
//            DateField::new('Date'),
            AssociationField::new('Topic'),
            AssociationField::new('Group'),
            BooleanField::new('Confirmed')->renderAsSwitch(),
            TextField::new('Time'),
            BooleanField::new('Status')->renderAsSwitch()
            //->setFormat('yyyy-MM-dd')
            //                ->setFormTypeOptions(['block_name' => 'datepicker'])
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('Date'))
            ->add(EntityFilter::new('Topic'))
            ->add(BooleanFilter::new('Status'));
    }



    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setFormThemes(['admin/form.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ;
    }




}
