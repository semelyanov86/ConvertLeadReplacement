//'use strict';
//TODO onload/onready
setTimeout(function () {

    // $('.blockMsg').css({"margin-top": "-50px"});

    // Init calendar plugin
    var dateParams = {calendars: 1, className : 'rangeCalendar'},
        dateElement = $('body').find('input.dateRange');
    dateElement.addClass('dateField').attr('data-date-format','dd-mm-yyyy');
    app.registerEventForDatePickerFields(dateElement, false, dateParams);

    //
    $('[name = ConvertLeadToSalesOrder]').validationEngine();
    $(':input').inputmask();
    $('.modelContainer').scroll(function (){
        $('.formErrorContent').parent().detach();
    });

    // Init events products
    addRow('prod', {
        id: 42,
        name: 'Букет цветов',
        description: '',
        listprice: 1000
    });
    eventsProducts();

    // Init events form
    eventsForm();

    // Set times
    $.each(timeValues, function (k, v) {
        if (v) {
            $('[name = ' + k + ']').val(v);
        }
    });

    if ($('[name = timedost]').val() == $('[name = timedostfrom]').val()) {
        /*
        $('[name = timeselect]').click();
        $('.timeselectJs').attr('disabled', true);
        $('[name = timedostfrom], [name = timededline]').val($('[name = timedost]').val());
        */
        $('[name = timedostminutefrom], [name = timededlineminute]').val($('[name = timedostminute]').val());
    }

    // Save Sales Order
    $('[name = ConvertLeadToSalesOrder]').on('submit', function(e) {
        //if (checkField() && getJsonSelectedProducts() && confirm('Преобразовать обращение?')) {
        //PINstudio begin @Denis #red-86
        if (!(checkField() && getJsonSelectedProducts())) return false;
        //TODO email validation skipped!
        e.preventDefault();
        var ConvertForm = this;
        var globModal = $('.blockPage');
        globModal.css('transition','all .5s ease');
        globModal.css('transform','scale(.8)');

        var bootConfirm = bootbox.confirm(
            "<h3>Преобразование</h3><hr/>"
            +"<br/>Подтверждаете создание заказа?",
            'Нет',
            'Да',
            function(result) {
                if(!result) {
                    globModal.css('transform','scale(1)');
                    return;
                }

                globModal.css('transform','translateY(150%)');
                var phoneNum = $('[name = mobile]', ConvertForm).val();
                var params = {
                    'module' : 'Leads',
                    'action' : 'ConvertLeadToSalesOrder',
                    'mode'   : 'getContactByNum',
                    'number' : phoneNum
                };
                var inputAction = $('[name = contactAction]');
                //TODO notifications, empty check
                AppConnector.request(params).then(function(contactData){
                    if (!contactData.result) {
                        ConvertForm.submit();
                        return;
                    }
                    var fio = contactData.result['fio'],
                        cid = contactData.result['crmid'];

                    contactActions(fio, phoneNum, cid)
                        .done(function(e) {
                            inputAction.val(cid);
                            ConvertForm.submit();
                        })
                        .fail(function(e){
                            ConvertForm.submit();
                        })
                });
        });
        $('.modal-backdrop').css('z-index','10010');
        //PINstudio end
    });


    // Event add product
    /*
     * only one empty row
     */
    $('#addDynaProduct').on('click', function(){
        var rows = $('#products .product-for-so');
        for (var i=0,l = rows.length; i<l; i++){
            //Vtiger_Pnotify('Заполните все существующие поля')
            if ($(rows[i]).data('id') == '') return false;
        }
        addRow('prod',false);
    });
    $('#addDynaService').on('click', function(){
        var rows = $('#products .product-for-so');
        for (var i=0,l = rows.length; i<l; i++){
            //Vtiger_Pnotify('Заполните все существующие поля')
            if ($(rows[i]).data('id') == '') return false;
        }
        addRow('svc',false);
    });
    $('#addProduct').on('click', function() {
        var popup = new Vtiger_Popup_Js;

        // Call popup Inventory
        var windowPopup = popup.show({view: "ProductsPopup", module: "SalesOrder", multi_select: true}, function (data){
            
            var cnt = $('#products tbody'),
                dataObj = $.parseJSON(data);

            for (var itemId in dataObj) {
                var item = dataObj[itemId];

                if (!item.id) {
                    for (var i in item) {
                        item = item[i];
                        break;
                    }
                }

                addRow('prod', item);
            }

            // Reload events products
            eventsProducts();

            // // Focus on quantity field of new product
            // $('[data-id = ' + dataObj.id + '] [name = quantity]').focus();
        });
    });
    $('#addService').on('click', function() {
        var popup = new Vtiger_Popup_Js;

        // Call popup Inventory
        var windowPopup = popup.show({view: "ServicesPopup", module: "SalesOrder", multi_select: true}, function (data){
            
            var cnt = $('#products tbody'),
                dataObj = $.parseJSON(data);

            for (var itemId in dataObj) {
                var item = dataObj[itemId];

                if (!item.id) {
                    for (var i in item) {
                        item = item[i];
                        break;
                    }
                }

                addRow('svc', item);
            }

            // Reload events products
            eventsProducts();

            // // Focus on quantity field of new product
            // $('[data-id = ' + dataObj.id + '] [name = quantity]').focus();
        });
    });
}, 10);


var Convert = {
    types : {
        prod : {
            md : 'Products',
            bg : '#EA6C6C',
            ws : '14'
        },
        svc  : {
            md : 'Services',
            bg : '#6c97ea',
            ws : '25'
        }
    }
}

/*
 * add [empty] product row
 * with autocomplete and events
 * @param one of the Convert types
 * @param mixed - false|inventory item
 */
function addRow(type, oItem)
{
    var item = oItem?oItem:{
        id : -1,
        name : '',
        description: '',
        listprice: 0
    }
    var cnt = $('#products tbody');
    var prodId = (oItem)?Convert.types[type].ws + 'x' + item.id:'';
    var bg     = (oItem)?'background:' + Convert.types[type].bg:'';
    var newRow = [
        '<tr class="product-for-so" data-id="' + prodId + '">',
        '<td class="fieldValue remove" style="width: 4%; cursor: pointer; ' + bg + '"><span class="icon-trash"></span></td>',
        '<td class="fieldValue"><input class="input-large auto" name="pt-title" type="text" value="' + item.name + '"></td>',
        '<td class="fieldValue"><textarea name="pt-description" rows="2" style="resize: none; width: 300px">' + item.description + '</textarea></td>',
        '<td class="fieldValue"><input class="input-small ePriceJs" style="width: 42px;" data-inputmask="\'mask\': \'9{*}\'" name="pt-quantity" type="text" data-validation-engine="validate[required,custom[integer],min[0]]" value="1"></td>',
        '<td class="fieldValue"><input class="input-small ePriceJs" style="width: 42px;" data-inputmask="\'mask\': \'9{*}\'" name="pt-price" type="text" data-validation-engine="validate[required,custom[integer],min[0]]" value="' + item.listprice + '"></td>',
        '<td class="fieldValue"><input class="input-small ePriceJs" style="width: 48px;" data-inputmask="\'mask\': \'9{*}\'" name="pt-sale" type="text" data-validation-engine="validate[required,custom[integer],min[0],max[100]]" value="0"></td>',
        '</tr>'
    ].join("\n");
    var $newNode = $(newRow);

    initAutocomplete($newNode, type);

    cnt.append($newNode);
    eventsProducts();
}

//PINstudio @DK refactoring
function recalcTotals() {
    var price = 0, tmpPrice, tmpSale;

    $('#products tbody tr').each(function (i,x){
        if (Number($(x).find('[name = pt-price]').val()) != 0) {
            tmpPrice = Number($(x).find('[name = pt-price]').val());

            if (Number($(x).find('[name = pt-quantity]').val()) != null) {
                tmpPrice *= Number($(x).find('[name = pt-quantity]').val());
            }

            if (Number($(x).find('[name = pt-sale]').val()) != 0) {
                tmpSale = tmpPrice / 100;
                tmpSale *= Number($(x).find('[name = pt-sale]').val());
                tmpPrice -= tmpSale;
            }

            price += tmpPrice;
        }
    });
    price += Number($('[name = s_h_amount]').val());

    $('#products').data('price', price);
    //console.log(price);

    // Заполнение полей
    var $paystatus = $('#paystatus');
    var $doplata   = $('[name = doplata]');
    //PINstudio begin @DK #445
    /*
    if ($('[name = paystcheck]').prop('checked') == true) {
        $paystatus.attr('readonly', true).val(price);
        $doplata.val(0);
    } else {
        $paystatus.attr('readonly', false);

        if ($paystatus.val() != 0 && $paystatus.val() != null) {
            if (price - Number($paystatus.val()) < 0) {
                alert('"Оплачено" больше чем общая сумма');
            } 
            $doplata.val(price - Number($paystatus.val()));
            
        } else {
            $doplata.val(price);
        }
    }

    $('[name = paystcheck]').prop('checked', false); $paystatus.attr('readonly', false);
    $paystatus.val(0);
    $doplata.val(price);
    */
    //PINstudio end
    // Итого
    var dopAmount = price - Number($paystatus.val());
    $doplata.val((dopAmount>0)?dopAmount:0);
    $('#products-summ span').text(price);
}
// PINstudio end

//PINstudio begin @DK #red-86

/*
 * modal in case contact with this number found
 */
function contactActions(fio, number, contactId){
    var a = $.Deferred();
    bootbox.confirm(
        '<h3>' + fio + ' уже есть в базе!</h3><hr/>'
        +'<br/>В базе уже существует контакт <b>' + fio + '</b> с моб. ' + number
        +'. <a href="index.php?module=Contacts&view=Detail&record=' + contactId + '" target="_blank">Посмотреть в новой вкладке</a>.'
        +'<br/>Добавить этот заказ к существующему контакту или создать новый?',
        'Создать новый контакт',
        'Добавить заказ к существующему',
        function(result) {
            if(result){
                a.resolve('Append');
            } else {
                a.reject('Create');
            }
        });
    return a.promise();
}
//PINstudio end
/*
 * input mask + recalculate
 * TODO scope by parent
 */
function eventsProducts() {
    $(':input').inputmask();

    //
    $('[name = pt-sale]').on('input', function (){
        if ($(this).val() > 100) {
            $(this).val(100);
        }
    });

    // Event remove product
    $('.product-for-so .remove').on('click', function (){
        $(this).parent().detach();
        recalcTotals();
    });

    // Подсчет суммы для оплаты
    $('.product-for-so .ePriceJs').on('input', function () {
        recalcTotals();
    });

}

/*
 * all form events
 */
function eventsForm() {
    // Если заказ себе
    $('[name = setmefunct]').on('click', function (){
        if ($(this).prop('checked') == true) {
            //console.log('e setmefunct');
            $('[name = namepolychatel], [name = phone]').attr('readonly', true);
        } else {
            $('[name = namepolychatel], [name = phone]').attr('readonly', false);
        }
    });
    /*
    // Подсчет суммы для оплаты
    var sumPrice = function () {
        // Заполнение полей
        var price = $('#products').data('price');
        if (price != undefined) {
            if ($('[name = paystcheck]').prop('checked') == true) {
                $('[name = paystatus]').attr('readonly', true).val(price);
                $('[name = doplata]').val(0);
            } else {
                $('[name = paystatus]').attr('readonly', false);

                if ($('[name = paystatus]').val() != 0 && $('[name = paystatus]').val() != null) {
                    if (price - Number($('[name = paystatus]').val()) < 0) {
                        alert('"Оплачено" больше чем общая сумма');
                    }
                    $('[name = doplata]').val(price - Number($('[name = paystatus]').val()));
                    
                } else {
                    $('[name = doplata]').val(price);
                }
            }
            // Итого
            $('#products-summ span').text(price);    
        } else {
            $('[name = paystcheck]').prop('checked', false);
        }
    };

    $('[name = paystatus]').on('change', sumPrice);
    $('[name = paystcheck]').on('click', sumPrice);
    */

    // Если к точному времени
    $('[name = timeselect]').on('click', function (){
        if ($(this).prop('checked') == true) {
            //console.log('e timeselect');
            $('.timeselectJs').attr('disabled', true);
            $('[name = timedostfrom], [name = timededline]').val($('[name = timedost]').val());
            $('[name = timedostminutefrom], [name = timededlineminute]').val($('[name = timedostminute]').val());
        } else {
            $('.timeselectJs').attr('disabled', false);
        }
    });

    // Если самовывоз
    $('[name = samov]').on('click', function (){
        var dc = $('[name=s_h_amount]');
        var state = $(this).is(':checked');
        // dc.prop('disabled', state);
        if (state == true) {
            //console.log('e samov');
            $('.samovJs').hide();
            $('#delivery_cost').hide();
            $('.jsSamovTime').show();
            dc.val(0);
            recalcTotals();
        } else {
            $('.samovJs').show();
            $('#delivery_cost').show();
            $('.jsSamovTime').hide();
        }
    });

    $('[name=s_h_amount]').keyup(function(e){
        recalcTotals();
    });

    //PINstudio begin @Denis #red-269
    var magaz = $('[name = magazin]');
    var shop  = $('[name = shopid]');
    
    shop.on('change', function(e, f) {
        if (f === false) return;
        magaz.val(shop.find('option:selected').text());
    });

    //Lookup button
    $('#getShops').on('click', function() {
        var popUp = Vtiger_Popup_Js.getInstance();
        var params = {
                'module'   : 'Shops',
                'src_field': 'shopid',
                'view'     : 'Popup'
            };
        var cityField = $('[name = city]').val();
        if (cityField !== ''){
            params['search_key'] = 'city';
            params['search_value'] = cityField;
        }
        popUp.show(
            params,
            function(data){
                var dataObj = $.parseJSON(data);
                var v = Object.keys(dataObj)[0];
                shop.val(v)
                    .trigger('liszt:updated') //deprecated
                    .trigger('change');
            },
            'Please select a shop',
            'eventGetShop'
        );
    });
    //PINstudio end


    // Проверка времени
    $('.checkTimeJs').on('change', function (){
        // // Для самовывоза
        // if ($('[name = timedostsam]').val() > $('[name = timedostfromsam]').val()) {
        //     $('[name = timedostfromsam]').val($('[name = timedostsam]').val());
        //     if ($('[name = timedostminutesam]').val() == '45') {
        //         $('[name = timedostminutefromsam]').val('00');
        //     } else {
        //         $('[name = timedostminutefromsam]').val(Number($('[name = timedostminutesam]').val()) + 15);
        //     }
        // } else if($('[name = timedostsam]').val() == $('[name = timedostfromsam]').val()) {
        //     if ($('[name = timedostminutesam]').val() > $('[name = timedostminutefromsam]').val()) {
        //         $('[name = timedostfromsam]').val($('[name = timedostsam]').val());
        //         if ($('[name = timedostminutesam]').val() == '45') {
        //             if ($('[name = timedostsam]').val() == '23') {
        //                 $('[name = timedostfromsam]').val('00');
        //             } else {
        //                 $('[name = timedostfromsam]').val(Number($('[name = timedostsam]').val()) + 1);
        //             }
        //             $('[name = timedostminutefromsam]').val('00');
        //         } else {
        //             $('[name = timedostminutefromsam]').val(Number($('[name = timedostminutesam]').val()) + 15);
        //         }
        //     }
        // }

        // // Для доставки
        // if ($('[name = timedost]').val() > $('[name = timedostfrom]').val()) {
        //     $('[name = timedostfrom], [name = timededline]').val($('[name = timedost]').val());
        //     if ($('[name = timedostminute]').val() == '45') {
        //         $('[name = timedostminutefrom], [name = timededlineminute]').val('00');
        //     } else {
        //         $('[name = timedostminutefrom], [name = timededlineminute]').val(Number($('[name = timedostminute]').val()) + 15);
        //     }
        // } else if($('[name = timedost]').val() == $('[name = timedostfrom]').val()) {
        //     if ($('[name = timedostminute]').val() > $('[name = timedostminutefrom]').val()) {
        //         $('[name = timedostfrom], [name = timededline]').val($('[name = timedost]').val());
        //         if ($('[name = timedostminute]').val() == '45') {
        //             if ($('[name = timedost]').val() == '23') {
        //                 $('[name = timedostfrom], [name = timededline]').val('00');
        //             } else {
        //                 $('[name = timedostfrom], [name = timededline]').val(Number($('[name = timedost]').val()) + 1);
        //             }
        //             $('[name = timedostminutefrom], [name = timededlineminute]').val('00');
        //         } else {
        //             $('[name = timedostminutefrom], [name = timededlineminute]').val(Number($('[name = timedostminute]').val()) + 15);
        //         }
        //     }
        // }

        // // Deadline
        // if ($('[name = timededline]').val() < $('[name = timedostfrom]').val()) {
        //     $('[name = timededline]').val($('[name = timedostfrom]').val());
        //     $('[name = timededlineminute]').val($('[name = timedostminutefrom]').val()) 
        // }
    });

    $('[name = timedostfrom]').on('change', function (){
        $('[name = timededline]').val($(this).val());
    });

    $('[name = timedostminutefrom]').on('change', function (){
        $('[name = timededlineminute]').val($(this).val());
    });

    $('#spanPayment').on('click',function(e){
        e.preventDefault();
        var l = Leads_Detail_Js.getInstance();
        l.showAddPayment();
    });
}

/*
 * reseting product values
 */
function resetValues($node)
{
    $node.data('id','');
    $node.find('.remove').css('background', '');
    $node.find('[name=pt-title]').val('');
    $node.find('[name=pt-price]').val('');
    $node.find('.ePriceJs').val(0);
    //$node.find('').val('');
    //$node.find('').val('');

    return false;
}

/*
 * init pt-title field with jquery autocomplete
 */
function initAutocomplete($node, type)
{
    if ($node.length != 1) return;
    //autocomplete for the product row
    var $selector = $node.find('[name=pt-title]');
    //*
    $selector.keyup(function() {
        if (!$(this).val()) {
            resetValues($node);
        }
    });
    //*/
    $selector.autocomplete({
        'minLength' : '3',
        'source' : function(request, response){
            var params = {
                module       : 'SalesOrder',
                action       : 'BasicAjax',
                search_module: Convert.types[type].md,
                search_value : $selector.val()
            };
            AppConnector.request(params).then(function(data){
                var searchResults = [];
                if (data.result.length === 0){
                    searchResults = [{
                        'label': app.vtranslate('JS_NO_RESULTS_FOUND'),
                        'type': 'no results'
                    }];
                } else {
                    data.result.map(function(x,i){
                        searchResults.push(x);
                    });
                }

                response(searchResults);
            });
        },
        'select' : function(event, ui ){
            var item = ui.item;
            if(typeof item.type != 'undefined'
                && item.type=='no results'
            ){
                resetValues($node);
                return false;
            }

            var params = {
                module : 'Inventory',
                action : 'GetTaxes',
                record : item.id
            };

            AppConnector.request(params).then(function(data){
                if(!data.success
                    || (typeof data.result != 'object')
                ) return;

                var inventoryItm = data.result[Object.keys(data.result)[0]];
                //$selector.attr('disabled','disabled');
                setInventory($node, type, inventoryItm);
                recalcTotals();
            });
        },
        'change' : function(event, ui) {
            if ($selector.attr('disabled') != 'undefined') return;

            resetValues($node);
            recalcTotals();
        }
    });
}

/*
 * set product values on $node
 */
function setInventory($node, type, item)
{
    var types = {
        prod : {
            bg: '#EA6C6C',
            ws: '14'
        },
        svc  :{
            bg: '#6c97ea',
            ws: '25'
        }
    }
    $node.find('.remove').css('background', types[type].bg);
    $node.data('id', types[type].ws+'x'+item.id);
    $node.find('[name=pt-title]').val(item.name);
    $node.find('[name=pt-description]').val(item.description);
    $node.find('[name=pt-price]').val(item.listprice);
    $node.find('[name=pt-quantity]').val(1);
}

/*
 * additional field check
 * TODO replace alerts with PNotify and validation messages
 */
function checkField() {
    //
    // if (($('[name = paystatus]').val() == 0 || $('[name = paystatus]').val() == '') && $('[name = typepay]').val() != 'Курьеру') {
    //     alert('Поле "Оплачено" должно отличаться от 0');

    //     return false;
    // }
    var $convertForm = $('[name = ConvertLeadToSalesOrder]')
    //
    if ($('#products tbody').html() == '') {
        alert('Необходимо выбрать Товар');

        return false;
    }

    //
    if ($('[name = magazin]').val() == '-') {
        alert('Необходимо выбрать Магазин доставки');

        return false;
    }

    //
    if ($('[name = brend]').val() == '-') {
        alert('Необходимо выбрать Бренд');

        return false;
    }

    //
    if ($('.firstnamelead').val() == '') {
        //alert('Необходимо заполнить Имя');

        return false;
    }

    //
    if ($('[name = maptack]').val() == '-') {
        alert('Необходимо выбрать Где принят заказ');

        return false;
    }

    //
    //PINstudio begin @Denis #red-301
    if ($('[name = mobile]', $convertForm).val() == '') {
        alert('Необходимо заполнить Мобильный тел.');

        return false;
    }
    //PINstudio end

    //
    // if ($('[name = city]').attr('data-validation-engine') != 'false' && $('[name = city]').val() == '') {
    //     //console.log(4);
    //     return false;
    // }

    //
    if ($('[name = typepay]').val() == '-') {
        alert('Необходимо выбрать Тип оплаты');

        return false;
    }

    //
    $('#products tbody tr').each(function (){
        if ($(this).find('[name = pt-price]').val() == '' || $(this).find('[name = pt-price]').val() < 0) {
            //alert('Поле "Цена" у товара "' + $(this).find('[name = pt-title]').val() + '" должно быть заполнено');

            window.errCheckField = true;
        }
        if ($(this).find('[name = pt-quantity]').val() == '' || $(this).find('[name = pt-quantity]').val() == 0) {
            //alert('Поле "Количество" у товара "' + $(this).find('[name = pt-title]').val() + '" должно быть заполнено');

            window.errCheckField = true;
        }
    });
    if (window.errCheckField) { window.errCheckField = false; return false; }

    //


    return true;
}

/*
 * flatten products into a json
 */
function getJsonSelectedProducts() {
    var prodList = {};
    var c = 0;

    $('#products .product-for-so').each(function (i, x){
        var $prodNode = $(x);
        var prodId = $prodNode.data('id');
        if (prodId == '' || typeof prodId == 'undefined') return;

        var qty = Number($prodNode.find('[name = pt-quantity]').val());
        if (qty == 0) return;

        var product = {};
        product.productid = prodId;
        product.listprice = Number($prodNode.find('[name = pt-price]').val());
        product.quantity  = qty;
        product.comment   = $prodNode.find('[name = pt-description]').val();
        product.discount_percent = Number($prodNode.find('[name = pt-sale]').val());

        prodList[c] = product;
        c++;
    });

    $('[name = jsonSelectedProducts]').val(JSON.stringify(prodList));

    return true;
}

//PIN @DK #red-564
/*
 * wait until maps are loaded
 */
function wait4maps(cb, time)
{
    time = time || 300;
    if (typeof ymaps != 'undefined') {
        cb();
        return;
    } else {
        setTimeout(function() {
            wait4maps(cb, time);
        }, time);
    }
}

/*
 * yandex maps geolocation
 */
wait4maps(function(){
    ymaps.ready(function(){
        mapsMsg('Maps init');
        var _ = {};
        var yshp, shops;

        yshp = {"type": "FeatureCollection", "features": []};
        shp.map(function(x){
            var coords = x.cc.split(',').map(parseFloat);
            yshp.features.push({
                "type": "Feature",
                "geometry": {
                    "type": "Point",
                    "coordinates": coords
                }
            });
        });

        shops = ymaps.geoQuery(yshp);

        _.searchPending = false;
        _.geoResolve = false;
        _.tid   = -1;
        _.delay = 1000;
        _.prev  = false;
        _.done  = false;
        _.container = $('#convertView');
        _.$addr = _.container.find('input[name = lane]');
        _.$city = _.container.find('input[name = city]');
        _.$shop = _.container.find('[name = shopid]');

        geoLookup();

        //TODO force search
        _.$addr.on('keyup', function (e){
            var v = this.value;
            if(
                v.length < 10
                //|| e.keyCode != 13 //keyPress init
            ){
                return;
            }
            if (_.tid != -1) {
                clearTimeout(_.tid)
            }
            _.tid = setTimeout(function(){
                geoLookup();
            }, _.delay);
        });

        _.$city.on('keyup', function (e){
            var v = this.value;
            if(
                v.length < 5
                //|| _.searchPending
                //|| e.keyCode != 13 //keyPress init
            ){
                return;
            }
            //_.searchPending = true;
            if (_.tid != -1) {
                clearTimeout(_.tid)
            }
            _.tid = setTimeout(function(){
                geoLookup();
            }, _.delay);
        })

        _.$shop.on('change', function (e){
            if (_.geoResolve) return;
            if (_.$shop.data('auto')) notify();
            _.$shop.data('auto', false);
        })

        /*
         * globals ymaps
         */
        function geoLookup()
        {
            var tgtRgn  = '';
            var shopRgn = '';

            var v = getAddr();
            if ( !v || (_.prev == v) ) return;

            mapsMsg('lookup: ' + v);
            _.prev = v;
            _.done = false;
            var tgtCoords = false;
            ymaps.geocode(v, {results:1})
                .then(function(x){ return x.geoObjects.get(0); })
                .then(function(y){
                    tgtCoords = y.geometry.getCoordinates();
                    if (typeof ymaps.geocode != 'function') {
                        return mapsMsg('Error: geocode not loaded');
                    }
                    return ymaps.geocode(tgtCoords, {results:1})
                })
                .then(function(z){ return z.geoObjects.get(0); })
                .then(function(rgn){
                    if (typeof rgn == 'undefined') 
                        return mapsMsg('Невозможно определить район адреса');
                    if (typeof rgn.getLocalities != 'function') {
                        return mapsMsg('Error: getLocalities not loaded');
                    }
                    tgtRgn = rgn.getLocalities()[1];
                    return shops.getClosestTo(tgtCoords);
                })
                .then(function(nearest){return nearLookup(nearest)})
                .fail(function(x){mapsMsg(x)})
                //.always(e=>console.log('8',e));
        }

        /*
         * globals ymaps
         */
        function nearLookup(nearShop)
        {
            var near = nearShop.geometry.getCoordinates();
            //getHumanLength()
            //ymaps.route([p1,p2]).then(function(x){console.log(x.getLength())})
            return ymaps.geocode(
                near,
                {kind:'district',results:1}
            )
            .then(function(z){ return z.geoObjects.get(0); })
            .then(function(rgn){
                if (typeof rgn == 'undefined')
                    return mapsMsg(
                        'Невозможно определить район ближайшего магазина'
                    );
                if (typeof rgn.getLocalities != 'function') {
                    return mapsMsg('Error: getLocalities not loaded');
                }
                var shopRgn = rgn.getLocalities()[1];
                var matches = shp.filter(function(x){return x.cc == near.toString()});
                if (matches.length == 0) return;

                var goal = matches[0];
                _.done = true;
                var currentShopId = _.$shop.val();
                _.geoResolve = true;
                _.$shop.val(goal.id)
                    .trigger('liszt:updated')
                    .trigger('change');
                _.$shop.data('auto', true);
                _.geoResolve = false;
                if ( currentShopId != '-' && currentShopId != goal.id){
                    notify(
                        _.$shop.find('option[value='+currentShopId+']').text(),
                        _.$shop.find('option[value='+goal.id+']').text()
                    );
                }
            });
            //.fail()
            //.catch()
        }

        function getAddr()
        {
            var city = _.$city.val();
            var addr = _.$addr.val();
            //Sanitize
                //город
            addr = addr.replace(
                /(улица|пункт|область|москва|петербург)/ig,
                ''
            );
            //Conditions
            if ( _.$city.length == 0
                || (city == '')
                || (city.length < 5)
                //|| ((city.search('Петер') < 0) || (city.search('Москва') < 0))
                || (addr.length < 10)
                || (_.container.find('[name*=samov]').is(':checked') == true)
                || (_.container.find('[name=maptack]').val() == 'Magazin')
            ) {
                return false;
            }

            return city +','+ addr;
        }

        function notify(from,to)
        {
            var msg = '';
            if (from) {
                msg = 'Магазин выставлен автоматически<br/>'
                    + '<b>' + from +'</b> &gt; <b>' + to + '</b>';
            } else {
                msg = 'Смена автоматически выбранного магазина!';
            }
            bootbox.dialog(
                msg,
                [{label: 'Ок'}],
                {backdrop: 'static', header: 'Внимание!', classes: 'cover'}
            );

            $('.cover + .modal-backdrop').css({
                'z-index': '10100',
                background: 'hsla(0,0%,100%,.8)'
            });
        }

        function mapsMsg(e)
        {
            console.log(e)    
        }
    });
});//wait4maps
//PIN end
