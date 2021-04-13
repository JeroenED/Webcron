$(function() {
    initDatePickers();
    initCronType();
    initSecretInputs();
    initDelayPattern();
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
    })
}

function initSecretInputs()
{
    $('.addsecret-btn').on('click', function() {
        $('.secret-group:first-child').clone().appendTo('.secrets').removeClass('hidden');
        $('.secrets-description').removeClass('hidden');
    })
}


function initDelayPattern()
{
    $('.delaypattern-item').on('click', function() {
        let time = $(this).data('time');
        $('#delay').val(time);
    })
}