$(document).ready( function () {

    var errorName               = false,
        errorStreet             = false,
        errorZip                = false,
        errorCity               = false,
        errorRoles              = false,
        errorFacilityAssigment  = false,

        nameRequired            = $('#error-name-message-required'),
        nameMustContain         = $('#error-name-message-must-contain'),
        streetRequired          = $('#error-street-message-required'),
        streetMustContain       = $('#error-street-message-must-contain'),
        zipRequired             = $('#error-zip-message-required'),
        zipMustContain          = $('#error-zip-message-must-contain'),
        cityRequired            = $('#error-city-message-required'),
        cityMustContain         = $('#error-city-message-must-contain'),

        inputName               = $('#form-name'),
        inputStreet             = $('#form-street'),
        inputZip                = $('#form-zip'),
        inputCity               = $('#form-city'),

        titleName               = $('#title-name'),
        titleStreet             = $('#title-street'),
        titleZip                = $('#title-zip'),
        titleCity               = $('#title-city');

    inputName.focusout(function () {
        checkName();
    });
    inputStreet.focusout(function(){
        checkStreet();
    });
    inputZip.focusout(function(){
        checkZip();
    });
    inputCity.focusout(function(){
        checkCity();
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

    function checkName() {

        refreshToDefault(inputName, titleName);

        var pattern = /^.{2,}$/,
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

        disableDuplicatingErrorMessages(nameRequired, nameMustContain);
    }

    function checkStreet() {

        refreshToDefault(inputStreet, titleStreet);

        var pattern = /^[a-zA-Z\x7f-\xff0-9\s,.'" ]{3,}$/,
            street = inputStreet.val();

        if (street !== '') {
            successCase(streetRequired, titleStreet);
            if (pattern.test(street)) {
                successCase(streetMustContain, titleStreet);
            } else {
                refreshToDefault(inputStreet, titleStreet);
                errorCase(streetMustContain, inputStreet, titleStreet);
                errorStreet = true;
            }
        } else {
            errorCase(streetRequired, inputStreet, titleStreet);
            errorStreet = true;
        }

        disableDuplicatingErrorMessages(streetRequired, streetMustContain);
    }

    function checkZip() {

        refreshToDefault(inputZip, titleZip);

        var pattern = /^[a-zA-Z0-9\s]{4,8}$/,
            zip = inputZip.val();

        if (zip !== '') {
            successCase(zipRequired, titleZip);
            if (pattern.test(zip)) {
                successCase(zipMustContain, titleZip);
            } else {
                refreshToDefault(inputZip, titleZip);
                errorCase(zipMustContain, inputZip, titleZip);
                errorZip = true;
            }
        } else {
            errorCase(zipRequired, inputZip, titleZip);
            errorZip = true;
        }

        disableDuplicatingErrorMessages(zipRequired, zipMustContain);
    }

    function checkCity() {

        refreshToDefault(inputCity, titleCity);

        var pattern = /^[a-zA-Z\s\x7f-\xff]{2,}$/,
            city = inputCity.val();

        if (city !== '') {
            successCase(cityRequired, titleCity);
            if (pattern.test(city)) {
                successCase(cityMustContain, titleCity);
            } else {
                refreshToDefault(inputCity, titleCity);
                errorCase(cityMustContain, inputCity, titleCity);
                errorCity = true;
            }
        } else {
            errorCase(cityRequired, inputCity, titleCity);
            errorCity = true;
        }

        disableDuplicatingErrorMessages(cityRequired, cityMustContain);
    }

    function checkUsersAssignedRoles() {
        var table = $('.facility-assignment'),
            roles = table.find('.facility-assignment-checkbox input:checked');

        if (roles.length === 0) {
            errorRoles = true;
            table.find('#error-user-assignment-message-required').removeClass('not-active');
        } else {
            table.find('#error-user-assignment-message-required').addClass('not-active');
        }
    }

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

    $('#tenant-edit-form').submit(function() {

        errorName = false;
        errorStreet = false;
        errorZip = false;
        errorCity = false;
        errorFacilityAssigment = false;

        checkName();
        checkStreet();
        checkZip();
        checkCity();
        checkFacilityAssigment($('.basic-facility-line'));

        if(
            errorName === false &&
            errorStreet === false &&
            errorZip === false &&
            errorCity === false &&
            errorFacilityAssigment === false
        ) {
            return true;
        } else {
            return false;
        }

    });

    $('#facility-add-form').submit(function() {

        errorName = false;
        errorStreet = false;
        errorZip = false;
        errorCity = false;
        errorRoles = false;
        errorFacilityAssigment = false;

        checkName();
        checkStreet();
        checkZip();
        checkCity();
        checkUsersAssignedRoles();
        checkFacilityAssigment($('.basic-facility-line'));

        if(
            errorName === false &&
            errorStreet === false &&
            errorZip === false &&
            errorCity === false &&
            errorRoles === false &&
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
        if (message.hasClass('not-active')) {
            message.removeClass('not-active');
        }
        input.addClass('error-input');
        title.addClass('error-name');
    }

    $('.checkbox-tenant-managers .disabled').click(function (e) {
        e.preventDefault();
    });
});
