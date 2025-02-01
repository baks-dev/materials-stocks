<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Materials\Stocks\UseCase\Admin\Moving;

use BaksDev\Contacts\Region\Repository\WarehouseChoice\WarehouseChoiceInterface;
use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Contacts\Region\Type\Call\ContactsRegionCallUid;
use BaksDev\Materials\Catalog\Type\Id\MaterialUid;
use BaksDev\Materials\Catalog\Type\Offers\ConstId\MaterialOfferConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\ConstId\MaterialVariationConst;
use BaksDev\Materials\Catalog\Type\Offers\Variation\Modification\ConstId\MaterialModificationConst;
use BaksDev\Materials\Stocks\Repository\MaterialChoice\MaterialChoiceWarehouseInterface;
use BaksDev\Materials\Stocks\Repository\MaterialModificationChoice\MaterialModificationChoiceWarehouseInterface;
use BaksDev\Materials\Stocks\Repository\MaterialOfferChoice\MaterialOfferChoiceWarehouseInterface;
use BaksDev\Materials\Stocks\Repository\MaterialVariationChoice\MaterialVariationChoiceWarehouseInterface;
use BaksDev\Materials\Stocks\Repository\MaterialWarehouseChoice\MaterialWarehouseChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileChoice\UserProfileChoiceInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;

final class MovingMaterialStockForm extends AbstractType
{
    //private WarehouseChoiceInterface $warehouseChoice;

    private MaterialChoiceWarehouseInterface $materialChoiceWarehouse;

    private MaterialVariationChoiceWarehouseInterface $materialVariationChoiceWarehouse;

    private MaterialOfferChoiceWarehouseInterface $materialOfferChoiceWarehouse;

    private MaterialModificationChoiceWarehouseInterface $materialModificationChoiceWarehouse;

    private MaterialWarehouseChoiceInterface $materialWarehouseChoice;

    private UserProfileChoiceInterface $userProfileChoice;

    private UserUid $user;

    private TokenStorageInterface $tokenStorage;
    private iterable $reference;

    public function __construct(
        UserProfileChoiceInterface $userProfileChoice,
        MaterialChoiceWarehouseInterface $materialChoiceWarehouse,
        MaterialOfferChoiceWarehouseInterface $materialOfferChoiceWarehouse,
        MaterialVariationChoiceWarehouseInterface $materialVariationChoiceWarehouse,
        MaterialModificationChoiceWarehouseInterface $materialModificationChoiceWarehouse,
        MaterialWarehouseChoiceInterface $materialWarehouseChoice,
        TokenStorageInterface $tokenStorage,
        #[AutowireIterator('baks.reference.choice')] iterable $reference,

    )
    {

        $this->materialChoiceWarehouse = $materialChoiceWarehouse;
        $this->materialOfferChoiceWarehouse = $materialOfferChoiceWarehouse;
        $this->materialVariationChoiceWarehouse = $materialVariationChoiceWarehouse;
        $this->materialModificationChoiceWarehouse = $materialModificationChoiceWarehouse;
        $this->materialWarehouseChoice = $materialWarehouseChoice;
        $this->userProfileChoice = $userProfileChoice;

        $this->tokenStorage = $tokenStorage;
        $this->reference = $reference;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $token = $this->tokenStorage->getToken();

        /** @var User $usr */
        $usr = $token?->getUser();

        $UserUid = $usr->getId();

        if($usr && $token instanceof SwitchUserToken)
        {
            /** @var User $originalUser */
            $originalUser = $token->getOriginalToken()->getUser();

            if($originalUser?->getUserIdentifier() !== $usr?->getUserIdentifier())
            {
                $UserUid = $originalUser->getId();
            }
        }

        $this->user = $usr->getId();  //$builder->getData()->getUsr();


        /**
         * Подукция
         *
         * @var MaterialUid $material
         */
        $builder->add('preProduct', TextType::class, ['attr' => ['disabled' => true]]);


        $materialChoiceWarehouse = $this->materialChoiceWarehouse->getMaterialsExistWarehouse($this->user);

        if($materialChoiceWarehouse->valid())
        {
            $builder->add(
                'preProduct',
                ChoiceType::class,
                [
                    'choices' => $materialChoiceWarehouse,
                    'choice_value' => function(?MaterialUid $material) {
                        return $material?->getValue();
                    },
                    'choice_label' => function(MaterialUid $material) {
                        return $material->getAttr();
                    },
                    'choice_attr' => function(?MaterialUid $material) {

                        if(!$material)
                        {
                            return [];
                        }

                        if($material->getAttr())
                        {
                            $attr['data-name'] = $material->getAttr();
                        }

                        if($material->getOption())
                        {
                            $attr['data-filter'] = '('.$material->getOption().')';
                        }

                        return $attr;
                    },

                    'label' => false,
                ]
            );
        }


        /**
         * Торговые предложения
         *
         * @var MaterialOfferConst $offer
         */

        $builder->add(
            'preOffer',
            HiddenType::class,
        );

        $builder->get('preOffer')->addModelTransformer(
            new CallbackTransformer(
                function($offer) {
                    return $offer instanceof MaterialOfferConst ? $offer->getValue() : $offer;
                },
                function($offer) {
                    return $offer ? new MaterialOfferConst($offer) : null;
                }
            ),
        );

        /**
         * Множественный вариант торгового предложения
         *
         * @var MaterialVariationConst $variation
         */

        $builder->add(
            'preVariation',
            HiddenType::class,
        );

        $builder->get('preVariation')->addModelTransformer(
            new CallbackTransformer(
                function($variation) {
                    return $variation instanceof MaterialVariationConst ? $variation->getValue() : $variation;
                },
                function($variation) {
                    return $variation ? new MaterialVariationConst($variation) : null;
                }
            ),
        );

        /**
         * Модификация множественного варианта торгового предложения
         *
         * @var MaterialModificationConst $modification
         */

        $builder->add(
            'preModification',
            HiddenType::class,
        );

        $builder->get('preModification')->addModelTransformer(
            new CallbackTransformer(
                function($modification) {
                    return $modification instanceof MaterialModificationConst ? $modification->getValue() : $modification;
                },
                function($modification) {
                    return $modification ? new MaterialModificationConst($modification) : null;
                }
            ),
        );

        /* Целевой склад */
        $builder->add(
            'targetWarehouse',
            ChoiceType::class,
            [
                'choices' => [],
                'label' => false,
                'required' => false,
            ]
        );

        $builder->get('preModification')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event): void {

                $parent = $event->getForm()->getParent();

                if(!$parent)
                {
                    return;
                }

                $material = $parent->get('preProduct')->getData();
                $offer = $parent->get('preOffer')->getData();
                $variation = $parent->get('preVariation')->getData();
                $modification = $parent->get('preModification')->getData();

                if($material)
                {
                    $this->formOfferModifier($event->getForm()->getParent(), $material);
                }

                if($material && $offer)
                {
                    $this->formVariationModifier($event->getForm()->getParent(), $material, $offer);
                }

                if($material && $offer && $variation)
                {
                    $this->formModificationModifier($event->getForm()->getParent(), $material, $offer, $variation);
                }

                if($material && $offer && $variation && $modification)
                {
                    $this->formTargetWarehouseModifier(
                        $event->getForm()->getParent(),
                        $material,
                        $offer,
                        $variation,
                        $modification);
                }
            },
        );


        /**
         * Если пользователь не по доверенности - получаем список собственных профилей
         */
        if($this->user->equals($UserUid))
        {
            $profiles = $this->userProfileChoice->getActiveUserProfile($this->user);
        }
        else
        {
            /** Получаем список профилей, имеющих доступ по доверенности текущего пользователя */
            $profiles = $this->userProfileChoice->getActiveProfileAuthority($this->user, $UserUid);
        }


        //        /** @var ?UserProfileUid $currentWarehouse */
        //        $currentWarehouse = (count($profiles) === 1) ? current($profiles) : null;
        //
        //        if($currentWarehouse)
        //        {
        //            $builder->addEventListener(
        //                FormEvents::PRE_SET_DATA,
        //                function(FormEvent $event) use ($currentWarehouse): void {
        //                    /** @var MovingMaterialStockDTO $data */
        //                    $data = $event->getData();
        //
        //                    $data->setTargetWarehouse($currentWarehouse);
        //                    $data->setDestinationWarehouse($currentWarehouse);
        //                },
        //            );
        //        }


        /* Склад назначения */
        $builder->add(
            'destinationWarehouse',
            ChoiceType::class,
            [
                'choices' => $profiles,
                'choice_value' => function(?UserProfileUid $warehouse) {
                    return $warehouse?->getValue();
                },
                'choice_label' => function(UserProfileUid $warehouse) {
                    return $warehouse->getAttr();
                },

                'label' => false,
                'required' => false,
            ]
        );

        // Количество
        $builder->add('preTotal', IntegerType::class, ['required' => false]);


        // Section Collection
        $builder->add(
            'move',
            CollectionType::class,
            [
                'entry_type' => MaterialStockForm::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
                'prototype_name' => '__material__',
            ]
        );


        $builder->add('comment', TextareaType::class, ['required' => false]);

        // Сохранить
        $builder->add(
            'moving',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']],
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => MovingMaterialStockDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ],
        );
    }


    private function formOfferModifier(FormInterface $form, MaterialUid $material): void
    {
        $offer = $this->materialOfferChoiceWarehouse
            ->user($this->user)
            ->material($material)
            ->getMaterialsOfferExistWarehouse();

        // Если у продукта нет ТП
        if(!$offer->valid())
        {
            $form->add(
                'preOffer',
                HiddenType::class,
            );

            $this->formTargetWarehouseModifier($form, $material);

            return;
        }

        $currentOffer = $offer->current();
        $label = $currentOffer->getOption();
        $domain = null;

        if($currentOffer->getReference())
        {
            /** Если торговое предложение Справочник - ищем домен переводов */
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currentOffer->getReference())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add(
                'preOffer',
                ChoiceType::class,
                [
                    'choices' => $offer,
                    'choice_value' => function(?MaterialOfferConst $offer) {
                        return $offer?->getValue();
                    },
                    'choice_label' => function(MaterialOfferConst $offer) {
                        return $offer->getAttr();
                    },
                    'choice_attr' => function(?MaterialOfferConst $offer) {

                        if(!$offer)
                        {
                            return [];
                        }

                        if($offer->getAttr())
                        {
                            $attr['data-name'] = $offer->getAttr();
                        }

                        if($offer->getProperty())
                        {
                            $attr['data-filter'] = trim($offer->getCharacteristic().' ('.$offer->getProperty().')');
                        }

                        return $attr;

                    },
                    'label' => $label,
                    'translation_domain' => $domain,
                    'placeholder' => sprintf('Выберите %s из списка...', $label),
                ]
            );
    }

    private function formVariationModifier(FormInterface $form, MaterialUid $material, MaterialOfferConst $offer): void
    {
        $variations = $this->materialVariationChoiceWarehouse
            ->user($this->user)
            ->material($material)
            ->offerConst($offer)
            ->getMaterialsVariationExistWarehouse();

        // Если у продукта нет множественных вариантов
        if(!$variations->valid())
        {
            $form->add('preVariation', HiddenType::class);
            $this->formTargetWarehouseModifier($form, $material, $offer);

            return;
        }

        $currentVariation = $variations->current();
        $label = $currentVariation->getOption();
        $domain = null;

        if($currentVariation->getReference())
        {
            /** Если торговое предложение Справочник - ищем домен переводов */
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currentVariation->getReference())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add(
                'preVariation',
                ChoiceType::class,
                [
                    'choices' => $variations,
                    'choice_value' => function(?MaterialVariationConst $variation) {
                        return $variation?->getValue();
                    },
                    'choice_label' => function(MaterialVariationConst $variation) {
                        return $variation->getAttr();
                    },
                    'choice_attr' => function(?MaterialVariationConst $variation) {

                        if(!$variation)
                        {
                            return [];
                        }

                        if($variation->getAttr())
                        {
                            $attr['data-name'] = $variation->getAttr();
                        }

                        if($variation->getProperty())
                        {
                            $attr['data-filter'] = trim($variation->getCharacteristic().' ('.$variation->getProperty().')');
                        }

                        return $attr;
                    },
                    'label' => $label,
                    'translation_domain' => $domain,
                    'placeholder' => sprintf('Выберите %s из списка...', $label),
                ],
            );
    }

    private function formModificationModifier(
        FormInterface $form,
        MaterialUid $material,
        MaterialOfferConst $offer,
        MaterialVariationConst $variation,
    ): void
    {

        $modifications = $this->materialModificationChoiceWarehouse
            ->user($this->user)
            ->material($material)
            ->offerConst($offer)
            ->variationConst($variation)
            ->getMaterialsModificationExistWarehouse();

        // Если у продукта нет модификаций множественных вариантов
        if(!$modifications->valid())
        {
            $form->add('preModification', HiddenType::class);
            $this->formTargetWarehouseModifier($form, $material, $offer, $variation);

            return;
        }

        $currentModification = $modifications->current();
        $label = $currentModification->getOption();
        $domain = null;

        if($currentModification->getReference())
        {
            /** Если торговое предложение Справочник - ищем домен переводов */
            foreach($this->reference as $reference)
            {
                if($reference->type() === $currentModification->getReference())
                {
                    $domain = $reference->domain();
                }
            }
        }

        $form
            ->add(
                'preModification',
                ChoiceType::class,
                [
                    'choices' => $modifications,
                    'choice_value' => function(?MaterialModificationConst $modification) {
                        return $modification?->getValue();
                    },
                    'choice_label' => function(MaterialModificationConst $modification) {
                        return $modification->getAttr();
                    },

                    'choice_attr' => function(?MaterialModificationConst $modification) {

                        if(!$modification)
                        {
                            return [];
                        }

                        if($modification->getAttr())
                        {
                            $attr['data-name'] = $modification->getAttr();
                        }

                        if($modification->getProperty())
                        {
                            $attr['data-filter'] = trim($modification->getCharacteristic().' ('.$modification->getProperty().')');
                        }

                        return $attr;
                    },


                    'label' => $label,
                    'translation_domain' => $domain,
                    'placeholder' => sprintf('Выберите %s из списка...', $label),
                ]
            );
    }

    private function formTargetWarehouseModifier(
        FormInterface $form,
        MaterialUid $material,
        ?MaterialOfferConst $offer = null,
        ?MaterialVariationConst $variation = null,
        ?MaterialModificationConst $modification = null,
    ): void
    {
        $this
            ->materialWarehouseChoice
            ->user($this->user)
            ->material($material);

        $offer ? $this->materialWarehouseChoice->offerConst($offer) : null;
        $variation ? $this->materialWarehouseChoice->variationConst($variation) : null;
        $modification ? $this->materialWarehouseChoice->modificationConst($modification) : null;

        $warehouses = $this->materialWarehouseChoice->fetchWarehouseByMaterial();

        if(!$warehouses->valid())
        {
            $form->add(
                'targetWarehouse',
                ChoiceType::class,
                [
                    'choices' => [],
                    'label' => false,
                    'required' => false,
                ]
            );

            return;
        }

        $form->add(
            'targetWarehouse',
            ChoiceType::class,
            [
                'choices' => $warehouses,
                'choice_value' => function(?UserProfileUid $warehouse) {
                    return $warehouse?->getValue();
                },
                'choice_label' => function(UserProfileUid $warehouse) {
                    return $warehouse->getAttr();
                },
                'choice_attr' => function(?UserProfileUid $warehouse) {

                    if(!$warehouse)
                    {
                        return [];
                    }

                    if($warehouse->getAttr())
                    {
                        $attr['data-name'] = $warehouse->getAttr();
                    }

                    if($warehouse->getProperty())
                    {
                        $attr['data-max'] = $warehouse->getProperty();
                        $attr['data-filter'] = '('.$warehouse->getProperty().')';
                    }

                    return $attr;
                },
                'label' => false,
                'required' => false,
            ]
        );
    }
}
