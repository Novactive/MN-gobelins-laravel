<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Translator\Translator;
use App\Entity\Category;
use App\Form\Admin\EventSubscriber\Category\FormEventSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryCrudController extends AbstractCrudController
{
    use Translator;

    private SluggerInterface $slugger;

    protected static string $translationDomain = 'category';

    /**
     * @param SluggerInterface $slugger
     */
    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setPageTitle(Crud::PAGE_INDEX, $this->translate('Catégorie'))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form.html.twig'])
            ->setPageTitle(Crud::PAGE_DETAIL, $this->translate('Détail'))
            ->setPageTitle(Crud::PAGE_EDIT, $this->translate('Edition'))
            ->setSearchFields(['name'])
            ->setFormOptions(
                ['attr' => ['enctype' => 'multipart/form-data']],
                ['attr' => ['enctype' => 'multipart/form-data']]
            );

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $fieldsList = [
            AssociationField::new('parent', 'Parent'),
            TextField::new('name'),
        ];

        $fieldsListDetail = [
            TextEditorField::new('description'),
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
