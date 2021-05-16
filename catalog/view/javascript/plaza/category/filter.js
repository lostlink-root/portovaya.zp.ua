$(document).ready(function() {
    ptfilter.productViewChange();
    ptfilter.paginationChangeAction();
    // if (localStorage.getItem('page_size') != 'null') {
    //     let pageSize = localStorage.getItem('page_size');
    //     $("#input-limit option:contains(" + pageSize + ")").attr("selected", "selected");
    //     ptfilter.filter($("#input-limit").val());
    // }
});

var ptfilter = {
    /* Filter action */
    'filter' : function(filter_url) {
        // let limit = filter_url.split("=");
        // let pageSize = limit[limit.length - 1];
        // localStorage.setItem("page_size", pageSize);
        $.ajax({
            url         : filter_url,
            type        : 'get',
            beforeSend  : function () {
                $('.layered-navigation-block').show();
                $('.ajax-loader').show();
            },
            success     : function(json) {
                $('.filter-url').val(json['filter_action']);
                $('.price-url').val(json['price_action']);
                $('.custom-category').html(json['result_html']);
                $('.layered').html(json['layered_html']);
                ptfilter.paginationChangeAction();
                ptfilter.productViewChange();
                $('.layered-navigation-block').hide();
                $('.ajax-loader').hide();


                $("html, body").animate({ scrollTop: 0 }, "slow"); // ПОДНИМАЕТ В КАТЕГОРИЯХ НАВЕРХ ПРИ ПЕРЕЛИСТЫВАНИИ,фильтрации
            },
			complete    : function() {
                if($( document ).width() < 992) {
                    $('#column-left').hide();
                    $('.show-sidebar').removeClass('opened');
                }
            }
        });

    },

    /* Use again and update ajaxComplete from common.js */
    'productViewChange' : function() {
        // Product List
        $('#list-view').click(function() {
            $('#content .product-grid > .clearfix').remove();

            $('#content .row > .product-grid').attr('class', 'product-layout product-list col-xs-12');
            $('#grid-view').removeClass('active');
            $('#list-view').addClass('active');

            localStorage.setItem('display', 'list');
            localStorage.setItem('type', 'none');
        });

        // Product Grid
        $('#grid-view').click(function() {
            var cols = $('#column-right, #column-left').length;

            if (cols == 2) {
                $('#content .product-list').attr('class', 'product-layout product-grid product-style-1 col-lg-6 col-md-6 col-sm-6 col-xs-6 product-items');
            } else if (cols == 1) {
                $('#content .product-list').attr('class', 'product-layout product-grid product-style-1 col-lg-4 col-md-4 col-sm-6 col-xs-6 product-items');
            } else {
                $('#content .product-list').attr('class', 'product-layout product-grid product-style-1 col-lg-3 col-md-3 col-sm-6 col-xs-6 product-items');
            }

            $('#list-view').removeClass('active');
            $('#grid-view').addClass('active');

            localStorage.setItem('display', 'grid');
            localStorage.setItem('type', 'none');
        });

        if (localStorage.getItem('display') == 'list') {
            $('#list-view').trigger('click');
            $('#list-view').addClass('active');
        } else {
            $('#grid-view').trigger('click');
            $('#grid-view').addClass('active');
        }

        // Custom Grid
        if(localStorage.getItem('type') == null) {
            var type = $('#category-view-type').val();
            var cols = $('#category-grid-cols').val();

            if(type == "list") {
                category_view.initView(type, cols, 'btn-list');
            }

            if(type == 'grid') {
                category_view.initView(type, cols, 'btn-grid-' + cols);
            }
        } else {
            var type = localStorage.getItem('type');
            var cols = localStorage.getItem('cols');
            var element = localStorage.getItem('element');

            if(type != 'none') {
                category_view.initView(type, cols, element);
            }
        }
    },

    /* Modify pagination links */
    paginationChangeAction: function () {
        $('.ajax_pagination .pagination a').each(function () {
            var href = $(this).attr('href');
            $(this).attr('onclick', 'ptfilter.filter("'+ href +'")');
            $(this).attr('href', 'javascript:void(0);');

            function updateURL() {
                if (history.pushState) {
                    var baseUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    var newUrl = href;
                    history.pushState(null, null, newUrl);
                }
                 else {
                     console.warn('History API не поддерживается');
                }
            }


        });
    }

};