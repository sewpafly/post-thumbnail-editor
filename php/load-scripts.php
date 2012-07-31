<?php

/**
 * Disable error reporting
 *
 * Set this to error_reporting( E_ALL ) or error_reporting( E_ALL | E_STRICT ) for debugging
 */
error_reporting(0);

/** Set ABSPATH for execution */
//define( 'ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/' );
define( 'ABSPATH', dirname(dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME']))))) . '/' );
define( 'WPINC', 'wp-includes' );
define( 'PLUGINPATH', '/wp-content/plugins/' . basename(dirname(dirname(__FILE__))));

/**
 * @ignore
 */
function __() {}

/**
 * @ignore
 */
function _x() {}

/**
 * @ignore
 */
function add_filter() {}

/**
 * @ignore
 */
function esc_attr() {}

/**
 * @ignore
 */
function apply_filters() {}

/**
 * @ignore
 */
function get_option() {}

/**
 * @ignore
 */
function is_lighttpd_before_150() {}

/**
 * @ignore
 */
function add_action() {}

/**
 * @ignore
 */
function did_action() {}

/**
 * @ignore
 */
function do_action_ref_array() {}

/**
 * @ignore
 */
function get_bloginfo() {}

/**
 * @ignore
 */
function is_admin() {return true;}

/**
 * @ignore
 */
function site_url() {}

/**
 * @ignore
 */
function admin_url() {}

/**
 * @ignore
 */
function home_url() {}

/**
 * @ignore
 */
function includes_url() {}

/**
 * @ignore
 */
function wp_guess_url() {}

if ( ! function_exists( 'json_encode' ) ) :
	/**
	 * @ignore
	 */
	function json_encode() {}
endif;

function get_file($path) {

	if ( function_exists('realpath') )
		$path = realpath($path);

	if ( ! $path || ! @is_file($path) )
		return '';

	return @file_get_contents($path);
}

$load = preg_replace( '/[^a-z0-9,_-]+/i', '', $_GET['load'] );
$load = explode(',', $load);

if ( empty($load) )
	exit;

require(ABSPATH . WPINC . '/script-loader.php');
require(ABSPATH . WPINC . '/version.php');

$compress = ( isset($_GET['c']) && $_GET['c'] );
$force_gzip = ( $compress && 'gzip' == $_GET['c'] );
$expires_offset = 31536000;
$out = '';
$suffix = ( isset($_GET['d']) && $_GET['d'] ) ? '.dev' : '';
print("/** SUFFIX: '$suffix' **/\n");

$wp_scripts = new WP_Scripts();
wp_default_scripts($wp_scripts);
// TODO: Fix the dev/min dichotemy to mimic wordpress functionality
wp_register_script( 'pte'
	, PLUGINPATH . "/js/pte.full${suffix}.js"
	, array('jquery','imgareaselect')
);
wp_register_script( 'jquery-json'
	, PLUGINPATH . '/apps/jquery.json-2.2.min.js'
	, array('jquery')
);
wp_localize_script('pte'
	, 'objectL10n'
	, array( 'pastebin_create_error' => __( 'Sorry, there was a problem trying to send to pastebin', PTE_DOMAIN )
		, 'pastebin_url' => __( 'PASTEBIN URL:', PTE_DOMAIN )
		, 'aspect_ratio_disabled' => __( 'Disabling aspect ratio', PTE_DOMAIN )
		, 'crop_submit_data_error' => __( 'Error parsing selection information', PTE_DOMAIN )
	)
);

foreach( $load as $handle ) {
	if ( !array_key_exists($handle, $wp_scripts->registered) )
		continue;

	$path = ABSPATH . $wp_scripts->registered[$handle]->src;
	print("/** PATH: '$path' **/\n");
	$out .= get_file($path) . "\n";
	$localized = $wp_scripts->get_data($handle, 'data');
	if ( !empty($localized) )
		$out .= $localized . "\n";
}

header('Content-Type: application/x-javascript; charset=UTF-8');
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");

if ( $compress && ! ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) ) {
	header('Vary: Accept-Encoding'); // Handle proxies
	if ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') && function_exists('gzdeflate') && ! $force_gzip ) {
		header('Content-Encoding: deflate');
		$out = gzdeflate( $out, 3 );
	} elseif ( false !== stripos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') && function_exists('gzencode') ) {
		header('Content-Encoding: gzip');
		$out = gzencode( $out, 3 );
	}
}

echo $out;
exit;
