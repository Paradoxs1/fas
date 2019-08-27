$(document).ready(function() {

    //--------------------------------- common ---------------------------------

    var digitRegExp = /\d+/,
        datePickInput = $('#date-pick'),
        catalogNumber;

    function toCurrency(numVal) {
        return numVal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
    }

    $('.site-content').on('focusout', '.currency-input', function () {
        var currencyInputs = $('.currency-input');

        currencyInputs.each(function () {
            if ($(this).val() !== '') {
                if (digitRegExp.test($(this).val())) {
                    $(this).formatCurrency({
                        digitGroupSymbol: "'",
                        symbol: '',
                        negativeFormat: '-%n'
                    });
                }
            }
        });
    });

    datePickInput.datepicker({
        dateFormat: 'dd.mm.yy'
    });

    //--------------------------------- cash report ---------------------------------

    $('.reports-overview-title-cash-link').on('click', function(){
        $('.cash-report-content').slideToggle(500);
        $('.reports-overview-title-cash-link .fa-angle-down').toggleClass('active');
    });

    var cashItems = $('.cash-report-earnings .cash-items-value'),
        cashTotalCount = $('.cash-report-earnings .cash-count-val');

    function sumCash(items, totalValue) {
        var sum = 0;
        items.each(function(){
            sum += $(this).asNumber();
        });
        totalValue.text(toCurrency(sum));
    }

    sumCash(cashItems, cashTotalCount);

    $('.cash-report-credit-card').each(function(){
        var items = $(this).find('.cash-items-value'),
            totalValue = $(this).find('.cash-count-val');
        sumCash(items, totalValue);
    });

    //--------------------------------- cashiers reports ---------------------------------

    var expensesApproveCheck = $('.cashiers-table-expenses .approval-column .payment-approve-check'),
        expensesInputs = $('.payment-column input'),
        vouchersMainApproval = $('.cashiers-table-vouchers .main-approval .payment-approve-check'),
        buttonSubmit = $('.cashiers-table-footer .button-submit'),
        issuedValueSales, issuedValue, totalSales, differenceSales;

    if($('.main-approval .payment-approve.not-active').length > 0) {
        buttonSubmit.prop( "disabled", true ).addClass('not-allow');
    } else {
        buttonSubmit.removeClass('not-allow');
    }

    $('.action-table-item').on('click', function(){
        $(this).parents('tr').find('.payment-title-arrow .fa-angle-down').toggleClass('active');
        $(this).parents('tr').next().find('.table-collapse').slideToggle(500);
    });

    expensesInputs.on('keyup', function(){
        expensesInputsApproved();
        allowSubmit();
        checkPopupExpensesLabels()
    });

    function approvePayment(paymentApproveIcon){
        paymentApproveIcon.on('click', function (){
            if(paymentApproveIcon.hasClass('allow')){
                if(!paymentApproveIcon.hasClass('not-active')){
                    paymentApproveIcon.addClass('not-active');
                }
                if(paymentApproveIcon.siblings().hasClass('not-active')){
                    paymentApproveIcon.siblings().removeClass('not-active');
                }
                allowSubmit();
            }
        });
    }

    $('.vouchers-additional-approval .payment-approve-check').each(function(){
        $(this).on('click', function(){
            if(!$(this).hasClass('not-active')){
                $(this).addClass('not-active');
            }
            if($(this).siblings().hasClass('not-active')){
                $(this).siblings().removeClass('not-active');
            }

            if($('.vouchers-additional-approval .payment-approve.not-active').length === 0){
                if(!vouchersMainApproval.hasClass('not-active')){
                    vouchersMainApproval.addClass('not-active');
                }
                if(vouchersMainApproval.siblings().hasClass('not-active')){
                    vouchersMainApproval.siblings().removeClass('not-active');
                }
            }
            allowSubmit();
        });
    });

    function allowSubmit(){
        if($('.main-approval .payment-approve.not-active').length === 0){
            buttonSubmit.prop( "disabled", false );
            if(buttonSubmit.hasClass('not-allow')){
                buttonSubmit.removeClass('not-allow');
            }
        } else {
            buttonSubmit.prop( "disabled", true );
            if(!buttonSubmit.hasClass('not-allow')){
                buttonSubmit.addClass('not-allow');
            }
        }
    }

    $('.cashiers-table-bills .payment-approve-check').click(function () {
        $(this).removeClass('allow').removeClass('payment-approve-check').addClass('payment-approve');
        $(this).next().addClass('allow').addClass('payment-approve-check').removeClass('payment-approve');
        allowSubmit();
    });

    catalogNumber = $('.cashiers-table-expenses-collapse .catalog-number');

    if (catalogNumber.length >= 1) {
        catalogNumber.each(function (index, element) {
            $(element).parent().prev().append($(element).text());
        });
    }

    expensesInputs.each(function () {
        expensesInputsApproved();
    });

    function expensesInputsApproved() {
        expensesInputs.each(function () {
            if($(this).val() === ''){
                if(!$(this).hasClass('error-input')){
                    $(this).addClass('error-input');
                }
                if(!$(this).parents('.collapse-table-item').find('label').hasClass('error-name')){
                    $(this).parents('.collapse-table-item').find('label').addClass('error-name');
                }

            } else {
                if($(this).hasClass('error-input')){
                    $(this).removeClass('error-input');
                }
                if($(this).parents('.collapse-table-item').find('label').hasClass('error-name')){
                    $(this).parents('.collapse-table-item').find('label').removeClass('error-name');
                }
            }
        });

        if($('.payment-column input.error-input').length === 0){

            if(expensesApproveCheck.hasClass('not-allow')){
                expensesApproveCheck.removeClass('not-allow');
            }
            if(!expensesApproveCheck.hasClass('allow')){
                expensesApproveCheck.addClass('allow');
            }
            approvePayment(expensesApproveCheck);
        } else {
            if(expensesApproveCheck.hasClass('not-active')){
                expensesApproveCheck.removeClass('not-active');
            }
            if(!expensesApproveCheck.siblings().hasClass('not-active')){
                expensesApproveCheck.siblings().addClass('not-active');
            }
            if(!expensesApproveCheck.hasClass('not-allow')){
                expensesApproveCheck.addClass('not-allow');
            }
            if(expensesApproveCheck.hasClass('allow')){
                expensesApproveCheck.removeClass('allow');
            }
        }
    }

    function checkValueOnNaN(value) {
        return (isNaN(value)) ? 0 : value;
    }

    function addIssuedVouchers(issuedValue) {
        issuedValueSales = parseFloat($('.cashiers-table-sales').find('.included-issued-vouchers span').text().replace(/("|')/g, ""));
        totalSales = parseFloat($('.cashiers-table-sales').find('.total-all-column p').text().replace(/("|')/g, ""));
        differenceSales = parseFloat($('.cashiers-table-sales').find('.total-difference-column p').text().replace(/("|')/g, ""));

        issuedValueSales = checkValueOnNaN(issuedValueSales);
        differenceSales = checkValueOnNaN(differenceSales);
        differenceSales += -issuedValue;

        $('.cashiers-table-sales').find('.total-difference-column p').html(toCurrency(differenceSales) + ' ' + global_currency);
        $('.cashiers-table-sales').find('.included-issued-vouchers').addClass('active').find('span').html(toCurrency(issuedValue + issuedValueSales) + ' ' + global_currency);
        $('.cashiers-table-sales').find('.total-all-column p').addClass('error-name').html(toCurrency(totalSales + issuedValue) + ' ' + global_currency);

        if (differenceSales === 0) {
            $('.table-sales-total-values p').removeClass('error-name');
        }
    }

    if (global_issued_total_sales.length < 1) {
        $('.issued-vouchers-collapse .payment-approve-check').click(function () {
            issuedValue = parseFloat($(this).parents('.issued-vouchers-collapse').find('.cashier-column p').text().replace(/("|')/g, ""));
            addIssuedVouchers(issuedValue);
        });
    }

    if (global_approved.length >= 1 && global_issued_total_sales.length < 1) {
        $('.issued-vouchers-collapse .cashier-column').each(function (index, element) {
            issuedValue = parseFloat($(element).find('p').text().replace(/("|')/g, ""));
            addIssuedVouchers(checkValueOnNaN(issuedValue));
        });
    }

    //--------------------------------- approve sales popup ---------------------------------

    var submitBtn = $('.approve-sales-popup-footer .button-submit');

    $('.close-icon').on('click', function(){
        if (!$('.approve-sales').hasClass('not-active')) {
            $('.approve-sales').addClass('not-active');
        }
    });

    $('#approve-and-book').on('click', function(){
        if ($('.approve-sales').hasClass('not-active')) {
            $('.approve-sales').removeClass('not-active');
        }
    });

    $('#approve-sales-popup-checkbox').on('click', function(){
        if ($(this).is(":checked")) {
            submitBtn.attr('disabled', false);
            submitBtn.removeClass('not-allow');
        } else {
            submitBtn.attr('disabled', true);
            submitBtn.addClass('not-allow');
        }
    });

    function checkPopupExpensesLabels() {
        var popupExpensesInputs = [];
        var popupSalesExpenses = [];

        $('.cashiers-table-expenses-collapse input').each(function(){
            var input = {};
            input['id'] = $(this).data('position-id');
            input['name'] = $(this).val();
            input['el'] = $(this);
            popupExpensesInputs.push(input);
        });

        $('.approve-sales-payments-table .approve-sales-expenses').each(function(){
            var expenses = {};
            expenses['id'] = $(this).data('position-id');
            expenses['name'] = $(this).text();
            expenses['el'] = $(this);
            popupSalesExpenses.push(expenses);
        });

        popupSalesExpenses.map( expenses => {
            popupExpensesInputs.map( input => {
                if (expenses.id === input.id) {
                    expenses.el.text(`Expense No. ${input.name}`);
                }
            });
        });
    }

    //popup validation

    var missingIncome = $('#approve-sales-payments-missing-income'),
        textInput = $('#approve-sales-popup-text-input'),
        triggerSendForm = true;

    missingIncome.on('focusout', function(){
        if (!digitRegExp.test($(this).val())) {
            $(this).val('');
        }

        if ($(this).val() !== '') {
            $(this).removeClass('error-input');
            triggerSendForm = true;
        }

        if (textInput.val() === '') {
            textInput.addClass('error-input');
            triggerSendForm = false;
        }

        if (textInput.val() !== '') {
            textInput.removeClass('error-input');
            triggerSendForm = true;
        }

        if ($(this).val() === '' && textInput.val() !== '') {
            triggerSendForm = false;
            $(this).addClass('error-input');
        }

        if ($(this).val() === '' && textInput.val() === '') {
            triggerSendForm = true;
            $(this).removeClass('error-input');
            textInput.removeClass('error-input');
        }
    });

    textInput.on('focusout', function(){
        if ($(this).val() !== '') {
            $(this).removeClass('error-input');
            triggerSendForm = true;
        } else {
            $(this).addClass('error-input');
            triggerSendForm = false;
        }

        if (missingIncome.val() === '') {
            missingIncome.addClass('error-input');
            triggerSendForm = false;
        }

        if ($(this).val() === '' && missingIncome.val() === '') {
            missingIncome.removeClass('error-input');
            $(this).removeClass('error-input');
            triggerSendForm = true;
        }
    });

    $('#reports-overview-form').submit(function() {
        return triggerSendForm;
    });

    checkPopupExpensesLabels();
    allowSubmit();
});
