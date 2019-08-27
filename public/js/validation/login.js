$(document).ready(function() {

    var errorName = false,
        errorPassword = false,

        nameRequired = $('#error-name-message-required'),
        nameMustContain = $('#error-name-message-must-contain'),
        passwordRequired = $('#error-password-message-required'),
        passwordMustContain = $('#error-password-message-must-contain'),

        inputName =  $('#username'),
        inputPassword = $('#password'),

        titleName =  $('#title-name'),
        titlePassword = $('#title-password');

    inputName.focusout(function () {
        checkName();
    });
    inputPassword.focusout(function(){
        checkPassword();
    });

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
    };

    function checkPassword() {

        refreshToDefault(inputPassword, titlePassword);

        var pattern = /^[a-zA-Z0-9\S]{6,}$/,
            password = inputPassword.val();

        if (password !== '') {
            successCase(passwordRequired, titlePassword);
            if (pattern.test(password)) {
                successCase(passwordMustContain, titlePassword);
            } else {
                refreshToDefault(inputPassword, titlePassword);
                errorCase(passwordMustContain, inputPassword, titlePassword);
                errorPassword = true;
            }
        } else {
            errorCase(passwordRequired, inputPassword, titlePassword);
            errorPassword = true;
        }
    };

    $('#login-form').submit(function() {

        errorName = false;
        errorPassword = false;

        checkName();
        checkPassword();

        if(
            errorName === false &&
            errorPassword === false
        ) {
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