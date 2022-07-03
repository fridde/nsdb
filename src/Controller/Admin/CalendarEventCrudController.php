<?php

namespace App\Controller\Admin;

use App\Entity\CalendarEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CalendarEventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CalendarEvent::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('StartDate'), // TODO: Use datepicker for these two
            TextField::new('EndDate'),
            TextField::new('Time'),
            TextField::new('Title'),
            TextareaField::new('Description'),
            TextField::new('Location'),
        ];
    }

}
