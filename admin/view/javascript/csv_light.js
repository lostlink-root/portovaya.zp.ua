(function($){$(document).ready(function(){

    var getProcent = function (min, max) {
        return Math.round( min / max * 100 );
    };

    var read_csv = function () {
        $('#loading').slideDown(250);
        var url = 'index.php?route=extension/module/csv_light/read_csv&user_token=' + getURLVar('user_token') + '&processed=' + processed;
        $.get(url, function(response){
            $('#export_import_logs').append(response.logs.join(''));
            var progressBar = getProcent(response.processed, response.products_count);
            document.querySelector('#export_import_progress_wrapper').style.transform = 'translateX(' + progressBar + '%)';

            if (response.error) {
                document.querySelector('#export_import_progress_wrapper').style.backgroundColor = '#E3503E';
            }

            if( response.works && !response.error) {
                processed = response.processed;
                read_csv();
                var div = $("#export_import_logs");
                div.scrollTop(div.prop('scrollHeight'));
            } else {
                $('#loading').slideUp(250);
                $('#button_export').attr('disabled', false);
                $('#button_import').attr('disabled', false);
                $('#import_file').val('');
            }
        });
    };

    var download_csv = function() {
        $('#loading').slideUp(250);
        $('#export_import_logs').append('<p><b>Файл сформирован!</b></p>');
        $('#button_export').attr('disabled', false);
        $('#button_import').attr('disabled', false);
        var url = 'index.php?route=extension/module/csv_light/download_csv&user_token=' + getURLVar('user_token');

       // var link = document.createElement('a');
       // link.setAttribute('href', url);
       // link.setAttribute('download', 'csv_lite.csv');
       // link.click();
        location = (url);
    };

    var create_csv = function() {
        var url = 'index.php?route=extension/module/csv_light/create_csv&user_token=' + getURLVar('user_token') + '&processed=' + processed;
        $.get(url, function(response){
            $('#export_import_logs').append(response.logs.join(''));
            var progressBar = getProcent(response.processed, response.products_count);
            document.querySelector('#export_import_progress_wrapper').style.transform = 'translateX(' + progressBar + '%)';

            if( response.works ) {
                processed = response.processed;
                create_csv();
            } else {
                download_csv();
            }
        });
    };

    var processed = 0;

    $('#button_export').on('click', function(){


//  D E B U G  {
/*
        var url = 'index.php?route=extension/module/csv_light/create_csv&user_token=' + getURLVar('user_token') + '&processed=' + processed;
        $.get(url, function(response){
            $('#export_import_logs').html(response);
        });

        return;
*/
//  D E B U G  }


        $('#button_export').attr('disabled', true);
        $('#button_import').attr('disabled', true);
        document.querySelector('#export_import_progress_wrapper').style.transform = 'translateX(0)';
        document.querySelector('#export_import_progress_wrapper').style.backgroundColor = '#1E91CF';
        $('#loading').slideDown(250);
        $('#export_import_logs').html('<p><b>Не закрывайте и не перезагружайте страницу до завершения процесса!</b></p>');
        $('#export_import_logs').append('<p><b>Создание файла...</b></p>');
        processed = 0;
        create_csv();
    });


//  D E B U G  {
/*
    $('#button_import').on('click', function () {
        var url = 'index.php?route=extension/module/csv_light/read_csv&user_token=' + getURLVar('user_token') + '&processed=' + processed;
        $.get(url, function(response){
            $('#export_import_logs').html(response);
        });
    });
*/

    $('#button_import_local').on('click', function () {
        var url = 'index.php?route=extension/module/csv_light/read_csv&user_token=' + getURLVar('user_token') + '&processed=' + processed;
        $.get(url, function(response){
            $('#export_import_logs').html(response);
                processed = 0;
                read_csv();
        });
    });

//  D E B U G  }


    $('#import_file').on('change', function(){
        $('#button_export').attr('disabled', true);
        $('#button_import').attr('disabled', true);

        document.querySelector('#export_import_progress_wrapper').style.transform = 'translateX(0)';
        document.querySelector('#export_import_progress_wrapper').style.backgroundColor = '#1E91CF';

        $('#export_import_logs').html('<p><b>Не закрывайте и не перезагружайте страницу до завершения процесса!</b></p>');
        $('#export_import_logs').append('<p><b>Загрузка файла...</b></p>');

        var myFormData = new FormData();
        myFormData.append('csv_file', document.querySelector('#import_file').files[0], 'csv_light.csv');

        $.ajax({
            url: 'index.php?route=extension/module/csv_light/upload_csv&user_token=' + getURLVar('user_token'),
            type: 'POST',
            processData: false,
            contentType: false,
            dataType : 'json',
            data: myFormData,
            success : function (response) {
                $('#export_import_logs').append(response.logs.join(''));
                if (response.success){
                    processed = 0;
                    read_csv();
                }
            }
        });
    });

});})(jQuery);