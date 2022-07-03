<?php

namespace App\Controller\Admin;

use App\Entity\Topic;
use App\Settings;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class TopicCrudController extends AbstractCrudController
{

    public function __construct(private Settings $settings)
    {
    }


    public static function getEntityFqcn(): string
    {
        return Topic::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('ShortName'),
            TextField::new('LongName'),
            ChoiceField::new('Segment')->setChoices($this->getSegmentLabels()),
            IntegerField::new('VisitOrder'),
            TextField::new('Food'),
            UrlField::new('Url')
        ];
    }

    private function getSegmentLabels(): array
    {
        return array_flip($this->settings->get('segments'));
    }
}
