$(function() {
    initDatePickers();
    initCronType();
});

function initDatePickers()
{
    $('#nextrunselector').datetimepicker({format: 'L LTS'});
    $('#lastrunselector').datetimepicker({format: 'L LTS'});
}

function initCronType()
{
    $('.crontype-item').on('click', function() {
        let type = $(this).data('type');
        $('.crontype').val(type);
        $('.crontype-inputs').hide();
        $('.crontype-' + type).show();
    })
}