(function($) {
    'use strict';

    $(document).ready(function() {

        if ($('.ap-color-picker').length) {
            $('.ap-color-picker').wpColorPicker();
        }

        $(document).on('click', '.ap-upload-btn', function(e) {
            e.preventDefault();

            var button = $(this);
            var uploader = button.closest('.ap-image-uploader');
            var input = uploader.find('.ap-image-input');
            var removeBtn = uploader.find('.ap-remove-btn');
            var preview = button.closest('td').find('.ap-preview');

            var frame = wp.media({
                title: '选择图片',
                button: {
                    text: '使用此图片'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                input.val(attachment.url);
                removeBtn.removeClass('hidden');

                if (preview.length) {
                    preview.find('img').attr('src', attachment.url);
                } else {
                    var previewHtml = '<div class="ap-preview"><img src="' + attachment.url + '" alt="Preview" style="max-width:300px;max-height:100px;margin-top:10px;" /></div>';
                    button.closest('td').append(previewHtml);
                }
            });

            frame.open();
        });

        $(document).on('click', '.ap-remove-btn', function(e) {
            e.preventDefault();

            var button = $(this);
            var uploader = button.closest('.ap-image-uploader');
            var input = uploader.find('.ap-image-input');

            input.val('');
            button.addClass('hidden');

            var preview = button.closest('td').find('.ap-preview');
            if (preview.length) {
                preview.remove();
            }
        });

        var colorSchemeSelect = $('#color_scheme');
        if (colorSchemeSelect.length) {
            colorSchemeSelect.on('change', function() {
                $('#submit').trigger('click');
            });
        }

        var gravatarSelect = $('.ap-gravatar-mirror-select');
        if (gravatarSelect.length) {
            gravatarSelect.on('change', function() {
                var customWrap = $(this).closest('td').find('.ap-gravatar-custom-wrap');
                if ($(this).val() === 'custom') {
                    customWrap.show();
                } else {
                    customWrap.hide();
                }
            });
        }
    });

})(jQuery);