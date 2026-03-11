<?php
/**
 * Plugin Name: TP – Link Item translations (Polylang) + Media, Pods, Tax & Relations Sync
 * Description: Collega gli "item" EN agli IT via Pods 'id_ita' e sincronizza media, Pods (testo, date, boolean), tassonomie (mirror/strict) e relazioni (museum/collection/item) in batch. Include dry-run globale e log dettagliato.
 * Author: TP
 * Version: 1.8.3
 */

if ( ! defined('ABSPATH') ) exit;

/* ==========================================================
 * ADMIN PAGE
 * ========================================================== */

add_action('admin_menu', function () {
    add_management_page(
        'Link traduzioni Item (Polylang)',
        'Link traduzioni Item (Polylang)',
        'manage_options',
        'tp-link-items-polylang',
        'tp_link_items_polylang_page'
    );
});

/* ==========================================================
 * DRY-RUN GUARD GLOBALE
 * ========================================================== */

function tp_set_dry_run($bool) { $GLOBALS['TP_SYNC_DRY_RUN'] = (bool) $bool; }
function tp_is_dry_run()       { return ! empty($GLOBALS['TP_SYNC_DRY_RUN']); }

/* ==========================================================
 * CONFIGURAZIONE: TASSONOMIE, CAMPI, RELAZIONI
 * ========================================================== */

/** Gruppi tassonomici: A = non tradotte (mirror 1:1), B = tradotte (mappa IT→EN, STRICT) */
function tp_taxonomy_sync_config() {
    return array(
        'cultural_context'       => 'B',
        'historical_time_period' => 'B',
        'item_author'            => 'A',
        'material'               => 'B',
        'place_of_production'    => 'B',
        'technique'              => 'B',
    );
}

/** Pods media (singolo / multiplo) */
function tp_pods_media_single_fields() { return array('main_image'); }
function tp_pods_media_multi_fields()  { return array('media_gallery', 'attachments', '3d_resources'); }

/** Pods scalari/testuali (copiati tal quali) */
function tp_pods_scalar_fields() {
    return array(
        'inventory_code',
        'authorship_details',
        'dimensions',
        'current_location',
        'iccd_code',
        'year_of_origin',          // copio raw (formato visualizzato Y; storage non normalizzato)
        'text_for_screen_reader',
        '3d_files_sources_paths',
        'content_advisory',
        'private_notes',
        'sensitive_content',       // yes/no/1/0 → copio raw senza trasformazioni
    );
}

/** Relazioni: cardinalità e CPT target */
function tp_relation_single_fields() { return array('museum', 'parent_item'); }
// multiple: include related_items
function tp_relation_multi_fields()  { return array('collection', 'child_items', 'related_items'); }

function tp_relation_cpt_for($field_slug) {
    switch ($field_slug) {
        case 'museum':        return 'museum';
        case 'collection':    return 'collection';
        case 'parent_item':   return 'item';
        case 'child_items':   return 'item';
        case 'related_items': return 'item';
        default:              return null;
    }
}

/* ==========================================================
 * UTILS – LETTURA/MODIFICA DATI PODS, MEDIA, TERMINI, RELAZIONI
 * ========================================================== */

function tp_get_id_ita_for_post($post_id) {
    $val = get_post_meta($post_id, 'id_ita', true);
    if ($val === '' || $val === null) {
        if ( function_exists('pods') ) {
            $pod = pods(get_post_type($post_id), $post_id);
            if ( $pod && ! $pod->is_empty() ) {
                $v = $pod->field('id_ita');
                if ( ! is_wp_error($v) && $v !== null ) $val = $v;
            }
        }
    }
    if (is_string($val) && preg_match('/\d+/', $val, $m)) $val = $m[0];
    return (int) $val;
}

/* ---------- Attachment parsing helpers ---------- */

function tp_attachment_id_from_mixed($mixed) {
    if ( is_numeric($mixed) ) return (int) $mixed;

    if ( is_string($mixed) ) {
        $s = trim($mixed);
        if ( function_exists('is_serialized') && is_serialized($s) ) {
            $data = @unserialize($s);
            return tp_attachment_id_from_mixed($data);
        }
        if ( preg_match('#^https?://#i', $s) ) {
            $aid = attachment_url_to_postid($s);
            if ( $aid ) return (int) $aid;
        }
        if ( ctype_digit($s) ) return (int) $s;
    }

    if ( is_array($mixed) ) {
        if ( isset($mixed['ID']) && is_numeric($mixed['ID']) ) return (int) $mixed['ID'];
        if ( isset($mixed['id']) && is_numeric($mixed['id']) )   return (int) $mixed['id'];
        if ( isset($mixed[0]) ) {
            $r = tp_attachment_id_from_mixed($mixed[0]);
            if ( $r ) return $r;
        }
        $url = $mixed['guid'] ?? $mixed['url'] ?? $mixed['guid_url'] ?? null;
        if ( $url ) {
            $aid = attachment_url_to_postid($url);
            if ( $aid ) return (int) $aid;
        }
        foreach ($mixed as $v) {
            $r = tp_attachment_id_from_mixed($v);
            if ( $r ) return $r;
        }
    }
    return 0;
}

function tp_parse_multiple_attachment_ids($raw) {
    $out = array();

    if ( is_numeric($raw) ) return array( (int) $raw );

    if ( is_string($raw) ) {
        $s = trim($raw);
        if ( $s === '' ) return array();
        if ( function_exists('is_serialized') && is_serialized($s) ) {
            $data = @unserialize($s);
            return tp_parse_multiple_attachment_ids($data);
        }
        $s = str_replace(array('|',';'), ',', $s);
        foreach ( explode(',', $s) as $tok ) {
            $tok = trim($tok);
            if ( $tok === '' ) continue;
            if ( preg_match('#^https?://#i', $tok) ) {
                $aid = attachment_url_to_postid($tok);
                if ( $aid ) $out[] = (int) $aid;
            } elseif ( ctype_digit($tok) ) {
                $out[] = (int) $tok;
            }
        }
        return array_values(array_unique($out));
    }

    if ( is_array($raw) ) {
        foreach ($raw as $el) {
            if ( is_array($el) || is_object($el) ) {
                $r = tp_attachment_id_from_mixed($el);
                if ( $r ) { $out[] = (int) $r; continue; }
            }
            if ( is_string($el) && function_exists('is_serialized') && is_serialized($el) ) {
                $data = @unserialize($el);
                $out = array_merge($out, tp_parse_multiple_attachment_ids($data));
                continue;
            }
            $aid = tp_attachment_id_from_mixed($el);
            if ( $aid ) $out[] = (int) $aid;
        }
        return array_values(array_unique($out));
    }

    return array();
}

/* ---------- Pods image (singola) ---------- */

function tp_get_pods_image_attachment_id($post_id, $field_slug) {
    $raw = get_post_meta($post_id, $field_slug, true);
    $aid = tp_attachment_id_from_mixed($raw);
    if ( $aid ) return $aid;

    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $v = $pod->field($field_slug);
            $aid2 = tp_attachment_id_from_mixed($v);
            if ( $aid2 ) return $aid2;
        }
    }
    return 0;
}

function tp_set_pods_image_attachment_id($post_id, $field_slug, $attachment_id) {
    if ( tp_is_dry_run() ) return;
    update_post_meta($post_id, $field_slug, (int) $attachment_id);
    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $pod->save($field_slug, (int) $attachment_id);
        }
    }
}

/* ---------- Pods gallery (multipla) ---------- */

function tp_get_pods_gallery_attachment_ids($post_id, $field_slug) {
    $ids = array();

    // meta multiplo
    $raw_multi = get_post_meta($post_id, $field_slug, false);
    if ( ! empty($raw_multi) ) {
        foreach ($raw_multi as $entry) {
            $ids = array_merge($ids, tp_parse_multiple_attachment_ids($entry));
        }
    }

    // meta singolo
    $raw_single = get_post_meta($post_id, $field_slug, true);
    if ( ! empty($raw_single) ) {
        $ids = array_merge($ids, tp_parse_multiple_attachment_ids($raw_single));
    }

    if ( ! empty($ids) ) {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return $ids;
    }

    // Pods API (storage tabellare)
    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $v = $pod->field($field_slug);
            $ids2 = tp_parse_multiple_attachment_ids($v);
            if ( ! empty($ids2) ) {
                $ids2 = array_values(array_unique(array_map('intval', $ids2)));
                return $ids2;
            }
        }
    }

    return array();
}

function tp_set_pods_gallery_attachment_ids($post_id, $field_slug, array $attachment_ids) {
    if ( tp_is_dry_run() ) return;

    $attachment_ids = array_values(array_unique(array_map('intval', $attachment_ids)));

    delete_post_meta($post_id, $field_slug);
    foreach ($attachment_ids as $aid) {
        add_post_meta($post_id, $field_slug, $aid);
    }

    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $pod->save($field_slug, $attachment_ids);
        }
    }
}

/* ---------- Pods scalari ---------- */

function tp_get_pods_scalar_field($post_id, $field_slug) {
    $val = get_post_meta($post_id, $field_slug, true);
    if ( $val === '' || $val === null ) {
        if ( function_exists('pods') ) {
            $pod = pods(get_post_type($post_id), $post_id);
            if ( $pod && ! $pod->is_empty() ) {
                $v = $pod->field($field_slug);
                if ( ! is_wp_error($v) && $v !== null ) $val = $v;
            }
        }
    }
    return $val;
}

function tp_set_pods_scalar_field($post_id, $field_slug, $value) {
    if ( tp_is_dry_run() ) return;
    update_post_meta($post_id, $field_slug, $value);
    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $pod->save($field_slug, $value);
        }
    }
}

/* ---------- Media helper ---------- */

function tp_pick_media_for_en($attachment_it_id, $lang_en) {
    $target = (int) $attachment_it_id;
    if ( $attachment_it_id && function_exists('pll_get_post_translations') ) {
        $tr = pll_get_post_translations($attachment_it_id);
        if ( ! empty($tr[$lang_en]) ) $target = (int) $tr[$lang_en];
    }
    return $target;
}

/* ---------- Terms / tassonomie ---------- */

function tp_get_term_ids_for_post($post_id, $taxonomy) {
    $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
    if ( is_wp_error($terms) || empty($terms) ) return array();
    return array_map('intval', $terms);
}

function tp_set_terms_mirror($post_id, $taxonomy, array $term_ids_final, array &$log, $dry_run) {
    $term_ids_final = array_values(array_unique(array_map('intval', $term_ids_final)));
    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "TAX[$taxonomy]: DRY-RUN → set [" . implode(',', $term_ids_final) . "] on EN #$post_id";
        return;
    }
    $res = wp_set_object_terms($post_id, $taxonomy, $term_ids_final, false);
    if ( is_wp_error($res) ) {
        $log[] = "TAX[$taxonomy]: ERROR setting terms on EN #$post_id → " . $res->get_error_message();
    } else {
        $log[] = "TAX[$taxonomy]: set → [" . implode(',', $term_ids_final) . "] on EN #$post_id";
    }
}

function tp_sync_taxonomies_it_to_en($it_id, $en_id, $lang_en, array $tax_config, array &$log, $dry_run) {
    $pll_terms_available = function_exists('pll_get_term');

    foreach ($tax_config as $taxonomy => $group) {
        if ( ! taxonomy_exists($taxonomy) ) {
            $log[] = "TAX[$taxonomy]: SKIP (taxonomy non esistente)";
            continue;
        }

        $it_term_ids = tp_get_term_ids_for_post($it_id, $taxonomy);
        $log[] = "TAX[$taxonomy]: IT terms → [" . implode(',', $it_term_ids) . "]";

        if ($group === 'A') {
            $log[] = "TAX[$taxonomy]: Gruppo A (non tradotta) → mirror 1:1 su EN";
            tp_set_terms_mirror($en_id, $taxonomy, $it_term_ids, $log, $dry_run);
            continue;
        }

        if ($group === 'B') {
            if ( ! $pll_terms_available ) {
                $log[] = "TAX[$taxonomy]: SKIP (funzioni Polylang per termini non disponibili)";
                continue;
            }
            $mapped  = array();
            $skipped = array();

            foreach ($it_term_ids as $tid_it) {
                $tid_en = (int) pll_get_term($tid_it, $lang_en);
                if ($tid_en) $mapped[] = $tid_en; else $skipped[] = $tid_it; // STRICT
            }

            $log[] = "TAX[$taxonomy]: mapped IT→EN → [" . implode(',', $mapped) . "]";
            if ( ! empty($skipped) ) {
                $log[] = "TAX[$taxonomy]: STRICT skip (senza traduzione EN) → [" . implode(',', $skipped) . "]";
            }

            tp_set_terms_mirror($en_id, $taxonomy, $mapped, $log, $dry_run);
            continue;
        }

        $log[] = "TAX[$taxonomy]: SKIP (gruppo '{$group}' non riconosciuto)";
    }
}

/* ---------- Relazioni (helpers + mapping) ---------- */

// Normalizza una lista di ID da qualsiasi forma (interi, CSV, JSON, array di strutture con ID/id)
function tp_normalize_relation_ids($val) {
    $out = array();

    if ($val === null || $val === '' ) return $out;

    // 1) numero singolo
    if ( is_numeric($val) ) return array( (int) $val );

    // 2) stringa CSV o JSON
    if ( is_string($val) ) {
        $s = trim($val);
        if ($s === '') return $out;

        // CSV di interi
        if ( preg_match('/^\s*\d+(?:\s*,\s*\d+)*\s*$/', $s) ) {
            foreach ( explode(',', $s) as $tok ) {
                $tok = trim($tok);
                if ( ctype_digit($tok) ) $out[] = (int) $tok;
            }
            return array_values(array_unique($out));
        }

        // JSON (Pods talvolta salva array di oggetti)
        $dec = json_decode($s, true);
        if ( is_array($dec) ) $val = $dec; else return $out;
    }

    // 3) array "piatto" o array di strutture
    if ( is_array($val) ) {
        foreach ($val as $entry) {
            if ( is_numeric($entry) ) { $out[] = (int) $entry; continue; }
            if ( is_array($entry) ) {
                if ( isset($entry['ID']) && is_numeric($entry['ID']) ) { $out[] = (int) $entry['ID']; continue; }
                if ( isset($entry['id']) && is_numeric($entry['id']) )   { $out[] = (int) $entry['id']; continue; }
                // fallback: un livello più in profondità
                foreach ($entry as $v) {
                    if ( is_numeric($v) ) { $out[] = (int) $v; break; }
                    if ( is_array($v) ) {
                        if ( isset($v['ID']) && is_numeric($v['ID']) ) { $out[] = (int) $v['ID']; break; }
                        if ( isset($v['id']) && is_numeric($v['id']) )   { $out[] = (int) $v['id']; break; }
                    }
                }
            }
        }
    }

    $out = array_values(array_unique(array_filter(array_map('intval', $out))));
    return $out;
}

// Legge TUTTE le forme di storage di una relazione multipla (meta multiplo, meta singolo, Pods API)
function tp_collect_relation_raw_values($post_id, $field_slug) {
    $vals = array();

    // meta multiplo: ogni riga meta è un “pezzo” potenzialmente singolo
    $raw_multi = get_post_meta($post_id, $field_slug, false);
    if ( ! empty($raw_multi) ) {
        foreach ($raw_multi as $v) { $vals[] = $v; }
    }

    // meta singolo: può essere un ID, un CSV, o un array serializzato
    $raw_single = get_post_meta($post_id, $field_slug, true);
    if ( $raw_single !== '' && $raw_single !== null ) {
        $vals[] = $raw_single;
    }

    // Pods API (storage tabellare, array di strutture, ecc.)
    if ( function_exists('pods') ) {
        $pod = pods(get_post_type($post_id), $post_id);
        if ( $pod && ! $pod->is_empty() ) {
            $v = $pod->field($field_slug);
            if ( $v !== null && ! is_wp_error($v) ) {
                $vals[] = $v;
            }
        }
    }

    return $vals;
}

function tp_map_relation_single_to_en($field_slug, $src_id) {
    if ( ! $src_id ) return 0;
    if ( is_array($src_id) ) {
        foreach ($src_id as $v) if (is_numeric($v)) { $src_id = (int)$v; break; }
        if ( ! is_numeric($src_id) ) return 0;
    }
    $cpt = tp_relation_cpt_for($field_slug);
    if ( ! $cpt ) return 0;

    if ( $cpt === 'item' ) {
        // item IT → item EN
        if ( function_exists('pll_get_post_language') && pll_get_post_language($src_id) === 'en' ) return (int)$src_id;
        return function_exists('pll_get_post') ? (int) pll_get_post((int)$src_id, 'en') : 0;
    } else {
        // museum/collection IT → EN
        return function_exists('pll_get_post') ? (int) pll_get_post((int)$src_id, 'en') : 0;
    }
}

function tp_map_relation_multi_to_en($field_slug, $src) {
    // Normalizza gli ID IT di partenza (accetta array di strutture/CSV/…)
    $ids_it = tp_normalize_relation_ids($src);
    if ( empty($ids_it) ) return array();

    $out = array();
    foreach ($ids_it as $id) {
        $mapped = tp_map_relation_single_to_en($field_slug, $id);
        if ($mapped) $out[] = $mapped;
    }
    return array_values(array_unique($out));
}

/* ==========================================================
 * SYNC OPERATIONS (FEATURED, PODS IMAGES/GALLERY, PODS SCALARI, RELAZIONI)
 * ========================================================== */

function tp_sync_featured_image_it_to_en($it_id, $en_id, $lang_en, $overwrite, $dry_run, array &$log) {
    $thumb_it = get_post_thumbnail_id($it_id);
    if ( ! $thumb_it ) { $log[] = "SYNC: IT #$it_id senza featured → skip"; return; }
    $thumb_en_current = get_post_thumbnail_id($en_id);
    if ( $thumb_en_current && ! $overwrite ) {
        $log[] = "SYNC: EN #$en_id ha già featured (no overwrite) → skip";
        return;
    }
    $thumb_to_set = tp_pick_media_for_en($thumb_it, $lang_en);

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "SYNC: DRY-RUN → set featured EN #$en_id = {$thumb_to_set}";
    } else {
        if ( $thumb_to_set ) set_post_thumbnail($en_id, $thumb_to_set);
        else                 delete_post_thumbnail($en_id);
    }

    $log[] = ($thumb_to_set === $thumb_it)
        ? "SYNC: featured EN #$en_id ← allegato IT #$thumb_it"
        : "SYNC: featured EN #$en_id ← allegato EN #$thumb_to_set (traduzione di IT #$thumb_it)";
}

function tp_sync_pods_image_it_to_en($field_slug, $it_id, $en_id, $lang_en, $overwrite, $dry_run, array &$log) {
    $img_it = tp_get_pods_image_attachment_id($it_id, $field_slug);
    if ( ! $img_it ) { $log[] = "SYNC[$field_slug]: IT #$it_id senza immagine → skip"; return; }

    $img_en_current = tp_get_pods_image_attachment_id($en_id, $field_slug);
    if ( $img_en_current && ! $overwrite ) { $log[] = "SYNC[$field_slug]: EN #$en_id ha già immagine (no overwrite) → skip"; return; }

    $img_to_set = tp_pick_media_for_en($img_it, $lang_en);

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "SYNC[$field_slug]: DRY-RUN → set EN #$en_id = {$img_to_set}";
    } else {
        tp_set_pods_image_attachment_id($en_id, $field_slug, $img_to_set);
    }

    $log[] = ($img_to_set === $img_it)
        ? "SYNC[$field_slug]: EN #$en_id ← allegato IT #$img_it"
        : "SYNC[$field_slug]: EN #$en_id ← allegato EN #$img_to_set (traduzione di IT #$img_it)";
}

function tp_sync_pods_gallery_it_to_en($field_slug, $it_id, $en_id, $lang_en, $overwrite, $dry_run, array &$log) {
    $ids_it = tp_get_pods_gallery_attachment_ids($it_id, $field_slug);
    if ( empty($ids_it) ) { $log[] = "SYNC[$field_slug]: IT #$it_id galleria vuota → skip"; return; }

    $ids_en_current = tp_get_pods_gallery_attachment_ids($en_id, $field_slug);
    if ( ! empty($ids_en_current) && ! $overwrite ) {
        $log[] = "SYNC[$field_slug]: EN #$en_id galleria già valorizzata (no overwrite) → skip";
        return;
    }

    $ids_target = array();
    foreach ($ids_it as $aid_it) $ids_target[] = tp_pick_media_for_en($aid_it, $lang_en);
    // preserva ordine e rimuovi duplicati
    $seen = array(); $ids_final = array();
    foreach ($ids_target as $aid) { $aid = (int) $aid; if ( $aid && empty($seen[$aid]) ) { $ids_final[] = $aid; $seen[$aid] = true; } }

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "SYNC[$field_slug]: DRY-RUN → set EN #$en_id = [" . implode(',', $ids_final) . "]";
    } else {
        tp_set_pods_gallery_attachment_ids($en_id, $field_slug, $ids_final);
    }

    $log[] = "SYNC[$field_slug]: EN #$en_id ← " . count($ids_final) . " media da IT #$it_id";
}

function tp_sync_pods_scalar_it_to_en($field_slug, $it_id, $en_id, $overwrite, $dry_run, array &$log) {
    $val_it = tp_get_pods_scalar_field($it_id, $field_slug);
    $is_empty_it = ($val_it === '' || $val_it === null);
    if ( $is_empty_it ) { $log[] = "SYNC[$field_slug]: IT #$it_id vuoto → skip"; return; }

    $val_en = tp_get_pods_scalar_field($en_id, $field_slug);
    $is_empty_en = ($val_en === '' || $val_en === null);
    if ( ! $is_empty_en && ! $overwrite ) {
        $log[] = "SYNC[$field_slug]: EN #$en_id già valorizzato (no overwrite) → skip";
        return;
    }

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "SYNC[$field_slug]: DRY-RUN → set EN #$en_id";
    } else {
        tp_set_pods_scalar_field($en_id, $field_slug, $val_it);
    }

    $log[] = "SYNC[$field_slug]: EN #$en_id ← IT #$it_id";
}

/* ---------- Relazioni sync ---------- */

function tp_sync_relation_single_it_to_en($field_slug, $it_id, $en_id, $overwrite, $dry_run, array &$log) {
    $src = get_post_meta($it_id, $field_slug, true);
    $dst_current = get_post_meta($en_id, $field_slug, true);

    if ( $dst_current && ! $overwrite ) {
        $log[] = "REL[$field_slug]: EN #$en_id già valorizzato (no overwrite) → skip";
        return;
    }

    $mapped = tp_map_relation_single_to_en($field_slug, $src);
    $log[]  = "REL[$field_slug]: IT → EN mapped = " . ( $mapped ? $mapped : '0' );

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "REL[$field_slug]: DRY-RUN → set EN #$en_id = " . ( $mapped ? $mapped : 'null' );
    } else {
        if ($mapped) update_post_meta($en_id, $field_slug, $mapped);
        else         delete_post_meta($en_id, $field_slug);
    }
}

function tp_sync_relation_multi_it_to_en($field_slug, $it_id, $en_id, $overwrite, $dry_run, array &$log) {
    // Raccogli TUTTE le forme di storage della relazione multipla (meta multiplo, meta singolo, Pods API)
    $chunks  = tp_collect_relation_raw_values($it_id, $field_slug);
    $src_ids = array();
    foreach ($chunks as $c) {
        $src_ids = array_merge($src_ids, tp_normalize_relation_ids($c));
    }
    $src_ids = array_values(array_unique(array_map('intval', $src_ids)));

    $dst_current = get_post_meta($en_id, $field_slug, true);
    if ( ! empty($dst_current) && is_array($dst_current) && ! $overwrite ) {
        $log[] = "REL[$field_slug]: EN #$en_id già valorizzato (no overwrite) → skip";
        return;
    }

    $mapped = array();
    if ( ! empty($src_ids) ) {
        $mapped = tp_map_relation_multi_to_en($field_slug, $src_ids);
    }

    $log[] = "REL[$field_slug]: IT:[" . implode(',', $src_ids) . "] → EN mapped:[" . implode(',', $mapped) . "]";

    if ( $dry_run || tp_is_dry_run() ) {
        $log[] = "REL[$field_slug]: DRY-RUN → set EN #$en_id = [" . implode(',', $mapped) . "]";
    } else {
        if (!empty($mapped)) update_post_meta($en_id, $field_slug, $mapped);
        else                 delete_post_meta($en_id, $field_slug);
    }
}

/* ==========================================================
 * ORCHESTRAZIONE PER COPPIA IT–EN
 * ========================================================== */

function tp_link_item_pair_full($it_id, $en_id, $post_type, $lang_it, $lang_en, $dry_run, $opts, array &$log) {
    $it = get_post($it_id);
    $en = get_post($en_id);
    if ( ! $it || ! $en ) { $log[] = "SKIP: post non trovati (IT #$it_id / EN #$en_id)"; return; }
    if ( $it->post_type !== $post_type || $en->post_type !== $post_type ) {
        $log[] = "SKIP: post_type non combacia (IT #$it_id: {$it->post_type}, EN #$en_id: {$en->post_type})";
        return;
    }

    // Lingue coerenti
    if ( function_exists('pll_get_post_language') ) {
        $it_lang = pll_get_post_language($it_id);
        $en_lang = pll_get_post_language($en_id);
        if ( $it_lang !== $lang_it ) {
            if ( $dry_run || tp_is_dry_run() ) { $log[] = "LANG: DRY-RUN → set IT #$it_id → $lang_it"; }
            elseif ( function_exists('pll_set_post_language') ) { pll_set_post_language($it_id, $lang_it); }
        }
        if ( $en_lang !== $lang_en ) {
            if ( $dry_run || tp_is_dry_run() ) { $log[] = "LANG: DRY-RUN → set EN #$en_id → $lang_en"; }
            elseif ( function_exists('pll_set_post_language') ) { pll_set_post_language($en_id, $lang_en); }
        }
    }

    // Linking
    $already_linked = false;
    if ( function_exists('pll_get_post_translations') ) {
        $en_tr = pll_get_post_translations($en_id);
        if ( ! empty($en_tr[$lang_it]) && (int) $en_tr[$lang_it] === (int) $it_id ) $already_linked = true;
    }
    if ( ! $already_linked ) {
        if ( $dry_run || tp_is_dry_run() ) {
            $log[] = "LINK: DRY-RUN → link IT #$it_id ↔ EN #$en_id";
        } elseif ( function_exists('pll_save_post_translations') ) {
            pll_save_post_translations(array( $lang_it => $it_id, $lang_en => $en_id ));
            $log[] = "link IT #$it_id ↔ EN #$en_id";
        }
    } else {
        $log[] = "OK: già collegati IT #$it_id ↔ EN #$en_id";
    }

    /* --- Sincronizzazioni opzionali --- */

    // Featured
    if ( ! empty($opts['sync_featured']) ) {
        tp_sync_featured_image_it_to_en($it_id, $en_id, $lang_en, ! empty($opts['overwrite_featured']), $dry_run, $log);
    }

    // Pods image: main_image
    if ( ! empty($opts['sync_main_image']) ) {
        tp_sync_pods_image_it_to_en('main_image', $it_id, $en_id, $lang_en, ! empty($opts['overwrite_main_image']), $dry_run, $log);
    }

    // Pods gallery (multipla): media_gallery, attachments, 3d_resources
    foreach ( tp_pods_media_multi_fields() as $gallery_slug ) {
        if ( ! empty($opts["sync_gallery__{$gallery_slug}"]) ) {
            tp_sync_pods_gallery_it_to_en($gallery_slug, $it_id, $en_id, $lang_en, ! empty($opts["overwrite_gallery__{$gallery_slug}"]), $dry_run, $log);
        }
    }

    // Pods scalari/testuali
    foreach ( tp_pods_scalar_fields() as $slug ) {
        if ( ! empty($opts["sync_scalar__{$slug}"]) ) {
            tp_sync_pods_scalar_it_to_en($slug, $it_id, $en_id, ! empty($opts["overwrite_scalar__{$slug}"]), $dry_run, $log);
        }
    }

    // Relazioni
    foreach ( tp_relation_single_fields() as $slug ) {
        if ( ! empty($opts["sync_rel_single__{$slug}"]) ) {
            tp_sync_relation_single_it_to_en($slug, $it_id, $en_id, ! empty($opts["overwrite_rel_single__{$slug}"]), $dry_run, $log);
        }
    }
    foreach ( tp_relation_multi_fields() as $slug ) {
        if ( ! empty($opts["sync_rel_multi__{$slug}"]) ) {
            tp_sync_relation_multi_it_to_en($slug, $it_id, $en_id, ! empty($opts["overwrite_rel_multi__{$slug}"]), $dry_run, $log);
        }
    }

    // Tassonomie (mirror, strict)
    if ( ! empty($opts['sync_taxonomies']) ) {
        $all = tp_taxonomy_sync_config();
        $selected = array();
        foreach ($all as $tax => $grp) {
            if ( ! empty($opts["tax__{$tax}"]) ) $selected[$tax] = $grp;
        }
        if ( ! empty($selected) ) {
            tp_sync_taxonomies_it_to_en($it_id, $en_id, $lang_en, $selected, $log, $dry_run);
        } else {
            $log[] = "TAX: nessuna tassonomia selezionata → skip";
        }
    }
}

/* ==========================================================
 * PAGINA ADMIN (BATCH)
 * ========================================================== */

function tp_link_items_polylang_page() {
    echo '<div class="wrap"><h1>Link traduzioni Item (Polylang) – batch sync</h1>';

    if ( ! function_exists('pll_set_post_language') || ! function_exists('pll_save_post_translations') ) {
        echo '<div class="notice notice-error"><p>Polylang non rilevato: servono le funzioni <code>pll_*</code>.</p></div></div>';
        return;
    }

    $post_type = 'item';
    $lang_it   = 'it';
    $lang_en   = 'en';

    $done = null;
    $log  = array();

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! isset($_POST['tp_run_linking']) ) {
        echo '<div class="notice notice-warning"><p>Submit ricevuto ma parametro di esecuzione mancante: verifica il bottone di invio.</p></div>';
    }

    if ( isset($_POST['tp_run_linking']) && check_admin_referer('tp_link_items_polylang') ) {
        $limit   = max(1, intval($_POST['limit'] ?? 500));
        $dry_run = ! empty($_POST['dry_run']);
        tp_set_dry_run($dry_run);

        // Opzioni media
        $opts = array(
            'sync_featured'            => ! empty($_POST['sync_featured']),
            'overwrite_featured'       => ! empty($_POST['overwrite_featured']),
            'sync_main_image'          => ! empty($_POST['sync_main_image']),
            'overwrite_main_image'     => ! empty($_POST['overwrite_main_image']),
            'sync_taxonomies'          => ! empty($_POST['sync_taxonomies']),
        );

        // Gallerie (multipli)
        foreach ( tp_pods_media_multi_fields() as $slug ) {
            $opts["sync_gallery__{$slug}"]     = ! empty($_POST["sync_gallery__{$slug}"]);
            $opts["overwrite_gallery__{$slug}"]= ! empty($_POST["overwrite_gallery__{$slug}"]);
        }

        // Scalars
        foreach ( tp_pods_scalar_fields() as $slug ) {
            $opts["sync_scalar__{$slug}"]      = ! empty($_POST["sync_scalar__{$slug}"]);
            $opts["overwrite_scalar__{$slug}"] = ! empty($_POST["overwrite_scalar__{$slug}"]);
        }

        // Relazioni
        foreach ( tp_relation_single_fields() as $slug ) {
            $opts["sync_rel_single__{$slug}"]      = ! empty($_POST["sync_rel_single__{$slug}"]);
            $opts["overwrite_rel_single__{$slug}"] = ! empty($_POST["overwrite_rel_single__{$slug}"]);
        }
        foreach ( tp_relation_multi_fields() as $slug ) {
            $opts["sync_rel_multi__{$slug}"]      = ! empty($_POST["sync_rel_multi__{$slug}"]);
            $opts["overwrite_rel_multi__{$slug}"] = ! empty($_POST["overwrite_rel_multi__{$slug}"]);
        }

        // Tassonomie spunte
        $tax_config = tp_taxonomy_sync_config();
        foreach ($tax_config as $tax => $grp) {
            $opts["tax__{$tax}"] = ! empty($_POST["tax__{$tax}"]);
        }

        // Query EN
        $args = array(
            'post_type'        => $post_type,
            'post_status'      => 'any',
            'numberposts'      => $limit,
            'fields'           => 'ids',
            'orderby'          => 'ID',
            'order'            => 'DESC',
            'lang'             => $lang_en,
            'suppress_filters' => false,
            'no_found_rows'    => true,
        );
        $en_posts = get_posts($args);
        if ( empty($en_posts) ) {
            $log[] = "Diagnostica: nessun post EN trovato con lang={$lang_en}. Riprovo senza filtro lingua.";
            $args2 = $args; unset($args2['lang']);
            $en_posts = get_posts($args2);
        }

        $examined = 0;
        foreach ($en_posts as $en_id) {
            $examined++;
            $it_id = tp_get_id_ita_for_post($en_id);
            if ( ! $it_id ) { $log[] = "EN #$en_id: Pods 'id_ita' mancante o non valido → SKIP"; continue; }
            tp_link_item_pair_full($it_id, $en_id, $post_type, $lang_it, $lang_en, $dry_run, $opts, $log);
        }

        $done = $examined;
        tp_set_dry_run(false);
    }

    // ==== UI ====
    echo '<p>Esegui un batch per collegare IT↔EN e sincronizzare media, Pods, tassonomie e relazioni. Modalità <strong>mirror</strong> e policy <strong>strict</strong> per le tassonomie tradotte. Con Dry-run attivo non viene scritto nulla.</p>';

    echo '<form method="post">';
    wp_nonce_field('tp_link_items_polylang');

    echo '<table class="form-table"><tbody>';
    echo '<tr><th scope="row">Limite batch</th><td><input type="number" name="limit" value="500" min="1" max="100000"> <span class="description">Item EN da esaminare.</span></td></tr>';
    echo '<tr><th scope="row">Dry-run</th><td><label><input type="checkbox" name="dry_run" value="1" checked> Simulazione (non scrive nulla)</label></td></tr>';

    // Media
    echo '<tr><th scope="row">Featured image</th><td>';
    echo '<label><input type="checkbox" name="sync_featured" value="1" checked> Sincronizza IT → EN</label> &nbsp; ';
    echo '<label><input type="checkbox" name="overwrite_featured" value="1"> Overwrite</label>';
    echo '</td></tr>';

    echo '<tr><th scope="row">Pods immagine singola</th><td>';
    echo '<label><input type="checkbox" name="sync_main_image" value="1" checked> <code>main_image</code></label> &nbsp; ';
    echo '<label><input type="checkbox" name="overwrite_main_image" value="1"> Overwrite</label>';
    echo '</td></tr>';

    echo '<tr><th scope="row">Pods gallerie (multiplo)</th><td>';
    foreach ( tp_pods_media_multi_fields() as $slug ) {
        echo '<div style="margin:.25rem 0;">';
        echo '<label><input type="checkbox" name="sync_gallery__'.esc_attr($slug).'" value="1" checked> <code>'.esc_html($slug).'</code></label> &nbsp; ';
        echo '<label><input type="checkbox" name="overwrite_gallery__'.esc_attr($slug).'" value="1"> Overwrite</label>';
        echo '</div>';
    }
    echo '<div class="description">Supporto meta multi-riga, array serializzati, URL e array di strutture. Preserva l’ordine; se media IT hanno traduzione EN, usa quella.</div>';
    echo '</td></tr>';

    // Scalars
    echo '<tr><th scope="row">Pods scalari/testo</th><td>';
    foreach ( tp_pods_scalar_fields() as $slug ) {
        echo '<div style="margin:.25rem 0;">';
        echo '<label><input type="checkbox" name="sync_scalar__'.esc_attr($slug).'" value="1" checked> <code>'.esc_html($slug).'</code></label> &nbsp; ';
        echo '<label><input type="checkbox" name="overwrite_scalar__'.esc_attr($slug).'" value="1"> Overwrite</label>';
        echo '</div>';
    }
    echo '<div class="description">I valori sono copiati tal quali (nessuna creazione/conversione contenuti). <code>year_of_origin</code> e <code>sensitive_content</code> sono salvati come presenti nell’IT.</div>';
    echo '</td></tr>';

    // Relazioni
    echo '<tr><th scope="row">Relazioni (mappa alle controparti EN)</th><td>';
    echo '<strong>Singole</strong><br>';
    foreach ( tp_relation_single_fields() as $slug ) {
        echo '<div style="margin:.25rem 0;">';
        echo '<label><input type="checkbox" name="sync_rel_single__'.esc_attr($slug).'" value="1" checked> <code>'.esc_html($slug).'</code></label> &nbsp; ';
        echo '<label><input type="checkbox" name="overwrite_rel_single__'.esc_attr($slug).'" value="1"> Overwrite</label>';
        echo '</div>';
    }
    echo '<strong>Multiple</strong><br>';
    foreach ( tp_relation_multi_fields() as $slug ) {
        echo '<div style="margin:.25rem 0;">';
        echo '<label><input type="checkbox" name="sync_rel_multi__'.esc_attr($slug).'" value="1" checked> <code>'.esc_html($slug).'</code></label> &nbsp; ';
        echo '<label><input type="checkbox" name="overwrite_rel_multi__'.esc_attr($slug).'" value="1"> Overwrite</label>';
        echo '</div>';
    }
    echo '<div class="description">Per <code>museum</code> e <code>collection</code> si usa la traduzione EN del post correlato; per <code>parent_item</code>, <code>child_items</code> e <code>related_items</code> si mappa l’item IT alla sua controparte EN.</div>';
    echo '</td></tr>';

    // Taxonomies
    $tax_config = tp_taxonomy_sync_config();
    echo '<tr><th scope="row">Tassonomie (mirror, strict)</th><td>';
    echo '<label><input type="checkbox" name="sync_taxonomies" value="1" checked> Attiva sincronizzazione tassonomica</label>';
    echo '<div style="margin:.5rem 0 .25rem; font-style:italic;">A = non tradotte (copia 1:1) &nbsp; | &nbsp; B = tradotte (mappa IT→EN; nessuna creazione/alterazione; salta i buchi).</div>';
    foreach ($tax_config as $tax => $grp) {
        $label = esc_html($tax) . ' <span style="opacity:.7;">[' . ($grp === 'A' ? 'A: non tradotta' : 'B: tradotta') . ']</span>';
        echo '<div style="margin:.25rem 0;"><label><input type="checkbox" name="tax__' . esc_attr($tax) . '" value="1" checked> ' . $label . '</label></div>';
    }
    echo '</td></tr>';

    echo '</tbody></table>';

    submit_button('Esegui collegamento / sincronizzazione', 'primary', 'tp_run_linking');

    echo '</form>';

    if ( isset($done) ) {
        echo '<h2>Risultato</h2>';
        echo '<p>Elementi EN esaminati: <strong>' . intval($done) . '</strong></p>';
        if ( ! empty($log) ) {
            echo '<details open><summary>Log</summary><pre style="white-space:pre-wrap;">' . esc_html(implode("\n", $log)) . '</pre></details>';
        } else {
            echo '<p>Nessun messaggio di log.</p>';
        }
    }

    echo '</div>';
}
