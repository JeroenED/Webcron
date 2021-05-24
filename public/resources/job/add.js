$(function() {
    initDatePickers();
    initCronType();
    initHostType();
    initContainerType();
    initVarInputs();
    initIntervalPattern();
    bsCustomFileInput.init()
});

function initDatePickers()
{
    $('#nextrunselector').datetimepicker({format: 'DD/MM/YYYY HH:mm:ss'});
    $('#lastrunselector').datetimepicker({format: 'DD/MM/YYYY HH:mm:ss'});
}

function initCronType()
{
    $('.crontype-group button').data('default-text', $('.crontype-group button').html());
    $('.crontype-item').on('click', function() {
        let type = $(this).data('type');
        $('#crontypeButton').html($(this).html());
        $('.crontype').val(type);
        $('.crontype-inputs:not(.hidden)').addClass('hidden');
        $('.crontype-' + type).removeClass('hidden');

        $('.crontype-inputs:not(.hidden) input').prop('disabled', false);
        $('.crontype-inputs.hidden input').prop('disabled', true);

        if(type != 'http') {
            $('.croncategory-group').addClass('btn-group');
            $('.croncategory-group').removeClass('hidden');
        } else {
            $('.croncategory-group:not(.crontype-group) button').each(function() {
                $(this).html($(this).data('default-text'))
            })

            $('.croncategory-group').removeClass('btn-group');
            $('.croncategory-group:not(.crontype-group)').addClass('hidden');
            $('.croncategory-inputs:not(.crontype-inputs)').addClass('hidden');

            $('.croncategory-inputs:not(.hidden) input').prop('disabled', false);
            $('.croncategory-inputs.hidden input').prop('disabled', true);
        }
    })
}
function initContainerType()
{

    $('.containertype-group button').data('default-text', $('.containertype-group button').html());
    $('.containertype-item').on('click', function() {

        $('#containertypeButton').html($(this).html());
        let type = $(this).data('type');
        $('.containertype').val(type);
        $('.containertype-inputs:not(.hidden)').addClass('hidden');
        $('.containertype-' + type).removeClass('hidden');

        $('.containertype-inputs:not(.hidden) input').prop('disabled', false);
        $('.containertype-inputs.hidden input').prop('disabled', true);
    })
}

function initHostType()
{

    $('.hosttype-group button').data('default-text', $('.hosttype-group button').html());
    $('.hosttype-item').on('click', function() {

        $('#hosttypeButton').html($(this).html());
        let type = $(this).data('type');
        $('.hosttype').val(type);
        $('.hosttype-inputs:not(.hidden)').addClass('hidden');
        $('.hosttype-' + type).removeClass('hidden');

        $('.hosttype-inputs:not(.hidden) input').prop('disabled', false);
        $('.hosttype-inputs.hidden input').prop('disabled', true);
    })

    $('.privkey-keep').on('click', function() {
        $('#privkey').prop('disabled', $(this).prop('checked'));
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