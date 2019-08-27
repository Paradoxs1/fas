$(document).ready(function(){

    var currency = (typeof global_currency !== 'undefined') ? global_currency : 'CHF',
        isViewMode = +$('#sales-report-form').data('view-mode'),
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
        $(this).each(function(){
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
                    if ( !$(this).hasClass('exception') ) {
                        var buttonSubmit = $('.sales-report-buttons .btn-submit'),
                            buttonError = $('.sales-report-buttons .not-approved-transact');

                        if (!buttonSubmit.hasClass('not-active')) {
                            buttonSubmit.addClass('not-active');
                        }
                        if (buttonError.hasClass('not-active')) {
                            buttonError.removeClass('not-active')
                        }

                        showNotApprovedMessageAndHideApprovedMessage($(this).parents('.report-section').find('.approved-message'), $(this).parents('.report-section').find('.not-approved-message'));
                    }
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
        reportPeriodDays = global_days_in_past.length > 0 ? global_days_in_past * -1 : null,
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
                var url = window.location.href;
                window.location = url.substring(0, url.lastIndexOf('/')) + '/' + date;
                return;

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
        beforeShowDay: function(datePicker) {
            var string = $.datepicker.formatDate('dd.mm.yy', datePicker);

            for (var item in datesToDisable) {
                if (date.length > 0 && datesToDisable[item] === date) {
                    delete datesToDisable[item];
                    return [true];
                }

                if (datesToDisable[item].includes(string)) {
                    return [false];
                }
            }
            return [true];
        }
    });

    datePickInput.datepicker('setDate', date ? date : today);

    addListenersToInput(datePickInput, notApprovedMessageReportDetails, approvedMessageReportDetails, datePickTitle);

    //--------------------------------- total sales ---------------------------------

    var notApprovedMessageTotalSales = $('.total-sales .not-approved-message'),
        approvedMessageTotalSales = $('.total-sales .approved-message'),
        totalSalesInputs =  $('.total-sales input'),
        totalSalesValue = 0,
        salesTotalCount =  $('.total-sales .total-count-val');

    salesTotalCount.text(toCurrency(+salesTotalCount.text()));

    totalSalesInputs.on('focusout', function(){
        checkTotalSalesInputsForApproveAndForErrors();
    });

    function checkTotalSalesInputsForApproveAndForErrors(){
        var arrOfEmptyInputs = [];

        totalSalesInputs.each(function(){
            if($(this).val() === ''){
                arrOfEmptyInputs.push($(this));
                addErrorClassAndRemoveSuccessClassFromInputs($(this));
                addErrorClassToInputTitle($(this).parent().find('label'));
            } else {
                addSuccessClassAndRemoveErrorClassFromInputs($(this));
                removeErrorClassFromInputTitle($(this).parent().find('label'));
            }
        });

        if(arrOfEmptyInputs.length > 0){
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageTotalSales, notApprovedMessageTotalSales);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageTotalSales, notApprovedMessageTotalSales)
        }

        sumTotalSalesInputs();
        if (currency === 'CHF') {
            roundTotalValues();
        }
    }

    function sumTotalSalesInputs(){
        var sum = 0;

        totalSalesInputs.each(function () {
            sum += $(this).asNumber();
        });

        totalSalesValue = sum;

        salesTotalCount.text(toCurrency(sum));
        if (currency === 'CHF') {
            roundTotalValues();
        }
        updateDuesTipsValues(totalSalesValue);
        checkDuesInputsForApproval();
        checkDuesTotalApproval();
        totalDues();

        summariseItemValueTotalCount(salesTotalCount, summarySalesVal, 'positive');
        totalSummarise();
        summariseItemApprovement(approvedMessageTotalSales, approvedMessageSummarySales, notApprovedMessageSummarySales);
    }

    //--------------------------------- credit cards ---------------------------------

    var notApprovedMessageCreditCards = $('.sales-report-credit-cards .not-approved-message'),
        approvedMessageCreditCards = $('.sales-report-credit-cards .approved-message'),
        creditCardsIdNumber = 1,
        totalCreditCardsValue = $('#total-cards-sales');

    $('.sales-report-credit-cards').on('focusout', '.report-input', function(){
        sumCreditCardsTerminalVal($(this));
        sumCreditCardsTotalVal();
        checkCreditCardsInputsForError();
        summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
        totalSummarise();
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

    function summariseItemCash(itemTotalValue, summariseItemValue){
        if(itemTotalValue.text() !== '0.00'){
            summariseItemValue.text('-' + itemTotalValue.text());
        } else{
            summariseItemValue.text('0.00');
        }
    }

    addListenerToRemoveCreditCardsBtn('#terminal-1 .btn-remove-line','#terminal-1 .report-input', '#terminal-1 .total-count-val');
    addListenerToAddCreditCardsBtn('#terminal-1 .btn-add-line');

    var initialTerminalBlock = $('#terminal-1');
    var nextInitialTerminalBlock = initialTerminalBlock.clone();

    $('.link-add-terminal').click(function(){
        var nextTerminalBlock = nextInitialTerminalBlock.clone();
        var currentTerminalBlock = $('#terminal-' + creditCardsIdNumber);
        currentTerminalBlock.after(nextTerminalBlock);

        creditCardsIdNumber++;
        nextTerminalBlock.attr('id', 'terminal-' + creditCardsIdNumber);
        nextTerminalBlock = $('#' + nextTerminalBlock.attr('id'));
        nextTerminalBlock.find('.sales-report-title-with-line').html('<p>' + translations['report.terminal'] + ' ' + creditCardsIdNumber + '</p>');

        nextTerminalBlock.find('.credit-cards-input').each(function () {
            $(this).attr('data-terminal', creditCardsIdNumber - 1);

            var inputField = $(this).find('input');
            var accountingPosition = $(this).data('accounting-position');
            var inputNumber = $(this).data('number');

            inputField.attr('id', 'credit-card-' + (creditCardsIdNumber - 1) + '-' + accountingPosition + '-' + inputNumber);
            inputField.attr('name', 'credit-cards[' + (creditCardsIdNumber - 1) + '][' + accountingPosition + '][' + inputNumber + ']');
            inputField.val('');
        });

        var prevTerminalDeleteLink = $('#terminal-'+ (creditCardsIdNumber - 1) +' .link-delete-terminal'),
            currentTerminalDeleteLink = $('#terminal-'+ creditCardsIdNumber +' .link-delete-terminal');

        addListenerToRemoveCreditCardsBtn('#terminal-'+ creditCardsIdNumber +' .btn-remove-line');
        addListenerToAddCreditCardsBtn('#terminal-'+ creditCardsIdNumber +' .btn-add-line');

        if(!prevTerminalDeleteLink.hasClass('not-active')){
            prevTerminalDeleteLink.addClass('not-active')
        }

        currentTerminalDeleteLink.removeClass('not-active');

        currentTerminalDeleteLink.click(function(){
            if(creditCardsIdNumber > 1){
                prevTerminalDeleteLink.removeClass('not-active');
                $('#terminal-'+ creditCardsIdNumber +'').remove();
                creditCardsIdNumber--;
                summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
                totalSummarise();
                summariseItemCash(totalCreditCardsValue, summaryCardsVal);
            }

            if ($('.terminals-container').children().length === 1) {
                if(!$('.link-delete-terminal').hasClass('not-active')) {
                    $('.link-delete-terminal').addClass('not-active');
                }
            }

            sumCreditCardsTotalVal();
            summariseItemCash(totalCreditCardsValue, summaryCardsVal);
            checkCreditCardsInputsForError();

            if (approvedMessageCreditCards.hasClass('not-active')) {
                showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            } else {
                showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummaryCards,notApprovedMessageSummaryCards);
            }
            summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
        });

        totalSummarise();
        checkCreditCardsInputsForError();
        summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
    });

    function addListenerToRemoveCreditCardsBtn(removeBtn){
        var removeBtnVar = $(removeBtn);

        removeBtnVar.click(function(){
            var elementToRemove = $(this).parents('.credit-cards-terminal').find('.report-input').parent().last();
            elementToRemove.remove();

            if ($(this).parent().prev().data('number') == 1) {
                $(this).addClass('not-active');
            }

            if($(this).siblings().hasClass('not-active')){
                $(this).siblings().removeClass('not-active')
            }

            $(this).parents('.credit-cards-terminal').find('input').each(function(){
                sumCreditCardsTerminalVal($(this));
            });

            checkCreditCardsInputsForError();
            sumCreditCardsTotalVal();
            summariseItemCash(totalCreditCardsValue, summaryCardsVal);
            summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);
            totalSummarise();
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

            var copiedBlock = inputBlock.clone();

            inputBlock.after(copiedBlock);
            copiedBlock.attr('data-number', inputNumber);

            var copiedBlockInput = $(copiedBlock.find('input')[0]);

            copiedBlockInput.attr('id', 'credit-card-' + terminal + '-' + accountingPosition + '-' + inputNumber);
            copiedBlockInput.attr('name', 'credit-cards[' + terminal + '][' + accountingPosition + '][' + inputNumber + ']');
            copiedBlockInput.val('');

            if (copiedBlock.data('number') > 1){
                $(this).prev().removeClass('not-active');
            }

            if (copiedBlock.data('number') == 3){
                $(this).addClass('not-active');
            }

            checkCreditCardsInputsForError();
            summariseItemApprovement(approvedMessageCreditCards, approvedMessageSummaryCards, notApprovedMessageSummaryCards);;
            totalSummarise();
            fixButtonsPosition($(this));
            summariseItemCash(totalCreditCardsValue, summaryCardsVal);
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
        if (currency === 'CHF') {
            roundTotalValues();
        }
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
        expensesAmountTitle = $('#expenses-amount-title');

    $('#add-expenses').on('click', function(){
        expensesIdNumber++;
        expensesContent.append('' +
            '<div class="new-expenses">\n' +
            '    <input name="expenses[' + expensesIdNumber + '][name]" type="text" id="expenses-name-line-'+ expensesIdNumber +'" class="fas-input report-input expenses-name-input">\n' +
            '    <div class="currency-input-wrapper">\n' +
            '       <input name="expenses[' + expensesIdNumber + '][amount]" type="text" id="expenses-amount-line-'+ expensesIdNumber +'" class="fas-input report-input expenses-amount-input currency-input">\n' +
            '       <span class="currency">' + currency + '</span>\n' +
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
        sumExpensesInputs();
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
    });

    expensesSection.on('focusout', 'input', function () {
        var that = $(this);
        sumExpensesInputs();

        if (currency === 'CHF') {
            roundTotalValues();
        }

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
        summaryExpensesVal.text(toCurrency(-1*sum));
    }

    function checkExpensesInputsForApprove(){
        var arrWithErrorInputs = [];

        $('.sales-report-expenses input').each(function(){
            if(!$(this).hasClass('success-input')){
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
        billsContent.append('' +
            '<div class="new-bills">\n' +
            '    <input name="bills['+ billsIdNumber +'][receiver]" type="text" id="bills-name-line-'+ billsIdNumber +'" class="fas-input report-input bills-name-input">\n' +
            '    <div class="currency-input-wrapper">\n' +
            '       <input name="bills['+ billsIdNumber +'][amount]" type="text" id="bills-amount-line-'+ billsIdNumber +'" class="fas-input report-input bills-amount-input currency-input">\n' +
            '       <span class="currency">' + currency + '</span>\n' +
            '    </div>\n' +
            '    <div class="currency-input-wrapper">\n' +
            '       <input name="bills['+ billsIdNumber +'][tip]" type="text" id="bills-tip-line-'+ billsIdNumber +'" class="fas-input report-input bills-tip-input currency-input">\n' +
            '       <span class="currency">' + currency + '</span>\n' +
            '    </div>\n' +
            '    <select name="bills['+ billsIdNumber +'][name]" id="bills-select-line-'+ billsIdNumber +'" class="fas-select report-select bills-select">\n' +
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
        if (currency === 'CHF') {
            roundTotalValues();
        }

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
        totalSummarise();
        summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
    });

    function sumBillsAmountInputs() {
        var sum = 0,
            bilsAmountTotalCount = $('#bills-count-amount .total-count-val');

        $('.bills-amount-input').each(function () {
            sum += $(this).asNumber();
        });

        bilsAmountTotalCount.text(toCurrency(sum));
        summaryBillsVal.text(toCurrency(totalBillsAmountValue.asNumber() * (-1)));
        totalSummarise();
    }

    function sumBillsTipInputs() {
        var sum = 0,
            bilsTipTotalCount = $('#bills-count-tip .total-count-val');

        $('.bills-tip-input').each(function () {
            sum += $(this).asNumber();
        });

        bilsTipTotalCount.text(toCurrency(sum));
        totalDues();
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

        summariseItemApprovement(approvedMessageBills, approvedMessageSummaryBills, notApprovedMessageSummaryBills);
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

    cigarettesInput.on(' focusout', function(){
        if(digitRegExp.test(cigarettesInput.val())){
            cigarettesTotalVal.text(toCurrency(cigarettesInput.asNumber()));
            if (currency === 'CHF') {
                roundTotalValues();
            }
            duesCigarettesVal.text(cigarettesTotalVal.text());
        } else{
            cigarettesTotalVal.text('0.00');
        }

        totalDues();
        checkCigarettesInput();
        checkAllApprovalSectionsAndChangeSubmitBtn();
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

    //--------------------------------- tenant report dues ---------------------------------

    var duesInputs = $('.sales-report-dues input'),
        duesCashInput = $('#dues-cash'),
        duesCashApprovalMessage = $('#dues-cash-approval .approved-message'),
        duesCashNotApprovalMessage = $('#dues-cash-approval .not-approved-message'),
        duesTotalApproveMessage = $('#dues-total-approval .approved-message'),
        duesTotalNotApproveMessage = $('#dues-total-approval .not-approved-message'),
        duesCigarettesVal = $('#dues-cigarettes'),
        totalDuesVal = $('#total-dues'),
        duesBillsTipVal = $('#dues-bill-tip');

    summariseItemValueTotalCount($('#bills-count-tip .total-count-val'), duesBillsTipVal);
    duesCigarettesVal.text(cigarettesTotalVal.text());
    totalDuesVal.text(toCurrency(duesCashInput.asNumber() + duesBillsTipVal.asNumber() + sumTips() + duesCigarettesVal.asNumber()));

    duesInputs.on('focusout', function(){
        checkDuesInputsForApproval();
        checkDuesTotalApproval();
        totalDues();
    });

    function totalDues(){
        var totalDues = 0;

        totalDues = duesCashInput.asNumber() + sumTips() + duesCigarettesVal.asNumber();
        totalDuesVal.text(toCurrency(totalDues));
        $('#total-dues-input').val(totalDues);

        var cshIncome = toCurrency(duesCashInput.asNumber() * (-1));
        duesCashIncomeVal.text(cshIncome);

        totalSummarise();
        summariseItemApprovement(duesTotalApproveMessage, approvedMessageSummaryCashIncome, notApprovedMessageSummaryCashIncome);
    }

    function sumTips(){
        var total = 0;

        $('.dues-tip-row input').each(function(){
            total += $(this).asNumber();
        });

        return total;
    }

    function updateDuesTipsValues(val){
        $('.dues-val input').each(function(){

            var name = $(this).data('name'),
                percent = +$(this).data('percent') * 0.01,
                value = val * percent;

            $(`input[data-name="${name}"]`).val(toCurrency(value));
        });
    }

    function checkDuesInputsForApproval(){
        $('.dues-val input').each(function(){

            var cashVal = duesCashInput.val(),
                tipsVal = $(`input[data-name="${$(this).data('name')}"]`).val();

            if(cashVal === '') {
                showNotApprovedMessageAndHideApprovedMessage(duesCashInput.parents('tr').find('.approved-message'), duesCashInput.parents('tr').find('.not-approved-message'));
            } else {
                showApprovedMessageAndHideNoApprovedMessage(duesCashInput.parents('tr').find('.approved-message'), duesCashInput.parents('tr').find('.not-approved-message'));
            }

            if(tipsVal === '') {
                showNotApprovedMessageAndHideApprovedMessage($(this).parents('tr').find('.approved-message'), $(this).parents('tr').find('.not-approved-message'));
            } else {
                showApprovedMessageAndHideNoApprovedMessage($(this).parents('tr').find('.approved-message'), $(this).parents('tr').find('.not-approved-message'));
            }
        });
    }

    function checkDuesTotalApproval(){
        if($('.dues-table .approved-message.not-active').length === 0){
            showApprovedMessageAndHideNoApprovedMessage(duesTotalApproveMessage, duesTotalNotApproveMessage);
        } else {
            showNotApprovedMessageAndHideApprovedMessage(duesTotalApproveMessage, duesTotalNotApproveMessage);
        }
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

    //--------------------------------- tenant report popup calculate total---------------------------------

    var popupDuesCalculate = $('.dues-popup-section-calculate-total'),
        popupDuesCalculateTotalCount = $('.dues-popup-total-count .total-count-val');

    $('#popup-calculate-total-link').on('click', function(){
        if(popupDuesCalculate.hasClass('not-active')){
            popupDuesCalculate.removeClass('not-active');
            $('body').css('overflow', 'hidden');
        }
    });

    $('.dues-popup-calculate-total-header .close-icon').on('click', function(){
        if(!popupDuesCalculate.hasClass('not-active')){
            popupDuesCalculate.addClass('not-active');
            $('body').css('overflow', 'auto');
        }
    });

    $('.enter-coins-amount-link').on('click', function(){
        $(this).toggleClass('active');
        $('.dues-calculation-cash-coins').slideToggle(400);
        $('.calculation-cash-coins-container').slideToggle(400);
        $('.dues-calculation-cash-coins-table input').val('');
        $('#coins').val('');
        popupCalcCashTotalCount();
    });

    $('#calculation-cash-popup-send-btn').on('click', function(){
        $('body').css('overflow', 'auto');
        duesCashInput.val(popupDuesCalculateTotalCount.text());
        $('.dues-popup-calculate-total input').val('');
        if(!popupDuesCalculate.hasClass('not-active')){
            popupDuesCalculate.addClass('not-active')
        }
        popupDuesCalculateTotalCount.text('0.00');
        showApprovedMessageAndHideNoApprovedMessage(duesCashApprovalMessage, duesCashNotApprovalMessage);
        addSuccessClassAndRemoveErrorClassFromInputs(duesCashInput);
        removeErrorClassFromInputTitle($('#dues-cash-label'));
        checkDuesTotalApproval();
        totalDues();
    });

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

        $('.dues-popup-calculate-total .result-input').each(function () {
            sum += $(this).asNumber();
        });

        popupDuesCalculateTotalCount.text(toCurrency(sum));
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
    //--------------------------------- tenant report summary---------------------------------

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
        duesCashIncomeVal = $('#summary-cash-income');

    summariseItemValueTotalCount(salesTotalCount, summarySalesVal, 'positive');
    summaryCardsVal.text($('.sales-report-credit-cards .sales-report-section-footer .total-count-val').text());
    summaryVouchersVal.text((toCurrency(($('#total-accepted-vouchers').asNumber()*(-1)))));
    summariseItemValueTotalCount($('#bills-count-amount .total-count-val'), summaryBillsVal);
    summariseItemValueTotalCount($('.expenses-total-count .total-count-val'), summaryExpensesVal);
    summariseItemCash(totalCreditCardsValue, summaryCardsVal);
    summariseVouchersCash();
    totalDues();
    totalSummarise();

    function summariseItemValueTotalCount(itemTotalValue, summariseItemValue, positive){
        if (currency === 'CHF') {
            roundTotalValues();
        }
        if (positive) {
            if(itemTotalValue.text() !== '0.00'){
                summariseItemValue.text(itemTotalValue.text());
            } else {
                summariseItemValue.text('0.00');
            }
        } else {
            if(itemTotalValue.text() !== '0.00'){
                if (itemTotalValue.text().charAt(0) === '-') {
                    summariseItemValue.text(itemTotalValue.text().replace(/^-/,''));
                } else {
                    summariseItemValue.text('-' + itemTotalValue.text());
                }
            } else {
                summariseItemValue.text('0.00');
            }
        }
    }

    function totalSummarise(){
        var totalSummary,
            summaryFooter = $('.sales-report-summary .sales-report-section-footer');

        totalSummary = (summarySalesVal.asNumber()) + (summaryCardsVal.asNumber()) + (summaryVouchersVal.asNumber()) + (summaryBillsVal.asNumber()) + (summaryExpensesVal.asNumber()) + (duesCashIncomeVal.asNumber()) + (summaryBillsTipVal.asNumber());
        totalSummaryVal.text(toCurrency(totalSummary));

        if (+(totalSummary.toFixed(2)) === 0.00) {
            if(!summaryFooter.hasClass('success-footer')) {
                summaryFooter.addClass('success-footer')
            }
            if(summaryFooter.hasClass('error-footer')) {
                summaryFooter.removeClass('error-footer')
            }
            totalSummaryVal.text('0.00');
        } else {
            if(summaryFooter.hasClass('success-footer')) {
                summaryFooter.removeClass('success-footer')
            }
            if(!summaryFooter.hasClass('error-footer')) {
                summaryFooter.addClass('error-footer')
            }
        }
    }

    function summariseItemApprovement(approvedMessageFromSection, approvedMessageSummarySection, notApprovedMessageSummarySection){
        if (approvedMessageFromSection.hasClass('not-active')) {
            showNotApprovedMessageAndHideApprovedMessage(approvedMessageSummarySection, notApprovedMessageSummarySection);
        } else {
            showApprovedMessageAndHideNoApprovedMessage(approvedMessageSummarySection, notApprovedMessageSummarySection);
        }

        totalSummarise();
        checkAllApprovalSectionsAndChangeSubmitBtn();
    }

    function summariseVouchersCash(){
        var totalVouchersSum,
            totalAcceptedVouchersVal = $('#total-accepted-vouchers').asNumber(),
            totalIssuedVouchersVal = $('#total-issued-vouchers').asNumber();

        totalVouchersSum = global_issued_total_sales ? (totalAcceptedVouchersVal - totalIssuedVouchersVal) * (-1) : totalAcceptedVouchersVal * (-1);
        summaryVouchersVal.text(toCurrency(totalVouchersSum));
    }

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
        summariseItemApprovement(approvedMessageExpenses, approvedMessageSummaryExpenses, notApprovedMessageSummaryExpenses);
    });

    facilitySalesReport.on('focusout', '.sales-report-credit-cards input', function(){
        summariseItemCash(totalCreditCardsValue, summaryCardsVal);
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

    //--------------------------------- sales report buttons ---------------------------------

    function checkAllApprovalSectionsAndChangeSubmitBtn(){
        var buttonSubmit = $('.sales-report-buttons .btn-submit'),
            buttonError = $('.sales-report-buttons .not-approved-transact'),
            arrOfNotApprovedMessages = [];

        $('.facility-sales-report .not-approved-message').each(function () {
            if(!$(this).hasClass('not-active')){
                arrOfNotApprovedMessages.push($(this))
            }

            if (buttonSubmit.hasClass('not-active')) {
                buttonSubmit.removeClass('not-active');
            }
            if (!buttonError.hasClass('not-active')) {
                buttonError.addClass('not-active')
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

    /*------------------------------------------------------------------------------------*/

    currency === '€' ? $('.sales-report-summary .total-count-val').css({'min-width':'190px'}) : false;
    $('#summary-sales').text() === '' ? $('#summary-sales').text('0.00') : false;

    if (isViewMode === 1) {
        duesCashIncomeVal.text('-' + $('#dues-cash').val());
        totalSummarise();
    }

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
                totalValue = toCurrency(roundedInteger);
            }

            if(totalValue !== '0'){
                $(this).text(totalValue);
            }
        });
    }


    $('#coins').on('focusout', function(){

        $(this).formatCurrency({
            digitGroupSymbol: "'",
            symbol: '',
            negativeFormat: '-%n'
        });

        if (currency === 'CHF') {
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

            var totalValue = $('.dues-popup-calculate-total-footer .total-count-val').text().replace(/\s/g, '');
                lastDigit = totalValue.slice(-1);
                twoLastDigits = (totalValue.slice(-2));
                integer = Number(totalValue.slice(0, -3).replace(/[']/g, ''));
                roundedLastDigit = Math.round(lastDigit/5)*5;
                roundedTwoLastDigits = Math.round(twoLastDigits/10)*10;
                roundedInteger = integer + 1;

            if(String(roundedLastDigit).length === 1){
                totalValue = totalValue.slice(0, -1) + roundedLastDigit;
            } else if(String(roundedTwoLastDigits).length === 2) {
                totalValue = totalValue.slice(0, -2) + roundedTwoLastDigits;
            } else {
                totalValue = toCurrency(roundedInteger);
            }

            if(totalValue !== '0'){
                $('.dues-popup-calculate-total-footer .total-count-val').text(totalValue);
            }
        }
    });

    //--------------------------------- approve sales popup ---------------------------------
    var submitBtn, approveSales, triggerSendForm = true;

    $('body').on('click', '.sales-report-buttons .btn-submit', function() {
        if (global_overlay_template.length > 0) {
            approveSales = $('.approve-overlay-container').find('.approve-sales');

            $.ajax({
                type: 'POST',
                url:  $(this).data("url"),
                data: $('#sales-report-form').serialize(),
                dataType: 'JSON',
                success: function(data) {
                    if (data.result && approveSales.length > 0) {
                        approveSales.remove();
                    }

                    if (data.result) {
                        $('.approve-overlay-container').append(data.content);
                        $('.approve-overlay-container .approve-sales').removeClass('not-active');
                    }
                }
            });
        }
    });

    $('.approve-overlay-container').on('click', '.close-icon', function(){
        if (!$('.approve-sales').hasClass('not-active')) {
            $('.approve-sales').addClass('not-active');
        }
    });

    $('.approve-overlay-container').on('click', '#approve-and-book', function(){
        if ($('.approve-sales').hasClass('not-active')) {
            $('.approve-sales').removeClass('not-active');
        }
    });

    $('.approve-overlay-container').on('click', '#approve-sales-popup-checkbox', function(){
        submitBtn = $('.approve-overlay-container .button-submit');

        if ($(this).is(":checked")) {
            submitBtn.attr('disabled', false);
            submitBtn.removeClass('not-allow');
        } else {
            submitBtn.attr('disabled', true);
            submitBtn.addClass('not-allow');
        }
    });

    //popup validation

    $('.approve-overlay-container').on('focusout', '#approve-sales-payments-missing-income', function(){
        var textInput = $('#approve-sales-popup-text-input');
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

    $('.approve-overlay-container').on('focusout', '#approve-sales-popup-text-input', function(){
        if ($(this).val() !== '') {
            $(this).removeClass('error-input');
            triggerSendForm = true;
        } else {
            $(this).addClass('error-input');
            triggerSendForm = false;
        }

        if ($('#approve-sales-payments-missing-income').val() === '') {
            $('#approve-sales-payments-missing-income').addClass('error-input');
            triggerSendForm = false;
        }

        if ($(this).val() === '' && $('#approve-sales-payments-missing-income').val() === '') {
            $('#approve-sales-payments-missing-income').removeClass('error-input');
            $(this).removeClass('error-input');
            triggerSendForm = true;
        }
    });

    $('#sales-report-form').submit(function() {
        return triggerSendForm;
    });

    checkAllApprovalSectionsAndChangeSubmitBtn();
});
