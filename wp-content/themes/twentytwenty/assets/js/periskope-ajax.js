jQuery(document).ready(function($) {
    $.ajax({
        url: periskope_ajax_obj.ajax_url,
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', periskope_ajax_obj.nonce);
        },
        success: function(data) {
            var tableRows = '';
            data.forEach(function(row) {
                tableRows += '<tr>';
                Object.values(row).forEach(function(value) {
                    tableRows += '<td>' + value + '</td>';
                });
                tableRows += '</tr>';
            });
            $('#periskope-table tbody').html(tableRows);
        },
        error: function(error) {
            console.log(error);
        }
    });
});
