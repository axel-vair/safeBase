<?php

namespace App\Form;

use App\Entity\Cron;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CronType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('backupFrequency', ChoiceType::class, [
                'label' => 'Fréquence des sauvegardes: ',
                'label_attr' => ['class' => 'mr-4'],
                'choices' => [
                    'Tous les jours' => 'daily',
                    'Toutes les semaines' => 'weekly',
                    'Tous les mois' => 'monthly',
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cron::class,
        ]);
    }
}
