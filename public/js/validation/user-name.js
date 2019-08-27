$(document).ready( function () {

    var errorName = false,
        nameRequired = $('#error-name-message-required'),
        nameMustContain = $('#error-name-message-must-contain'),
        inputName =  $('#username'),
        titleName =  $('#title-name');

    if ($('.reset-name ul li').length > 0) {
        inputName.addClass('error-input');
        titleName.addClass('error-name');
    }

    inputName.focusout(function () {
        checkName();
    });

    function disableDuplicatingErrorMessages(message1, message2) {
        if (
            (!message1.hasClass('not-active')) &&
            (!message2.hasClass('not-active'))
        ) {
            message2.addClass('not-active')
        }
    }

    function checkName() {

        refreshToDefault(inputName, titleName);

        var pattern = /[A-Za-z]{2,}/,
            name = inputName.val();

        if (name !== '') {
            successCase(nameRequired, titleName);
            if (pattern.test(name)) {
                successCase(nameMustContain, titleName);
            } else {
                refreshToDefault(inputName, titleName);
                errorCase(nameMustContain, inputName, titleName);
                errorName = true;
            }
        } else {
            errorCase(nameRequired, inputName, titleName);
            errorName = true;
        }

        disableDuplicatingErrorMessages(nameRequired, nameMustContain)
    };

    $('#login-form').submit(function() {

        errorName = false;

        checkName();

        if( errorName === false ) {
            return true;
        } else {
            return false;
        }

    });

    function refreshToDefault (input, title) {

        if (title.hasClass('success')) {
            title.removeClass('success');
        };
        if (input.hasClass('error-input')) {
            input.removeClass('error-input');
        };
        if (title.hasClass('error-name')) {
            title.removeClass('error-name');
        };
    }

    function successCase (message, title) {
        if(!message.hasClass('not-active')) {
            message.addClass('not-active');
        }
        title.addClass('success');
    }

    function errorCase (message, input, title) {
        message.removeClass('not-active');
        input.addClass('error-input');
        title.addClass('error-name');
    }

});