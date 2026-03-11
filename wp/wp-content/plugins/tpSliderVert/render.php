<?php
function tp_slider_vert_render_callback($attributes) {
    wp_enqueue_style('tp-slider-vert-style');
    wp_enqueue_script('tp-slider-vert-script');

    $sliders = [
        'firstSlider' => 'slider1',
        'secondSlider' => 'slider2',
        'thirdSlider' => 'slider3',
    ];

    $speeds = [
        'firstSlider' => $attributes['speed1'] ?? 16.5,
        'secondSlider' => $attributes['speed2'] ?? 21,
        'thirdSlider' => $attributes['speed3'] ?? 25.5,
    ];

    $mobile_height = $attributes['mobileHeight'] ?? 200;

    ob_start();
    ?>
    <style>
        :root {
            --tp-slider-speed-1: <?php echo esc_attr($speeds['firstSlider']); ?>s;
            --tp-slider-speed-2: <?php echo esc_attr($speeds['secondSlider']); ?>s;
            --tp-slider-speed-3: <?php echo esc_attr($speeds['thirdSlider']); ?>s;
            --tp-slider-mobile-height: <?php echo esc_attr($mobile_height); ?>px;
        }
    </style>
    <div class="tp-slider-vert-container-wrapper">
        <div class="tp-slider-vert-container">
            <?php
            $i = 1;
            foreach ($sliders as $key => $id) {
                $images = $attributes[$key] ?? [];
                echo '<div class="tp-slider-column' . ($i === 2 ? ' reverse' : '') . '" id="' . esc_attr($id) . '"><div class="tp-slide-track">';
                foreach ($images as $img_id) {
                    $item_id = get_post_meta($img_id, 'item_link', true);
                    $valid = $item_id && get_post_status($item_id);
                    $href = $valid ? get_permalink($item_id) : 'javascript:void(0)';
                    $link_class = $valid ? '' : ' no-pointer';
                    $url = wp_get_attachment_image_url($img_id, 'large');
                    if ($url) {
                        echo '<div class="tp-slide">';
                        echo '<a href="' . esc_url($href) . '" class="tp-slide-link' . esc_attr($link_class) . '">';
                        echo '<img src="' . esc_url($url) . '" alt="'. get_post_meta( $img_id, '_wp_attachment_image_alt', true ) .'">';
                        echo '</a></div>';
                    }
                }
                echo '</div></div>';
                $i++;
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}