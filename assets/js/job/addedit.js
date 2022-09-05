import 'bootstrap';
import moment from 'moment';
import * as tempusDominus from '@eonasdan/tempus-dominus/dist/js/tempus-dominus';
import customDateFormat from '@eonasdan/tempus-dominus/dist/plugins/customDateFormat'
import Utils from "./Utils";

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        initDatePickers();
        initCronType();
        initHostType();
        initContainerType();
        initVarInputs();
        initRangeInput();
        initIntervalPattern();
        initEternalCheckbox();
        Utils.initTags();
    }
});

const timepickerOptions = {
    localization:{
        locale: 'nl',
        format: 'dd/MM/yyyy HH:mm:ss'
    },
    display: {
        icons: {
            time: 'icon-clock-o',
            date: 'icon-calendar',
            up: 'icon-arrow-up',
            down: 'icon-arrow-down',
            previous: 'icon-chevron-left',
            next: 'icon-chevron-right',
            today: 'icon-calendar-check-o',
            clear: 'icon-delete',
            close: 'icon-x',
        },
        components: {
            seconds: true,
            useTwentyfourHour: true
        }
    },
}
function initDatePickers()
{
    tempusDominus.extend(customDateFormat);
    new tempusDominus.TempusDominus(document.querySelector('#nextrunselector'), timepickerOptions);
    new tempusDominus.TempusDominus(document.querySelector('#lastrunselector'), timepickerOptions);
}

function initCronType()
{
    let crontypegroupbtn = document.querySelector('.crontype-group button');
    crontypegroupbtn.dataset.defaultText = crontypegroupbtn.innerHTML.trim();

    document.querySelectorAll('.crontype-item').forEach(elem => {
        elem.addEventListener('click', event => {
            let type = event.target.dataset.type;
            document.getElementById('crontypeButton').innerText = event.target.innerText;
            document.querySelector('.crontype').value = type;
            document.querySelectorAll('.crontype-inputs:not(.d-none)').forEach(elem => elem.classList.add('d-none'));
            document.querySelector('.crontype-' + type).classList.remove('d-none');

            document.querySelectorAll('.crontype-inputs:not(.d-none) input').forEach(elem => elem.disabled = false);
            document.querySelectorAll('.crontype-inputs.d-none input').forEach(elem => elem.disabled = true);

            if (type == 'http') {
                document.querySelectorAll('.croncategory-group:not(.crontype-group) button').forEach(elem => elem.innerText = elem.dataset.defaultText)

                document.querySelectorAll('.croncategory-group').forEach(elem => elem.classList.remove('btn-group'));
                document.querySelectorAll('.croncategory-group:not(.crontype-group)').forEach(elem => elem.classList.add('d-none'));
                document.querySelectorAll('.croncategory-inputs:not(.crontype-inputs)').forEach(elem => elem.classList.add('d-none'));

                document.querySelectorAll('.croncategory-inputs:not(.d-none) input').forEach(elem => elem.disabled = false);
                document.querySelectorAll('.croncategory-inputs.d-none input').forEach(elem => elem.disabled = true);
            }
            if (type == 'reboot') {
                if (document.querySelector('#btn-group-discriminator') === null) {
                    let discriminator = document.createElement('div');
                    discriminator.classList.add('d-none');
                    discriminator.id = 'btn-group-discriminator';
                    document.querySelector('body').append(discriminator);
                }
                document.querySelectorAll('.croncategory-group.containertype-group button').forEach(elem =>  elem.innerText = elem.dataset.defaultText)

                document.querySelectorAll('.croncategory-group').forEach(elem => elem.classList.add('btn-group'))
                document.querySelectorAll('.croncategory-group').forEach(elem => elem.classList.remove('d-none'))

                if(document.querySelector('#btn-group-discriminator') !== null) {
                    document.querySelector('#btn-group-discriminator').append(document.querySelector('.containertype-group'));
                }

                let containergroupselect = document.querySelector('.croncategory-selector .containertype-group');
                if (containergroupselect !== null) containergroupselect.remove();

                document.querySelectorAll('.croncategory-group.containertype-group').forEach(elem => elem.classList.add('d-none'))
                document.querySelectorAll('.croncategory-inputs.containertype-inputs').forEach(elem => elem.classList.add('d-none'))

                document.querySelectorAll('.croncategory-inputs:not(.d-none) input').forEach(elem => elem.disabled = false)
                document.querySelectorAll('.croncategory-inputs.d-none input').forEach(elem => elem.disabled = true)
            }
            if (type == 'command') {
                if (document.querySelector('#btn-group-discriminator .containertype-group') !== null) {
                    document.querySelector('.croncategory-selector').append(document.querySelector('#btn-group-discriminator .containertype-group'));
                }
                document.querySelectorAll('.croncategory-group').forEach(elem => elem.classList.add('btn-group'));
                document.querySelectorAll('.croncategory-group').forEach(elem => elem.classList.remove('d-none'));
            }
        })
    });
}

function initHostType()
{

    document.querySelector('.hosttype-group button').dataset.defaultText = document.querySelector('.hosttype-group button').innerHTML.trim();
    document.querySelectorAll('.hosttype-item').forEach(elem => {
        elem.addEventListener('click', event => {

            document.querySelector('#hosttypeButton').innerHTML  = event.target.innerHTML;
            let type = event.target.dataset.type;
            document.querySelector('.hosttype').value = type;
            document.querySelectorAll('.hosttype-inputs:not(.d-none)').forEach(elem => elem.classList.add('d-none'));
            document.querySelectorAll('.hosttype-' + type).forEach(elem => elem.classList.remove('d-none'));

            document.querySelectorAll('.hosttype-inputs:not(.d-none) input').forEach(elem => elem.disabled = false);
            document.querySelectorAll('.hosttype-inputs.d-none input').forEach(elem => elem.disabled = true);
        })
    })

    if (document.querySelector('.privkey-keep') !== null) {
        document.querySelector('.privkey-keep').addEventListener('click', event => {
            document.querySelector('#privkey').disabled = event.target.checked
        })
    }
}

function initContainerType()
{

    document.querySelector('.containertype-group button').dataset.defaultText = document.querySelector('.containertype-group button').innerHTML.trim();
    document.querySelectorAll('.containertype-item').forEach(elem => {
        elem.addEventListener('click', event => {

            document.querySelector('#containertypeButton').innerHTML = event.target.innerHTML;
            let type = event.target.dataset.type;
            document.querySelector('.containertype').value = type;


            document.querySelectorAll('.containertype-inputs:not(.d-none)').forEach(elem => elem.classList.add('d-none'));
            document.querySelectorAll('.containertype-' + type).forEach(elem => elem.classList.remove('d-none'));

            document.querySelectorAll('.containertype-inputs:not(.d-none) input').forEach(elem => elem.disabled = false);
            document.querySelectorAll('.containertype-inputs.d-none input').forEach(elem => elem.disabled = true);
        })
    })
}

function initRangeInput() {
    document.querySelector('.range-input-fail-pct').addEventListener('input', event => {
        document.querySelector('.range-value-fail-pct').innerHTML = event.target.value +  '%';
    })
}

function initVarInputs()
{
    document.querySelector('.addvar-btn').addEventListener('click', event => {
        let index = document.querySelectorAll('.var-group').length;
        let group = document.querySelector('.var-group');

        let newgroup = group.cloneNode(true);
        newgroup.classList.remove('d-none');
        newgroup.dataset.index = index;
        newgroup.querySelector('.var-issecret').name = 'var-issecret[' + index + ']';
        newgroup.querySelector('.var-issecret').addEventListener('click', handleSecretCheckbox);
        newgroup.querySelector('.var-id').name = 'var-id[' + index + ']';
        newgroup.querySelector('.var-value').name = 'var-value[' + index + ']';

        document.querySelector('.vars').append(newgroup);
        document.querySelector('.vars-description').classList.remove('d-none');
    })
    document.querySelectorAll('.var-issecret').forEach(elem => elem.addEventListener('click', handleSecretCheckbox));
}

function handleSecretCheckbox(event) {
    let ischecked = event.target.checked;
    event.target.closest('.var-group').querySelector('.var-value').type = ischecked ? 'password' : 'text';
}

function initEternalCheckbox() {
    document.querySelector('.lastrun-eternal').addEventListener('click', event => {
        let nextrunselector = document.querySelector('#lastrunselector');
        nextrunselector.disabled = event.target.checked;
        nextrunselector.placeholder = event.target.checked ? '' : nextrunselector.dataset.placeholder;
        nextrunselector.value = '';
    })
}

function initIntervalPattern()
{
    document.querySelectorAll('.intervalpattern-item').forEach(elem => elem.addEventListener('click', event => {
        let time = event.target.dataset.time;
        document.querySelector('#interval').value = time;
    }));
}