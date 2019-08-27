$(document).ready(function(){

    //--------------------------------- common ---------------------------------

    var currency = (typeof global_currency !== 'undefined') ? global_currency : 'CHF';

    var digitRegExp = /\d+/;44

    function toCurrency ( numVal ) {
        return numVal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
    }

    $('.site-content').on('focusout', '.currency-input', function(){

        var currencyInputs = $('.currency-input');

        currencyInputs.each(function(){
            if( $(this).val() !== ''){
                if(digitRegExp.test( $(this).val())){
                    $(this).formatCurrency({
                        digitGroupSymbol: "'",
                        symbol: '',
                        negativeFormat: '-%n'
                    });
                }
            }
        });

        if (currency === 'CHF'){
            roundInputValue($(this));
        }
    });

    var datePickInput = $('#date-pick'),
        checkDate = $(datePickInput).data('check-date');

    datePickInput.datepicker({
        dateFormat: 'dd.mm.yy'
    });

    datePickInput.datepicker('setDate', checkDate);

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

    //--------------------------------- total sales ---------------------------------

    var totalSalesInputs =  $('.total-sales input');

    totalSalesInputs.on('focusout', function(){
        sumTotalSalesInputs();
    });

    sumTotalSalesInputs();

    function sumTotalSalesInputs(){
        var sum = 0,
            salesTotalCount =  $('.total-sales .total-count-val');

        totalSalesInputs.each(function () {
            $(this).formatCurrency({
                digitGroupSymbol: "'",
                symbol: '',
                negativeFormat: '-%n'
            });
            if (currency === 'CHF'){
                roundInputValue($(this));
            }
            sum += $(this).asNumber();
        });
        salesTotalCount.text(toCurrency(sum));
    }

    //--------------------------------- credit cards ---------------------------------

    var initialTerminalBlock = $('.credit-card-default-template .sales-report-credit-cards-terminal'),
        nextInitialTerminalBlock = initialTerminalBlock.clone(),
        creditCardsIdNumber = +initialTerminalBlock.attr('data-terminal-id') - 1,
        existingTerminalsInputsInfoArr = [],
        beckendTerminal = $('.terminals-container .sales-report-credit-cards-terminal').last().find('.credit-cards-input').first().data('terminal');

    $('.sales-report-credit-cards').on('focusout', '.report-input', function(){
        sumCreditCardsTerminalVal($(this));
    });

    if ($('.terminals-container').children().length > 1) {
        if ($('.terminals-container').children().last().find('.link-delete-terminal').hasClass('not-active')) {
            $('.terminals-container').children().last().find('.link-delete-terminal').removeClass('not-active')
        }
    }

    $('.terminal-content .credit-cards-terminal').each(function(){

        if ($(this).find('.report-input').length === 1) {
            if ($(this).find('.btn-add-line').hasClass('not-active')) {
                $(this).find('.btn-add-line').removeClass('not-active');
            }
            if (!$(this).find('.btn-remove-line').hasClass('not-active')) {
                $(this).find('.btn-remove-line').addClass('not-active');
            }
        } else if ($(this).find('.report-input').length === 3) {
            if (!$(this).find('.btn-add-line').hasClass('not-active')) {
                $(this).find('.btn-add-line').addClass('not-active');
            }
            if ($(this).find('.btn-remove-line').hasClass('not-active')) {
                $(this).find('.btn-remove-line').removeClass('not-active');
            }
        }
    });

    $('.sales-report-credit-cards-terminal').each(function(){
        findTerminalSum($(this).find('input'), $(this).find('.total-count-val'));
    });

    function findTerminalSum(inputs, output){
        var sumTotal = 0;

        inputs.each(function(){
            $(this).formatCurrency({
                digitGroupSymbol: "'",
                symbol: '',
                negativeFormat: '-%n'
            });
            if (currency === 'CHF'){
                roundInputValue($(this));
            }
            sumTotal += $(this).asNumber();
        });

        sumTotal = toCurrency(sumTotal);

        output.text(sumTotal);
    }


    function sumCreditCardsTerminalVal(that) {
        var sumTerminal = 0;

        that.parents('.sales-report-credit-cards-terminal').find('.report-input').each(function(){
            $(this).formatCurrency({
                digitGroupSymbol: "'",
                symbol: '',
                negativeFormat: '-%n'
            });
            if (currency === 'CHF'){
                roundInputValue($(this));
            }
            sumTerminal += $(this).asNumber();
        });

        that.parents('.sales-report-credit-cards-terminal').find('.total-count-val').text(toCurrency(sumTerminal));
    }

    addListenerToRemoveCreditCardsBtn('.terminals-container .btn-remove-line');
    addListenerToAddCreditCardsBtn('.btn-add-line');

    addListenerToDeleteTerminalLink();

    $('.link-add-terminal').click(function(){
        var nextTerminalBlock = nextInitialTerminalBlock.clone(),
            currentTerminalBlock = $('.terminals-container .sales-report-credit-cards-terminal').last();
        currentTerminalBlock.after(nextTerminalBlock);

        creditCardsIdNumber++;
        beckendTerminal++;

        nextTerminalBlock.attr('id', 'terminal-' + creditCardsIdNumber);

        nextTerminalBlock.attr('data-terminal-id', creditCardsIdNumber);

        nextTerminalBlock = $('#' + nextTerminalBlock.attr('id'));

        nextTerminalBlock.find('.sales-report-title-with-line').html('<p> Terminal ' + creditCardsIdNumber + '</p>');

        nextTerminalBlock.find('.credit-cards-input').each(function () {

            var inputField = $(this).find('input');
            var accountingPosition = $(this).data('accounting-position');
            var inputNumber = $(this).data('number');
            $(this).data('terminal', beckendTerminal);

            inputField.attr('id', 'credit-card-' + (beckendTerminal) + '-' + accountingPosition);
            inputField.attr('name', 'credit-cards[' + (beckendTerminal) + '][' + accountingPosition + '][]');
            inputField.val('');
        });

        var prevTerminalDeleteLink = $('.terminals-container #terminal-'+ (creditCardsIdNumber - 1) +' .link-delete-terminal'),
            currentTerminalDeleteLink = $('.terminals-container #terminal-'+ creditCardsIdNumber +' .link-delete-terminal');

        existingTerminalsInputsInfoArr.push({
            input: '#terminal-'+ creditCardsIdNumber +' .report-input',
            val: '#terminal-'+ creditCardsIdNumber +' .total-count-val'
        });

        addListenerToRemoveCreditCardsBtn('.terminals-container #terminal-'+ creditCardsIdNumber +' .btn-remove-line');
        addListenerToAddCreditCardsBtn('.terminals-container #terminal-'+ creditCardsIdNumber +' .btn-add-line');

        if(!prevTerminalDeleteLink.hasClass('not-active')){
            prevTerminalDeleteLink.addClass('not-active')
        }

        currentTerminalDeleteLink.removeClass('not-active');

        addListenerToDeleteTerminalLink(prevTerminalDeleteLink);
    });


    function addListenerToDeleteTerminalLink(){

        $('.terminals-container .link-delete-terminal').each(function(){
            $(this).click(function(){
                    $(this).parents('.sales-report-credit-cards-terminal').prev().find('.link-delete-terminal').removeClass('not-active');
                    $(this).parents('.sales-report-credit-cards-terminal').remove();

                    creditCardsIdNumber = $('.terminals-container').children().length;
                    beckendTerminal = $('.terminals-container .sales-report-credit-cards-terminal').last().find('.credit-cards-input').first().data('terminal');

                    if ($('.terminals-container').children().length === 1) {

                        if(!$('.terminals-container .link-delete-terminal').hasClass('not-active')) {
                            $('.terminals-container .link-delete-terminal').addClass('not-active');
                        }
                    }
                }
            );
        });
    }

    function addListenerToRemoveCreditCardsBtn(removeBtn){
        var removeBtnVar = $(removeBtn);

        removeBtnVar.click(function(){

            var elementToRemove = $(this).parents('.credit-cards-terminal').find('.report-input').parent().last();

            elementToRemove.remove();

            if ($(this).parents('.credit-cards-terminal').find('.report-input').length === 1) {
                if (!$(this).hasClass('not-active')) {
                    $(this).addClass('not-active');
                }
                if ($(this).siblings().hasClass('not-active')) {
                    $(this).siblings().removeClass('not-active');
                }
            } else if ($(this).parents('.credit-cards-terminal').find('.report-input').length === 2) {
                if ($(this).siblings().hasClass('not-active')) {
                    $(this).siblings().removeClass('not-active');
                }
            }

            $(this).parents('.credit-cards-terminal').find('input').each(function(){
                sumCreditCardsTerminalVal($(this));
            });

            fixButtonsPosition($(this));
        });
    }

    function addListenerToAddCreditCardsBtn(addBtn){

        $(addBtn).click(function(){
            var inputBlock = $(this).prev().parent().prev();
            var terminal = inputBlock.data('terminal');
            var accountingPosition = inputBlock.data('accounting-position');
            var inputNumber = inputBlock.data('number');

            var copiedBlock = inputBlock.clone();

            inputBlock.after(copiedBlock);
            copiedBlock.attr('data-number', inputNumber);

            var copiedBlockInput = $(copiedBlock.find('input')[0]);

            copiedBlockInput.attr('id', 'credit-card-' + terminal + '-' + accountingPosition);
            copiedBlockInput.attr('name', 'credit-cards[' + terminal + '][' + accountingPosition + '][]');
            copiedBlockInput.val('');

            if ($(this).parents('.credit-cards-terminal').find('.report-input').length === 3) {
                if (!$(this).hasClass('not-active')) {
                    $(this).addClass('not-active');
                }
                if ($(this).siblings().hasClass('not-active')) {
                    $(this).siblings().removeClass('not-active');
                }
            } else if (
                ($(this).parents('.credit-cards-terminal').find('.report-input').length === 2) ||
                ($(this).parents('.credit-cards-terminal').find('.report-input').length === 1)
            ) {
                if ($(this).siblings().hasClass('not-active')) {
                    $(this).siblings().removeClass('not-active');
                }
            }

            fixButtonsPosition($(this));
        });
    }

    function fixButtonsPosition(button){
        var currentTerminal = button.parents('.terminal-content'),
            terminalTotalCountEl = currentTerminal.find('.total-count-value-terminal-section');

        if(
            (currentTerminal.find('.btn-add-line.not-active').length === 0) &&
            (currentTerminal.find('.btn-remove-line.not-active').length !== currentTerminal.find('.btn-remove-line').length)
        ) {
            terminalTotalCountEl.css({'right': '62px'});
        } else {
            terminalTotalCountEl.css({'right': '38px'});
        }
    }

    $('.terminal-content').each(function(){
        var terminalTotalCountEl = $(this).find('.total-count-value-terminal-section');
        if(
            ($(this).find('.btn-add-line.not-active').length === 0) &&
            ($(this).find('.btn-remove-line.not-active').length !== $(this).find('.btn-remove-line').length)
        ) {
            terminalTotalCountEl.css({'right': '62px'});
        } else {
            terminalTotalCountEl.css({'right': '38px'});
        }
    });

    //--------------------------------- report popup calculate total---------------------------------

    var popupCalculate = $('.popup-section-calculate-total'),
         popupCalculateTotalCount = $('.popup-total-count .total-count-val');

    $('.sales-report-received-cash').on('click', '.report-cash-calculate-link', function(){
        if(popupCalculate.hasClass('not-active')){
            popupCalculate.removeClass('not-active');
            $('body').css('overflow', 'hidden');
        }
    });

    $('.popup-calculate-total-header .close-icon').on('click', function(){
        if(!popupCalculate.hasClass('not-active')){
            popupCalculate.addClass('not-active');
            $('body').css('overflow', 'auto');
        }
    });

    $('.enter-coins-amount-link').on('click', function(){
        $(this).toggleClass('active');
        $('.calculation-cash-coins').slideToggle(400);
        $('.calculation-cash-coins-container').slideToggle(400);
        $('.calculation-cash-coins-table input').val('');
        $('#coins').val('');
        popupCalcCashTotalCount();
    });

    function sendTotalValueFromPopupToMainForm(cashierInputId ,clearCashierInputVal) {
        $('#calculation-cash-popup-send-btn').on('click', function(){
            $('body').css('overflow', 'auto');
            if(currentCashierInputId === cashierInputId){
                $(cashierInputId).val(popupCalculateTotalCount.text());
                if (clearCashierInputVal) {
                    $('.popup-calculate-total input').val('');
                    popupCalculateTotalCount.text('0.00');
                    clearCashierInputVal = false;
                }
            }
            if(!popupCalculate.hasClass('not-active')){
                popupCalculate.addClass('not-active')
            }
        });
    }

    /*--------------------------------for CHF currency--------------------------------*/

    addListenersToCalcCashNumberInputs($('#CHF-1000-number'), $('#CHF-1000-result'), 1000);
    addListenersToCalcCashNumberInputs($('#CHF-200-number'), $('#CHF-200-result'), 200);
    addListenersToCalcCashNumberInputs($('#CHF-100-number'), $('#CHF-100-result'), 100);
    addListenersToCalcCashNumberInputs($('#CHF-50-number'), $('#CHF-50-result'), 50);
    addListenersToCalcCashNumberInputs($('#CHF-20-number'), $('#CHF-20-result'), 20);
    addListenersToCalcCashNumberInputs($('#CHF-10-number'), $('#CHF-10-result'), 10);

    addListenersToCalcCashNumberInputs($('.CHF-currency #coins'), $(''), 1);

    addListenersToCalcCashNumberInputs($('#CHF-5-number'), $('#CHF-5-result'), 5);
    addListenersToCalcCashNumberInputs($('#CHF-2-number'), $('#CHF-2-result'), 2);
    addListenersToCalcCashNumberInputs($('#CHF-1-number'), $('#CHF-1-result'), 1);
    addListenersToCalcCashNumberInputs($('#CHF-0-50-number'), $('#CHF-0-50-result'), 0.5);
    addListenersToCalcCashNumberInputs($('#CHF-0-20-number'), $('#CHF-0-20-result'), 0.2);
    addListenersToCalcCashNumberInputs($('#CHF-0-10-number'), $('#CHF-0-10-result'), 0.1);
    addListenersToCalcCashNumberInputs($('#CHF-0-05-number'), $('#CHF-0-05-result'), 0.05);

    /*----------------------------------------------------------------------------------*/

    /*--------------------------------for EUR currency--------------------------------*/

    addListenersToCalcCashNumberInputs($('#EUR-500-number'), $('#EUR-500-result'), 500);
    addListenersToCalcCashNumberInputs($('#EUR-200-number'), $('#EUR-200-result'), 200);
    addListenersToCalcCashNumberInputs($('#EUR-100-number'), $('#EUR-100-result'), 100);
    addListenersToCalcCashNumberInputs($('#EUR-50-number'), $('#EUR-50-result'), 50);
    addListenersToCalcCashNumberInputs($('#EUR-20-number'), $('#EUR-20-result'), 20);
    addListenersToCalcCashNumberInputs($('#EUR-10-number'), $('#EUR-10-result'), 10);
    addListenersToCalcCashNumberInputs($('#EUR-5-number'), $('#EUR-5-result'), 5);

    addListenersToCalcCashNumberInputs($('.EUR-currency #coins'), $(''), 1);


    addListenersToCalcCashNumberInputs($('#EUR-2-number'), $('#EUR-2-result'), 2);
    addListenersToCalcCashNumberInputs($('#EUR-1-number'), $('#EUR-1-result'), 1);
    addListenersToCalcCashNumberInputs($('#EUR-0-50-number'), $('#EUR-0-50-result'), 0.5);
    addListenersToCalcCashNumberInputs($('#EUR-0-20-number'), $('#EUR-0-20-result'), 0.2);
    addListenersToCalcCashNumberInputs($('#EUR-0-10-number'), $('#EUR-0-10-result'), 0.1);
    addListenersToCalcCashNumberInputs($('#EUR-0-05-number'), $('#EUR-0-05-result'), 0.05);
    addListenersToCalcCashNumberInputs($('#EUR-0-02-number'), $('#EUR-0-02-result'), 0.02);
    addListenersToCalcCashNumberInputs($('#EUR-0-01-number'), $('#EUR-0-01-result'), 0.01);

    /*----------------------------------------------------------------------------------*/

    function popupCalcCashTotalCount(){
        var sum = 0;

        $('.popup-calculate-total .result-input').each(function () {
            $(this).formatCurrency({
                digitGroupSymbol: "'",
                symbol: '',
                negativeFormat: '-%n'
            });
            if (currency === 'CHF'){
                roundInputValue($(this));
            }
            sum += $(this).asNumber();
        });

         popupCalculateTotalCount.text(toCurrency(sum));
    }

    function calcCurrencyValFromBillsNumber(billsNumberInput, currencyResultInput, multiplier){
        var resultVal = Number(billsNumberInput.val()) * multiplier;
        currencyResultInput.val(toCurrency(resultVal));
    }

    function addListenersToCalcCashNumberInputs(billsNumberInput, currencyResultInput, multiplier){
        billsNumberInput.on('focusout', function(){
            calcCurrencyValFromBillsNumber(billsNumberInput, currencyResultInput, multiplier);
            popupCalcCashTotalCount();
        });
    }

    //--------------------------------- add cashier ---------------------------------

    var linkAddCashier = $('.link-add-cashier .btn-add-line'),
        cashierId,
        cashiers = JSON.parse(JSON.stringify($('.sales-report-received-cash').data('cashiers'))),
        cashiersList = {},
        accountingPositionCashier = $('.sales-report-received-cash').data('accounting-position-cashier'),
        accountingPositionAmount = $('.sales-report-received-cash').data('accounting-position-amount');

    if ($('.sales-report-cashier').length === 0) {
        cashierId = 1;
    } else {
        var id = ($('.sales-report-cashier').last().find('input').attr('id')).match(/\d+/);
        cashierId = id[0];
    }


    /*----------------rendering------------------*/

    var cashiersSelect = '';

    function createCashiersSelect(){

        cashiersSelect = `<div class="col-12 sales-report-cashier">
                <select id="cashier-select-${cashierId}" name="cashier[${accountingPositionCashier}][]" class="fas-select report-select cashier-select">
                    ${generateCashiersOptions(false).options}
                </select>
                <div class="currency-input-wrapper">
                    <input id="cashier-input-${cashierId}" name="cash-amount[${accountingPositionAmount}][]" type="text" class="fas-input report-input currency-input">
                    <span class="currency">${currency}</span>
                </div>
                <span class="report-cash-calculate-link">Calculate Total</span>
                <div class="link-delete-cashier">
                    <div class="btn-remove-line">
                        <i class="fas fa-minus-circle"></i>
                        <span>Delete Cashier</span>
                    </div>
                </div>
            </div>`;
        cashierId++;

        return generateCashiersOptions().id;
    }

    function generateCashiersOptions(selectedOption){
        var selectedElId,
            cashiersSelectOptions,
            isItFirstElement = true;

        if (selectedOption) {
            cashiersSelectOptions += `<option value="${selectedOption.key}" selected>${selectedOption.value}</option>`;
            selectedElId = selectedOption.key;
            for (var key in cashiersList) {
                var value = cashiersList[key];
                    cashiersSelectOptions += `<option value="${key}">${value}</option>`;
            }
        } else {
            for (var key in cashiersList) {
                var value = cashiersList[key];
                if (isItFirstElement){
                    cashiersSelectOptions += `<option value="${key}" selected>${value}</option>`;
                    selectedElId = key;
                } else {
                    cashiersSelectOptions += `<option value="${key}">${value}</option>`;
                }
                isItFirstElement = false;
            }
        }

        return {options: cashiersSelectOptions, id: selectedElId};
    }

    function updateCashiersSelects() {
        $('.sales-report-received-cash select').each(function(){
            var selectedOption = {key: $(this).find('option:selected').val(), value:  $(this).find('option:selected').text()};
            $(this).empty();
            $(this).append(generateCashiersOptions(selectedOption).options);
        });
    }

    function updateSelectedCashiersList() {
        cashiersList = {...cashiers};
        $('.sales-report-received-cash select').each(function(){
            for (var key in cashiers) {
                if (key = $(this).find('option:selected').val()) {
                    delete cashiersList[key];
                }
            }
        });
    }

    /*----------------actions------------------*/

    updateSelectedCashiersList();
    updateCashiersSelects();

    //Add cashier
    linkAddCashier.on('click', function(){
        var selectedElId = createCashiersSelect();
        $('.sales-report-received-cash').append(cashiersSelect);
        updateSelectedCashiersList();
        updateCashiersSelects();
        if ((Object.keys(cashiersList).length === 0)&&(!$(this).hasClass('not-active'))) {
            $(this).addClass('not-active')
        }
    });

    //Delete cashier
    $('.sales-report-received-cash').on('click', '.link-delete-cashier', function() {
        for (var key in cashiers) {
            if ($(this).parent('.sales-report-cashier').find('select option:selected').val() === key) {
                cashiersList[key] = $(this).parent('.sales-report-cashier').find('select option:selected').text();
            }
        }
        $(this).parent('.sales-report-cashier').remove();
        updateSelectedCashiersList();
        updateCashiersSelects();
        if ((Object.keys(cashiersList).length > 0)&&(linkAddCashier.hasClass('not-active'))) {
            linkAddCashier.removeClass('not-active');
        }
        cashierId++;
    });

    //Change cashier
    $('.sales-report-received-cash').on('change', 'select', function(){
        updateSelectedCashiersList();
        updateCashiersSelects();
    });

    var currentCashierInputId;

    $('.sales-report-received-cash').on('click', '.report-cash-calculate-link', function(){
        var clearCashierInputVal = true,
            cashierInputId = '#' + $(this).parent('.sales-report-cashier').find('input').attr('id');
        currentCashierInputId = cashierInputId;
        sendTotalValueFromPopupToMainForm(cashierInputId, clearCashierInputVal);
    });

});
