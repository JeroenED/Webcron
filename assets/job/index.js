import 'bootstrap';

$(function() {
    initDeleteButtons();
    initRunNowButtons();
})

function initDeleteButtons() {
    $('.delete-btn').on('click', function() {
        let me = $(this)
        let href = me.data('href');
        let confirmation = me.data('confirmation');

        if(confirm(confirmation)) {
            $.ajax({
                url: href,
                method: 'DELETE',
                success: function(data) {
                    window.location.href = data.return_path;
                }
            })
        }
    })
}

function initRunNowButtons() {
    $('.runnow').on('click', function() {
        let me = $(this)
        let href = me.data('href');
        $.ajax({
            url: href,
            method: 'GET',
            success: function(data) {
                let modal = $('#runnow_result');
                modal.find('.modal-title').html(data.title);
                if (data.status == 'deferred') {
                    modal.find('.modal-body').html(data.message);
                    me.addClass('disabled');

                    let td = me.parents('td');
                    td.find('.btn').each(function() {
                        let btn = $(this);
                        btn.addClass('btn-outline-success');
                        btn.removeClass('btn-outline-primary');
                        btn.removeClass('btn-outline-danger');
                    })


                    let tr = me.parents('tr');
                    tr.addClass('running');
                    tr.removeClass('norun');
                } else if (data.status == 'ran') {
                    let content = '<p>Cronjob ran in ' + data.runtime.toFixed(3) + ' seconds with exit code ' + data.exitcode +'</p>'
                    content += '<pre>' + data.output + '</pre>'

                    modal.find('.modal-body').html(content);
                }

                modal.modal({show: true})
            }
        })
    })
}