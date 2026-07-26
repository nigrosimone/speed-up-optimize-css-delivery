<?php
/**
 * Bootstrap dei test.
 *
 * Il plugin usa poche funzioni di WordPress: bastano degli stub, senza caricare
 * il core ne' una libreria di mocking.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('ABSPATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR);

/** @var array<string, array> */
$GLOBALS['speedup_calls'] = array(
    'add_action' => array(),
    'add_filter' => array(),
);

/** Valore restituito da is_admin(). */
$GLOBALS['speedup_is_admin'] = false;

/**
 * Valore restituito da apply_filters() per il filtro di esclusione: true
 * significa "questo handle non va ottimizzato".
 */
$GLOBALS['speedup_exclude_handle'] = false;

if (!function_exists('is_admin')) {
    function is_admin() {
        return (bool) $GLOBALS['speedup_is_admin'];
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['speedup_calls']['add_action'][] = array($hook, $callback, $priority);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        $GLOBALS['speedup_calls']['add_filter'][] = array($hook, $callback, $priority);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value) {
        return $GLOBALS['speedup_exclude_handle'];
    }
}

// esc_url e esc_attr in WordPress fanno molto di piu'; qui basta che
// l'escaping sia osservabile.
if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return htmlspecialchars(strip_tags((string) $url), ENT_QUOTES, 'UTF-8');
    }
}

require_once dirname(__DIR__) . '/speed-up-optimize-css-delivery.php';

$GLOBALS['speedup_registered_at_load'] = $GLOBALS['speedup_calls'];
