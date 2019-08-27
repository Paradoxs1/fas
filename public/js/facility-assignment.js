$(document).ready(function() {

    addListenersToRemoveLineBtn();
    addListenersToCheckboxesAndRadio();

    var addedFacilities = [];
    addedFacilities.push($('.tenant-select option:selected').val());

    $('.btn-add-line').click(function() {
        let itemsCnt = parseInt(getNextIndex());
        let row = $('.basic-facility-line').last().clone();

        // row.find('.tenant-select option').each(function () {
        //     if (addedFacilities.includes($(this).val())) {
        //         $(this).attr('disabled', 'disabled');
        //     }
        // });

        row.find('.tenant-select option').each(function () {
            $(this).removeAttr('selected');
        });
        row.find('.facility-role').prop('checked', false);

        let rowIndex = itemsCnt + 1;
        row.find('.facility-role').each(function( index ) {
            let i = parseInt(index + 1);
            $(this).attr('id', 'facility-row-' + rowIndex + '-item-' + i);
            $(this).attr('name', 'fas_tenant_account[accountFacilityRoles][' + rowIndex + '][role][]');
            $(this).parent().find('label').attr('for', 'facility-row-' + rowIndex + '-item-' + i);
        });

        row.find('.tenant-select').attr('name', 'fas_tenant_account[accountFacilityRoles][' + rowIndex + '][facility]');
        row.find('.remove-facility-row').show();

        $('.basic-facility-line').last().after(row);


        addFacilitiesRowsNumber(1);
        addListenersToRemoveLineBtn();
        addListenersToCheckboxesAndRadio();

    });

    function getNextIndex() {
        return $('.facility-assignment').attr('data-count');
    }

    function addFacilitiesRowsNumber(number) {
        var currentIndex = parseInt(getNextIndex());
        $('.facility-assignment').attr('data-count', currentIndex+number);
    }

    function addListenersToRemoveLineBtn() {

        $('.btn-remove-line').click(function() {
            $(this).parents('tr').remove();
        });

    };

    function addListenersToCheckboxesAndRadio() {

        $('.facility-assignment input:checkbox').click(function() {
            $(this).parent('td').parent().find('input:radio').prop('checked', false);
        });

        $('.facility-assignment input:radio').click(function() {
            $(this).parent('td').parent().find('input:checkbox').prop('checked', false);
        });

    };

});
