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


// modal.addEventListener('shown.bs.modal', function () {
//
//
//
//     /* Change MATERIAL */
//     let object_material = document.getElementById('incoming_material_stock_form_preMaterial');
//
//
//
//     if (object_material) {
//         object_material.addEventListener('change', changeObjectMaterial, false);
//
//
//         let $addButtonStock = document.getElementById('incoming_material_stock_form_addIncoming');
//
//         if ($addButtonStock) {
//             $addButtonStock.addEventListener('click', addMaterialIncoming, false);
//         }
//
//
//
//     } else {
//         eventEmitter.addEventListener('complete', function ()
//         {
//             let object_material = document.getElementById('incoming_material_stock_form_preMaterial');
//
//             if (object_material) {
//                 object_material.addEventListener('change', changeObjectMaterial, false);
//
//
//                 let $addButtonStock = document.getElementById('incoming_material_stock_form_addIncoming');
//
//                 if ($addButtonStock) {
//                     $addButtonStock.addEventListener('click', addMaterialIncoming, false);
//                 }
//             }
//         });
//     }
//
// });


function changeObjectMaterial()
{

    let replaceId = 'incoming_material_stock_form_preOffer';


    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();
    requestModalName.responseType = "document";

    /* Имя формы */
    let incomingForm = document.forms.incoming_material_stock_form;
    let formData = new FormData();
    formData.append(this.getAttribute('name'), this.value);

    requestModalName.open(incomingForm.getAttribute('method'), incomingForm.getAttribute('action'), true);

    /* Указываем заголовки для сервера */
    requestModalName.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    /* Получаем ответ от сервера на запрос*/
    requestModalName.addEventListener("readystatechange", function()
    {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(requestModalName.readyState === 4 && requestModalName.status === 200)
        {

            let result = requestModalName.response.getElementById('preOffer');


            document.getElementById('preOffer').replaceWith(result);

            let replacer = document.getElementById(replaceId);

            if(replacer.tagName === 'SELECT')
            {
                new NiceSelect(replacer, {searchable: true, id: 'select2-' + replaceId});

                /** Событие на изменение торгового предложения */
                let offerChange = document.getElementById('incoming_material_stock_form_preOffer');

                if(offerChange)
                {
                    offerChange.addEventListener('change', changeObjectOffer, false);
                }
            }


        }

        return false;
    });

    requestModalName.send(formData);
}


function changeObjectOffer()
{


    let replaceId = 'incoming_material_stock_form_preVariation';

    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();
    requestModalName.responseType = "document";

    /* Имя формы */
    let incomingForm = document.forms.incoming_material_stock_form;
    let formData = new FormData();
    formData.append(this.getAttribute('name'), this.value);

    requestModalName.open(incomingForm.getAttribute('method'), incomingForm.getAttribute('action'), true);

    /* Указываем заголовки для сервера */
    requestModalName.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    /* Получаем ответ от сервера на запрос*/
    requestModalName.addEventListener("readystatechange", function()
    {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(requestModalName.readyState === 4 && requestModalName.status === 200)
        {

            let result = requestModalName.response.getElementById('preVariation');

            document.getElementById('preVariation').replaceWith(result);

            let replacer = document.getElementById(replaceId);

            if(replacer.tagName === 'SELECT')
            {
                new NiceSelect(document.getElementById(replaceId), {searchable: true, id: 'select2-' + replaceId});

                /** Событие на изменение множественного варианта предложения */
                let offerVariation = document.getElementById('incoming_material_stock_form_preVariation');

                if(offerVariation)
                {
                    offerVariation.addEventListener('change', changeObjectVariation, false);
                }
            }

        }

        return false;
    });

    requestModalName.send(formData);
}


function changeObjectVariation()
{

    let replaceId = 'incoming_material_stock_form_preModification';

    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();
    requestModalName.responseType = "document";

    /* Имя формы */
    let incomingForm = document.forms.incoming_material_stock_form;
    let formData = new FormData();
    formData.append(this.getAttribute('name'), this.value);

    requestModalName.open(incomingForm.getAttribute('method'), incomingForm.getAttribute('action'), true);

    /* Указываем заголовки для сервера */
    requestModalName.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    /* Получаем ответ от сервера на запрос*/
    requestModalName.addEventListener("readystatechange", function()
    {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(requestModalName.readyState === 4 && requestModalName.status === 200)
        {

            let result = requestModalName.response.getElementById('preModification');

            document.getElementById('preModification').replaceWith(result);

            let replacer = document.getElementById(replaceId);

            if(replacer.tagName === 'SELECT')
            {
                new NiceSelect(document.getElementById(replaceId), {searchable: true, id: 'select2-' + replaceId});
            }

        }

        return false;
    });

    requestModalName.send(formData);

}


function addMaterialIncoming()
{

    /* Блок для новой коллекции КАТЕГОРИИ */
    let $blockCollectionStock = document.getElementById('collectionStock');

    /* Добавляем новую коллекцию */
    //$addButtonStock.addEventListener('click', function () {


    let $errorFormHandler = null;


    let $preTotal = document.getElementById('incoming_material_stock_form_preTotal');
    let $TOTAL = $preTotal.value * 1;
    if($TOTAL === undefined || $TOTAL < 1)
    {

        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"Добавить приход"  , ' +
            '"message" : "Не заполнено количество прихода" }';

    }

    let $preWarehouse = document.getElementById('incoming_material_stock_form_preWarehouse');
    if($preWarehouse.value.length === 0)
    {

        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"Добавить приход"  , ' +
            '"message" : "' + $preWarehouse.options[0].textContent + '" }';

    }


    let $preMaterial = document.getElementById('incoming_material_stock_form_preMaterial');
    if($preMaterial.value.length === 0)
    {

        $errorFormHandler = '{ "type":"danger" , ' +
            '"header":"Добавить приход"  , ' +
            '"message" : "' + $preMaterial.options[0].textContent + '" }';

    }


    let $preOffer = document.getElementById('incoming_material_stock_form_preOffer');
    if($preOffer)
    {
        if($preOffer.tagName === 'SELECT' && $preOffer.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"Добавить приход"  , ' +
                '"message" : "' + $preOffer.options[0].textContent + '" }';
        }
    }


    let $preVariation = document.getElementById('incoming_material_stock_form_preVariation');
    if($preVariation)
    {
        if($preVariation.tagName === 'SELECT' && $preVariation.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"Добавить приход"  , ' +
                '"message" : "' + $preVariation.options[0].textContent + '" }';
        }
    }

    let $preModification = document.getElementById('incoming_material_stock_form_preModification');
    if($preModification)
    {
        if($preModification.tagName === 'SELECT' && $preModification.value.length === 0)
        {

            $errorFormHandler = '{ "type":"danger" , ' +
                '"header":"Добавить приход"  , ' +
                '"message" : "' + $preModification.options[0].textContent + '" }';
        }
    }


    /* Выводим сообщение об ошибке заполнения */

    if($errorFormHandler)
    {
        createToast(JSON.parse($errorFormHandler));
        return false;
    }


    /* получаем прототип коллекции  */
    let $addButtonStock = this;

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

    let $warehouse = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_warehouse');
    $warehouse.value = $preWarehouse.value;

    let $material = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_material');
    $material.value = $preMaterial.value;

    let $offer = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_offer');
    $offer.value = $preOffer.value;

    let $variation = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_variation');
    $variation.value = $preVariation.value;

    let $modification = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_modification');
    $modification.value = $preModification.value;

    let $total = stockDiv.querySelector('#incoming_material_stock_form_material_' + index + '_total')
    $total.value = $preTotal.value;


    let materialIndex = $preMaterial.selectedIndex;
    let $materialName = $preMaterial.options[materialIndex].textContent;

    let offerIndex = $preOffer.selectedIndex;
    let $offerName = $preOffer.tagName === 'SELECT' ? document.querySelector('label[for="' + $preOffer.id + '"]').textContent + ' ' + $preOffer.options[offerIndex].textContent : '';

    let variationIndex = $preVariation.selectedIndex;
    let $variationName = $preVariation.tagName === 'SELECT' ? document.querySelector('label[for="' + $preVariation.id + '"]').textContent + ' ' + $preVariation.options[variationIndex].textContent : '';

    let modificationIndex = $preModification.selectedIndex;
    let $modificationName = $preModification.tagName === 'SELECT' ? document.querySelector('label[for="' + $preModification.id + '"]').textContent + ' ' + $preModification.options[modificationIndex].textContent : '';


    let $materialTextBlock = stockDiv.querySelector('#material-text-' + index);
    $materialTextBlock.innerHTML = $materialName + ' ' + $offerName + ' ' + $variationName + ' ' + $modificationName + '&nbsp; : &nbsp;' + $total.value + ' шт.';

    $preTotal.value = null;


    /* Удаляем при клике колекцию СЕКЦИЙ */
    stockDiv.querySelector('.del-item-material').addEventListener('click', function()
    {
        this.closest('.item-collection-material').remove();
        index = $addButtonStock.dataset.index * 1;
        $addButtonStock.dataset.index = (index - 1).toString();
    });

    /* Увеличиваем data-index на 1 после вставки новой коллекции */
    $addButtonStock.dataset.index = (index + 1).toString();

    /* применяем select2 */
    //new NiceSelect(div.querySelector('[data-select="select2"]'), {searchable: true});


    //});
}



