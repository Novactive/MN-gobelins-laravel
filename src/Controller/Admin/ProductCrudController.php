<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Translator\Translator;
use App\Entity\Product;
use App\Form\Admin\EventSubscriber\Category\FormEventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCrudController extends AbstractCrudController
{
    use Translator;

    private EntityManagerInterface $entityManager;
    private SluggerInterface $slugger;

    protected static string $translationDomain = 'product';

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
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setPageTitle(Crud::PAGE_INDEX, $this->translate('Produit'))
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
            AssociationField::new('categories', $this->translate('category'))
                ->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder) => $queryBuilder
                        ->where('entity.parent IS NOT NULL')
                        ->orderBy('entity.name', 'ASC')
                ),
            TextField::new('name', $this->translate('name')),
            SlugField::new('slug', $this->translate('slug'))->setTargetFieldName('name'),
            ImageField::new('illustration')
                ->setBasePath('uploads/')
                ->setUploadDir('public/uploads/')
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            NumberField::new('price', $this->translate('price')),
            NumberField::new('stock', $this->translate('stock')),
        ];

        $fieldsListDetail = [
            TextEditorField::new('description', $this->translate('description')),
        ];

        switch ($pageName) {
            case Crud::PAGE_INDEX:
                break;
            case Crud::PAGE_DETAIL:
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                $fieldsList = array_merge($fieldsList, $fieldsListDetail);
                break;
            default:
                $fieldsList = [];
        }

        return $fieldsList;
    }
}
