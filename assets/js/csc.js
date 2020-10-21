jQuery(function ($) {
    // Handle if a customer clicks a droppoint
    $(document).on('click', '[name="coolrunner_search_droppoints"]', function () {
        var _ = $('.coolrunner_select_shop'),
            diff_ship_address = $('[name="ship_to_different_address"]').is(':checked'),
            origin_zip_code = diff_ship_address ? $('[name="shipping_postcode"]').val() : $('[name="billing_postcode"]').val(),
            origin_street = diff_ship_address ? $('[name="shipping_address_1"]').val() : $('[name="billing_address_1"]').val(),
            origin_city = diff_ship_address ? $('[name="shipping_city"]').val() : $('[name="billing_city"]').val(),
            origin_country = diff_ship_address ? $('[name="shipping_country"]').val() : $('[name="billing_country"]').val(),
            chosen_zip_code = $('[name="coolrunner_zip_code_search"]'),
            carrier = $('[name="shipping_method[0]"]:checked').val();

        chosen_zip_code = chosen_zip_code.val() ? chosen_zip_code.val() : chosen_zip_code.attr('placeholder');
        carrier = carrier.split("_");
        carrier = carrier[0];
        console.log(carrier);

        var ajax_data = {
            action: 'coolrunner_droppoint_search',
            zip_code: chosen_zip_code,
            carrier: carrier,
            country: origin_country
        };

        if (chosen_zip_code === origin_zip_code) {
            ajax_data.city = origin_city;
            ajax_data.street = origin_street;
        }

        if (chosen_zip_code.length !== 0) {
            $('.coolrunner-droppoints').slideUp(250, function () {
                $(this).slideDown(250).html('<div class="cr-loading-droppoints"><span class="cr-spinner"></span><span class="cr-searching">' + csc.lang.droppoint_searching + '</span></div>');
            });
            $.ajax({
                url: csc.ajax_url,
                type: 'post',
                data: ajax_data,
                success: function (response) {
                    $('.coolrunner-droppoints').slideUp(250, function () {
                        $(this).slideDown(250).html(response)
                    });
                },
                fail: function () {
                    alert('Something went wrong while trying to acquire droppoints - Please contact the system administrator');
                }
            });
        }
    });

    $(document).on('click', '.shipping_method', function () {
        var diff_ship_address = $('[name="ship_to_different_address"]'),
            origin_zip_code = diff_ship_address ? $('[name="billing_postcode"]').val() : $('[name="shipping_postcode"]').val();

        $('#coolrunner_zip_code_search').attr('placeholder', origin_zip_code);
        $('#coolrunner-search-results').hide();
    });

    $(document).on('keydown keypress', '[name="coolrunner_zip_code_search"]', function (e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            $('#coolrunner_search_droppoints').click();

            return false;
        }
    }).trigger('click');

    (function checkout_digest() {
        var droppoint_container = $('.woocommerce-checkout-review-order .coolrunner_select_shop');

        if ($('body').is('.woocommerce-checkout')) {
            if ($('[name="shipping_method[0]"]').length) {
                droppoint_container.show();
                var val = $('[name="shipping_method[0]"]:checked').val();

                if (val === undefined) {
                    val = $('[name="shipping_method[0]"]').val()
                }

                if (val !== undefined) {
                    if (val.indexOf('droppoint') === -1) {
                        droppoint_container.hide();
                    } else {
                        droppoint_container.show();
                    }

                    var parts = val.split('_', 3),
                        carrier = parts[0],
                        product = parts[1],
                        service = parts.hasOwnProperty(2) ? parts[2] : null;

                    if (droppoint_container.attr('data-carrier') !== carrier) {
                        droppoint_container.attr('data-carrier', carrier);
                        droppoint_container.find('.coolrunner-droppoints').slideUp(250, function () {
                            $(this).html('');
                        }).slideDown(250);
                    }

                    if (val !== undefined && val.indexOf('coolrunner') === 0) {

                        if (service !== null && service.indexOf('droppoint') === 0) {
                            droppoint_container.filter('.hidden').slideDown(250, function () {
                                $(this).removeClass('hidden');
                            });
                            $('[name="coolrunner_carrier"]').val(carrier);
                        } else {
                            droppoint_container.filter(':not(.hidden)').addClass('hidden').stop().slideUp(250, function () {
                                droppoint_container.find('.coolrunner-droppoints').html('');
                                droppoint_container.find('.coolrunner-droppoints').find('input').remove();
                            });
                        }
                    } else {
                        droppoint_container.filter(':not(.hidden)').addClass('hidden').stop().slideUp(250, function () {
                            droppoint_container.find('.coolrunner-droppoints').html('');
                            droppoint_container.find('.coolrunner-droppoints').find('input').remove();
                        });
                    }
                }

            } else {
                droppoint_container.hide();
            }
            setTimeout(checkout_digest, 100);
        }


        var _ = $('.coolrunner_select_shop'),
            diff_ship_address = $('[name="ship_to_different_address"]').is(':checked'),
            origin_zip_code = diff_ship_address ? $('[name="shipping_postcode"]').val() : $('[name="billing_postcode"]').val(),
            chosen_zip_code = $('[name="coolrunner_zip_code_search"]');
        chosen_zip_code.attr('placeholder', origin_zip_code);
    })();
});



