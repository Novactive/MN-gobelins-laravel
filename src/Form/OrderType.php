<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'choice_label' => 'fullName',
                'label' => 'Choisissez le client',
                'required' => true,
                'class' => User::class,
                'multiple' => false,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    return $er->createQueryBuilder('u')
                        ->where('u.id != :current')
                        ->setParameter('current', $options['user'])
                        ->orderBy('u.firstName', 'ASC');
                },
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Remise',
                'attr' => [
                    'placeholder' => 'remise',
                    'pattern' => '\d+',
                ],
                'required' => true,
                'mapped' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider la commande',
                'attr' =>  [
                    'class' =>  'btn btn-success btn-block'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => [],
            'data_class' => Order::class,
        ]);
    }
}
