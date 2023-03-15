import {Modal} from 'bootstrap';
import image from '/assets/images/ajax-loader.gif'
import '/assets/scss/job/index.scss';
import Utils from "./Utils";
import customDateFormat from '@eonasdan/tempus-dominus/dist/plugins/customDateFormat';
import {DateTime,TempusDominus,extend} from "@eonasdan/tempus-dominus";

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        initDeleteButtons();
        initRunButtons();
        initTimepicker();
        Utils.initTags();
    }
});

function initDeleteButtons() {
    document.querySelectorAll('.delete-btn').forEach(elem => elem.addEventListener("click", event => {
        let me = event.currentTarget;
        let href = me.dataset.href;
        let confirmation = me.dataset.confirmation;

        if(confirm(confirmation)) {
            fetch(href, { method: 'DELETE' })
                .then(response => response.json())
                .then(data => {
                window.location.href = data.return_path
            })
        }
    }));
}

var selecttimedatepicker;
function initTimepicker() {
    extend(customDateFormat);
    let modal = document.querySelector('#run_selecttime');
    let datepickeroptions = Utils.timepickerOptions;
    datepickeroptions.display.inline = true;
    datepickeroptions.display.sideBySide = true;
    datepickeroptions.restrictions = {
        minDate: new Date()
    };
    selecttimedatepicker = new TempusDominus(document.querySelector('#selecttime_datepicker'), datepickeroptions);
}
function initRunButtons() {
    document.querySelectorAll('.run').forEach(elem => elem.addEventListener("click", event => {
        let me = event.currentTarget;
        let norun = me.closest('tr').classList.contains('norun')
        let maxdate = new DateTime(me.dataset.nextrun)
        if (maxdate < new DateTime() ) {
            if (norun) {
                maxdate = undefined;
            } else {
                console.error('You cannot have to be run jobs in the past');
                return;
            }
        }
        selecttimedatepicker.updateOptions({
            restrictions: {
                maxDate: maxdate
            }
        })
        selecttimedatepicker.viewDate = new DateTime();
        var bsModal = new Modal('#run_selecttime');
        bsModal.show();

        let schedulefn = event => {
            bsModal.hide();
            let time = Math.floor(selecttimedatepicker.viewDate / 1000);
            run(me, time);
        }
        let runnowfn = event => {
            bsModal.hide();
            run(me);
        }
        let closebtnfn = event => {
            bsModal.hide();
            document.querySelectorAll('.schedule').forEach(elem => elem.removeEventListener("click", schedulefn));
            document.querySelectorAll('.run-now').forEach(elem => elem.removeEventListener("click",runnowfn));
        }
        document.querySelectorAll('.schedule').forEach(elem => elem.addEventListener("click", schedulefn, { once: true } ));
        document.querySelectorAll('.run-now').forEach(elem => elem.addEventListener("click", runnowfn, { once: true } ));
        document.querySelectorAll('.btn-close').forEach(elem => elem.addEventListener("click", closebtnfn ));
    } ));
}
function run(elem, time = 0) {
    let href = elem.dataset.href;
    if (time > 0) href = href + '/' + time.toString();

    let runCnt = document.querySelector('.run-content');
    if(runCnt.querySelector('img') === null) {
        let loaderImg = document.createElement('img');
        loaderImg.src = image;
        runCnt.appendChild(loaderImg);
    }
    document.querySelector('.container-fluid').classList.add('blur');
    document.querySelector('.run-overlay').classList.add('d-block');
    document.querySelector('.run-overlay').classList.remove('d-none');

    fetch(href, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            let modal = document.querySelector('#run_result');
            modal.querySelector('.modal-title').innerHTML = data.title;
            if (data.status == 'deferred') {
                modal.querySelector('.modal-body').innerHTML = data.message;
                elem.classList.add('disabled');
                let td = elem.closest('td');
                td.querySelectorAll('.btn').forEach(btn => {
                    btn.classList.add('btn-outline-success');
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.remove('btn-outline-danger');
                })


                let tr = elem.closest('tr');
                tr.classList.add('running');
                tr.classList.add('text-success');
                tr.classList.remove('norun');
                tr.classList.remove('text-danger');
            } else if (data.status == 'ran') {
                let content = '<p>' + data.message + '</p>'
                content += '<pre>' + data.output + '</pre>'

                modal.querySelector('.modal-body').innerHTML = content;
            }

            var runModal = new Modal('#run_result');
            runModal.show();

            document.querySelector('.container-fluid').classList.remove('blur');
            document.querySelector('.run-overlay').classList.remove('d-block');
            document.querySelector('.run-overlay').classList.add('d-none');
        })
}