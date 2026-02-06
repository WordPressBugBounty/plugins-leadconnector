<?php
if (!defined('ABSPATH')) exit;

/**
 * Option prefix & fields
 */
const LCSEO_OPT_PREFIX = 'lc_seo_overrides_by_path-';
const LCSEO_FIELDS     = ['page_title','meta_description','meta_keywords'];

/**
 * Current request path in your storage format:
 *  - lowercase
 *  - no leading/trailing slash
 *  Examples: 'book-appointment', 'category/uncategorized', '' (home)
 */
function lcseo_current_path_key(): string {
  $reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $path = trim(strtolower(preg_replace('#/+#', '/', $reqPath)), '/');
  return $path; // '' = home
}

/** Build option_name => lc_seo_overrides_by_path-{path}-{field} */
function lcseo_option_name(string $path, string $field): string {
  return LCSEO_OPT_PREFIX . $path . '-' . $field;
}

/** Decode stored value: URL-decode + underscores → spaces + trim */
function lcseo_decode(string $v): string {
  $v = rawurldecode($v);
  $v = str_replace('_', ' ', $v);
  return trim($v);
}

/** Read one field override for a given path */
function lcseo_get_field_for_path(string $path, string $field): ?string {
  if (!in_array($field, LCSEO_FIELDS, true)) return null;
  $raw = get_option(lcseo_option_name($path, $field), '');
  if (!is_string($raw) || $raw === '') return null;
  $val = lcseo_decode($raw);
  return $val !== '' ? $val : null;
}

/** Gather all overrides for current path (with basic home fallback) */
function lcseo_get_overrides_for_current(): array {
  $path = lcseo_current_path_key();
  $vals = [];
  foreach (LCSEO_FIELDS as $f) {
    $v = lcseo_get_field_for_path($path, $f);
    if ($v !== null && $v !== '') $vals[$f] = $v;
  }

  // Optional home fallbacks if you store home as 'home' or '/'
  if (empty($vals) && $path === '') {
    foreach (['home','/'] as $homeKey) {
      foreach (LCSEO_FIELDS as $f) {
        $v = lcseo_get_field_for_path($homeKey, $f);
        if ($v !== null && $v !== '') $vals[$f] = $v;
      }
      if (!empty($vals)) break;
    }
  }
  return $vals;
}

/* -------------------- Title override -------------------- */
add_filter('document_title_parts', function(array $parts){
  $vals = lcseo_get_overrides_for_current();
  if (!empty($vals['page_title'])) {
    $parts['title'] = $vals['page_title'];
  }
  return $parts;
}, 40);

/* -------------------- Meta tags (description, keywords + social parity) -------------------- */
add_action('wp_head', function () {
  $vals = lcseo_get_overrides_for_current();

  // Debug comments (safe to keep; remove later if you want)
  $path = lcseo_current_path_key();
  echo "\n<!-- LCSEO current path: " . ($path === '' ? '[home]' : esc_html($path)) . " -->\n";
  if (!empty($vals)) {
    foreach ($vals as $k => $v) echo "<!-- LCSEO {$k}: " . esc_html($v) . " -->\n";
  } else {
    echo "<!-- LCSEO: no overrides found -->\n";
  }

  if (!empty($vals['meta_description'])) {
    $d = $vals['meta_description'];
    echo '<meta name="description" content="' . esc_attr($d) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($d) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($d) . '">' . "\n";
  }
  if (!empty($vals['page_title'])) {
    $t = $vals['page_title'];
    echo '<meta property="og:title" content="' . esc_attr($t) . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($t) . '">' . "\n";
  }
  if (!empty($vals['meta_keywords'])) {
    echo '<meta name="keywords" content="' . esc_attr($vals['meta_keywords']) . '">' . "\n";
  }
}, 5);
