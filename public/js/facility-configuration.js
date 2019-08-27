$(document).ready(function() {

    /*--------------------------------General--------------------------------*/

    var dataMaxConfigPosition = Number($('.default-configuration').data('config-position'));
    var digitsPattern = /^\d+$/;

    //Currency format settings

    var currencyFormat = {
        digitGroupSymbol: "'",
        symbol: '',
        negativeFormat: '-%n'
    };
    var currency = 'CHF';

    //Days in past

    $('input#facility_layout_daysInPast').on('change', function() {
        var value = $(this).val();
        value = (value < 0) ? value * -1 : value;
        value = parseInt(value);
        $(this).val(value);
    });

    //Currency update

    $('select#facility_layout_currency').on('change', function() {
        currency = $(this).find('option:selected').text();

        $('select.facility-configuration-select option.currency-select').each(function() {
            $(this).text(currency);
        });
    });

    //Estimated costs per day

    $('input.facility-configuration-input')
        .not('#facility_layout_daysInPast, .flex-param')
        .each(function() {
            $(this).formatCurrency(currencyFormat);
        });

    $('input.facility-configuration-input')
        .not('#facility_layout_daysInPast, .flex-param')
        .on('focusout', function() {
            var selectboxField = $(this).next();
            var value = $(this).val();

            if ('relative' == $(selectboxField).find('option:selected').val()) {
                if (value > 100) {
                    $(this).val(100);
                }
            }

            $(this).formatCurrency(currencyFormat);
            if(currency === 'CHF'){
                roundInputValue($(this));
            }
        });

    if (typeof(window.global_shift) !== 'undefined' && global_shift == 0) {
        $('.shifts-switch-block .facility-configuration-select option').removeAttr('selected');
        $('.shifts-switch-block .facility-configuration-select option:first').attr('selected', 'selected');
    }

    if (+$('.shifts-switch-block .facility-configuration-select').find('option:selected').val() === 0) {
        if ($('.shifts-switch-block').css('display') === 'block') {
            $('.shifts-switch-block').toggle();
            $('#enable-shifts-checkbox').prop('checked', false);
        }
    } else {
        if ($('.shifts-switch-block').css('display') === 'none') {
            $('.shifts-switch-block').toggle();
            $('#enable-shifts-checkbox').prop('checked', true);
        }
    }

    $('select.facility-configuration-select').on('change', function() {
        var value = $(this).find('option:selected').val();
        var inputField = $(this).prev();

        if ('relative' == value) {
            $(inputField).val('0.00');
        }
    });

    if ($('#facility_layout_daysInPast').val() == 0) {

        var reportSwitch =  $('.reporting-switch');

        reportSwitch.find('input').prop('checked', false);
        reportSwitch.siblings('div').slideToggle(200);
    }

    if ($('#default_facility_layout_shifts').find('option:selected').val() == 0) {

        var shiftsSwitch =  $('.shifts-switch');

        shiftsSwitch.find('input').prop('checked', false);
    }

    if ($('#facility_layout_params').val() == 0) {

        var interfaceSwitch =  $('.interface-switch');

        interfaceSwitch.find('input').prop('checked', false);
        interfaceSwitch.siblings('div').slideToggle(200);
    }

    if (!$('#enable-interface-checkbox').prop('checked')) {
        $('.interface-switch-block').slideToggle(200);
    }

    $('.facility-configuration-checkbox').click(function() {
        if ($(this).hasClass('interface-switch') && $(this).find('input').is(':checked')) {
            $('.interface-switch-block').slideToggle(200);
        } else if ($(this).hasClass('shifts-switch') && $(this).find('input').is(':checked')) {
            $('.shifts-switch-block').slideToggle(200);
        } else if ($(this).hasClass('reporting-switch') && $(this).find('input').is(':checked')) {
            $('.reporting-switch-block').slideToggle(200);
        }
    });

    function toCurrency ( numVal ) {
        return numVal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
    }

    function roundInputValue(input){
        var inputValue = input.val(),
            lastDigit = inputValue.slice(-1),
            twoLastDigits = (inputValue.slice(-2)),
            integer = Number(inputValue.slice(0, -3).replace(/[']/g, '')),
            roundedLastDigit = Math.round(lastDigit/5)*5,
            roundedTwoLastDigits = Math.round(twoLastDigits/10)*10,
            roundedInteger = integer + 1;
        if(String(roundedLastDigit).length === 1){
            inputValue = inputValue.slice(0, -1) + roundedLastDigit;
        } else if(String(roundedTwoLastDigits).length === 2) {
            inputValue = inputValue.slice(0, -2) + roundedTwoLastDigits;
        } else {
            inputValue = toCurrency(roundedInteger);
        }

        if(inputValue !== '0'){
            input.val(inputValue);
        }
    }

    /*--------------------------------Sales Category section--------------------------------*/

    var max_ap_id = $($('.flex-param')[0]).data('ap-id');

    //Max Accounting position Id
    $('.flex-param').each(function () {
        if ($(this).data('ap-id') > max_ap_id) {
            max_ap_id = $(this).data('ap-id');
        }
    });

    if (typeof(max_ap_id) == "undefined") {
        max_ap_id = 1;
    }

    $('.sales-category-header .btn-add-line').click(function() {

        var salesCategory = $('.facility-configuration-sales-category');

        if (salesCategory.find('thead').length === 0) {
            $('.sales-category-content').append($('.default-sales-category').clone().html());
        } else {

            dataMaxConfigPosition++;

            var newItem = $('.default-sales-category').find('tbody').clone();

            newItem.find('input').each(function(){
                var inputMethod = $(this).data('method'),
                    inputType = $(this).data('type');

                $(this).attr('name', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);
                $(this).attr('id', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);

                if ($(this).attr('type') === 'checkbox') {
                    $(this).siblings().attr('for', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`)
                }
            });

            salesCategory.find('tbody').append(newItem.html());
            salesCategory.find('tbody').sortable({
                opacity: 0.7,
                placeholder: 'sortable-placeholder',
                stop: function() {
                    removeDeleteBtnInSalesCategory();
                },
                containment: `.sales-category-table`
            });
        }
        removeDeleteBtnInSalesCategory();

    });

    function removeDeleteBtnInSalesCategory(){

        $('.facility-configuration-sales-category').find('tbody tr .btn-remove-line').each(function(){
           if($(this).hasClass('not-active')){
               $(this).removeClass('not-active');
           }
        });

        if (!$('.facility-configuration-sales-category').find('tbody tr:first .btn-remove-line').hasClass('not-active')) {
            $('.facility-configuration-sales-category').find('tbody tr:first .btn-remove-line').addClass('not-active')
        }
    }

    $('.sales-category-content').on('click', '.btn-remove-line', function(){
        $(this).parents('tr').remove();
    });

    $('.facility-configuration-sales-category').find('tbody').sortable({
        opacity: 0.7,
        placeholder: 'sortable-placeholder',
        stop: function() {
            removeDeleteBtnInSalesCategory();
        },
        containment: `.sales-category-table`
    });
    removeDeleteBtnInSalesCategory();

    /*--------------------------------Payment Methods section--------------------------------*/

    var defaultSections = {};

    $('.default-payment-method-row').each(function(){
        defaultSections[`${$(this).data('method')}`] = $(this).find('tbody tr');
    });

    var creditCards = {
            name: 'creditCards',
            link: '#creditCard-link',
            sectionLink: '#creditCard',
            removeLinkId: "#remove-creditCard",
            linkTag: '<li id="creditCard-link">' + $('.payment-methods-link .btn-add-line').data('translate-credit-cards') + '</li>',
            section: $('#creditCard').html()
        },
        acceptedVoucher = {
            name: 'acceptedVoucher',
            link: '#acceptedVoucher-link',
            sectionLink: '#acceptedVoucher',
            removeLinkId: '#remove-acceptedVoucher',
            linkTag: '<li id="acceptedVoucher-link">' + $('.payment-methods-link .btn-add-line').data('translate-accepted-voucher') + '</li>',
            section: $('#acceptedVoucher').html()
        },
        issuedVoucher = {
            name: 'issuedVoucher',
            link: '#issuedVoucher-link',
            sectionLink: '#issuedVoucher',
            removeLinkId: '#remove-issuedVoucher',
            linkTag: '<li id="issuedVoucher-link">' + $('.payment-methods-link .btn-add-line').data('translate-issued-voucher') + '</li>',
            section: $('#issuedVoucher').html()
        },
        bill = {
            name: 'bill',
            link: '#bill-link',
            sectionLink: '#bill',
            removeLinkId: '#remove-bill',
            linkTag: '<li id="bill-link">' + $('.payment-methods-link .btn-add-line').data('translate-bills') + '</li>',
            section: $('#bill').html()
        },
        expenses = {
            name: 'expenses',
            link: '#expenses-link',
            sectionLink: '#expenses',
            removeLinkId: '#remove-expenses',
            linkTag: '<li id="expenses-link">' + $('.payment-methods-link .btn-add-line').data('translate-expenses') + '</li>',
            section: $('#expenses').html()
        },
        tip = {
            name: 'tip',
            link: '#tip-link',
            sectionLink: '#tip',
            removeLinkId: '#remove-tip',
            linkTag: '<li id="tip-link">' + $('.payment-methods-link .btn-add-line').data('translate-tip') + '</li>',
            section: $('#tip').html()
        },
        cash = {
            name: 'cash',
            link: '#cash-link',
            sectionLink: '#cash',
            removeLinkId: '#remove-cash',
            linkTag: '<li id="cash-link">' + $('.payment-methods-link .btn-add-line').data('translate-cash') + '</li>',
            section: $('#cash').html()
        },
        cigarettes = {
            name: 'cigarettes',
            link: '#cigarettes-link',
            sectionLink: '#cigarettes',
            removeLinkId: '#remove-cigarettes',
            linkTag: '<li id="cigarettes-link">' + $('.payment-methods-link .btn-add-line').data('translate-cigarettes') + '</li>',
            section: $('#cigarettes').html()
        },
        paymentMethodsArr = [creditCards, acceptedVoucher, issuedVoucher, bill, expenses, tip, cash, cigarettes];

    paymentMethodsArr.forEach(
        item => {

            removeMethod(item);

            if (
                (item.linkTag.search('creditCard-link') != -1 && $('.facility-configuration #creditCard').length != 0) ||
                (item.linkTag.search('tip-link') != -1 && $('.facility-configuration #tip').length != 0) ||
                (item.linkTag.search('expenses-link') != -1 && $('.facility-configuration #expenses').length != 0) ||
                (item.linkTag.search('cigarettes-link') != -1 && $('.facility-configuration #cigarettes').length != 0) ||
                (item.linkTag.search('cash-link') != -1 && $('.facility-configuration #cash').length != 0) ||
                (item.linkTag.search('acceptedVoucher-link') != -1 && $('.facility-configuration #acceptedVoucher').length != 0) ||
                (item.linkTag.search('issuedVoucher-link') != -1 && $('.facility-configuration #issuedVoucher').length != 0) ||
                (item.linkTag.search('bill-link') != -1 && $('.facility-configuration #bill').length != 0)
            ) {
                return;
            }

            $('.payment-methods-submenu ul').append(item.linkTag);

            clickOnLinks(item);
        }
    );

    function clickOnLinks(item) {

        checkActiveStateOfTheLink();

        $(item.link).click(function(){

            ++max_ap_id;

            paymentMethodsArr.splice(paymentMethodsArr.indexOf(item), 1);

            $(item.link).remove();

            $('.payment-methods-lines').append(item.section);

            removeMethod(item);
        });
    }

    function removeMethod(item) {

        $(item.removeLinkId).click(function() {
            $(item.sectionLink).remove();

            $('.payment-methods-submenu ul').append(item.linkTag);

            paymentMethodsArr.push(item);

            clickOnLinks(item);

            if (paymentMethodsArr.length !== 0) {
                if ($('.payment-methods-link').hasClass('not-active-link')) {
                    $('.payment-methods-link').removeClass('not-active-link')
                }
            }
        });
    }

    $('.facility-configuration-payment-methods').on('click', '.payment-method-remove-line .btn-remove-line', function(){
        $(this).parents('.payment-method-header').parent().remove();
    });

    $('.facility-configuration-payment-methods').on('click', '.payment-method-dropdown-arrow', function () {
        $(this).toggleClass('active');
        $(this).parents('.payment-method-header').siblings().slideToggle(400);
    });

    $('.payment-methods-lines').sortable({
        opacity: 0.7,
        containment: '.payment-methods-content'
    });

    var mouseleave = false,
        btn_add_line = $('.payment-methods-link');

    checkActiveStateOfTheLink();

    btn_add_line.click(function () {

        checkActiveStateOfTheLink();

        $('.payment-methods-submenu').toggleClass('not-active');

        mouseleave = false;
    });

    function checkActiveStateOfTheLink() {

        if(!!btn_add_line) {
            var submenuItemsLength = $('.payment-methods-submenu ul').children().length;

            if (submenuItemsLength === 0) {
                if (!btn_add_line.hasClass('not-active')) {
                    btn_add_line.addClass('not-active');
                }

            } else {
                if (btn_add_line.hasClass('not-active')) {
                    btn_add_line.removeClass('not-active');
                }
            }
        }
    }

    btn_add_line.on('mouseleave', function(){
        mouseleave = true;
    });

    $(document).click(function() {
        if (mouseleave) {
            $('.payment-methods-submenu').addClass('not-active');
            mouseleave = false;
        };
    });

    $('.payment-methods-content').find('tbody').each(function(){

        var method = `.${$(this).parents('.ui-sortable-handle').attr('id')}-table`;

        $(this).sortable({
            opacity: 0.7,
            placeholder: 'sortable-placeholder',
            containment: method
        });
    });

    $('.payment-methods-content').on('click', '.btn-add-line', function(){
        var method = $(this).siblings().data('method');
        var newItem;

        dataMaxConfigPosition++;

        if (!method) {
            method = $(this).parents('.ui-sortable-handle').attr('id');
        }

        newItem = defaultSections[`${method}`].clone();
        newItem.find('input').each(function(){
            var inputMethod = $(this).data('method'),
                inputType = $(this).data('type');

            $(this).attr('name', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);
            $(this).attr('id', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);

            if ($(this).attr('type') === 'checkbox') {
                $(this).siblings().attr('for', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`)
            }
        });

        $(this).siblings().find('tbody').append(newItem);

        $(this).siblings().find('tbody').sortable({
            opacity: 0.7,
            placeholder: 'sortable-placeholder',
            containment: `.${method}-table`
        });
    });

    formatTipField();

    $('.questions-header .btn-add-line').click(function() {

        var questions = $('.facility-configuration-questions');

        if (questions.find('thead').length === 0) {
            $('.questions-content').append($('.default-questions-content').clone().html());
        } else {

            dataMaxConfigPosition++;

            var newItem = $('.default-questions-content').find('tbody').clone();

            newItem.find('input').each(function(){
                var inputMethod = $(this).data('method'),
                    inputType = $(this).data('type');

                $(this).attr('name', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);
                $(this).attr('id', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`);

                if ($(this).attr('type') === 'checkbox') {
                    $(this).siblings().attr('for', `${inputMethod}[${dataMaxConfigPosition}][${inputType}]`)
                }
            });

            questions.find('tbody').append(newItem.html());
            questions.find('tbody').sortable({
                opacity: 0.7,
                placeholder: 'sortable-placeholder',
                containment: `.questions-content`
            });
        }
    });

    $('.facility-configuration-questions').on('click', '.btn-remove-line', function(){
        $(this).parents('tr').remove();
    });

    $('input.flex-param.percentage').formatCurrency(currencyFormat);
    $('.facility-configuration-payment-methods').on('change', 'input.flex-param.percentage', function(){
        $(this).formatCurrency(currencyFormat);
    });

    /*----------------------------------- Misc. -----------------------------------*/

    $('.facility-configuration-payment-methods').on('click', '.btn-remove-line', function () {
        $(this).parents('tr').remove();
    });

    function formatTipField() {
        $('input.input-percentage').on('change', function() {
            value = $(this).val();

            if (value > 100) {
                $(this).val(100);
            }

            $(this).formatCurrency(currencyFormat);
        });
    };

    $('.questions-table tbody').sortable({
        opacity: 0.7,
        placeholder: 'sortable-placeholder',
        containment: '.questions-content'
    });

    /*-------------------------------------Test API Connecting------------------------------------------*/
    var overlay = $('.overlay'),
        params  = $('#facility_layout_params');

    $('.test-connection-link').click(function (e) {
        e.preventDefault();

        $.ajax({
            type: 'GET',
            url: $(this).data("url"),
            data: params.serialize(),
            dataType: 'JSON',
            success: function (data) {
                $(overlay).find('.popup-text p').text(data.message);
                overlay.fadeIn(200).removeClass('not-active');
            }
        });
    })

    $('.popup-footer .btn-submit').click(function () {
        overlay.fadeOut(200).addClass('not-active');
    });
});
