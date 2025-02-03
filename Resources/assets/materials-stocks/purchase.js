/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */


executeFunc(function materialStocsPurchase()
{
    /* Имя формы */
    ChangePurchaseForm = document.forms.purchase_material_stock_form;

    if(typeof ChangePurchaseForm === 'undefined')
    {
        return false;
    }

    var object_category = document.getElementById(ChangePurchaseForm.name + '_category');

    if(object_category === null)
    {
        return false;
    }

    object_category.addEventListener('change', function()
    {
        changeObjectCategory(ChangePurchaseForm);
    }, false);


    /**
     * Если форма новая - фокусируем на номере
     * при клике Enter  - выделяем категорию
     */
    let numberElement = document.getElementById(ChangePurchaseForm.name + '_invariable_number');
    numberElement.focus();

    numberElement.addEventListener("keydown", function(event)
    {
        if(event.key === "Enter")
        {
            event.preventDefault();

            var categoryElementSelect2 = document.getElementById(ChangePurchaseForm.name + '_category_select2');

            if(categoryElementSelect2)
            {
                categoryElementSelect2.click();
            } else
            {
                var categoryElementSelect = document.getElementById(ChangePurchaseForm.name + '_category');
                categoryElementSelect2.click();
            }
        }
    });

    let $addButtonStock = document.getElementById(ChangePurchaseForm.name + '_addPurchase');
    $addButtonStock.addEventListener('click', addMaterialPurchase, false);

    document.getElementById(ChangePurchaseForm.name + '_purchase')
        .addEventListener('click', function(event)
        {
            /** Поверяем, что в коллекции имеются элементы сырья */
            if(ChangePurchaseForm.querySelectorAll('.item-collection-material')?.length <= 0)
            {
                let $errorFormHandlerMaterials = JSON.stringify({
                    type: 'danger',
                    header: 'Добавить лист закупки сырья',
                    message: 'В списке нет ни одного элемента сырья'
                });

                /* Выводим сообщение об ошибке заполнения */
                createToast(JSON.parse($errorFormHandlerMaterials));

                return false;
            }

            let elementTotal = document.getElementById(ChangePurchaseForm.name + '_preTotal');

            /** Поверяем, что в "Количество" не указано число (пользователь не кликнул "Добавить в закупку")  */
            if(elementTotal)
            {
                if(elementTotal.value.trim() !== "")
                {
                    let $errorFormHandlerMaterials = JSON.stringify({
                        type: 'danger',
                        header: 'Добавить лист закупки сырья',
                        message: 'Вы не добавили сырьё в общий список кликнув "+ Добавить в закупку"'
                    });

                    /* Выводим сообщение об ошибке заполнения */
                    createToast(JSON.parse($errorFormHandlerMaterials));

                    return false;
                }
            }

            if(event.key !== "Enter")
            {
                submitModalForm(ChangePurchaseForm);
            }

            return false;
        });


    return true;
});


async function changeObjectCategory(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preMaterial')?.classList.add('d-none');
    document.getElementById('preOffer')?.classList.add('d-none');
    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');
    document.getElementById('purchase_material_stock_form_addPurchase')?.classList.replace('btn-outline-primary', 'btn-outline-secondary');


    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');

                let preMaterial = result.getElementById('preMaterial');


                /** Сбрасываем ошибки валидации */
                if(preMaterial)
                {
                    preMaterial.querySelectorAll('.is-invalid').forEach((el) => { el.classList.remove('is-invalid'); });
                    preMaterial.querySelectorAll('.invalid-feedback').forEach((el) => { el.remove(); });
                }

                document.getElementById('preMaterial').replaceWith(preMaterial);

                preMaterial ?
                    document
                        ?.getElementById('material')
                        ?.replaceWith(preMaterial) :
                    preMaterial.innerHTML = '';

                /** SELECT2 */
                let replacer = document.getElementById(forms.name + '_preMaterial');
                replacer && replacer.type !== 'hidden' ? preMaterial.classList.remove('d-none') : null;

                /** Событие на изменение модификации */
                if(replacer)
                {

                    if(replacer.tagName === 'SELECT')
                    {
                        new NiceSelect(replacer, {searchable: true});

                        let focus = document.getElementById(forms.name + '_preMaterial_select2');
                        focus ? focus.click() : null;
                    }
                }

                /** сбрасываем зависимые поля */
                let preOffer = document.getElementById('preOffer');
                preOffer ? preOffer.innerHTML = '' : null;

                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById('preVariation');
                preVariation ? preVariation.innerHTML = '' : null;

                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


                if(replacer)
                {

                    replacer.addEventListener('change', function(event)
                    {
                        changeObjectMaterial(forms);
                        return false;
                    });
                }
            }

            enableElementsForm(forms);
        });
}

async function changeObjectMaterial(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preOffer')?.classList.add('d-none');
    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');
    document.getElementById('purchase_material_stock_form_addPurchase')?.classList.replace('btn-outline-primary', 'btn-outline-secondary');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preOffer = result.getElementById('preOffer');

                preOffer ? document.getElementById('preOffer').replaceWith(preOffer) : preOffer.innerHTML = '';

                if(preOffer)
                {
                    /** Сбрасываем ошибки валидации */
                    preOffer.querySelectorAll('.is-invalid').forEach((el) => { el.classList.remove('is-invalid'); });
                    preOffer.querySelectorAll('.invalid-feedback').forEach((el) => { el.remove(); });

                    /** SELECT2 */

                    let replaceOfferId = forms.name + '_preOffer';

                    let replacer = document.getElementById(replaceOfferId);
                    replacer && replacer.type !== 'hidden' ? preOffer.classList.remove('d-none') : null;

                    if(replacer.tagName === 'SELECT')
                    {
                        new NiceSelect(replacer, {searchable: true});

                        let focus = document.getElementById(forms.name + '_preOffer_select2');
                        focus ? focus.click() : null;

                    } else
                    {
                        // Выделяем элемент Количество
                        selectTotal(forms);
                    }

                }

                /** сбрасываем зависимые поля */
                let preVariation = document.getElementById('preVariation');
                preVariation ? preVariation.innerHTML = '' : null;

                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


                /** Событие на изменение торгового предложения */
                let offerChange = document.getElementById(forms.name + '_preOffer');

                if(offerChange)
                {

                    offerChange.addEventListener('change', function(event)
                    {
                        changeObjectOffer(forms);
                        return false;
                    });
                }


                // return;
                //
                //
                // /** Изменияем список целевых складов */
                // let warehouse = result.getElementById('targetWarehouse');
                //
                //
                // document.getElementById('targetWarehouse').replaceWith(warehouse);
                // document.getElementById('new_order_form_targetWarehouse').addEventListener('change', changeObjectWarehause, false);
                //
                // new NiceSelect(document.getElementById('new_order_form_targetWarehouse'), {
                //     searchable: true,
                //     id: 'select2-' + replaceId
                // });

            }

            enableElementsForm(forms);
        });
}

async function changeObjectOffer(forms)
{
    disabledElementsForm(forms);

    document.getElementById('preVariation')?.classList.add('d-none');
    document.getElementById('preModification')?.classList.add('d-none');
    document.getElementById('purchase_material_stock_form_addPurchase')?.classList.replace('btn-outline-primary', 'btn-outline-secondary');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preVariation = result.getElementById('preVariation');

                if(preVariation)
                {

                    preVariation.querySelectorAll('.is-invalid').forEach((el) => { el.classList.remove('is-invalid'); });
                    preVariation.querySelectorAll('.invalid-feedback').forEach((el) => { el.remove(); });


                    document.getElementById('preVariation').replaceWith(preVariation);

                    /** SELECT2 */

                    let replacer = document.getElementById(forms.name + '_preVariation');
                    replacer && replacer.type !== 'hidden' ? preVariation.classList.remove('d-none') : null;

                    if(replacer)
                    {

                        if(replacer.tagName === 'SELECT')
                        {
                            new NiceSelect(replacer, {searchable: true});

                            let focus = document.getElementById(forms.name + '_preVariation_select2');
                            focus ? focus.click() : null;

                            replacer.addEventListener('change', function(event)
                            {
                                changeObjectVariation(forms);
                                return false;
                            });
                        } else
                        {
                            // Выделяем элемент Количество
                            selectTotal(forms);
                        }

                    }

                }


                let preModification = document.getElementById('preModification');
                preModification ? preModification.innerHTML = '' : null;


            }

            enableElementsForm(forms);
        });
}

async function changeObjectVariation(forms)
{

    disabledElementsForm(forms);

    document.getElementById('preModification')?.classList.add('d-none');
    document.getElementById('purchase_material_stock_form_addPurchase')?.classList.replace('btn-outline-primary', 'btn-outline-secondary');

    const data = new FormData(forms);
    data.delete(forms.name + '[_token]');


    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },

        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        //.then((response) => response)
        .then((response) =>
        {

            if(response.status !== 200)
            {
                return false;
            }

            return response.text();

        })

        .then((data) =>
        {

            if(data)
            {

                var parser = new DOMParser();
                var result = parser.parseFromString(data, 'text/html');


                let preModification = result.getElementById('preModification');

                if(preModification)
                {
                    preModification.querySelectorAll('.is-invalid').forEach((el) => { el.classList.remove('is-invalid'); });
                    preModification.querySelectorAll('.invalid-feedback').forEach((el) => { el.remove(); });


                    document.getElementById('preModification').replaceWith(preModification);

                    /** SELECT2 */
                    let replacer = document.getElementById(forms.name + '_preModification');
                    replacer && replacer.type !== 'hidden' ? preModification.classList.remove('d-none') : null;

                    /** Событие на изменение модификации */
                    if(replacer)
                    {
                        if(replacer.tagName === 'SELECT')
                        {
                            new NiceSelect(replacer, {searchable: true});

                            let focus = document.getElementById(forms.name + '_preModification_select2');
                            focus ? focus.click() : null;

                            replacer.addEventListener('change', function(event)
                            {
                                selectTotal(forms)
                                return false;
                            });

                        } else
                        {
                            // Выделяем элемент Количество
                            selectTotal(forms);
                        }
                    }
                }
            }

            enableElementsForm(forms);
        });
}

function selectTotal(forms)
{
    setTimeout(function()
    {
        let focusTotal = document.getElementById(ChangePurchaseForm.name + '_preTotal');

        document.getElementById(ChangePurchaseForm.name + '_addPurchase')?.classList.replace('btn-outline-secondary', 'btn-outline-primary');

        focusTotal.value = '';
        focusTotal ? focusTotal.focus() : null;

        focusTotal.addEventListener("keydown", enterTotalElement);

    }, 100);
}

/** При клике Enter добавляем элемент сырья в коллекцию */
function enterTotalElement(event)
{
    if(event.key === "Enter")
    {
        event.preventDefault();  // Отменяем стандартное действие
        addMaterialPurchase();     // Вызываем вашу функцию

        // Удаляем обработчик события
        document.removeEventListener('keydown', enterTotalElement);
    }
}


var collectionMaterialPurshase = new Map();

function addMaterialPurchase()
{

    const dynamicElements = ['preMaterial', 'preOffer', 'preVariation', 'preModification'];

    /* Блок для новой коллекции КАТЕГОРИИ */
    let $blockCollectionStock = document.getElementById('collectionStock');

    /* Добавляем новую коллекцию */
    //$addButtonStock.addEventListener('click', function () {


    let $errorFormHandler = null;
    let header = 'Добавить лист закупки сырья';


    let $preTotal = document.getElementById(ChangePurchaseForm.name + '_preTotal');


    /** Проверяем на заполнение количество */
    let $TOTAL = $preTotal.value * 1;

    if($TOTAL === undefined || $TOTAL < 1)
    {
        $errorFormHandler = JSON.stringify({
            type: 'danger',
            header: header,
            message: "Не заполнено количество"
        });
    }

    /** Проверяем что заполнен номер квитанции */
    let $number = document.getElementById(ChangePurchaseForm.name + '_invariable_number');

    if($number === null || $number.value.length === 0)
    {
        $errorFormHandler = JSON.stringify({
            type: 'danger',
            header: header,
            message: "Не заполнен номер закупки"
        });
    }

    let $preCategory = document.getElementById(ChangePurchaseForm.name + '_category');
    let $preMaterial = document.getElementById(ChangePurchaseForm.name + '_preMaterial');
    let $preOffer = document.getElementById(ChangePurchaseForm.name + '_preOffer');
    let $preVariation = document.getElementById(ChangePurchaseForm.name + '_preVariation');
    let $preModification = document.getElementById(ChangePurchaseForm.name + '_preModification');

    /** Делаем проверку на выбор сырья */
    if($preCategory.value.length === 0)
    {
        $errorFormHandler = JSON.stringify({
            type: 'danger',
            header: header,
            message: $preCategory.options[0].textContent
        });
    }

    /** Делаем проверку на выбор динамических элементов */
    dynamicElements.forEach(id =>
    {
        const element = document.getElementById(ChangePurchaseForm.name + '_' + id);
        if(element && element.tagName === 'SELECT' && element.value.length === 0)
        {
            const message = element.options[0].textContent;
            $errorFormHandler = JSON.stringify({
                type: 'danger',
                header: header,
                message: message
            });
        }
    });


    /** Добавляем сырьё в карту уникальных элементов */
    const mapKey = [$preMaterial, $preOffer, $preVariation, $preModification].map(item => item ? item.value : '').join('');

    if(collectionMaterialPurshase.has(mapKey))
    {
        $errorFormHandler = JSON.stringify({
            type: 'danger',
            header: header,
            message: 'сырьё уже добавлено в список'
        });
    }

    /* Выводим сообщение об ошибке заполнения */

    if($errorFormHandler)
    {
        createToast(JSON.parse($errorFormHandler));
        return false;
    }


    /** Добавляем сырьё в коллекцию */


    /* получаем прототип коллекции  */
    let $addButtonStock = document.getElementById(ChangePurchaseForm.name + '_addPurchase');

    let newForm = $addButtonStock.dataset.prototype;
    let index = $addButtonStock.dataset.index * 1;

    /* Замена '__name__' в HTML-коде прототипа
     вместо этого будет число, основанное на том, сколько коллекций */
    newForm = newForm.replace(/__material__/g, index);
    //newForm = newForm.replace(/__FIELD__/g, index);


    /* Вставляем новую коллекцию */
    let stockDiv = document.createElement('div');

    stockDiv.classList.add('item-collection-material');
    stockDiv.classList.add('w-100');
    stockDiv.innerHTML = newForm;
    $blockCollectionStock.append(stockDiv);


    let $total = stockDiv.querySelector('#' + ChangePurchaseForm.name + '_material_' + index + '_total')
    $total.value = $preTotal.value;


    /** Присваиваем элемент сырья */
    let $material = stockDiv.querySelector('#' + ChangePurchaseForm.name + '_material_' + index + '_material');
    $material.value = $preMaterial.value;

    let materialIndex = $preMaterial.selectedIndex;
    let $materialName = $preMaterial.options[materialIndex].textContent;


    /** Присваиваем элемент торгового предложения */
    let $offerName = '';

    if($preOffer)
    {
        let $offer = stockDiv.querySelector('#' + ChangePurchaseForm.name + '_material_' + index + '_offer');
        $offer.value = $preOffer?.value;

        let offerIndex = $preOffer.selectedIndex;
        $offerName = $preOffer.tagName === 'SELECT' ? '&nbsp; <small class="text-muted fw-normal">' + document.querySelector('label[for="' + $preOffer.id + '"]').textContent + '</small> ' + $preOffer.options[offerIndex].textContent : '';
    }

    /** Присваиваем элемент множественного варианта торгового предложения */
    let $variationName = '';

    if($preVariation)
    {
        let $variation = stockDiv.querySelector('#' + ChangePurchaseForm.name + '_material_' + index + '_variation');
        $variation.value = $preVariation.value;

        let variationIndex = $preVariation.selectedIndex;
        $variationName = $preVariation.tagName === 'SELECT' ? '&nbsp; <small class="text-muted fw-normal">' + document.querySelector('label[for="' + $preVariation.id + '"]').textContent + '</small> ' + $preVariation.options[variationIndex].textContent : '';
    }

    /** Присваиваем элемент модификации множественного варианта торгового предложения */
    let $modificationName = '';

    if($preModification)
    {
        let $modification = stockDiv.querySelector('#' + ChangePurchaseForm.name + '_material_' + index + '_modification');
        $modification.value = $preModification.value;

        let modificationIndex = $preModification.selectedIndex;
        $modificationName = $preModification.tagName === 'SELECT' ? '&nbsp; <small class="text-muted fw-normal">' + document.querySelector('label[for="' + $preModification.id + '"]').textContent + '</small> ' + $preModification.options[modificationIndex].textContent : '';

    }


    /** Конкатенируем полученные значения для вставки */
    let $materialTextBlock = stockDiv.querySelector('#material-text-' + index);
    $materialTextBlock.innerHTML = $materialName + $offerName + $variationName + $modificationName + '&nbsp; : &nbsp;' + $total.value + ' шт.';


    /** Сбрасываем количество после добавления */
    $preTotal.value = null;


    /* Удаляем при клике элемент коллекции */
    stockDiv.querySelector('.del-item-material').addEventListener('click', function()
    {
        this.closest('.item-collection-material').remove();
        index = $addButtonStock.dataset.index * 1;
        $addButtonStock.dataset.index = (index - 1).toString();

        collectionMaterialPurshase.delete(mapKey);
    });

    /* Увеличиваем data-index на 1 после вставки новой коллекции */
    $addButtonStock.dataset.index = (index + 1).toString();


    /* После применения сбрасываем ТП и выделяем сырьё */
    dynamicElements.forEach(id =>
    {
        if(id === 'preMaterial')
        {
            return;
        }

        const element = document.getElementById(id);

        if(element)
        {
            element.innerHTML = '';
            element.classList.add('d-none');
        }
    });

    /** Добавляем элемент в карту уникальности */
    collectionMaterialPurshase.set(mapKey, $total.value);

    var limit_faaJUfW = 1000;

    setTimeout(function init_TQCmNQtx()
    {

        var input_preMaterial_select = document.getElementById(ChangePurchaseForm.name + '_preMaterial_select2');

        if(input_preMaterial_select)
        {
            input_preMaterial_select.click();
            return;
        }

        var input_preMaterial = document.getElementById(ChangePurchaseForm.name + '_preMaterial');

        if(input_preMaterial)
        {
            document.getElementById(ChangePurchaseForm.name + '_preMaterial_select2');
            return;
        }

        if(limit_faaJUfW > 1000)
        {
            return;
        }

        limit_faaJUfW = limit_faaJUfW * 2;

        setTimeout(init_TQCmNQtx, limit_faaJUfW);

    }, 100);
}