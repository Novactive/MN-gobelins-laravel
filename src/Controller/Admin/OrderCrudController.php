<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Translator\Translator;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class OrderCrudController extends AbstractCrudController
{
    use Translator;

    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    protected static string $translationDomain = 'order';

    /**
     * @param EntityManagerInterface $entityManager
     * @param SluggerInterface $slugger
     */
    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setPageTitle(Crud::PAGE_INDEX, $this->translate('Commande'))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form.html.twig'])
            ->setPageTitle(Crud::PAGE_DETAIL, $this->translate('Détail'))
            ->setPageTitle(Crud::PAGE_EDIT, $this->translate('Edition'))
            ->setSearchFields(['name', 'categories.name'])
            ->setFormOptions(
                ['attr' => ['enctype' => 'multipart/form-data']],
                ['attr' => ['enctype' => 'multipart/form-data']]
            );

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $fieldsList = [
            AssociationField::new('user', $this->translate('Client'))
                ->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder) => $queryBuilder
                        ->orderBy('entity.firstName', 'ASC')
                ),

            TextField::new('reference', $this->translate('Reference')),
            NumberField::new('amount', $this->translate('Total')),
            NumberField::new('discount', $this->translate('Réduction')),
            NumberField::new('netToPay', $this->translate('Net à payer')),
            NumberField::new('pay', $this->translate('Payer')),
            NumberField::new('remainderToPay', $this->translate('Reste à payer')),
            AssociationField::new('orderDetails', $this->translate('Details'))
                ->setTemplatePath('admin/fields/details.html.twig'),
            DateTimeField::new('createdAt', $this->translate('Date de création')),

        ];


        switch ($pageName) {
            case Crud::PAGE_INDEX:
                break;
            case Crud::PAGE_DETAIL:
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                $fieldsList = $fieldsList;
                break;
            default:
                $fieldsList = [];
        }

        return $fieldsList;
    }
}
