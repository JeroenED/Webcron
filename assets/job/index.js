import 'bootstrap';

$(function() {
    initDeleteButtons();
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