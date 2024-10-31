<?php
/*
Plugin Name: Satellite Optimization Monitoring
Plugin URI: https://satellite.fm
Description: Actionable WordPress Optimization for non developers. After activating this plugin, click the Satellite link in your WordPress admin.
Version: 1.2.3
Author: Satellite.fm
Author URI: https://satellite.fm/
License: GPLv2 or later
*/

define( 'SATELLITE_VERSION', '1.2.3' );

define( 'SATELLITE_SITE', 'https://satellite.fm/' );

define( 'SATELLITE_FILE', __FILE__ );

define( 'SATELLITE_PATH', plugin_dir_path( __FILE__ ) );

define( 'SATELLITE_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );

if ( file_exists( SATELLITE_PATH . '/admin/dashboard.php' ) ) :
	require SATELLITE_PATH . '/admin/dashboard.php';
endif;

/**
* Listening to requests
**/

add_action( 'template_redirect', 'satellite_scan', 0 );
if ( ! function_exists( 'satellite_scan' ) ) :
	function satellite_scan( $hook ) {
		// Check for key
		if ( isset( $_REQUEST['spk'] ) && $_REQUEST['spk'] == 'tNxUcyrNaCfQBenQ' ) :
			// Show json content headers
			header( 'Content-Type: application/json' );
			header( 'Access-Control-Allow-Origin: *' );
			// Activation check
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'client_ok' ) :
				print json_encode( array( 'client' => 'ok' ) );
			endif;
			// Version check
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'plugin_version' ) :
				print json_encode( array( 'version' => SATELLITE_VERSION ) );
			endif;
			// Getting the total file size
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'file_total_size' ) :
				$db_size	= satellite_get_db_size();
				$file_size	= satellite_get_site_size();
				print json_encode( array( 'file_size' => $file_size, 'db_size' => $db_size ) );
			endif;
			// Getting the disallowed plugins
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'plugin_report' ) :
				$report = satellite_get_plugin_report();
				print json_encode( $report );
			endif;
			// Getting the available updates
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'updates_report' ) :
				$report = satellite_get_updates_report();
				print json_encode( $report );
			endif;
			// Getting comments report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'comments_report' ) :
				$report = satellite_get_comments_report();
				print json_encode( $report );
			endif;
			// Getting trackback report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'trackback_report' ) :
				$report = satellite_get_trackback_report();
				print json_encode( $report );
			endif;
			// Getting pingback report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'pingback_report' ) :
				$report = satellite_get_pingback_report();
				print json_encode( $report );
			endif;
			// Getting pings report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'pings_report' ) :
				$report = satellite_get_pings_report();
				print json_encode( $report );
			endif;
			// Getting posts report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'posts_report' ) :
				$report = satellite_get_posts_report();
				print json_encode( $report );
			endif;
			// Getting functions status report
			if ( isset( $_REQUEST['get'] ) && $_REQUEST['get'] == 'functions_report' ) :
				$report = satellite_get_functions_report();
				print json_encode( $report );
			endif;
			exit( 0 );
		endif;
	}
endif;

/**
* Functions
**/

/*****/

if ( ! function_exists( 'satellite_get_home_path' ) ) :
    function satellite_get_home_path() {
		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) :
			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
			$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
			$home_path = trailingslashit( $home_path );
		else :
			$home_path = ABSPATH;
		endif;
		return str_replace( '\\', '/', $home_path );
    }
endif;

/*****/

if ( ! function_exists( 'satellite_get_folder_size' ) ) :
	function satellite_get_folder_size( $folder_path ) {
		$size = 0;
		// The files and folders we want to exclude
		$blacklist = array( '.', '..', '.git', 'wp-admin', 'wp-includes', 'cache', 'backupbuddy_backups', 'backupbuddy_temp', 'pb_backupbuddy', 'sxd', 'envato-backups', 'uber-grid-cache', 'error.log', '.back', 'backup', 'backups', 'cgi-bin', 'htdocs', 'dev', 'error_log', 'wordpress.sql', 'managewp', 'simple-backup', 'wp-snapshots', '__MACOSX', 'updraft', 'upgrade', 'wc-logs', 'wpallimport' );
		if ( is_dir( $folder_path ) ) :
			$results = scandir( $folder_path );
			foreach ( $results as $result ) :
				if ( ! in_array( $result, $blacklist ) ) :
					if ( substr( $folder_path, -1 ) != '/' ) :
						$folder_path.= '/';
					endif;
					$the_path = $folder_path . $result;
					if ( is_dir( $the_path ) ) :
						$size+= satellite_get_folder_size( $the_path . '/' );
					elseif ( is_file( $the_path ) ) :
						$size+= filesize( $the_path );
					endif;
				endif;
			endforeach;
		endif;
		return $size;
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_site_size' ) ) :
	function satellite_get_site_size() {
		$root_folder = satellite_get_home_path();
		$total_size = satellite_get_folder_size( $root_folder );
		return $total_size;
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_db_size' ) ) :
	function satellite_get_db_size() {
		global $wpdb;
		$tables = $wpdb->get_results( 'SHOW TABLE STATUS' );
		$dbsize = 0;
		foreach ( $tables as $table ) :
			$dbsize += $table->Data_length + $table->Index_length;
		endforeach;
		return $dbsize;
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_plugin_report' ) ) :
	function satellite_get_plugin_report( $host = '' ) {

		global $wpdb;

		// Include some require functions
		if ( ! function_exists( 'get_plugins' ) ) :
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		endif;

		// Get plugins report
		wp_cache_set( 'plugins', '', 'plugins', -1 );
		$plugins			= get_plugins();
		$plugins_files		= array();
		$plugins_info		= array();
		$plugins_active		= array();
		$plugins_inactive	= array();
		if ( $plugins ) :
			foreach ( $plugins as $plugin_file => $plugin ) :
				$plugin_slug = explode( '/', $plugin_file );
				$plugin_slug = @$plugin_slug[ 0 ];
				$plugin_slug = str_replace( '.php', '', $plugin_slug );
				if ( $plugin['Name'] == 'Satellite Optimization Monitoring' ) :
					continue;
				endif;
				$plugins_files[] 				= $plugin_slug;
				$plugins_info[ $plugin_slug ]	= array(
					'file'				=> $plugin_file,
					'slug'				=> $plugin_slug,
					'name'				=> $plugin['Name'],
					'current_version'	=> $plugin['Version'],
				);
				if ( is_plugin_active( $plugin_file ) ) :
					$plugins_active[ $plugin_slug ] = $plugin['Name'];
				else :
					$plugins_inactive[ $plugin_slug ] = $plugin['Name'];
				endif;
			endforeach;
		endif;

		// Get themes report
		$themes			= wp_get_themes();
		$themes_found	= array();
		$themes_info	= array();
		$theme_active	= get_stylesheet();
		if ( $themes ) :
			foreach ( $themes as $stylesheet => $theme ) :
				$themes_found[ $stylesheet ] = $theme->display( 'Name' );
				$themes_info[ $stylesheet ]	= array(
					'slug'				=> $stylesheet,
					'name'				=> $theme->display( 'Name' ),
					'current_version'	=> $theme->display( 'Version' ),
					'status'			=> ( $stylesheet == $theme_active ? 'yes' : 'no' ),
				);
			endforeach;
		endif;

		// Getting the current permalink structure
		$permalink_structure 	= '';
		$results				= $wpdb->get_results( 'SELECT `option_value` FROM `' . $wpdb->options . '` WHERE `option_name` = "permalink_structure"' );
		foreach ( $results as $result ) :
			$permalink_structure = $result->option_value;
		endforeach;

		return array(
			'all_plugins'			=> $plugins_files,
			'plugins_info'			=> $plugins_info,
			'active_plugins'		=> $plugins_active,
			'inactive_plugins'		=> $plugins_inactive,
			'all_themes'			=> $themes_found,
			'themes_info'			=> $themes_info,
			'active_theme'			=> $theme_active,
			'active_theme_name'		=> $themes_info[$theme_active]['name'],
			'genesis_theme'			=> ( $theme_active == 'genesis' ? 'Genesis' : '' ),
			'permalink_structure'	=> $permalink_structure,
		);

	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_updates_report' ) ) :
	function satellite_get_updates_report() {
		global $wp_local_package, $wpdb, $wp_version;
		// Include some require functions
		if ( ! function_exists( 'get_plugins' ) ) :
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		endif;
		if ( ! function_exists( 'get_plugin_updates' ) ) :
			require_once ABSPATH . 'wp-admin/includes/update.php';
		endif;
		// Force check updates
		$wp_version_check = wp_version_check( array(), TRUE );
		// Get availables core updates
		$core_updates	= get_core_updates();
		$core_update	= 'upgrade';
		if ( ! isset( $core_updates[0]->response ) || 'latest' == $core_updates[0]->response ) :
			$core_update = 'latest';
		endif;
		// Force check updates for plugins before get them
		wp_update_plugins();
		// Get availables updates for plugins
		$plugins		= get_plugin_updates();
		$plugin_updates	= array();
		if ( $plugins ) :
			foreach ( $plugins as $plugin_file => $plugin ) :
				$plugin_slug = explode( '/', $plugin_file );
				$plugin_slug = @$plugin_slug[ 0 ];
				$plugin_slug = str_replace( '.php', '', $plugin_slug );
				$plugin_updates[ $plugin_slug ] = array(
					'file'				=> $plugin_file,
					'slug'				=> $plugin_slug,
					'name'				=> $plugin->Name,
					'current_version'	=> $plugin->Version,
					'last_version'		=> $plugin->update->new_version,
				);
			endforeach;
		endif;
		// Force check updates for themes before get them
		wp_update_themes();
		// Get availables updates for themes
		$themes			= get_theme_updates();
		$themes_updates = array();
		if ( $themes ) :
			foreach ( $themes as $stylesheet => $theme ) :
				$themes_updates[ $stylesheet ] = array(
					'slug'				=> $stylesheet,
					'name'				=> $theme->display( 'Name' ),
					'current_version'	=> $theme->display( 'Version' ),
					'last_version'		=> $theme->update['new_version'],
				);
			endforeach;
		endif;
		// Return response
		return array(
			'current_version'	=> $wp_version,
			'core_update'		=> $core_update,
			'plugin_updates' 	=> $plugin_updates,
			'themes_updates'	=> $themes_updates,
		);
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_comments_report' ) ) :
	function satellite_get_comments_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Get total number of comments
		$total	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = ""' );
		// Get total number of spam comments
		$spam	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "" AND `comment_approved` = "spam"' );
		// Output
		return array(
			'total'	=> $total,
			'spam'	=> $spam
		);
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_trackback_report' ) ) :
	function satellite_get_trackback_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Get total number of trackback
		$total	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "trackback"' );
		// Get total number of spam trackback
		$spam	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "trackback" AND `comment_approved` = "spam"' );
		// Output
		return array(
			'total'	=> $total,
			'spam'	=> $spam
		);
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_pingback_report' ) ) :
	function satellite_get_pingback_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Get total number of pingback
		$total	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "pingback"' );
		// Get total number of spam pingback
		$spam	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "pingback" AND `comment_approved` = "spam"' );
		// Output
		return array(
			'total'	=> $total,
			'spam'	=> $spam
		);
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_pings_report' ) ) :
	function satellite_get_pings_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Get total number of pings
		$total	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "pings"' );
		// Get total number of spam pings
		$spam	= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->comments . '` WHERE `comment_type` = "pings" AND `comment_approved` = "spam"' );
		// Output
		return array(
			'total'	=> $total,
			'spam'	=> $spam
		);
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_posts_report' ) ) :
	function satellite_get_posts_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Output
		$output		= array();
		// Get all post types in the DB
		$post_types	= $wpdb->get_results( 'SELECT DISTINCT `post_type` FROM `' . $wpdb->posts . '`' );
		if ( $post_types ) :
			foreach ( $post_types as $post_type ) :
				// Get total number of items
				$total		= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->posts . '` WHERE `post_type` = "' . $post_type->post_type . '"' );
				// Get total number of trash items
				$trash		= $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->posts . '` WHERE `post_status` = "trash" AND `post_type` = "' . $post_type->post_type . '"' );
				$output[]	= array(
					'post_type'	=> $post_type->post_type,
					'total'		=> $total,
					'trash'		=> $trash,
				);
			endforeach;
		endif;
		// Output
		return $output;
	}
endif;

/*****/

if ( ! function_exists( 'satellite_get_functions_report' ) ) :
	function satellite_get_functions_report( $markup = true, $translate = true ) {
		global $wpdb;
		// Functions to check
		$functions	= array( 'exec', 'fsockopen', );
		// Output
		$output		= array();
		// Check functions
		foreach ( $functions as $function ) :
			$output[ $function ] = ( function_exists( $function ) ? 1 : 0 );
		endforeach;
		// Output
		return $output;
	}
endif;

/*****/


/**
 * Normalize domain to match with Satellite reports.
 */
if ( ! function_exists( 'satellite_domain_normalized' ) ) :
	function satellite_domain_normalized() {
		$domain = strtolower( get_bloginfo( 'home' ) );
		$domain = str_replace( 'https://', '', $domain );
		$domain = str_replace( 'http://', '', $domain );
		$domain = str_replace( 'www.', '', $domain );
		if ( substr( $domain, -1 ) == '/' ) :
			$domain = substr( $domain, 0, strlen( $domain ) - 1 );
		endif;
		// Output
		return $domain;
	}
endif;

/*****/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
