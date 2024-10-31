<?php


/**
 * Add required styles and scripts for Satellite.
 */
add_action( 'admin_enqueue_scripts', 'satellite_admin_scripts' );
function satellite_admin_scripts() {
	// Include scripts
	wp_enqueue_script( 'satellite', SATELLITE_URL . 'js/scripts.js', array( 'jquery' ), SATELLITE_VERSION );
}


/**
 * Add Satellite admin menu to the dashboard.
 */
add_action( 'admin_menu', 'satellite_dashboard_admin_menu' );
function satellite_dashboard_admin_menu() {
	// Get WordPress home url
	$domain = get_home_url();
	// Normalize domain
	$domain = satellite_domain_normalized( $domain );
	// Add menu to dashboard
	add_menu_page( 'Optimization report for ' . $domain, 'Optimize', 'read', 'satellite-dashboard', 'satellite_dashboard', SATELLITE_URL . 'images/icon.png' );
}


/**
 * Add Satellite widget to the dashboard.
 */
function satellite_dashboard() {
	?>
	<div class="satellite_dashboard" data-view="dashboard">
		<div class="satellite-dashboard-wrap">
			<div class="satellite-connecting">Connecting...</div>
		</div><!-- .satellite-dashboard-wrap -->
	</div><!-- .satellite_dashboard -->
	<?php
}


/**
 * Add Satellite widget to the dashboard.
 */
add_action( 'wp_dashboard_setup', 'satellite_dashboard_widget_setup' );
function satellite_dashboard_widget_setup() {
	wp_add_dashboard_widget(
		'satellite_dashboard_widget',
		'Satellite Optimization Check',
		'satellite_dashboard_widget_display'
	);	
}


/**
 * Output the contents of Satellite dashboard widget.
 */
function satellite_dashboard_widget_display() {
	?>
	<div class="satellite_widget" data-view="widget">
		<div class="satellite-connecting">Connecting...</div>
	</div><!-- .satellite_widget -->
	<?php
}


/**
 * Call to Satellite
 */
add_action( 'wp_ajax_satellite_ajax_call', 'satellite_call' );
function satellite_call() {
	global $current_user;
	// Check if is an ajax call
	if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) :
		return '';
	endif;
	// Checks if it's a multisite
	if ( function_exists( 'is_multisite' ) && is_multisite() ) :
		$admin_email = get_site_option( 'admin_email' ); 
	else :
		$admin_email = get_option( 'admin_email' ); 
	endif;
	// Set attributes
	$atts 		= array(
		'satellite_plugin'	=> satellite_domain_normalized(),
		'email'				=> ( ! empty( $admin_email ) ? $admin_email : $current_user->user_email ),
		'rand'				=> time(),
		'view'				=> $_REQUEST['view'],
	);
	// Make url for curl call
	$url		= add_query_arg( $atts, SATELLITE_SITE );
	// Init curl
	$ch			= curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
	curl_setopt( $ch, CURLOPT_FAILONERROR, TRUE );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
	curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
	$response	= curl_exec( $ch );
	$response	= json_decode( $response );
	curl_close( $ch );
	// Return the response
	wp_send_json_success( array( 'html' => $response->html ) );
}
