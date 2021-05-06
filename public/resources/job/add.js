$(function() {
    initDatePickers();
    initCronType();
    initVarInputs();
    initIntervalPattern();
    bsCustomFileInput.init()
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
        $('.crontype-inputs:not(.hidden)').addClass('hidden');
        $('.crontype-' + type).removeClass('hidden');

        $('.crontype-inputs:not(.hidden) input').prop('disabled', false);
        $('.crontype-inputs.hidden input').prop('disabled', true);
    })
}

function initVarInputs()
{
    $('.addvar-btn').on('click', function() {
        let index = $('.var-group').length;
        $('.var-group:first-child').clone().appendTo('.vars').removeClass('hidden');
        $('.var-group:last-child').data({index: index});
        $('.var-group:last-child .var-issecret').prop('name', 'var-issecret[' + index + ']');
        $('.var-group:last-child .var-id').prop('name', 'var-id[' + index + ']');
        $('.var-group:last-child .var-value').prop('name', 'var-value[' + index + ']');
        $('.vars-description').removeClass('hidden');
    })
    $(document).on('click', '.var-issecret', function() {
        let ischecked = $(this).prop('checked');
        $(this).parents('.var-group').find('.var-value').prop('type', ischecked ? 'password' : 'text');

    })
}


function initIntervalPattern()
{
    $('.intervalpattern-item').on('click', function() {
        let time = $(this).data('time');
        $('#interval').val(time);
    })
}