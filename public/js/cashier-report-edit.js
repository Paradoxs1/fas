$(document).ready(function(){

    var currency = (typeof global_currency !== 'undefined') ? global_currency : 'CHF',
        dataMaxReportPosition = +($('#sales-report-form').attr('data-max-report-position')),
        facilitySalesReport = $('.facility-sales-report'),
        digitRegExp = /\d+/;

    //--------------------------------- common ---------------------------------

    function showApprovedMessage(approveMessage){
        if(approveMessage.hasClass('not-active')){
            approveMessage.removeClass('not-active');
        }
    }

    function hideApprovedMessage(approveMessage){
        if(!approveMessage.hasClass('not-active')){
            approveMessage.addClass('not-active');
        }
    }

    function showNotApprovedMessage(notApprovedMessage){
        if(notApprovedMessage.hasClass('not-active')){
            notApprovedMessage.removeClass('not-active');
        }
    }

    function hideNotApprovedMessage(notApprovedMessage){
        if(!notApprovedMessage.hasClass('not-active')){
            notApprovedMessage.addClass('not-active');
        }
    }

    function addSuccessClassToInput(formInput){
        if(!formInput.hasClass('success-input')){
            formInput.addClass('success-input');
        }
    }

    function removeSuccessClassFromInput(formInput){
        if(formInput.hasClass('success-input')){
            formInput.removeClass('success-input');
        }
    }

    function addErrorClassToInput(formInput){
        if(!formInput.hasClass('error-input')){
            formInput.addClass('error-input');
        }
    }

    function removeErrorClassFromInput(formInput){
        if(formInput.hasClass('error-input')){
            formInput.removeClass('error-input');
        }
    }

    function addErrorClassToInputTitle(formInputTitle){
        if(!formInputTitle.hasClass('error-name')){
            formInputTitle.addClass('error-name');
        }
    }

    function removeErrorClassFromInputTitle(formInputTitle){
        if(formInputTitle.hasClass('error-name')){
            formInputTitle.removeClass('error-name');
        }
    }

    function showApprovedMessageAndHideNoApprovedMessage(approvedMessage, notApprovedMessage){
        showApprovedMessage(approvedMessage);
        hideNotApprovedMessage(notApprovedMessage);
    }

    function showNotApprovedMessageAndHideApprovedMessage(approvedMessage, notApprovedMessage){
        showNotApprovedMessage(notApprovedMessage);
        hideApprovedMessage(approvedMessage);
    }

    function addSuccessClassAndRemoveErrorClassFromInputs(formInput){
        addSuccessClassToInput(formInput);
        removeErrorClassFromInput(formInput);
    }

    function addErrorClassAndRemoveSuccessClassFromInputs(formInput){
        addErrorClassToInput(formInput);
        removeSuccessClassFromInput(formInput);
    }

    function approvedCase(formInput, notApprovedMessage, approvedMessage, formInputTitle) {
        showApprovedMessageAndHideNoApprovedMessage(approvedMessage, notApprovedMessage);
        addSuccessClassAndRemoveErrorClassFromInputs(formInput);
        removeErrorClassFromInputTitle(formInputTitle);
    }

    function notApprovedCase(formInput, notApprovedMessage, approvedMessage, formInputTitle) {
        showNotApprovedMessageAndHideApprovedMessage(approvedMessage, notApprovedMessage);
        addErrorClassAndRemoveSuccessClassFromInputs(formInput);
        addErrorClassToInputTitle(formInputTitle);
    }

    function addListenersToInput(formInput, notApprovedMessage, approvedMessage, formInputTitle) {
        formInput.on('focusout', function(){
            if(formInput.val() === ''){
                notApprovedCase(formInput, notApprovedMessage, approvedMessage, formInputTitle);
            } else {
                approvedCase(formInput, notApprovedMessage, approvedMessage, formInputTitle);
            }
            checkAllApprovalSectionsAndChangeSubmitBtn();
        });
    }

    facilitySalesReport.on('focusout', '.currency-input', function(){
        var currencyInputs = $('.currency-input');

        currencyInputs.each(function(){
            if( $(this).val() !== ''){
                if(digitRegExp.test( $(this).val())){
                    $(this).formatCurrency({
                        digitGroupSymbol: "'",
                        symbol: '',
                        negativeFormat: '-%n'
                    });
                } else {
                    $(this).val('');
                    addErrorClassToInput($(this));
                    showNotApprovedMessageAndHideApprovedMessage($(this).parents('.report-section').find('.approved-message'), $(this).parents('.report-section').find('.not-approved-message'));
                }
            }
        });

        if (currency === 'CHF') {
            roundInputsValues();
            roundTotalValues();
        }
    });

    function toCurrency ( numVal ) {
        return numVal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
    }

    //--------------------------------- report details ---------------------------------


    var notApprovedMessageReportDetails = $('.sales-report-details .not-approved-message'),
        approvedMessageReportDetails = $('.sales-report-details .approved-message'),
        datePickInput = $('#date-pick'),
        datePickTitle = $('#date-pick-title'),
        reportPeriodDays = (typeof global_days_in_past !== 'undefined') ? global_days_in_past * -1 : null,
        numberOfHourToShift = (typeof global_number_of_hours_to_shift !== 'undefined') ? global_number_of_hours_to_shift : 3,
        today = new Date(),
        currentHour = today.getHours(),
        maxDate = 0,
        datesToDisable = (typeof JSON.parse(global_dates_disabled) === 'object') ? JSON.parse(global_dates_disabled) : {},
        translations = (typeof global_translations !== 'undefined') ? JSON.parse(global_translations) : {},
        date = (typeof global_date !== 'undefined') ? global_date : null,
        overlay = $('.overlay');

    if (currentHour < numberOfHourToShift) {
        today.setDate(today.getDate() - 1);
        --reportPeriodDays;
        maxDate = -1;
    }

    var popup_header = $('.popup .popup-header h2'),
        popup_text   = $('.popup .popup-text'),
        popup_yes    = $('.popup .btn-submit'),
        popup_no     = $('.popup .btn-cancel'),

        popup_original_title = popup_header.html(),
        popup_original_text  = popup_text.html(),
        popup_original_yes   = popup_yes.html(),
        popup_original_no    = popup_no.html();

    $('.popup-buttons .btn-cancel').click(function(){
        setTimeout(function (){
            popup_header.html(popup_original_title);
            popup_text.html(popup_original_text);
            popup_yes.html(popup_original_yes);
            popup_no.html(popup_original_no);
        }, 200);

        datePickInput.datepicker('setDate', today);
    });

    datePickInput.datepicker({
        dateFormat: 'dd.mm.yy',
        minDate: reportPeriodDays,
        maxDate: maxDate,
        onSelect: function(date, i) {
            if (date !== i.lastVal) {

                if (datePickInput.data('report')) {
                    var url = window.location.href;
                    window.location = url.substring(0, url.lastIndexOf('/')) + '/' + date;
                    return;
                }

                if (overlay.hasClass('not-active')) {
                    overlay.fadeIn(200).removeClass('not-active');
                }

                popup_header.html(translations['report.unsaved.data.getting.lost']);
                popup_text.html(translations['report.load.new.report']);
                popup_yes.html(translations['report.button.yes']);
                popup_no.html(translations['report.button.cancel']);

                $('#date-changed').val(date);
            }
        },
        beforeShowDay: function(date) {
            var string = $.datepicker.formatDate('dd.mm.yy', date);

            if (Object.keys(datesToDisable).length !== 0) {
                for (var key in datesToDisable) {
                    var val = datesToDisable[key];
                    if (val.indexOf(string) == -1) {
                        return [true];
                    }
                    else {
                        return [false];
                    }
                }
            } else {
                return [true];
            }
        }
    });

    datePickInput.datepicker('setDate', date ? date : today);

    addListenersToInput(datePickInput, notApprovedMessageReportDetails, approvedMessageReportDetails, datePickTitle);

    //--------------------------------- total sales ---------------------------------

    var notApprovedMessageTotalSales = $('.total-sales .not-approved-message'),
        approvedMessageTotalSales = $('.total-sales .approved-message'),
        totalSalesInput =  $('#total-sales'),
        totalSalesTitle = $('#total-sales-title');

    addListenersToInput(totalSalesInput, notApprovedMessageTotalSales, approvedMessageTotalSales, totalSalesTitle);

    totalSalesInput.on('focusout', function(){
        updateDuesTipsValues(totalSalesInput.val());
    });

    //--------------------------------- credit cards ---------------------------------

    var initialTerminalBlock = $('.credit-card-default-template .sales-report-credit-cards-terminal'),
        nextInitialTerminalBlock = initialTerminalBlock.clone(),
        notApprovedMessageCreditCards = $('.sales-report-credit-cards .not-approved-message'),
        approvedMessageCreditCards = $('.sales-report-credit-cards .approved-message'),
        creditCardsIdNumber = +initialTerminalBlock.attr('data-terminal-id') - 1,
        existingTerminalsInputsInfoArr = [],
        totalCreditCardsValue = $('#total-cards-sales');

    $('.sales-report-credit-cards').on('focusout', '.report-input', function(){
        sumCreditCardsTerminalVal($(this));
        sumCreditCardsTotalVal();
        if (currency === 'CHF') {
            roundTotalValues();
        }
        checkCreditCardsInputsForError();
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

    function sumCreditCardsTerminalVal(that) {
        var sumTerminal = 0;

        that.parents('.sales-report-credit-cards-terminal').find('.report-input').each(function(){
            sumTerminal += $(this).asNumber();
        });
        that.parents('.sales-report-credit-cards-terminal').find('.total-count-val').text(toCurrency(sumTerminal));
    }

    function sumCreditCardsTotalVal() {
        var sumTotal = 0;

        $('.sales-report-credit-cards .report-input').each(function(){
            sumTotal += $(this).asNumber();
        });

        $('.sales-report-credit-cards .sales-report-section-footer .total-count-val').text(toCurrency(sumTotal));
    }

    function validationCreditCards(that) {
        if (that.val() === '') {
            addErrorClassToInput(that);
        } else {
            removeErrorClassFromInput(that);
        }

        if (that.parents('.credit-cards-terminal').find('.report-input.error-input').length !== 0) {
            addErrorClassToInputTitle(that.parents('.credit-cards-terminal').find('label'));
        } else {
            removeErrorClassFromInputTitle(that.parents('.credit-cards-terminal').find('label'));
        }
    }

    function checkCreditCardsInputsForError() {
        $('.sales-report-credit-cards input').each(function(){
            validationCreditCards($(this));
        });

        if ($('.sales-report-credit-cards input.error-input').length === 0) {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageCreditCards, notApprovedMessageCreditCards);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageCreditCards, notApprovedMessageCreditCards);
        }
    }

    addListenerToRemoveCreditCardsBtn('.terminals-container .btn-remove-line');
    addListenerToAddCreditCardsBtn('.btn-add-line');

    addListenerToDeleteTerminalLink();

    $('.link-add-terminal').click(function(){

        var nextTerminalBlock = nextInitialTerminalBlock.clone(),
        currentTerminalBlock = $('.terminals-container .sales-report-credit-cards-terminal').last();
        currentTerminalBlock.after(nextTerminalBlock);
        creditCardsIdNumber++;
        dataMaxReportPosition++;

        nextTerminalBlock.attr('id', 'terminal-' + creditCardsIdNumber);

        nextTerminalBlock = $('#' + nextTerminalBlock.attr('id'));

        nextTerminalBlock.find('.sales-report-title-with-line').html('<p> Terminal ' + creditCardsIdNumber + '</p>');


        nextTerminalBlock.find('.credit-cards-input').each(function () {
            $(this).attr('data-terminal', dataMaxReportPosition);

            var inputField = $(this).find('input');
            var accountingPosition = $(this).data('accounting-position');
            var inputNumber = $(this).data('number');

            inputField.attr('id', 'credit-card-' + (creditCardsIdNumber - 1) + '-' + accountingPosition + '-' + inputNumber);
            inputField.attr('name', 'credit-cards[' + (dataMaxReportPosition) + '][' + accountingPosition + '][' + inputNumber + ']');
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

        summariseItemCash(totalCreditCardsValue, summaryCardsVal);
        checkCreditCardsInputsForError();
        if (approvedMessageCreditCards.hasClass('not-active')) {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
        }
        summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
        checkSummaryCardsApprovement();

    });


    function addListenerToDeleteTerminalLink(){

        $('.terminals-container .link-delete-terminal').each(function(){
            $(this).click(function(){
                    $(this).parents('.sales-report-credit-cards-terminal').prev().find('.link-delete-terminal').removeClass('not-active');
                    $(this).parents('.sales-report-credit-cards-terminal').remove();
                    creditCardsIdNumber--;
                    summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
                    summariseItemCash(totalCreditCardsValue, summaryCardsVal);

                    if ($('.terminals-container').children().length === 1) {

                        if(!$('.terminals-container .link-delete-terminal').hasClass('not-active')) {
                            $('.terminals-container .link-delete-terminal').addClass('not-active');
                        }
                    }

                    sumCreditCardsTotalVal();
                    checkCreditCardsInputsForError();
                    checkSummaryCardsApprovement();
                    if (approvedMessageCreditCards.hasClass('not-active')) {
                        showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
                    } else {
                        showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
                    }
                    summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
                    summariseItemCash(totalCreditCardsValue, summaryCardsVal);
                }
            );
        });
    }

    function checkSummaryCardsApprovement(){
        approvedMessageCreditCards.hasClass('not-active') ?
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards, notApprovedMessageSummaryCards) :
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards, notApprovedMessageSummaryCards);
        totalSummarise();

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

            checkCreditCardsInputsForError();
            sumCreditCardsTotalVal();
            if (approvedMessageCreditCards.hasClass('not-active')) {
                showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            } else {
                showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            }

            summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
            summariseItemCash(totalCreditCardsValue, summaryCardsVal);
            fixButtonsPosition($(this));
        });
    }

    function addListenerToAddCreditCardsBtn(addBtn){

        $(addBtn).click(function(){
            var inputBlock = $(this).prev().parent().prev();
            var terminal = inputBlock.data('terminal');
            var accountingPosition = inputBlock.data('accounting-position');
            var inputNumber = inputBlock.data('number');

            ++inputNumber;
            ++dataMaxReportPosition;

            var copiedBlock = inputBlock.clone();

            inputBlock.after(copiedBlock);
            copiedBlock.attr('data-number', inputNumber);


            var copiedBlockInput = $(copiedBlock.find('input')[0]);

            copiedBlockInput.attr('id', 'credit-card-' + terminal + '-' + accountingPosition + '-' + inputNumber);
            copiedBlockInput.attr('name', 'credit-cards[' + terminal + '][' + accountingPosition + '][' + inputNumber + ']');
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

            summariseItemCash(totalCreditCardsValue, summaryCardsVal);
            checkCreditCardsInputsForError();
            if (approvedMessageCreditCards.hasClass('not-active')) {
                showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            } else {
                showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            }

            summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
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
            terminalTotalCountEl.css({'right': '58px'});
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

    //--------------------------------- vouchers ---------------------------------

    var vouchersSection = $('.sales-report-voucher'),
        acceptedVoucherIdNumber = 0,
        issuedVoucherIdNumber = 0,
        acceptedVouchersContent = $('.accepted-vouchers-section .vouchers-section-content'),
        issuedVouchersContent = $('.issued-vouchers-section .vouchers-section-content'),
        approvedMessageVouchers = $('.sales-report-voucher .approved-message'),
        notApprovedMessageVouchers = $('.sales-report-voucher .not-approved-message'),
        acceptedVoucherNumberTitle = $('#accepted-vouchers-number-title'),
        acceptedVoucherAmountTitle = $('#accepted-vouchers-amount-title'),
        issuedVoucherNumberTitle = $('#issued-vouchers-number-title'),
        issuedVoucherAmountTitle = $('#issued-vouchers-amount-title');

    $('#add-accepted-voucher').on('click', function(){

        acceptedVoucherIdNumber++;

        acceptedVouchersContent.append('' +
            '    <div class="new-voucher">\n' +
            '        <input type="text" name="accepted-vouchers[' + acceptedVoucherIdNumber + '][number]" id="accepted-vouchers-number-line-'+ acceptedVoucherIdNumber +'" class="fas-input report-input accepted-vouchers-number-input">\n' +
            '        <div class="currency-input-wrapper">\n' +
            '           <input type="text" name="accepted-vouchers[' + acceptedVoucherIdNumber + '][amount]" id="accepted-vouchers-amount-line-'+ acceptedVoucherIdNumber +'" class="fas-input report-input accepted-vouchers-amount-input currency-input">\n' +
            '           <span class="currency">' + currency + '</span>\n' +
            '        </div>\n' +
            '        <span class="btn-remove-line">\n' +
            '            <i class="fas fa-minus-circle"></i>\n' +
            '        </span>\n' +
            '    </div>');

        var labelsOfAcceptedVouchers = $('.accepted-vouchers-section .vouchers-section-labels');

        if(labelsOfAcceptedVouchers.hasClass('not-active')){
            labelsOfAcceptedVouchers.removeClass('not-active');
        }

        checkAcceptedVouchersInputsForApprove();
        summariseVouchersCash();
        summariseItemApprovement(approvedMessageVouchers, approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher);
    });

    $('#add-issued-voucher').on('click', function(){

        issuedVoucherIdNumber++;

        issuedVouchersContent.append('' +
            '    <div class="new-voucher">\n' +
            '        <input type="text" name="issued-vouchers[' + issuedVoucherIdNumber + '][number] id="issued-vouchers-number-line-'+ issuedVoucherIdNumber +'" class="fas-input report-input issued-vouchers-number-input">\n' +
            '        <div class="currency-input-wrapper">\n' +
            '           <input type="text" name="issued-vouchers[' + issuedVoucherIdNumber + '][amount] id="issued-vouchers-amount-line-'+ issuedVoucherIdNumber +'" class="fas-input report-input issued-vouchers-amount-input currency-input">\n' +
            '           <span class="currency">' + currency + '</span>\n' +
            '        </div>\n' +
            '        <span class="btn-remove-line">\n' +
            '            <i class="fas fa-minus-circle"></i>\n' +
            '        </span>\n' +
            '    </div>');

        var labelsOfIssuedVouchers = $('.issued-vouchers-section .vouchers-section-labels');

        if(labelsOfIssuedVouchers.hasClass('not-active')){
            labelsOfIssuedVouchers.removeClass('not-active')
        }

        checkIssuedVouchersInputsForApprove();
        summariseVouchersCash();
        summariseItemApprovement(approvedMessageVouchers, approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher);
    });


    $('.sales-report-voucher .vouchers-section-content').on('click', '.btn-remove-line', function(){

        if(($(this).parents('.vouchers-section-content').children().length - 1) < 1){
            if(!$(this).parent().parent().parent().find('.vouchers-section-labels').hasClass('not-active')){
                $(this).parent().parent().parent().find('.vouchers-section-labels').addClass('not-active');
            }
        }

        var isAcceptedVouchersSection = $(this).parents('.vouchers-section-content').parent().attr('class') === 'col-12 accepted-vouchers-section';

        $(this).parent().remove();

        if (isAcceptedVouchersSection) {
            checkAcceptedVouchersInputsForApprove();
        } else {
            checkIssuedVouchersInputsForApprove();
        }

        sumAcceptedVouchersAmountInputs();
        sumIssuedVouchersAmountInputs();

        checkAcceptedVoucherNumberInputsForErrorNames();
        checkAcceptedVoucherAmountInputsForErrorNames();
        checkIssuedVoucherNumberInputsForErrorNames();
        checkIssuedVoucherAmountInputsForErrorNames();

        summariseVouchersCash();
        summariseItemApprovement(approvedMessageVouchers, approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher);
    });

    vouchersSection.on('focusout', 'input', function () {
        if($(this).val() === ''){
            addErrorClassAndRemoveSuccessClassFromInputs($(this));
        } else {
            addSuccessClassAndRemoveErrorClassFromInputs($(this));
        }

        checkVoucherInputsNamesForError($(this));

        if($(this).parents('.vouchers-section-content').parent().attr('class') === 'col-12 accepted-vouchers-section'){
            sumAcceptedVouchersAmountInputs();
            if (currency === 'CHF') {
                roundTotalValues();
            }
            checkAcceptedVouchersInputsForApprove();
        } else {
            sumIssuedVouchersAmountInputs();
            if (currency === 'CHF') {
                roundTotalValues();
            }
            checkIssuedVouchersInputsForApprove();
        }
    });

    function checkVoucherInputsNamesForError(that){
        if(that.hasClass('accepted-vouchers-number-input')){
            checkAcceptedVoucherNumberInputsForErrorNames();
        } else if(that.hasClass('accepted-vouchers-amount-input')){
            checkAcceptedVoucherAmountInputsForErrorNames();
        } else if(that.hasClass('issued-vouchers-number-input')){
            checkIssuedVoucherNumberInputsForErrorNames();
        } else if(that.hasClass('issued-vouchers-amount-input')){
            checkIssuedVoucherAmountInputsForErrorNames();
        }
    }

    function sumAcceptedVouchersAmountInputs() {
        var sum = 0;

        $('.accepted-vouchers-amount-input').each(function () {
            sum += $(this).asNumber();
        });

        $('.voucher-accepted-total-count .total-count-val').text(toCurrency(sum));
    }

    function sumIssuedVouchersAmountInputs(){
        var sum = 0;

        $('.issued-vouchers-amount-input').each(function(){
            sum += $(this).asNumber();
        });

        $('.voucher-issued-total-count .total-count-val').text(toCurrency(sum));
    }

    function checkAcceptedVouchersInputsForApprove(){

        var approvedAcceptedVouchers = $('.accepted-vouchers-section').next().find('.approved-message'),
            notApprovedAcceptedVouchers = $('.accepted-vouchers-section').next().find('.not-approved-message');

        if($('.accepted-vouchers-section input.success-input').length === $('.accepted-vouchers-section input').length){
            showApprovedMessageAndHideNoApprovedMessage(approvedAcceptedVouchers, notApprovedAcceptedVouchers);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(approvedAcceptedVouchers, notApprovedAcceptedVouchers);
        }
    };

    function checkIssuedVouchersInputsForApprove(){
        var approvedIssuedVouchers = $('.issued-vouchers-section').next().find('.approved-message'),
            notApprovedIssuedVouchers = $('.issued-vouchers-section').next().find('.not-approved-message');

        if($('.issued-vouchers-section input.success-input').length === $('.issued-vouchers-section input').length){
            showApprovedMessageAndHideNoApprovedMessage(approvedIssuedVouchers, notApprovedIssuedVouchers);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(approvedIssuedVouchers, notApprovedIssuedVouchers);
        }
    };

    function checkAcceptedVoucherNumberInputsForErrorNames() {

        var arrWithAcceptedNumberErrorInputs = [];

        $('.accepted-vouchers-number-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithAcceptedNumberErrorInputs.push($(this));
            }
        });
        errorVouchersNamesCase(arrWithAcceptedNumberErrorInputs, acceptedVoucherNumberTitle);
    }

    function checkAcceptedVoucherAmountInputsForErrorNames() {

        var arrWithAcceptedAmountErrorInputs = [];

        $('.accepted-vouchers-amount-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithAcceptedAmountErrorInputs.push($(this));
            }
        });
        errorVouchersNamesCase(arrWithAcceptedAmountErrorInputs, acceptedVoucherAmountTitle);
    }

    function checkIssuedVoucherNumberInputsForErrorNames() {

        var arrWithIssuedNumberErrorInputs = [];

        $('.issued-vouchers-number-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithIssuedNumberErrorInputs.push($(this));
            }
        });
        errorVouchersNamesCase(arrWithIssuedNumberErrorInputs, issuedVoucherNumberTitle);
    }

    function checkIssuedVoucherAmountInputsForErrorNames() {

        var arrWithIssuedAmountErrorInputs = [];

        $('.issued-vouchers-amount-input').each(function(){
            if(! $(this).hasClass('success-input')){
                arrWithIssuedAmountErrorInputs.push($(this));
            }
        });
        errorVouchersNamesCase(arrWithIssuedAmountErrorInputs, issuedVoucherAmountTitle);
    }

    function errorVouchersNamesCase(errorInputsArr, title){
        if(errorInputsArr.length === 0){
            removeErrorClassFromInputTitle(title);
        } else {
            addErrorClassToInputTitle(title);
        }
    }

    if (+$('.accepted-vouchers-section').next().find('.total-count-val').text() === 0) {
        $('.accepted-vouchers-section').next().find('.total-count-val').text('0.00');
    }

    if (+$('.issued-vouchers-section').next().find('.total-count-val').text() === 0) {
        $('.issued-vouchers-section').next().find('.total-count-val').text('0.00');
    }

    //---------------------------------expenses---------------------------------

    var expensesSection = $('.sales-report-expenses'),
        expensesIdNumber = 0,
        expensesContent = $('.expenses-section-content'),
        approvedMessageExpenses = $('.sales-report-expenses .approved-message'),
        notApprovedMessageExpenses = $('.sales-report-expenses .not-approved-message'),
        expensesNameTitle = $('#expenses-name-title'),
        expensesAmountTitle = $('#expenses-amount-title'),
        totalExpensesValue = $('#total-expenses');

    $('#add-expenses').on('click', function(){

        expensesIdNumber++;
        dataMaxReportPosition++;

        expensesContent.append('' +
            '<div class="new-expenses">\n' +
            '    <input name="expenses[' + dataMaxReportPosition + '][name]" type="text" id="expenses-name-line-'+ expensesIdNumber +'" class="fas-input report-input expenses-name-input">\n' +
            '    <div class="currency-input-wrapper">\n' +
            '        <input name="expenses[' + dataMaxReportPosition + '][amount]" type="text" id="expenses-amount-line-'+ expensesIdNumber +'" class="fas-input report-input expenses-amount-input currency-input">\n' +
            '        <span class="currency">' + currency + '</span>\n' +
            '    </div>\n' +
            '    <span class="btn-remove-line">\n' +
            '        <i class="fas fa-minus-circle"></i>\n' +
            '    </span>\n' +
            '</div>');

        var labelsOfExpenses = $('.expenses-section-labels');

        if(labelsOfExpenses.hasClass('not-active')){
            labelsOfExpenses.removeClass('not-active');
        }

        checkExpensesInputsForApprove();
        summariseItemApprovement(approvedMessageExpenses, approvedMessageSummaryExpenses, notApprovedMessageSummaryExpenses);
        summariseItemCash(totalExpensesValue, summaryExpensesVal);
    });

    $('.sales-report-expenses .expenses-section-content').on('click', '.btn-remove-line', function(){

        if(($(this).parents('.expenses-section-content').children().length - 1) < 1){
            if(!$(this).parent().parent().parent().find('.expenses-section-labels').hasClass('not-active')){
                $(this).parent().parent().parent().find('.expenses-section-labels').addClass('not-active');
            }
        }

        $(this).parent().remove();

        sumExpensesInputs();
        checkExpensesInputsForApprove();

        checkExpensesNameInputsForErrorNames();
        checkExpensesAmountInputsForErrorNames();

        summariseItemApprovement(approvedMessageExpenses, approvedMessageSummaryExpenses, notApprovedMessageSummaryExpenses);
        summariseItemCash(totalExpensesValue, summaryExpensesVal);
    });

    expensesSection.on('focusout', 'input', function () {

        var that = $(this);

        sumExpensesInputs();

        if($(this).val() === ''){
            addErrorClassAndRemoveSuccessClassFromInputs($(this));
        } else {
            addSuccessClassAndRemoveErrorClassFromInputs($(this));
        }

        checkExpensesInputsForApprove();
        checkExpensesInputsNamesForError(that);
    });

    function sumExpensesInputs() {
        var sum = 0;

        $('.expenses-amount-input').each(function () {
            sum += $(this).asNumber();
        });

        $('.expenses-total-count .total-count-val').text(toCurrency(sum));
    }

    function checkExpensesInputsForApprove(){

        var arrWithErrorInputs = [];

        $('.sales-report-expenses input').each(function(){
            if(! $(this).hasClass('success-input')){
                arrWithErrorInputs.push($(this));
            }
        });

        if(arrWithErrorInputs.length === 0){
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageExpenses, notApprovedMessageExpenses);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageExpenses, notApprovedMessageExpenses);
        }
    }

    function checkExpensesNameInputsForErrorNames() {

        var arrWithNameErrorInputs = [];

        $('.expenses-name-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithNameErrorInputs.push($(this));
            }
        });
        errorExpensesNamesCase(arrWithNameErrorInputs, expensesNameTitle);
    }

    function checkExpensesAmountInputsForErrorNames() {

        var arrWithAmountErrorInputs = [];

        $('.expenses-amount-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithAmountErrorInputs.push($(this));
            }
        });
        errorExpensesNamesCase(arrWithAmountErrorInputs, expensesAmountTitle);
    }

    function errorExpensesNamesCase(errorInputsArr, title){
        if(errorInputsArr.length === 0){
            removeErrorClassFromInputTitle(title);
        } else {
            addErrorClassToInputTitle(title);
        }
    }

    function checkExpensesInputsNamesForError(that){
        if(that.hasClass('expenses-name-input')){
            checkExpensesNameInputsForErrorNames();
        } else if(that.hasClass('expenses-amount-input')){
            checkExpensesAmountInputsForErrorNames();
        }
    }

    if (+$('.sales-report-expenses .total-count-val').text() === 0) {
        $('.sales-report-expenses .total-count-val').text('0.00');
    }

    //---------------------------------bills---------------------------------

    var billsSection = $('.sales-report-bills'),
        billsIdNumber = 0,
        billsContent = $('.bills-section-content'),
        approvedMessageBills = $('.sales-report-bills .approved-message'),
        notApprovedMessageBills = $('.sales-report-bills .not-approved-message'),
        billsNameTitle = $('#bills-name-title'),
        billsAmountTitle = $('#bills-amount-title'),
        billsTipTitle = $('#bills-tip-title'),
        totalBillsAmountValue = $('#total-bills-amount'),
        totalBillsTipValue = $('#total-bills-tip'),
        billSelectOptionsData = $('#bills-options-data').html();

    $('#add-bills').on('click', function(){

        billsIdNumber++;
        dataMaxReportPosition++;

        billsContent.append('' +
            '<div class="new-bills">\n' +
            '    <input name="bills['+ dataMaxReportPosition +'][receiver]" type="text" id="bills-name-line-'+ billsIdNumber +'" class="fas-input report-input bills-name-input">\n' +
            '    <div class="currency-input-wrapper">\n' +
            '        <input name="bills['+ dataMaxReportPosition +'][amount]" type="text" id="bills-amount-line-'+ billsIdNumber +'" class="fas-input report-input bills-amount-input currency-input">\n' +
            '        <span class="currency">' + currency + '</span>\n' +
            '    </div>\n' +
            '    <div class="currency-input-wrapper">\n' +
            '        <input name="bills['+ dataMaxReportPosition +'][tip]" type="text" id="bills-tip-line-'+ billsIdNumber +'" class="fas-input report-input bills-tip-input currency-input">\n' +
            '        <span class="currency">' + currency + '</span>\n' +
            '    </div>\n' +
            '    <select name="bills['+ dataMaxReportPosition +'][name]" id="bills-select-line-'+ billsIdNumber +'" class="fas-select report-select bills-select">\n' +
            billSelectOptionsData +
            '    </select>\n' +
            '    <span class="btn-remove-line">\n' +
            '        <i class="fas fa-minus-circle"></i>\n' +
            '    </span>\n' +
            '</div>');

        var labelsOfbills = $('.bills-section-labels');

        if(labelsOfbills.hasClass('not-active')){
            labelsOfbills.removeClass('not-active');
        }

        checkBillsInputsForApprove();
        summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
    });

    $('.sales-report-bills .bills-section-content').on('click', '.btn-remove-line', function(){

        if(($(this).parents('.bills-section-content').children().length - 1) < 1){
            if(!$(this).parent().parent().parent().find('.bills-section-labels').hasClass('not-active')){
                $(this).parent().parent().parent().find('.bills-section-labels').addClass('not-active');
            }
        }

        $(this).parent().remove();

        sumBillsAmountInputs();
        sumBillsTipInputs();
        checkBillsInputsForApprove();
        checkBillsNameInputsForErrorNames();
        checkBillsAmountInputsForErrorNames();
        checkBillsTipInputsForErrorNames();
        summaryBillsVal.text(toCurrency(totalBillsAmountValue.asNumber() * (-1)));
        summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
    });

    billsSection.on('focusout', 'input', function () {

        var that = $(this);

        sumBillsAmountInputs();
        sumBillsTipInputs();

        if($(this).val() === ''){
            addErrorClassAndRemoveSuccessClassFromInputs($(this));
        } else {
            addSuccessClassAndRemoveErrorClassFromInputs($(this));
        }

        checkBillsInputsForApprove();
        checkBillsInputsNamesForError(that);
        summaryBillsVal.text(toCurrency(totalBillsAmountValue.asNumber() * (-1)));

        if (approvedMessageBills.hasClass('not-active')) {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryBills, notApprovedMessageSummaryBills );
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryBills, notApprovedMessageSummaryBills);
        }

        summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
    });

    function sumBillsAmountInputs() {
        var sum = 0;

        $('.bills-amount-input').each(function () {
            sum += $(this).asNumber();
        });

        $('#bills-count-amount .total-count-val').text(toCurrency(sum));
    }

    function sumBillsTipInputs() {
        var sum = 0;

        $('.bills-tip-input').each(function () {
            sum += $(this).asNumber();
        });

        $('#bills-count-tip .total-count-val').text(toCurrency(sum));
        if (currency === 'CHF') {
            roundTotalValues();
        }
        $('#dues-bill-tip').text('-' + $('#bills-count-tip .total-count-val').text());
    }

    function checkBillsInputsForApprove(){

        var arrWithErrorInputs = [];

        $('.sales-report-bills input').each(function(){
            if(! $(this).hasClass('success-input')){
                arrWithErrorInputs.push($(this));
            }
        });

        if(arrWithErrorInputs.length === 0 && !negativeBillsTips($('.sales-report-bills .bills-tip-input'))){
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageBills, notApprovedMessageBills);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageBills, notApprovedMessageBills);
        }
    }

    function checkBillsNameInputsForErrorNames() {

        var arrWithNameErrorInputs = [];

        $('.bills-name-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithNameErrorInputs.push($(this));
            }
        });
        errorbillsNamesCase(arrWithNameErrorInputs, billsNameTitle);
    }

    function checkBillsAmountInputsForErrorNames() {

        var arrWithAmountErrorInputs = [];

        $('.bills-amount-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithAmountErrorInputs.push($(this));
            }
        });
        errorbillsNamesCase(arrWithAmountErrorInputs, billsAmountTitle);
    }

    function checkBillsTipInputsForErrorNames() {

        var arrWithAmountErrorInputs = [];

        $('.bills-tip-input').each(function () {
            if (!$(this).hasClass('success-input')) {
                arrWithAmountErrorInputs.push($(this));
            }
        });
        errorbillsNamesCase(arrWithAmountErrorInputs, billsTipTitle);
    }

    function errorbillsNamesCase(errorInputsArr, title){
        if(errorInputsArr.length === 0){
            removeErrorClassFromInputTitle(title);
        } else {
            addErrorClassToInputTitle(title);
        }
    }

    function checkBillsInputsNamesForError(that){
        if(that.hasClass('bills-name-input')){
            checkBillsNameInputsForErrorNames();
        } else if(that.hasClass('bills-amount-input')){
            checkBillsAmountInputsForErrorNames();
        } else if (that.hasClass('bills-tip-input')){
            checkBillsTipInputsForErrorNames();
        }
    }

    //--------------------------------- cigarettes ---------------------------------

    var notApprovedMessageCigarettes = $('.sales-report-cigarettes .not-approved-message'),
        approvedMessageCigarettes = $('.sales-report-cigarettes .approved-message'),
        cigarettesInput =  $('#cigarettes'),
        cigarettesTitle = $('#cigarettes-title'),
        cigarettesTotalVal = $('.cigarettes-total-count .total-count-val');

    cigarettesInput.on('focusout ', function(){

        if(digitRegExp.test(cigarettesInput.val())){
            cigarettesTotalVal.text(toCurrency(cigarettesInput.asNumber()));
            if (currency === 'CHF') {
                roundTotalValues();
            }
            duesCigarettesVal.text(cigarettesTotalVal.text());
        } else{
            cigarettesTotalVal.text('0.00');
        }

        checkCigarettesInput();
        checkAllApprovalSectionsAndChangeSubmitBtn();
        totalDues();
    });

    checkCigarettesInput();

    function checkCigarettesInput() {
        if((cigarettesInput.val() === 0) || (cigarettesInput.val() === '0')){
            cigarettesInput.val('0.00');
        } else {
            cigarettesInput.formatCurrency({
                digitGroupSymbol: "'",
                symbol: ''
            });
        }
    }

    addListenersToInput(cigarettesInput, notApprovedMessageCigarettes, approvedMessageCigarettes, cigarettesTitle);

    +(cigarettesTotalVal.text()) === 0 ? cigarettesTotalVal.text('0.00') : false;

    //--------------------------------- summary and dues ---------------------------------

    var notApprovedMessageSummarySales = $('#summary-sales-approval .not-approved-message'),
        approvedMessageSummarySales = $('#summary-sales-approval .approved-message'),
        notApprovedMessageSummaryCards = $('#summary-cards-approval .not-approved-message'),
        approvedMessageSummaryCards = $('#summary-cards-approval .approved-message'),
        notApprovedMessageSummaryVoucher = $('#summary-voucher-approval .not-approved-message'),
        approvedMessageSummaryVoucher = $('#summary-voucher-approval .approved-message'),
        notApprovedMessageSummaryBills = $('.summary-bills-approval .not-approved-message'),
        approvedMessageSummaryBills = $('.summary-bills-approval .approved-message'),
        notApprovedMessageSummaryExpenses = $('#summary-expenses-approval .not-approved-message'),
        approvedMessageSummaryExpenses = $('#summary-expenses-approval .approved-message'),
        notApprovedMessageSummaryCashIncome = $('#summary-cash-income-approval .not-approved-message'),
        approvedMessageSummaryCashIncome = $('#summary-cash-income-approval .approved-message'),
        summarySalesVal = $('#summary-sales'),
        summaryCardsVal = $('#summary-card-payments'),
        summaryBillsTipVal = $('#dues-bill-tip'),
        summaryVouchersVal = $('#summary-vouchers'),
        summaryBillsVal = $('#summary-bills'),
        summaryExpensesVal = $('#summary-expenses'),
        totalSummaryVal = $('#total-summary'),
        duesCashIncomeVal = $('#dues-cash-income'),
        duesBillsTipVal = $('#dues-bill-tip'),
        duesCigarettesVal = $('#dues-cigarettes'),
        duesKitchenTipInputs =  $('.dues-val input'),
        totalDuesVal = $('#total-dues'),
        approvedMessageDuesKitchenTip = $('#dues-kitchen-tip-approval .approved-message'),
        notApprovedMessageDuesKitchenTip = $('#dues-kitchen-tip-approval .not-approved-message');

    summariseItemApprovement(approvedMessageTotalSales, approvedMessageSummarySales, notApprovedMessageSummarySales);
    summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
    summariseItemApprovement(approvedMessageVouchers, approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher);
    summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
    summariseItemApprovement(approvedMessageExpenses, approvedMessageSummaryExpenses, notApprovedMessageSummaryExpenses);
    summariseItemCash(totalCreditCardsValue, summaryCardsVal);
    summariseItemCash(totalExpensesValue, summaryExpensesVal);
    summariseItemCash(totalBillsAmountValue, summaryBillsVal);
    summariseVouchersCash();
    summarySalesVal.text(toCurrency(totalSalesInput.asNumber()));
    duesCigarettesVal.text(cigarettesTotalVal.text());
    summaryBillsVal.text(toCurrency(totalBillsAmountValue.asNumber() * (-1)));
    totalSummarise();
    totalDues();

    facilitySalesReport.on('focusout', '#total-sales', function(){
        if(digitRegExp.test(totalSalesInput.val())){
            summarySalesVal.text(toCurrency(totalSalesInput.asNumber()));
        } else{
            summarySalesVal.text('0.00');
        }
        summariseItemApprovement(approvedMessageTotalSales, approvedMessageSummarySales, notApprovedMessageSummarySales);
    });

    facilitySalesReport.on('focusout', '.sales-report-credit-cards input', function(){
        summariseItemCash(totalCreditCardsValue, summaryCardsVal);
        summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
    });

    facilitySalesReport.on('focusout', '.sales-report-voucher input', function(){
        var notActiveApprovalMessagesArray = $('.sales-report-voucher .approved-message.not-active');

        summariseVouchersCash();
        notActiveApprovalMessagesArray.length !== 0 ?
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher) :
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryVoucher, notApprovedMessageSummaryVoucher);
        totalSummarise();
        totalDues();
        checkAllApprovalSectionsAndChangeSubmitBtn();
    });

    facilitySalesReport.on('focusout', '.sales-report-expenses input', function(){
        summariseItemCash(totalExpensesValue, summaryExpensesVal);
        summariseItemApprovement(approvedMessageExpenses, approvedMessageSummaryExpenses, notApprovedMessageSummaryExpenses);
    });

    totalSalesInput.on('focusout', function(){
        checkApprovalForDuesInputField($(this), approvedMessageDuesKitchenTip, notApprovedMessageDuesKitchenTip);
        totalDues();
    });

    duesKitchenTipInputs.on('focusout', function(){
        duesKitchenTipInputsLoop($(this));
        totalDues();
        checkAllApprovalSectionsAndChangeSubmitBtn();
    });

    facilitySalesReport.on('focusout', '.sales-report-bills .bills-tip-input', function(){
        var totalBillsTips = 0.00;
        if (negativeBillsTips($('.sales-report-bills .bills-tip-input'))) {
            totalBillsTips = toCurrency(-1 * $('#bills-count-tip .total-count-val').text());
        } else {
            totalBillsTips =  '-' + $('#bills-count-tip .total-count-val').text();

        }

        duesBillsTipVal.text(totalBillsTips);
        (negativeBillsTips($(this))) ? $(this).addClass('error-input') : $(this).removeClass('error-input');
    });

    function duesKitchenTipInputsLoop(that){
        that.each(function(){
            checkApprovalForDuesInputField($(this), $(this).parents('tr').find('.approved-message'), $(this).parents('tr').find('.not-approved-message'));
        });
    }

    function summariseItemCash(itemTotalValue, summariseItemValue){
        if(itemTotalValue.text() !== '0.00'){
            summariseItemValue.text('-' + itemTotalValue.text());
        } else{
            summariseItemValue.text('0.00');
        }
    }

    function summariseItemApprovement(approvedMessageFromSection, approvedMessageSummarySection, notApprovedMessageSummarySection){
        if (approvedMessageFromSection.hasClass('not-active')) {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummarySection, notApprovedMessageSummarySection);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummarySection, notApprovedMessageSummarySection);
        }
        totalSummarise();
        totalDues();
        checkAllApprovalSectionsAndChangeSubmitBtn();
    }

    function  summariseVouchersCash(){
        var totalVouchersSum,
            totalAcceptedVouchersVal = $('#total-accepted-vouchers').asNumber(),
            totalIssuedVouchersVal = $('#total-issued-vouchers').asNumber();
        totalVouchersSum = global_issued_total_sales ? (totalAcceptedVouchersVal - totalIssuedVouchersVal) * (-1) : totalAcceptedVouchersVal * (-1);;

        summaryVouchersVal.text(toCurrency(totalVouchersSum));
    }

    function totalSummarise(){
        var totalSummary;
        totalSummary = (summarySalesVal.asNumber()) + (summaryCardsVal.asNumber()) + (summaryVouchersVal.asNumber()) + (summaryBillsVal.asNumber()) + (summaryExpensesVal.asNumber() + (summaryBillsTipVal.asNumber()));

        totalSummaryVal.text(toCurrency(totalSummary));

        if(
            (approvedMessageSummarySales.hasClass('not-active')) ||
            (approvedMessageSummaryCards.hasClass('not-active')) ||
            (approvedMessageSummaryVoucher.hasClass('not-active')) ||
            (approvedMessageSummaryBills.hasClass('not-active')) ||
            (approvedMessageSummaryExpenses.hasClass('not-active'))
        ){
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCashIncome, notApprovedMessageSummaryCashIncome);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCashIncome, notApprovedMessageSummaryCashIncome);
        }

        duesCashIncomeVal.text(toCurrency(totalSummary));
        $('#cash-income').val(totalSummary);
    }

    function totalDues(){
        var totalDues;

        totalDues = (duesCashIncomeVal.asNumber()) + (duesCigarettesVal.asNumber()) + getTipsInputsSum();

        var amount = toCurrency(totalDues);
        totalDuesVal.text(amount);
        $('#total-dues-input').val(amount);
    }

    function checkApprovalForDuesInputField(input, aproveMessage, notApproveMessage){
        if (input.val() === ''){
            showNotApprovedMessageAndHideApprovedMessage(aproveMessage, notApproveMessage);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(aproveMessage, notApproveMessage);
        }
    }

    function updateDuesTipsValues(val){
        duesKitchenTipInputs.each(function(){
            var name = $(this).attr('name'),
                percent = +$(this).data('percent') * 0.01,
                value = val * percent;

            $(`input[name="${name}"]`).val(toCurrency(value));
        });
    }

    function getTipsInputsSum(){
        var sum = 0;
        duesKitchenTipInputs.each(function(){
            sum += $(this).asNumber();
        });
        return sum;
    }

    function negativeBillsTips(numbers) {
        var result = false;
        numbers.each(function (index, element) {
            if ($(element).val() < 0) {
                result = true;
            }
        });

        return result;
    }

    $('.dues-table .dues-val').each(function(){
        (+$(this).text() === 0) ?  $(this).text('0.00') : false;
    });

    //--------------------------------- sales report buttons ---------------------------------

    function checkAllApprovalSectionsAndChangeSubmitBtn(){
        var buttonSubmit = $('.sales-report-buttons .btn-submit'),
            buttonError = $('.sales-report-buttons .not-approved-transact'),
            arrOfNotApprovedMessages = [];

        $('.facility-sales-report .not-approved-message').each(function () {
            if(!$(this).hasClass('not-active')){
                arrOfNotApprovedMessages.push($(this))
            }
            if (arrOfNotApprovedMessages.length > 0) {
                if (!buttonSubmit.hasClass('not-active')) {
                    buttonSubmit.addClass('not-active');
                }
                if (buttonError.hasClass('not-active')) {
                    buttonError.removeClass('not-active')
                }

            } else {
                if (buttonSubmit.hasClass('not-active')) {
                    buttonSubmit.removeClass('not-active');
                }
                if (!buttonError.hasClass('not-active')) {
                    buttonError.addClass('not-active')
                }
            }
        });
    }

    //---------------------------------success data saved message---------------------------------

    var dataSavedMessage = $('.data-saved-message');

    $('.data-saved-message .close-icon').on('click', function(){
        if(!dataSavedMessage.hasClass('not-active')){
            dataSavedMessage.addClass('not-active')
        }
    });

    if ($('#summary-sales').text() === '') {
        $('#summary-sales').text('0.00')
    }

    //--------------------------------------------------------------------------------------------

    currency === '€' ? $('.sales-report-summary .total-count-val').css({'min-width':'190px'}) : false;
    $('#summary-sales').text() === '' ? $('#summary-sales').text('0.00') : false;

    facilitySalesReport.on('focusout', 'input', function(){
        if (currency === 'CHF') {
            roundInputsValues();
        }
    });

    function roundInputsValues(){
        facilitySalesReport.find('input').each(function(){
            if($(this).next().hasClass('currency')){
                var inputValue = $(this).val(),
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
                    $(this).val(inputValue);
                }

            }
        });
    }
    function roundTotalValues(){
        facilitySalesReport.find('.total-count-val').each(function(){
            var totalValue = $(this).text().replace(/\s/g, ''),
                lastDigit = totalValue.slice(-1),
                twoLastDigits = (totalValue.slice(-2)),
                integer = Number(totalValue.slice(0, -3).replace(/[']/g, '')),
                roundedLastDigit = Math.round(lastDigit/5)*5,
                roundedTwoLastDigits = Math.round(twoLastDigits/10)*10,
                roundedInteger = integer + 1;

            if(String(roundedLastDigit).length === 1){
                totalValue = totalValue.slice(0, -1) + roundedLastDigit;
            } else if(String(roundedTwoLastDigits).length === 2) {
                totalValue = totalValue.slice(0, -2) + roundedTwoLastDigits;
            } else {
                totalValue = roundedInteger.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            }

            if(totalValue !== '0'){
                $(this).text(totalValue);
            }
        });
    }


});
