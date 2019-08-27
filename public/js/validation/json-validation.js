$(document).ready(function() {

    var errorConfiguration = false,
        errorConfigurationJsonLength = false,
        configurationMustContain = $('#error-configuration-message-must-contain'),
        configurationExceededChars = $('#error-configuration-message-exceeded-chars'),
        inputConfiguration =  $('#facility_layout_params'),
        titleConfiguration =  $('#enable-interface-title'),
        testConnectionLink = $('.test-connection-link'),
        allowJsonFormatting = false,
        maxNumberOfJsonChars = 1000;

    inputConfiguration.on('keyup focusout', function () {
        allowJsonFormatting = true;
        checkConfiguration();
    });

    allowJsonFormatting = true;
    checkConfiguration();

    function checkConfiguration() {

        refreshToDefault(inputConfiguration, titleConfiguration);

        var configuration = inputConfiguration.val(),
            isJson = true;

        try {
            JSON.parse(configuration);
        } catch(e) {
            isJson = false;
        }

        if (isJson) {
            successCase(configurationMustContain, titleConfiguration);
            if(testConnectionLink.hasClass('not-active')) {
                testConnectionLink.removeClass('not-active');
            };
            if(allowJsonFormatting) {
                var objJsonVal = JSON.parse(configuration),
                    newJsonVal = JSON.stringify(objJsonVal, undefined, 4);
                inputConfiguration.val(newJsonVal);
                allowJsonFormatting = false;
            }
            if (inputConfiguration.val().length <= maxNumberOfJsonChars) {
                successCase(configurationExceededChars, titleConfiguration);
                if(testConnectionLink.hasClass('not-active')) {
                    testConnectionLink.removeClass('not-active');
                }
            } else {
                refreshToDefault(inputConfiguration, titleConfiguration);
                errorCase(configurationExceededChars, inputConfiguration, titleConfiguration);
                errorConfigurationJsonLength = true;
                if(!testConnectionLink.hasClass('not-active')) {
                    testConnectionLink.addClass('not-active');
                };
            }
        } else {
            refreshToDefault(inputConfiguration, titleConfiguration);
            errorCase(configurationMustContain, inputConfiguration, titleConfiguration);
            errorConfiguration = true;
            if(!testConnectionLink.hasClass('not-active')) {
                testConnectionLink.addClass('not-active');
            }
        }
    };

    if ($('#facility_layout_params').length) {
        $('#facility-configuration-form').submit(function() {
            errorConfiguration = false;
            errorConfigurationJsonLength = false;

            checkConfiguration();

            if(
                (errorConfiguration === false) &&
                (errorConfigurationJsonLength === false)
            ) {
                return true;
            } else {
                return false;
            }

        });
    }

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
    };

    function successCase (message, title) {
        if(!message.hasClass('not-active')) {
            message.addClass('not-active');
        }
        title.addClass('success');
    };

    function errorCase (message, input, title) {
        message.removeClass('not-active');
        input.addClass('error-input');
        title.addClass('error-name');
    };

});
