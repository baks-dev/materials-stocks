<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Materials\Stocks\UseCase\Admin\Warehouse;

use BaksDev\Contacts\Region\Repository\WarehouseChoice\WarehouseChoiceInterface;
use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileChoice\UserProfileChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WarehouseMaterialStockForm extends AbstractType
{
    public function __construct(private readonly UserProfileChoiceInterface $userProfileChoice) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Склад
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event): void {
                /** @var WarehouseMaterialStockDTO $data */
                $data = $event->getData();
                $form = $event->getForm();

                /** Все профили пользователя */
                $profiles = $this->userProfileChoice->getActiveUserProfile($data->getUsr());

                if(count($profiles) === 1)
                {
                    $data->setProfile(current($profiles));
                }

                // Склад
                $form
                    ->add('profile', ChoiceType::class, [
                        'choices' => $profiles,
                        'choice_value' => function(?UserProfileUid $profile) {
                            return $profile?->getValue();
                        },
                        'choice_label' => function(UserProfileUid $profile) {
                            return $profile->getAttr();
                        },

                        'label' => false,
                        'required' => true,
                    ]);
            }
        );

        // Section Collection
        $builder->add('material', CollectionType::class, [
            'entry_type' => Products\MaterialStockForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__material__',
        ]);

        $builder->add('comment', TextareaType::class, ['required' => false]);

        // Сохранить
        $builder->add(
            'send',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => WarehouseMaterialStockDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
                'csrf_protection' => false
            ]
        );
    }
}
