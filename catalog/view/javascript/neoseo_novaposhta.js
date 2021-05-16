// Autocomplete */

(function ($) {
	$.fn.autocomplete_address = function (option) {
		return this.each(function () {
			this.timer = null;
			this.items = new Array();

			$.extend(this, option);

			$(this).attr('autocomplete', 'off');

			// Focus
			$(this).on('focus', function () {
				this.request();
			});

			// Blur
			$(this).on('blur', function () {
				setTimeout(function (object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$(this).on('keydown', function (event) {
				switch (event.keyCode) {
					case 27: // escape
						this.hide();
						break;
					default:
						this.request();
						break;
				}
			});

			// Click
			this.click = function (event) {
				event.preventDefault();

				value = $(event.target).parent().attr('data-value');

				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function () {
				var pos = $(this).position();

				$(this).siblings('ul.dropdown-menu').css({
					top: pos.top + $(this).outerHeight(),
					left: pos.left
				});

				$(this).siblings('ul.dropdown-menu').show();
			}

			// Hide
			this.hide = function () {
				$(this).siblings('ul.dropdown-menu').hide();
			}

			// Request
			this.request = function () {
				clearTimeout(this.timer);

				this.timer = setTimeout(function (object) {
					object.source($(object).val(), $.proxy(object.response, object));
				}, 200, this);
			}

			// Response
			this.response = function (json) {
				html = '';

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						this.items[json[i]['value']] = json[i];
					}

					for (i = 0; i < json.length; i++) {
						if (!json[i]['category']) {
							html += '<li data-value="' + json[i]['value'] + '"><a href="#">' + json[i]['label'] + '</a></li>';
						}
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$(this).siblings('ul.dropdown-menu').html(html);
			}

			$(this).after('<ul class="dropdown-menu"></ul>');
			$(this).siblings('ul.dropdown-menu').delegate('a', 'click', $.proxy(this.click, this));

		});
	}
})(window.jQuery);

$(document).delegate('input[name=\'payment_address.city\']', 'keydown.autocomplete', function () {
	if ($(this).attr('autocompleted') == 1)
		return;
	$(this).attr('autocompleted', 1);
	$(this).autocomplete_address({
		'source': function (request, response) {
			$.ajax({
				url: 'index.php?route=extension/shipping/neoseo_novaposhta/cityautocomplete&filter_name=' + encodeURIComponent(request),
				dataType: 'json',
				success: function (json) {
					response($.map(json, function (item) {
						return {
							label: item['name'],
							value: item['id'],
						}
					}));
				},
				error: function (xhr, ajaxOptions, thrownError) {
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});
		},
		'select': function (item) {
			$(this).val(item['label']);
			$.ajax({
				url: 'index.php?route=extension/shipping/neoseo_novaposhta/savecity&city_id=' + item['value']
			});
		}
	});
});
// Для Симпл чекаут BEGIN
$(document).delegate('input[name=\'shipping_address[city]\']', 'keydown.autocomplete', function () {
	if ($(this).attr('autocompleted') == 1)
		return;
	$(this).attr('autocompleted', 1);
	$(this).autocomplete_address({
		'source': function (request, response) {
			$.ajax({
				url: 'index.php?route=extension/shipping/neoseo_novaposhta/cityautocomplete&filter_name=' + encodeURIComponent(request),
				dataType: 'json',
				success: function (json) {
					response($.map(json, function (item) {
						return {
							label: item['name'],
							value: item['id'],
						}
					}));
				},
				error: function (xhr, ajaxOptions, thrownError) {
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});
		},
		'select': function (item) {
			$(this).val(item['label']);
			$.ajax({
				url: 'index.php?route=extension/shipping/neoseo_novaposhta/savecity&city_id=' + item['value']
			});
		}
	});
});
// Для Симпл чекаут END
function update_wh()
{
	$.ajax({
		url: 'index.php?route=extension/shipping/neoseo_novaposhta/savewarehouse&wh_id=' + $('#neoseo_novaposhta_selected_warehouse').val()
	});
}

function update_address()
{
	$.ajax({
		url: 'index.php?route=extension/shipping/neoseo_novaposhta/saveCourierAdress&address=' + $('#neoseo_novaposhta_address').val()
	});
}

function np_validator()
{
	if ($('.notice_for_np').length)
		$('.notice_for_np').remove();
	if ($('input[name="shipping_method"]:checked').length) {

		var shipping_data = $('input[name="shipping_method"]:checked').val().split('.');

		if (shipping_data.length) {
			if (shipping_data[0] == 'neoseo_novaposhta') {
				if (shipping_data[1] == 'warehouse_delivery') {
					if (($('#neoseo_novaposhta_selected_warehouse').length && $('#neoseo_novaposhta_selected_warehouse').val() == 0)) {
						$('#wh_select').addClass('in')
						$('#wh_select').append('<div class="alert alert-danger notice_for_np" >Обязательное поле</div>');
						return false;
					}
				}
				if (shipping_data[1] == 'courier_delivery') {
					if (($('#neoseo_novaposhta_address').length && $('#neoseo_novaposhta_address').val() == '')) {
						$('#ad_select').addClass('in')
						$('#ad_select').append('<div class="alert alert-danger notice_for_np" >Обязательное поле</div>');
						return false;
					}
				}
			}
		}
	}

	return true;
}