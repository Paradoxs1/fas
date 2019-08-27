$(document).ready(function() {

    var idValue = 1,
        userAssigned = [],
        facilityAssignment = [],
        prevValueSelect,
        currentValueSelect,
        cloneOption,
        lastSelect;

    function countrySelectDisabled() {
        $('#country-select option').removeAttr('disabled').each(function () {
            if ($(this).val() === $('#country-select').val()) {
                $(this).attr('disabled', 'disabled');
            }
        });
    }

    $('#country-select option[value="'+ $('#country-select').val() +'"]').attr('selected', 'selected');

    userAssigned.push($('select.tenant-select option:selected').val());

    $('.facility-assignment select').each(function () {
        facilityAssignment[$(this).attr('name')] = $(this).find('option:selected').val();
    });

    addListenersToCheckboxesAndRadio();

    $('.btn-add-line').click(function() {

        idValue = $('.facility-assignment .facility-assignment-checkbox:last input').attr('id');
        idValue = +idValue.slice(idValue.lastIndexOf('-') + 1) + 1;

        $('.tenant-select option').each(function () {
            if (userAssigned.includes($(this).val())) {
                $(this).attr('disabled', 'disabled');
            }
        });

        var restaurant_user_select = $($('.facility-assignment .tenant-select')[0]).clone();

        restaurant_user_select.find('option').each(function () {
            $(this).removeAttr('selected');
        });

        if ($(this).prev().hasClass('facility-assignment-edit')) {
            restaurant_user_select.find('option:disabled').remove();
        }

        var restaurant_user_select_options = restaurant_user_select.html();

        var tenant_manager_id = 'tenant-manager-'+ idValue,
            tenant_manager_name = 'roles[tenant-manager]['+ idValue +']',

            tenant_user_id = 'tenant-user-'+ idValue,
            tenant_user_name = 'roles[tenant-user]['+ idValue +']',

            stakeholder_id = 'stakeholder-'+ idValue,
            stakeholder_name = 'roles[stakeholder]['+ idValue +']',

            facility_manager_id = 'facility-manager-'+ idValue,
            facility_manager_name = 'roles[facility-manager-user]['+ idValue +']',

            facility_user_id = 'facility-user-'+ idValue,
            facility_user_name = 'roles[facility-manager-user]['+ idValue +']';

        $('.facility-assignment').children('tbody').append("" +
            "                         <tr class=\"new-facility-line basic-facility-line\">\n" +
            "                            <td>\n" +
            "                                <select class=\"fas-select tenant-select\" name=\"user-to-assign["+ idValue +"]\">\n" +
                                                restaurant_user_select_options +
            "                                </select>\n" +
            "                            </td>\n" +
            "                            <td class=\"facility-assignment-checkbox\">\n" +
            "                                <input id="+ tenant_manager_id +" type=\"checkbox\" name="+ tenant_manager_name +">\n" +
            "                                <label for="+ tenant_manager_id +">\n" +
            "                                    <i class=\"far fa-square\"></i>\n" +
            "                                    <i class=\"far fa-check-square\"></i>\n" +
            "                                </label>\n" +
            "                            </td>\n" +
            "                            <td class=\"facility-assignment-checkbox\">\n" +
            "                                <input id="+ tenant_user_id +" type=\"checkbox\" name="+ tenant_user_name +">\n" +
            "                                <label for="+ tenant_user_id +">\n" +
            "                                    <i class=\"far fa-square\"></i>\n" +
            "                                    <i class=\"far fa-check-square\"></i>\n" +
            "                                </label>\n" +
            "                            </td>\n" +
            "                            <td class=\"facility-assignment-checkbox\">\n" +
            "                                <input id="+ stakeholder_id +" type=\"checkbox\" name="+ stakeholder_name +">\n" +
            "                                <label for="+ stakeholder_id +">\n" +
            "                                    <i class=\"far fa-square\"></i>\n" +
            "                                    <i class=\"far fa-check-square\"></i>\n" +
            "                                </label>\n" +
            "                            </td>\n" +
            "                            <td class=\"facility-assignment-checkbox\">\n" +
            "                                <input id="+ facility_manager_id +" type=\"radio\" name="+ facility_manager_name + " value=fm>\n" +
            "                                <label for="+ facility_manager_id +">\n" +
            "                                    <i class=\"far fa-circle\"></i>\n" +
            "                                    <i class=\"far fa-check-circle\"></i>\n" +
            "                                </label>\n" +
            "                            </td>\n" +
            "                            <td class=\"facility-assignment-checkbox\">\n" +
            "                                <input id="+ facility_user_id +" type=\"radio\" name="+ facility_user_name +" value=fu>\n" +
            "                                <label for="+ facility_user_id +">\n" +
            "                                    <i class=\"far fa-circle\"></i>\n" +
            "                                    <i class=\"far fa-check-circle\"></i>\n" +
            "                                </label>\n" +
            "                            </td>\n" +
                                        `<td class="error-message">
                                            <span class="not-active">
                                                Please assign at least one user to facility
                                            </span>
                                        </td>`+
            "                            <td>\n" +
            "                                <div class=\"btn-remove-line\">\n" +
            "                                    <i class=\"fas fa-minus-circle\"></i>\n" +
            "                                    <span>Remove User</span>\n" +
            "                                </div>\n" +
            "                            </td>\n" +
            "                        </tr>");

        addListenersToCheckboxesAndRadio();
        addListenersToSelectUser();

        //Hide button
        if ($('.facility-assignment select:first option').length < 3) {
            $(this).css('display', 'none');
        }

        if ($('.btn-remove-line').length  == 2) {
            $('.btn-remove-line').css('visibility', 'visible');
        }

        $('.facility-assignment select:last option:disabled').attr('selected', 'selected').val();
        $('.facility-assignment select option:not(:selected):disabled').remove();

        lastSelect = $('.facility-assignment select:last');
        facilityAssignment[lastSelect.attr('name')] = lastSelect.find('option:defined').val();

        countrySelectDisabled();
    });

    $('body').on('click', '.btn-remove-line', function() {
        var tr = $(this).parents('tr');
        cloneOption = tr.find('select option:disabled').removeAttr('selected').clone();

        delete facilityAssignment[tr.find('select').attr('name')];

        $(this).parents('tr').remove();

        $('.facility-assignment select').append(cloneOption);

        //if One left

        if ($('.facility-initial-line').length === 0) {
            if ($('.btn-remove-line').length  === 1) {
                $('.btn-remove-line').css('visibility', 'hidden');
            }
        }

        userAssigned = [];

        $('select.tenant-select').each(function () {
            userAssigned.push($(this).find('option:selected').val());
        });

        $('select.tenant-select option').each(function () {
            if (userAssigned.includes($(this).val())) {
                $(this).attr('disabled', 'disabled');
            } else {
                $(this).removeAttr('disabled');
            }
        });

        //Show button
        $('.btn-add-line').css('display', 'inline-block');
    });

    function addListenersToCheckboxesAndRadio() {

        $('.facility-assignment input:checkbox').click(function() {
            $(this).parent('td').parent().find('input:radio').prop('checked', false);
        });

        $('.facility-assignment input:radio').click(function() {
            $(this).parent('td').parent().find('input:checkbox').prop('checked', false);
        });
    }

    function addListenersToSelectUser() {
        userAssigned = [];

        $('select.tenant-select').each(function () {
            userAssigned.push($(this).find('option:selected').val());
        });

        $('select.tenant-select').change(function () {
            userAssigned = [];

            $('select.tenant-select').each(function () {
                userAssigned.push($(this).find('option:selected').val());
            });
        });

        $('select.tenant-select option').each(function () {
            if (userAssigned.includes($(this).val())) {
                $(this).attr('disabled', 'disabled');
            } else {
                $(this).removeAttr('disabled');
            }
        });

        $('#country-select option').each(function () {
            if ($(this).attr('selected')) {
                $(this).attr('disabled', 'disabled');
            }
        });
    }

    addListenersToSelectUser();

    $(document).on('change', '.facility-assignment select.tenant-select', function () {
        prevValueSelect = facilityAssignment[$(this).attr('name')];
        currentValueSelect = $(this).val();
        facilityAssignment[$(this).attr('name')] = currentValueSelect;

        $(this).find('option[value='+ prevValueSelect +']').removeAttr('disabled');
        $(this).find('option[value='+ currentValueSelect +']').attr('disabled', 'disabled');

        cloneOption = $(this).find('option[value='+ prevValueSelect +']').clone();

        $('.facility-assignment select:not([name="'+ $(this).attr('name') +'"])').each(function () {
            $(this).find('option[value='+ currentValueSelect +']').remove();
        }).append($(cloneOption).removeAttr('selected'));
    });

    $(document).on('change', '#country-select', function () {
        countrySelectDisabled();
    });

    $('form').bind('submit', function () {
        $(this).find('select option').prop('disabled', false);
    });

    $('.facility-assignment select option:not(:selected):disabled').remove();
});
