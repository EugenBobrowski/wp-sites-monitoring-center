'use strict';

(function ($) {

    var $body, $status_icon;

    $(document).ready(function () {
        $body = $('body');

        $body.on('click', '#connect_wp_site', function (e) {
            e.preventDefault();

            var $this = $(this);
            var $metabox = $this.parents('.inside');

            console.log();

            $metabox.find('.spinner').addClass('is-active');


            $.post(connect_metabox.ajax_url, {
                _wpnonce: connect_metabox.nonce,
                action: 'connetct_metabox',
                todo: 'connect',
                post_id: $body.find('#post_ID').val(),
                json: $body.find('#connection_json').val()
            }, function (r) {
                $metabox.find('.spinner').removeClass('is-active');

                if (typeof r.success != 'undefined' && r.success ) {
                    $metabox.html(r.html);
                }
                console.log((typeof r.success != 'undefined' && r.success ), r);
            });

        })

        $body.on('click', '.activate-remote-plugin, .deactivate-remote-plugin', function (e) {
            e.preventDefault();

            var $this = $(this),
                $row = $this.parents('tr'),
                data = {
                    _wpnonce: connect_metabox.nonce,
                    action: 'connetct_metabox',
                    todo: $this.data('action'),
                    plugin: $row.data('plugin'),
                    post_id: $body.find('#post_ID').val()
                };

            if ( typeof data.todo == 'undefined' )  return;

            $.post(connect_metabox.ajax_url, data, function (r) {
                if (data.todo == 'plugin_activate' && r.activation_success) {
                    $row.removeClass('inactive').addClass('active')
                        $row.find('.activate').hide();
                        $row.find('.deactivate').show();
                } else if (data.todo == 'plugin_deactivate' && r.deactivation_success) {
                    $row.removeClass('active').addClass('inactive');
                    $row.find('.activate').show();
                    $row.find('.deactivate').hide();
                }
                console.log(r, (data.todo == 'activate_plugin'), (data.todo == 'deactivate_plugin' && r.deactivation_success) , r.deactivation_success);
            });

        })


    });
})(jQuery);