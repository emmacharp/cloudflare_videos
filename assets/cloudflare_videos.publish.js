/**
 * Copyright: Deux Huit Huit 2019
 * License: MIT, see the LICENSE file
 */
(function ($) {

    'use strict';

    var fetchMeta = function (url, options) {
        $.get({
            url: url,
            headers: {
                'X-Auth-Key': options.apiKey,
                'X-Auth-Email': options.email,
                'Content-Type': 'application/json'
            }
        }).success(function (data) {
            if (!data || !data.success || !data.result) {
                window.alert('Failed to fetch video metadata');
                return;
            }

            var parsedMetas = data.result;

            options.$meta.val(JSON.stringify(data.result));
            options.$remove.removeClass('is-hidden');

            if (!parsedMetas.readyToStream) {
                options.$player.empty().append(
                    $('<img />').attr('src', data.result.thumbnail)
                ).append(
                    $('<p />').text('The video is currently being processed. You can save the entry.')
                ).removeClass('is-hidden');
            } else {
                options.$player.append($(options.template.replace('VIDEOID', parsedMetas.uid)));
                options.$player.removeClass('is-hidden');
            }

            options.$upload.addClass('is-hidden');
            options.$progress.addClass('is-hidden');
        });
    };

    var uploadFile = function (options) {
        var file = options.file;
        var upload = new window.tus.Upload(file, {
            endpoint: 'https://api.cloudflare.com/client/v4/zones/' + options.zoneId + '/media',
            retryDelays: [0, 1000, 3000, 5000],
            overridePatchMethod: false,
            metadata: {
                filename: file.name,
                filesize: file.size,
                filetype: file.type,
                lastModified: file.lastModified,
                lastModifiedDate: file.lastModifiedDate
            },
            headers: {
                'X-Auth-Key': options.apiKey,
                'X-Auth-Email': options.email
            },
            onError: function (error) {
                window.alert("Upload Failed because: " + error);
            },
            onProgress: function(bytesUploaded, bytesTotal) {
                var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)  + '%';
                options.$progress.find('.js-cf-video-progress-bar').css('width', percentage);
                options.$progress.find('.js-cf-video-progress-value').text(percentage);
                console.log("Uploading %s: [%d/%d] %s", file.name, bytesUploaded, bytesTotal, percentage);
            },
            onSuccess: function() {
                console.log('Download %s from %s', upload.file.name, upload.url);
                options.$url.val(upload.url);
                fetchMeta(upload.url, options);
            }
        });
        options.$upload.prop('disabled', true);
        options.$progress.removeClass('is-hidden');
        options.$progress.find('.js-cf-video-progress-bar').css('width', '0%');
        upload.start();
    };

    var init = function () {
        if (!window.tus || !window.tus.Upload) {
            window.alert('Failed to load tus client');
            return;
        }

        window.Symphony.Elements.contents.find('div.field-cloudflare_video').each(function () {
            var $field = $(this);
            var $file = $field.find('.js-cf-video-file');
            var $url = $field.find('.js-cf-video-url');
            var $meta = $field.find('.js-cf-video-meta');
            var $player = $field.find('.js-cf-video-player');
            var $upload = $field.find('.js-cf-video-upload');
            var $progress = $field.find('.js-cf-video-progress');
            var $remove = $field.find('.js-cf-video-remove');
            var template = $field.find('.js-cf-video-template').html().trim();

            var parsedMetas = JSON.parse($meta.val() || '{}');

            if (!!parsedMetas.uid) {
                fetchMeta($url.val(), {
                    apiKey: $field.attr('data-cf-api-key'),
                    email: $field.attr('data-cf-email'),
                    $meta: $meta,
                    $player: $player,
                    $progress: $progress,
                    $remove: $remove,
                    template: template
                });

                $remove.removeClass('is-hidden');
            }

            $remove.on('click', function (e) {
                $upload.removeClass('is-hidden');
                $upload.find(':input').prop('disabled', false).val('');
                $player.empty().addClass('is-hidden');
                $remove.addClass('is-hidden');

                return event.preventDefault() && event.stopPropagation();
            });

            $file.on('change', function (e) {
                var file = e.originalEvent.target.files[0];
                if (!file) {
                    window.alert('Failed to locate the file!');
                    return;
                }
                uploadFile({
                    zoneId: $field.attr('data-cf-zone-id'),
                    apiKey: $field.attr('data-cf-api-key'),
                    email: $field.attr('data-cf-email'),
                    file: file,
                    $url: $url,
                    $meta: $meta,
                    $player: $player,
                    $upload: $upload,
                    $progress: $progress,
                    $remove: $remove,
                    template: template
                });
                $file.prop('disabled', true);
            });
        });
    };

    $(init);

})(window.jQuery);
