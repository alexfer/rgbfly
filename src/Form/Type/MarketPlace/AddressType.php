<?php

namespace App\Form\Type\MarketPlace;

use App\Entity\MarketPlace\MarketAddress;
use App\Entity\MarketPlace\MarketCustomer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Locale;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('line1', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'min' => 10,
                    'max' => 250,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.address.not_blank',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.address.min',
                        'max' => 250,
                        'maxMessage' => 'form.address.max',
                    ]),
                ],
            ])
            ->add('line2', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'min' => 10,
                    'max' => 250,
                ],
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.address.min',
                        'max' => 250,
                        'maxMessage' => 'form.address.max',
                    ]),
                ],
            ])
            ->add('country', ChoiceType::class, [
                'mapped' => false,
                'placeholder' => 'form.country.placeholder',
                'label' => 'label.country',
                'required' => true,
                'multiple' => false,
                'expanded' => false,
                'choices' => array_flip(Countries::getNames(Locale::getDefault())),
            ])
            ->add('city', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'min' => 10,
                    'max' => 250,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.city.not_blank',
                    ]),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.address.min',
                        'max' => 250,
                        'maxMessage' => 'form.address.max',
                    ]),
                ],
            ])
            ->add('region', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'min' => 3,
                    'max' => 250,
                ],
                'constraints' => [
                    new Length([
                        'min' => 3,
                        'minMessage' => 'form.region.min',
                        'max' => 250,
                        'maxMessage' => 'form.region.max',
                    ]),
                ],
            ])
            ->add('postal', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'min' => 5,
                    'max' => 50,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'form.city.not_blank',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'form.address.min',
                        'max' => 50,
                        'maxMessage' => 'form.address.max',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'pattern' => "[+0-9]+$",
                    'min' => 10,
                    'max' => 50,
                ],
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'minMessage' => 'form.phone.min',
                        'max' => 50,
                        'maxMessage' => 'form.phone.max',
                    ]),
                    new Regex([
                        'pattern' => "/[+0-9]+$/i",
                        'message' => 'form.phone.not_valid',
                    ]),
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarketAddress::class,
        ]);
    }
}

