/**
 * Created by eugen on 29.03.17.
 */

// alert(';lskdaf;dkfj;');

'use strict';

(function ($) {

    var $body, $status_icon;

    $(document).ready(function () {
        $body = $('body');

        $body.on('click', '.status-icon', function (e) {
            e.preventDefault();

            var $this = $(this);

            $.post(sites_monitor.ajax_url, {
                _wpnonce: sites_monitor._wpnonce,
                action: 'refresh_status',
                site_id: $this.data('site-id')
            }, function (r) {

                $this.attr('class', r.class);

                console.log(r);
            });

        })


    });
})(jQuery);