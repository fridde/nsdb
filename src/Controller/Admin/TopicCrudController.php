<?php

namespace App\Controller\Admin;

use App\Entity\Topic;
use App\Enums\Segment;
use App\Settings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TopicCrudController extends AbstractCrudController
{

    public function __construct()
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
            TextField::new('Symbol'),
            AssociationField::new('Location'),
            ChoiceField::new('SegmentString', 'Segment')->setChoices(array_flip(Segment::getLabels())),
            NumberField::new('ColleaguesPerGroup')->setNumDecimals(2),
            IntegerField::new('VisitOrder'),
            TextField::new('Food'),
            UrlField::new('Url'),
            BooleanField::new('Status')->renderAsSwitch(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }
}
