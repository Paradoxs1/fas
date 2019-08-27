$(document).ready( function () {

    var  errorFirstName                 = false,
         errorLastName                  = false,
         errorEmail                     = false,
         errorUserName                  = false,
         errorPassword                  = false,
         errorConfirmPassword           = false,
         passwordWasChanged             = false,
         errorFacilityAssigment         = false,

         firstNameRequired              = $('#error-first-name-message-required'),
         firstNameMustContain           = $('#error-first-name-message-must-contain'),
         lastNameRequired               = $('#error-last-name-message-required'),
         lastNameMustContain            = $('#error-last-name-message-must-contain'),
         emailRequired                  = $('#error-email-message-required'),
         emailCheck                     = $('#error-email-message'),
         telCheck                       = $('#error-tel-message'),
         userNameRequired               = $('#error-user-name-message-required'),
         userNameMustContain            = $('#error-user-name-message-must-contain'),
         userNameUnique                 = $('#error-user-name-message-unique'),
         passwordRequired               = $('#error-password-message-required'),
         passwordMustContain            = $('#error-password-message-must-contain'),
         passwordConfirmRequired        = $('#error-password-confirm-message-required'),
         passwordConfirmMustContain     = $('#error-password-confirm-message-must-contain'),

         inputFirstName                 = $('#form-first-name'),
         inputLastName                  = $('#form-last-name'),
         inputEmail                     = $('#form-email'),
         inputTel                       = $('#form-tel'),
         inputUserName                  = $('#form-user-name'),
         inputPassword                  = $('#form-password'),
         inputConfirmPassword           = $('#form-confirm-password'),

         titleFirstName                 = $('#title-first-name'),
         titleLastName                  = $('#title-last-name'),
         titleEmail                     = $('#title-email'),
         titleTel                       = $('#title-tel'),
         titleUserName                  = $('#title-user-name'),
         titlePassword                  = $('#title-password'),
         titleConfirmPassword           = $('#title-confirm-password'),

         allowChangeUserNameFromEmail   = true;


    inputFirstName.focusout(function () {
        checkFirstName();
    });
    inputLastName.focusout(function(){
        checkLastName();
    });
    inputEmail.focusout(function(){
        checkEmail();
    });
    inputTel.focusout(function(){
        checkTel();
    });
    inputUserName.focusout(function(){
        checkUserName();
    });
    inputPassword.focusout(function(){
        checkPassword();
        inputConfirmPassword.val('');
        passwordWasChanged = true;
    });
    inputConfirmPassword.focusout(function(){
        checkConfirmPassword();
    });
    $('.facility-assignment').on('change', 'input',function(){
        var userRoles = $(this).parents('tr');
        checkFacilityAssigment(userRoles);
    });

    function disableDuplicatingErrorMessages(message1, message2) {
        if (
            (!message1.hasClass('not-active')) &&
            (!message2.hasClass('not-active'))
        ) {
            message2.addClass('not-active')
        }
    }

    function checkFirstName() {

        refreshToDefault(inputFirstName, titleFirstName);

        var pattern = /[A-Za-z]{2,}/,
            firstName = inputFirstName.val();

        if (firstName !== '') {
            successCase(firstNameRequired, titleFirstName);
            if (pattern.test(firstName)) {
                successCase(firstNameMustContain, titleFirstName);
            } else {
                errorCase(firstNameMustContain, inputFirstName, titleFirstName);
                errorFirstName = true;
            }
        } else {
            errorCase(firstNameRequired, inputFirstName, titleFirstName);
            errorFirstName = true;
        }

        disableDuplicatingErrorMessages(firstNameRequired, firstNameMustContain);
    };

    function checkLastName() {

        refreshToDefault(inputLastName, titleLastName);

        var pattern = /[A-Za-z]{2,}/,
            lastName = inputLastName.val();

        if (lastName !== '') {
            successCase(lastNameRequired, titleLastName);
            if (pattern.test(lastName)) {
                successCase(lastNameMustContain, titleLastName);
            } else {
                refreshToDefault(inputLastName, titleLastName);
                errorCase(lastNameMustContain, inputLastName, titleLastName);
                errorLastName = true;
            }
        } else {
            errorCase(lastNameRequired, inputLastName, titleLastName);
            errorLastName = true;
        }

        disableDuplicatingErrorMessages(lastNameRequired, lastNameMustContain);
    };

    function checkEmail() {

        if(allowChangeUserNameFromEmail){
            inputUserName.val(inputEmail.val());
        }

        refreshToDefault(inputEmail, titleEmail);

        var pattern = /^([\w-\._%+-]+@([\w-]+\.)+[\w-]{2,4})?$/,
            email = inputEmail.val();

        if (email !== '') {
            successCase(emailRequired, titleEmail);

            if (pattern.test(email)) {
                successCase(emailCheck, titleEmail);
            } else {
                refreshToDefault(inputEmail, titleEmail);
                errorCase(emailCheck, inputEmail, titleEmail);
                errorEmail = true;
            }

        } else {
            errorCase(emailRequired, inputEmail, titleEmail);
            errorEmail = true;
        }

        checkUserName();
        disableDuplicatingErrorMessages(emailRequired, emailCheck);
    };

    function checkTel() {

        var pattern = /^([+][0-9]{11})|([0-9]{10,13})$/,
            tel = inputTel.val();

        if ((pattern.test(tel)) || (tel === '')) {
            refreshToDefault(inputTel, titleTel);
            successCase(telCheck, titleTel);
        } else {
            refreshToDefault(inputTel, titleTel);
            errorCase(telCheck, inputTel, titleTel);
        }
    };

    function checkUserName() {

        refreshToDefault(inputUserName, titleUserName);

        var pattern = /^[a-zA-Z0-9\S]{2,}$/,
            unique = true,
            userName = inputUserName.val();

        if (userName !== '') {
            successCase(userNameRequired, titleUserName);

            if (pattern.test(userName)) {
                successCase(userNameMustContain, titleUserName);
            } else {
                refreshToDefault(inputUserName, titleUserName);
                errorCase(userNameMustContain, inputUserName, titleUserName);
                errorUserName = true;
            }

            $.ajax({
                type: "POST",
                url: "/users/username/unique",
                data: JSON.stringify({
                    'user_name' : userName,
                    'user_edit_id' : (typeof global_user_edit_id !== 'undefined') ? global_user_edit_id : null
                }),
                dataType: "json",
                success: function (data) {
                    if (data.result === 'success') {
                        unique = false;
                    } else {
                        unique = true;
                    }
                }
            });

            if (unique) {
                successCase(userNameUnique, titleUserName);
            } else {
                refreshToDefault(inputUserName, titleUserName);
                errorCase(userNameUnique, inputUserName, titleUserName);
                errorUserName = true;
            }
        } else {
            errorCase(userNameRequired, inputUserName, titleUserName);
            errorUserName = true;
        }

        disableDuplicatingErrorMessages(userNameRequired, userNameMustContain);
    };

    function checkPassword() {

        if(passwordWasChanged){
            errorCase(passwordConfirmRequired, inputConfirmPassword, titleConfirmPassword);
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

            disableDuplicatingErrorMessages(passwordRequired, passwordMustContain);
        }

    };

    function checkConfirmPassword() {

        if(passwordWasChanged){
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

            disableDuplicatingErrorMessages(passwordConfirmRequired, passwordConfirmMustContain);
        }

    };

    function checkFacilityAssigment(rows) {
        if (rows.length) {
            rows.each(function(){
                var errorMessage = $(this).find('.error-message span');

                if ($(this).find('input:checked').length) {
                    if(!errorMessage.hasClass('not-active')) {
                        errorMessage.addClass('not-active');
                    }
                    if($(this).find('.facility-assignment-checkbox').hasClass('error-name')) {
                        $(this).find('.facility-assignment-checkbox').removeClass('error-name');
                    }

                } else {
                    if(errorMessage.hasClass('not-active')) {
                        errorMessage.removeClass('not-active');
                    }
                    if(!$(this).find('.facility-assignment-checkbox').hasClass('error-name')) {
                        $(this).find('.facility-assignment-checkbox').addClass('error-name');
                    }
                    errorFacilityAssigment = true;
                }
            });
        } /*else if(rows) {
            var errorMessage = rows.find('.error-message span');

            if (rows.find('input:checked').length) {
                if(!errorMessage.hasClass('not-active')) {
                    errorMessage.addClass('not-active');
                }
                if(rows.find('.facility-assignment-checkbox').hasClass('error-name')) {
                    rows.find('.facility-assignment-checkbox').removeClass('error-name');
                }

            } else {
                if(errorMessage.hasClass('not-active')) {
                    errorMessage.removeClass('not-active');
                }
                if(!rows.find('.facility-assignment-checkbox').hasClass('error-name')) {
                    rows.find('.facility-assignment-checkbox').addClass('error-name');
                }
                errorFacilityAssigment = true;
            }
        }*/
    }


    $('#user-edit-form').submit(function() {

        allowChangeUserNameFromEmail = false;

        errorFirstName = false;
        errorLastName = false;
        errorEmail = false;
        errorUserName = false;
        errorPassword = false;
        errorConfirmPassword = false;
        errorFacilityAssigment = false;

        checkFirstName();
        checkLastName();
        checkEmail();
        checkUserName();
        checkPassword();
        checkConfirmPassword();
        checkFacilityAssigment($('.basic-facility-line'));

        if(
            errorFirstName === false &&
            errorLastName === false &&
            errorEmail === false &&
            errorUserName === false &&
            errorPassword === false &&
            errorConfirmPassword === false &&
            errorFacilityAssigment === false
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
        if( message.hasClass('not-active')) {
            message.removeClass('not-active');
        }
        input.addClass('error-input');
        title.addClass('error-name');
    }

});
