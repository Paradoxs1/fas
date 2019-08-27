$(document).ready( function () {

    var errorPassword = false,
        errorConfirmPassword = false,
        passwordRequired = $('#error-password-message-required'),
        passwordMustContain = $('#error-password-message-must-contain'),
        passwordConfirmRequired = $('#error-password-confirm-message-required'),
        passwordConfirmMustContain = $('#error-password-confirm-message-must-contain'),
        inputPassword = $('#password'),
        inputConfirmPassword = $('#confirm-password'),
        titlePassword = $('#title-password'),
        titleConfirmPassword = $('#title-confirm-password');

    inputPassword.focusout(function(){
        checkPassword();
    });
    inputConfirmPassword.focusout(function(){
        checkConfirmPassword();
    });

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

    function checkConfirmPassword() {

        var password = inputPassword.val(),
            confirmPassword = inputConfirmPassword.val();

        refreshToDefault(inputConfirmPassword, titleConfirmPassword);

        if (confirmPassword !== '') {
            successCase(passwordConfirmRequired, titleConfirmPassword);

            if (confirmPassword === password) {
                successCase(passwordConfirmMustContain, titleConfirmPassword);
            } else {
                refreshToDefault(inputConfirmPassword, titleConfirmPassword);
                errorCase(passwordConfirmMustContain, inputConfirmPassword, titleConfirmPassword);
                errorConfirmPassword = true;
            }

        } else {
            errorCase(passwordConfirmRequired, inputConfirmPassword, titleConfirmPassword);
            errorConfirmPassword = true;
        }

    };

    $('#login-form').submit(function() {

        errorPassword = false;
        errorConfirmPassword = false;

        checkPassword();
        checkConfirmPassword();

        if(
            errorPassword === false &&
            errorConfirmPassword === false
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