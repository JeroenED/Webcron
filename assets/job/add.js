import 'tempusdominus-bootstrap-4';
import bsCustomFileInput from 'bs-custom-file-input';
import 'bootstrap';

$(function() {
    initDatePickers();
    initCronType();
    initHostType();
    initContainerType();
    initVarInputs();
    initIntervalPattern();
    initEternalCheckbox();
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
        $('.crontype-inputs:not(.d-none)').addClass('d-none');
        $('.crontype-' + type).removeClass('d-none');

        $('.crontype-inputs:not(.d-none) input').prop('disabled', false);
        $('.crontype-inputs.d-none input').prop('disabled', true);

        if(type == 'http') {
            $('.croncategory-group:not(.crontype-group) button').each(function() {
                $(this).html($(this).data('default-text'))
            })

            $('.croncategory-group').removeClass('btn-group');
            $('.croncategory-group:not(.crontype-group)').addClass('d-none');
            $('.croncategory-inputs:not(.crontype-inputs)').addClass('d-none');

            $('.croncategory-inputs:not(.d-none) input').prop('disabled', false);
            $('.croncategory-inputs.d-none input').prop('disabled', true);
        }
        if(type == 'reboot') {
            if($('#btn-group-discriminator').length == 0) {
                $('body').append('<div id="btn-group-discriminator" class="d-none">');
            }
            $('.croncategory-group.containertype-group button').each(function() {
                $(this).html($(this).data('default-text'))
            })
            $('.croncategory-group').addClass('btn-group');
            $('.croncategory-group').removeClass('d-none');

            $('#btn-group-discriminator').append($('.containertype-group'));
            $('.croncategory-selector .containertype-group').remove();
            $('.croncategory-group.containertype-group').addClass('d-none');
            $('.croncategory-inputs.containertype-inputs').addClass('d-none');

            $('.croncategory-inputs:not(.d-none) input').prop('disabled', false);
            $('.croncategory-inputs.d-none input').prop('disabled', true);
        }
        if(type == 'command') {
            if($('#btn-group-discriminator .containertype-group').length > 0) {
                $('.croncategory-selector').append($('#btn-group-discriminator .containertype-group'));
            }
            $('.croncategory-group').addClass('btn-group');
            $('.croncategory-group').removeClass('d-none');
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
        $('.containertype-inputs:not(.d-none)').addClass('d-none');
        $('.containertype-' + type).removeClass('d-none');

        $('.containertype-inputs:not(.d-none) input').prop('disabled', false);
        $('.containertype-inputs.d-none input').prop('disabled', true);
    })
}

function initHostType()
{

    $('.hosttype-group button').data('default-text', $('.hosttype-group button').html());
    $('.hosttype-item').on('click', function() {

        $('#hosttypeButton').html($(this).html());
        let type = $(this).data('type');
        $('.hosttype').val(type);
        $('.hosttype-inputs:not(.d-none)').addClass('d-none');
        $('.hosttype-' + type).removeClass('d-none');

        $('.hosttype-inputs:not(.d-none) input').prop('disabled', false);
        $('.hosttype-inputs.d-none input').prop('disabled', true);
    })

    $('.privkey-keep').on('click', function() {
        $('#privkey').prop('disabled', $(this).prop('checked'));
    })
}

function initVarInputs()
{
    $('.addvar-btn').on('click', function() {
        let index = $('.var-group').length;
        $('.var-group:first-child').clone().appendTo('.vars').removeClass('d-none');
        $('.var-group:last-child').data({index: index});
        $('.var-group:last-child .var-issecret').prop('name', 'var-issecret[' + index + ']');
        $('.var-group:last-child .var-id').prop('name', 'var-id[' + index + ']');
        $('.var-group:last-child .var-value').prop('name', 'var-value[' + index + ']');
        $('.vars-description').removeClass('d-none');
    })
    $(document).on('click', '.var-issecret', function() {
        let ischecked = $(this).prop('checked');
        $(this).parents('.var-group').find('.var-value').prop('type', ischecked ? 'password' : 'text');

    })
}

function initEternalCheckbox() {
    $('.lastrun-eternal').on('click', function() {
        $('#lastrunselector').prop('disabled', $(this).prop('checked'));
        $('#lastrunselector').prop('value', '');
    })
}

function initIntervalPattern()
{
    $('.intervalpattern-item').on('click', function() {
        let time = $(this).data('time');
        $('#interval').val(time);
    })
}