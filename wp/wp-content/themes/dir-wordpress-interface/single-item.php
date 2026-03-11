<?php
/**
 * single-item.php – CPT “item”
 * Reqs: Pods + helper get_item_card()
 */

/* ------------------------------------------------------------
   Enqueue assets (Material Symbols + model-viewer)
------------------------------------------------------------ */
add_action('wp_enqueue_scripts', function () {

    /* Material Symbols */
    if (!wp_style_is('material-symbols-outlined', 'enqueued')) {
        wp_enqueue_style(
            'material-symbols-outlined',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined'
        );
    }

    /* model-viewer ES module */
    if (!wp_script_is('google-model-viewer', 'enqueued')) {
        wp_enqueue_script(
            'google-model-viewer',
            'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js',
            [],
            null,
            true          // in footer
        );
        /* 👇 rende lo script <script type="module" …> */
        wp_script_add_data('google-model-viewer', 'type', 'module');
    }

    /* Splide CSS & JS (UMD) */
    if (!wp_style_is('splide-css', 'enqueued')) {
        wp_enqueue_style(
            'splide-css',
            'https://cdn.jsdelivr.net/npm/@splidejs/splide@4/dist/css/splide.min.css'
        );
    }
    if (!wp_script_is('splide-js', 'enqueued')) {
        wp_enqueue_script(
            'splide-js',
            'https://cdn.jsdelivr.net/npm/@splidejs/splide@4/dist/js/splide.min.js',
            [],
            null,
            true
        );
    }
});

/* 🔧 forza type="module" anche se l’enqueue lo perde */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if ($handle === 'google-model-viewer') {
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>' . "\n";
    }
    return $tag;
}, 10, 3);

get_header();


/* ------------------------------------------------------------
   Loop
------------------------------------------------------------ */
while (have_posts()):
    the_post();

    $pod = pods('item', get_the_ID());
	$explore_string = 'esplora';
	if( get_locale() == 'en_GB'){
		$explore_string = 'en/explore';
	}

    /* ------------------------------------------------------------
       1. Field map
    ------------------------------------------------------------ */
    $fields = [
        'iccd_code' => 'text',
        'inventory_code' => 'text',
        'alternative_name' => 'text',
        'year_of_origin' => 'date',
        'short_description' => 'paragraph',
        'description' => 'paragraph',
        'detailed_description' => 'wysiwyg',
        'text_for_screen_reader' => 'paragraph',
        'main_image' => 'file',
        'media_gallery' => 'file',
        'attachments' => 'file',
        'config_json' => 'code',
        'sensitive_content' => 'yesno',
        'dimensions' => 'paragraph',
        'bibliography' => 'wysiwyg',
        'content_advisory' => 'paragraph',
        'current_location' => 'text',
        'exhibitions' => 'wysiwyg',
        'parent_item' => 'relation',
        'child_items' => 'relation',
        'related_items' => 'relation',
        'collection' => 'relation',
        'museum' => 'relation',
        '3d_files_sources_paths' => 'text',
        'historical_time_period_details' => 'text',
        'authorship_details' => 'text',
        'place_of_production_details' => 'text',
        'manufacturing_details' => 'text',
        'dir_id' => 'text'
    ];

    /* ------------------------------------------------------------
       2. Normalisation → $out[ key ]
    ------------------------------------------------------------ */
    $out = [];

    foreach ($fields as $key => $type) {
        $raw = $pod->field($key);
        $val = '';

        switch ($type) {

            /* ---------- FILES ---------- */
            case 'file':
                $single = ['main_image'];
                $multi = ['media_gallery', 'attachments'];
                if (empty($raw))
                    break;

                if (in_array($key, $single, true)) {
                    $id = is_array($raw) ? ($raw['ID'] ?? $raw['id'] ?? null) :
                        (is_numeric($raw) ? $raw : null);
                    $val = $id ? wp_get_attachment_url($id) : '';
                    break;
                }
                if (in_array($key, $multi, true) && is_array($raw)) {
                    $urls = [];
                    foreach ($raw as $item) {
                        $id = is_array($item) ? ($item['ID'] ?? $item['id'] ?? null) : null;
                        if ($id)
                            $urls[] = wp_get_attachment_url($id);
                    }
                    $val = implode(', ', array_unique(array_filter($urls)));
                    break;
                }
                if (is_numeric($raw))
                    $val = wp_get_attachment_url($raw);
                break;

            /* ---------- RELATIONS ---------- */
            case 'relation':
                $rel_single = ['parent_item', 'museum'];
                $rel_multi = ['child_items', 'related_items', 'collection', 'steps'];
                if (empty($raw))
                    break;

                $ids = [];
                if (in_array($key, $rel_single, true)) {
                    $id = is_array($raw) ? ($raw['ID'] ?? $raw['id'] ?? null) : null;
                    if ($id)
                        $ids[] = (int) $id;
                } else {
                    foreach ($raw as $item)
                        if (is_array($item))
                            $ids[] = (int) ($item['ID'] ?? $item['id'] ?? 0);
                }

                if ($ids) {

                    $html = '';
                    foreach (array_unique($ids) as $id) {
                        if ($key == 'museum') {
                            $html .= '<a href="/'.$explore_string.'/?mu=' . $id . '">' . get_the_title($id) . '</a>';
                        } elseif (in_array($key, ['child_items', 'related_items'], true)) {
                            $html .= function_exists('get_item_card')
                                ? '<li class="splide__slide">' . get_item_card($id) . '</li>'
                                : '';
                        } else {
                            $html .= function_exists('get_item_card')
                                ? get_item_card($id)
                                : '';
                        }

                    }

                    $val = $html;
                }
                break;

            /* ---------- OTHER TYPES ---------- */
            case 'wysiwyg':
                $val = wpautop($raw);
                break;
            case 'paragraph':
            case 'text':
                $val = nl2br(esc_html($raw));
                break;
            case 'yesno':
            case 'color':
                $val = esc_html($raw);
                break;
            case 'code':
                $val = '<pre><code>' . esc_html($raw) . '</code></pre>';
                break;
            case 'date':
                if ($raw)
                    $val = date_i18n('d/m/Y', strtotime($raw));
                break;
        }

        if ($val !== '')
            $out[$key] = $val;
    }

    /* ---------- Taxonomies to links ---------- */
    $taxonomies = [
        'cultural_context' => '',
        'historical_time_period' => 'hp',
        'item_author' => 'au',
        'material' => 'ma',
        'place_of_production' => 'pp',
        'public_tag' => 'tg',
        'technique' => 'tq',
    ];

    foreach ($taxonomies as $tax => $qvar) {
        $terms = get_the_terms(get_the_ID(), $tax);
        if (!empty($terms) && !is_wp_error($terms)) {
            $links = [];
            foreach ($terms as $t) {
                if ('' != $qvar) {
                    $links[] = '<a href="/'.$explore_string.'/?' . $qvar . '=' . $t->term_id . '">' . esc_html($t->name) . '</a>';
                } else {
                    $links[] = esc_html($t->name);
                }
            }
            $out[$tax] = implode(', ', $links);
        }
    }

    /* ---------- Lists ---------- */
    $hide = ['alternative_name', 'year_of_origin', 'text_for_screen_reader', 'iccd_code', 'config_json', 'sensitive_content', 'content_advisory', 'collection'];

    $order = [
        'short_description' => __('Descrizione breve', 'dir-wordpress-interface'),
		'iccd_code' => __('Codice ICCD', 'dir-wordpress-interface'),
        'inventory_code' => __('Codice inventario', 'dir-wordpress-interface'),
		
		'item_author' => __('Autore', 'dir-wordpress-interface'),
		'authorship_details' => __('Specifiche di attribuzione', 'dir-wordpress-interface'),
		'historical_time_period' => __('Periodo storico', 'dir-wordpress-interface'),
        'historical_time_period_details' => __('Dettagli periodo storico', 'dir-wordpress-interface'),
		'place_of_production' => __('Luogo di produzione', 'dir-wordpress-interface'),
        'place_of_production_details' => __('Specifiche sul luogo di produzione', 'dir-wordpress-interface'),
		'cultural_context' => __('Contesto culturale', 'dir-wordpress-interface'),

		'material' => __('Materiale', 'dir-wordpress-interface'),
        'technique' => __('Tecnica', 'dir-wordpress-interface'),
        'manufacturing_details' => __('Specifiche di lavorazione', 'dir-wordpress-interface'),
		'dimensions' => __('Misure', 'dir-wordpress-interface'),

        'description' => __('Descrizione', 'dir-wordpress-interface'),
        'detailed_description' => __('Approfondimento', 'dir-wordpress-interface'),
        'attachments' => __('Allegati', 'dir-wordpress-interface'),
		'current_location' => __('Luogo di conservazione / posizione', 'dir-wordpress-interface'),
        'museum' => __('Museo', 'dir-wordpress-interface'),

        'parent_item' => __('Questo manufatto fa parte di', 'dir-wordpress-interface'),
        'child_items' => __('Fanno parte di questo manufatto', 'dir-wordpress-interface'),
        
        'bibliography' => __('Bibliografia', 'dir-wordpress-interface'),

		'exhibitions' => __('Mostre', 'dir-wordpress-interface'),
		
		'public_tag' => __('Tag', 'dir-wordpress-interface'),
        'related_items' => __('Oggetti correlati', 'dir-wordpress-interface'),
    ];

    /* ---------- Icon map for attachments ---------- */
    $icon_map = [
        'mp3' => 'audiotrack',
        'wav' => 'audiotrack',
        'ogg' => 'audiotrack',
        'm4a' => 'audiotrack',
        'flac' => 'audiotrack',
        'mp4' => 'movie',
        'mov' => 'movie',
        'webm' => 'movie',
        'avi' => 'movie',
        'mkv' => 'movie',
        'ogv' => 'movie',
        'm4v' => 'movie',
        'pdf' => 'picture_as_pdf',
        'jpg' => 'photo',
        'jpeg' => 'photo',
        'png' => 'photo',
        'tif' => 'photo',
        'tiff' => 'photo',
        'webp' => 'photo',
        'gltf' => 'view_in_ar',
        'glb' => 'view_in_ar',
        'obj' => 'view_in_ar',
        'stl' => 'view_in_ar',
        'usdz' => 'view_in_ar',
        'doc' => 'description',
        'docx' => 'description',
        'txt' => 'description',
        'rtf' => 'description',
        'odt' => 'description',
        'ppt' => 'slideshow',
        'pptx' => 'slideshow',
        'odp' => 'slideshow',
        'xls' => 'table_chart',
        'xlsx' => 'table_chart',
        'csv' => 'table_chart',
        'ods' => 'table_chart',
        'zip' => 'unarchive',
        'rar' => 'unarchive',
        '7z' => 'unarchive',
        'tar' => 'unarchive',
        'gz' => 'unarchive'
    ];

    $has_3d = !empty($out['3d_files_sources_paths']);
    ?>
	<main id="primary" class="site-main">
		<div class="single-item-container">

			<h1 class="single-item-title h3 has-large-font-size"><?php the_title(); ?></h1>


			<!-- CONTENT -->
			<div class="single-item-content">
				<?php
				foreach ($order as $key => $label) {
					if (empty($out[$key]))
						continue;
					$cls = str_replace('_', '-', $key);
					echo '<div class="brut-item-single-meta-wrapper ' . esc_attr($cls) . '">';

					switch ($key) {
	//                     case 'historical_time_period':
	//                     case 'item_author':
	//                         echo '<div class="brut-item-card-meta">' . $out[$key] . '</div>';
	//                         break;

						case 'detailed_description':
							echo '<details class="item-accordion"><summary class="brut-item-card-meta">' . __($label, 'textdomain') . '</summary>' .
								$out[$key] . '</details>';
							break;

						case 'attachments':
							if ($label != '') {
								echo '<div class="brut-item-card-meta"><label>' . __($label, 'textdomain') . '</label> ';
							}
							echo '<ul class="attachments-list">';
							foreach (array_map('trim', explode(',', $out[$key])) as $url) {
								$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
								$icon = $icon_map[$ext] ?? 'insert_drive_file';
								echo '<li>' .
									'<span class="material-symbols-outlined">' .
									esc_html($icon) . '</span>' .
									'<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' .
									esc_html(basename($url)) . '</a></li>';
							}
							echo '</ul></div>';
							break;

						case 'parent_item':
							echo '<div class="brut-item-card-meta"><label>' . __($label, 'textdomain') . '</label> ';
							echo $out[$key];
							echo '</div>';
							break;
						case 'child_items':
						case 'related_items':
							$slides = $out[$key];                         // già <li>…</li>
							echo '<div class="brut-item-card-meta"><label>' . __($label, 'textdomain') . '</label> ';
							echo '<div class="splide items-carousel"><div class="splide__track"><ul class="splide__list">'
								. $slides .
								'</ul></div></div></div>';
							break;
						default:
							if ($label != '') {
								echo '<div class="brut-item-card-meta"><label>' . __($label, 'textdomain') . '</label> ' . $out[$key] . '</div>';
							} else {
								echo '<div class="brut-item-card-meta">' . $out[$key] . '</div>';
							}
					}

					echo '</div>';
					unset($out[$key]);
				}
				?>
			</div><!-- /.single-item-content -->

			<!-- GALLERY -->
			<?php if (!empty($out['media_gallery']) || $has_3d || !empty($out['main_image'])): ?>
				<div class="single-item-gallery splide">
					<div class="splide__track">
						<ul class="splide__list">

							<?php
							/* main image */
							if (!empty($out['main_image'])) {
								$id = attachment_url_to_postid($out['main_image']);
								$alt_txt = $id ? get_post_meta($id, '_wp_attachment_image_alt', true) : '';
								$caption = $id ? wp_get_attachment_caption($id) : '';

								echo '<li class="splide__slide"><figure class="single-item-main-image">';
								echo '<img src="' . esc_url($out['main_image']) . '" alt="' . esc_attr($alt_txt) . '">';
								if ($caption)
									echo '<figcaption>' . esc_html($caption) . '</figcaption>';
								echo '</figure></li>';

								unset($out['main_image']);
							}

							/* 3D viewer(s) */
							if ($has_3d) {
								$urls = array_map('trim', explode(',', $out['3d_files_sources_paths']));
								$idx = 0;
								foreach ($urls as $url) {
									if (empty($url))
										continue;
									$idx++;

									echo '<li class="splide__slide"><figure class="single-item-3d-viewer" >';

									/* controls */
									echo '<div class="viewer-controls">';
									echo '<label style="display:block;">Zoom <input type="range" class="zoom-range" min="100" max="300" value="100"> <span class="zoom-label">100 %</span></label>';
									echo '<label style="display:block;">Esp. <input type="range" class="expo-range" min="0.1" max="2" step="0.1" value="1" > <span class="expo-label">1.0</span></label>';
									echo '</div>';

									/* model-viewer */
									echo '<model-viewer src="' . esc_url($url) . '" alt="'.get_the_title().' 3d '.$idx.'" camera-controls enable-pan ' .
										'camera-orbit="0deg 90deg auto" min-camera-orbit="auto 45deg auto" max-camera-orbit="auto 90deg auto" ' .
										' exposure="1" ' .
										'shadow-intensity="1" shadow-softness="0" style="width:100%;aspect-ratio:1;background:#000;height:auto"></model-viewer>';

									//echo '<figcaption>' . esc_html(basename($url)) . '</figcaption>';
									echo '</figure></li>';
								}
								unset($out['3d_files_sources_paths']);
							}

							/* media gallery (img / video) */
							if (!empty($out['media_gallery'])) {
								$video_ext = ['mp4', 'webm', 'ogv', 'mov', 'm4v', 'avi', 'mkv'];
								$index = 0;

								foreach (array_map('trim', explode(',', $out['media_gallery'])) as $url) {
									$index++;
									$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
									$is_video = in_array($ext, $video_ext, true);

									$att_id = attachment_url_to_postid($url);
									$alt_txt = $att_id ? get_post_meta($att_id, '_wp_attachment_image_alt', true) : '';
									$caption = $att_id ? wp_get_attachment_caption($att_id) : '';
									$cap_id = $caption ? 'media-cap-' . $index : '';

									echo '<li class="splide__slide"><figure class="single-item-image-gallery">';

									if ($is_video) {
										echo '<video src="' . esc_url($url) . '" controls playsinline ' .
											($alt_txt ? 'aria-label="' . esc_attr($alt_txt) . '" ' : '') .
											($caption ? 'aria-labelledby="' . esc_attr($cap_id) . '" ' : '') .
											' style="max-width:100%"></video>';
									} else {
										echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt_txt) . '" >';
									}

									if ($caption)
										echo '<figcaption id="' . esc_attr($cap_id) . '">' . esc_html($caption) . '</figcaption>';

									echo '</figure></li>';
								}
								unset($out['media_gallery']);
							}
							
							?>
						</ul>
					</div>
				</div>
			<?php endif; ?>

			<?php
			/* debug leftover */
			foreach ($hide as $h)
				unset($out[$h]);
			if (!empty($out)) {
				echo '<div class="single-item-debug" border:2px dashed #888;">';
				foreach ($out as $k => $v)
					echo '<p><strong>' . esc_html($k) . ':</strong> ' . $v . '</p>';
				echo '</div>';
			}
			
			?>
			
		</div><!-- /.single-item-container -->
	</main>

    <?php
endwhile;
get_footer();
?>

<!-- ------------------------------------------------------------
     JS: per ogni viewer gestiamo slider zoom / esposizione
------------------------------------------------------------ -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.single-item-3d-viewer').forEach(w => {
            const v = w.querySelector('model-viewer'), z = w.querySelector('.zoom-range'),
                e = w.querySelector('.expo-range'), lz = w.querySelector('.zoom-label'),
                le = w.querySelector('.expo-label');
            if (!v || !z || !e) return;
            const lab = (el, val, u = '') => el.textContent = val + u;
            z.addEventListener('input', () => { const p = z.value; lab(lz, p, ' %'); v.setAttribute('field-of-view', (45 * 100 / p).toFixed(1) + 'deg'); });
            e.addEventListener('input', () => { lab(le, e.value); v.setAttribute('exposure', e.value); });
            lab(lz, z.value, ' %'); lab(le, e.value);
            v.addEventListener('load', () => { v.cameraOrbit = '0deg 90deg auto'; v.jumpCameraToGoal(); });
        });
    });

    document.addEventListener('DOMContentLoaded', () => {

        const gallery = document.querySelector('.single-item-gallery');
        if (!gallery) return;

        let splide = null;
        const mql = window.matchMedia('(max-width:959px)');

        function mountSlider() {
            if (splide || !mql.matches || !window.Splide) return;

            /* 1. Aggiungo la classe richiesta da Splide */
            gallery.classList.add('splide');

            /* 2. Inizializzo lo slider */
            splide = new Splide(gallery, {
                perPage: 1,
                gap: '1rem',
                pagination: true,
                arrows: true,
            }).mount();
        }

        function unmountSlider() {
            if (splide) {
                /* destroy(true) = smonta eventi e ripristina markup originale */
                splide.destroy(true);
                splide = null;
            }
            /* 3. Tolgo le classi che renderebbero la gallery invisibile */
            gallery.classList.remove('splide', 'is-active');
        }

        function update() {
            mql.matches ? mountSlider() : unmountSlider();
        }

        /* run once + on breakpoint change */
        update();
        mql.addEventListener('change', update);
    });

    document.addEventListener('DOMContentLoaded', function () {

        /* istanzia tutti i caroselli */
        document.querySelectorAll('.items-carousel').forEach(function (el) {

            let splide = new Splide(el, makeOpts(el),).mount();

            /* aggiorna perPage on-resize */
            window.addEventListener('resize', throttle(function () {
                const per = el.clientWidth > 480 ? 2 : 1;
                if (splide.options.perPage !== per) {
                    splide.options = { perPage: per };
                    splide.refresh();
                }
            }, 150));
        });

        /* ----- helpers ----- */
        function makeOpts(container) {
            return {
                perPage: container.clientWidth > 480 ? 2 : 1,
                perMove: 1,
                gap: '1rem',
                arrows: true,
                pagination: false,
                drag: true,
                breakpoints: {
                    480: { perPage: 1 }
                }
            };
        }
        function throttle(fn, wait) {
            let t;
            return function () { clearTimeout(t); t = setTimeout(fn, wait); };
        }
    });
</script>