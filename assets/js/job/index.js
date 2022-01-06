import { Modal } from 'bootstrap';
import * as image from '../../images/ajax-loader.gif';

document.addEventListener("readystatechange", event => {
    if(event.target.readyState === 'complete') {
        initDeleteButtons();
        initRunNowButtons();
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

function initRunNowButtons() {
    document.querySelectorAll('.runnow').forEach(elem => elem.addEventListener("click", event => {
        let me = event.currentTarget;
        let href = me.dataset.href;

        let runnowCnt = document.querySelector('.runnow-content');
        if(runnowCnt.querySelector('img') === null) {
            let loaderImg = document.createElement('img');
            loaderImg.src = image.default;
            runnowCnt.appendChild(loaderImg);
        }
        document.querySelector('.container-fluid').classList.add('blur');
        document.querySelector('.runnow-overlay').classList.add('d-block');
        document.querySelector('.runnow-overlay').classList.remove('d-none');

        fetch(href, { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                let modal = document.querySelector('#runnow_result');
                modal.querySelector('.modal-title').innerHTML = data.title;
                if (data.status == 'deferred') {
                    modal.querySelector('.modal-body').innerHTML = data.message;
                    me.classList.add('disabled');
                    let td = me.closest('td');
                    td.querySelectorAll('.btn').forEach(btn => {
                        btn.classList.add('btn-outline-success');
                        btn.classList.remove('btn-outline-primary');
                        btn.classList.remove('btn-outline-danger');
                    })


                    let tr = me.closest('tr');
                    tr.classList.add('running');
                    tr.classList.add('text-success');
                    tr.classList.remove('norun');
                    tr.classList.remove('text-danger');
                } else if (data.status == 'ran') {
                    let content = '<p>Cronjob ran in ' + data.runtime.toFixed(3) + ' seconds with exit code ' + data.exitcode +'</p>'
                    content += '<pre>' + data.output + '</pre>'

                    modal.querySelector('.modal-body').innerHTML = content;
                }

                var bsModal = new Modal('#runnow_result').show();

                document.querySelector('.container-fluid').classList.remove('blur');
                document.querySelector('.runnow-overlay').classList.remove('d-block');
                document.querySelector('.runnow-overlay').classList.add('d-none');
            })
        })
    )
}