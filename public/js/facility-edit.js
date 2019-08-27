$(document).ready(function() {
   if ($('#routine-template-id').length > 0) {
       $('select[name="facility[routineTemplate]"] option[value="' + $('#routine-template-id').val() + '"]').attr('selected', 'selected');
       $('select[name="facility[routineTemplate]"] option:not(:selected)').attr('disabled', 'disabled');
   }
});
