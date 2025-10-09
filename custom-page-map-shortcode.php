<?php
/**
 * Custom Page Map shortcode for displaying Google Maps branch markers.
 */
add_shortcode('custom_page_map', function ($atts) {
    $atts = shortcode_atts(
        [
            'height' => '460px',
            'zoom'   => 12,
            'id'     => 'naekiMap',
        ],
        $atts
    );

    $posts = get_posts([
        'post_type'   => 'place',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);

    $branches = [];
    foreach ($posts as $post) {
        if (stripos($post->post_title, 'Krung Thon Buri') !== false) {
            continue;
        }

        $lat = get_post_meta($post->ID, 'latitude', true);
        $lng = get_post_meta($post->ID, 'longitude', true);
        if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
            continue;
        }

        $branches[] = [
            'slug'  => sanitize_title($post->post_title),
            'title' => get_post_meta($post->ID, 'title', true) ?: $post->post_title,
            'lat'   => (float) $lat,
            'lng'   => (float) $lng,
            'addr'  => (string) get_post_meta($post->ID, 'address', true),
            'tel'   => (string) get_post_meta($post->ID, 'tel', true),
            'open'  => (string) get_post_meta($post->ID, 'open', true),
            'close' => (string) get_post_meta($post->ID, 'close', true),
            'hours' => (string) get_post_meta($post->ID, 'hours', true),
        ];
    }

    ob_start();
    ?>
    <div id="<?= esc_attr($atts['id']); ?>" style="width:100%;height:<?= esc_attr($atts['height']); ?>"></div>
    <script>
        (function() {
            function init() {
                var map = new google.maps.Map(
                    document.getElementById('<?= esc_js($atts['id']); ?>'),
                    {
                        zoom: <?= (int) $atts['zoom']; ?>,
                        center: {lat: 13.7425, lng: 100.54},
                        mapTypeControl: false,
                        streetViewControl: false
                    }
                );

                var data = <?= wp_json_encode($branches); ?>;
                var bounds = new google.maps.LatLngBounds();
                var markers = {};

                data.forEach(function(p) {
                    var pos = {lat: +p.lat, lng: +p.lng};
                    var mk = new google.maps.Marker({position: pos, map: map, title: p.title});
                    var html = '<strong>' + p.title + '</strong>' +
                        (p.addr ? '<br>' + p.addr : '') +
                        (p.tel ? '<br>☎ ' + p.tel : '') +
                        ((p.open || p.close) ? '<br>เวลา: ' + (p.open || '-') + ' - ' + (p.close || '-') : '') +
                        (p.hours ? '<br><br>' + p.hours.replace(/\n/g, '<br>') : '');
                    var info = new google.maps.InfoWindow({content: html});
                    mk.addListener('click', function() {
                        info.open(map, mk);
                    });
                    markers[p.slug] = mk;
                    bounds.extend(pos);
                });

                if (data.length > 1) {
                    map.fitBounds(bounds);
                }

                document.querySelectorAll('[data-branch]').forEach(function(row) {
                    row.addEventListener('click', function() {
                        var key = this.getAttribute('data-branch');
                        if (markers[key]) {
                            var m = markers[key];
                            map.panTo(m.getPosition());
                            map.setZoom(15);
                            google.maps.event.trigger(m, 'click');
                            document.getElementById('<?= esc_js($atts['id']); ?>')
                                .scrollIntoView({behavior: 'smooth', block: 'start'});
                        }
                    });
                });
            }

            if (window.google && google.maps) {
                init();
            } else {
                var s = document.createElement('script');
                s.src = 'https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=init';
                s.async = true;
                s.defer = true;
                document.body.appendChild(s);
                window.init = init;
            }
        })();
    </script>
    <?php

    return ob_get_clean();
});
