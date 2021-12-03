<?php
/**
 * Plugin Name: Theme Json Demo
 * Text Domain: theme-json-demo
 * Domain Path: /languages
 * Plugin URI: https://themejason.com
 * Assets URI: https://themejason.com
 * Author: Theme Jason
 * Author URI: https://themejason.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Description: This plugin makes it possible to view all the amazing styles Theme Jason has to offer. 
 * Requires PHP: 7.0
 * Requires At Least: 5.8
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class ThemeJsonDemo {

	const TJD_DIRECTORY = WP_PLUGIN_DIR . '/theme-json-demo';

	function __construct() {
		add_action( 'wp_body_open', array( $this, 'top_bar' ) );
		add_filter( 'stylesheet_directory', array( $this, 'override_directory' ), 10 );
		add_filter( 'template_redirect', array( $this, 'change_theme' ), 10 );
	}

	/**
	 * Adds the theme selection top bar.
	 *
	 * @return void
	 */
	public function top_bar() {
		$themes = array();
		foreach ( $this->get_available_themes() as $key => $value ) {
			$themes[ $value ] = $this->get_theme_name_from_folder( $value );
		}

		wp_enqueue_style( 'tjd-top-bar-css', plugin_dir_url( __FILE__ ) . 'assets/css/top-bar.css', array(), time(), 'all' );
		wp_enqueue_script( 'tjd-top-bar-js', plugin_dir_url( __FILE__ ) . 'assets/js/top-bar.js', array( 'jquery' ), time(), true );

		$data = array(
			'themes'  => $themes,
			'current' => $this->get_current_tjd_theme(),
		);

		wp_localize_script( 'tjd-top-bar-js', 'tjdData', $data );
		require self::TJD_DIRECTORY . '/partials/top-bar.php';
	}

	/**
	 * Overrides the stylesheet directory when trying to get the theme.json file.
	 *
	 * @param string $template_dir The theme stylesheet directory.
	 * @return string
	 */
	public function override_directory( $template_dir ) {

		// Prevent from overriding the directory too much earlier.
		if ( ! did_action( 'template_redirect' ) ) {
			return $template_dir;
		}

		$theme_json_methods = array( 'get_file_path_from_theme' );
		$theme_json_files   = array( 'wp-includes/class-wp-theme-json-resolver.php', 'wp-content/plugins/gutenberg/lib/class-wp-theme-json-resolver-gutenberg.php' );
		$trace              = debug_backtrace();
		$valid              = false;

		$trace = array_slice( $trace, 0, 10 );
		foreach ( $theme_json_methods as $key => $value ) {
			$key = array_search( $value, array_column( $trace, 'function' ), true );

			foreach ( $theme_json_files as $k => $v ) {
				if ( substr( $trace[ $key ]['file'], - strlen( $v ) ) === $v ) {
					if ( ! empty( $trace[ $key ]['args'] ) && 'theme.json' === $trace[ $key ]['args'][0] ) { // Guarantee we are dealing with a theme.json request.
						$valid = true;
						break;
					}
				}
			}

			if ( $valid ) {
				break;
			}
		}

		if ( false === $valid ) {
			return $template_dir;
		}
		$theme          = $this->get_current_tjd_theme();
		$current_theme  = sanitize_key( wp_get_theme()->name );
		$theme_location = self::TJD_DIRECTORY . '/themes/' . $current_theme . '/' . $theme;

		if ( empty( $theme ) || ! is_readable( $theme_location . '/theme.json' ) ) {
			return $template_dir;
		}

		return $theme_location;
	}

	/**
	 * Changes the current theme using the tjd_theme GET parameter.
	 *
	 * @return void|string
	 */
	public function change_theme() {
		if ( empty( $_GET['style'] ) ) {
			return;
		}
		$current_theme = sanitize_key( wp_get_theme()->name );
		$theme         = sanitize_key( $_GET['style'] );
		$this->start_session();
		if ( empty( $theme ) || ! is_readable( self::TJD_DIRECTORY . '/themes/' . $current_theme . '/' . $theme . '/theme.json' ) ) {
			unset( $_SESSION['tjd_current_theme'] );
		} else {
			$_SESSION['tjd_current_theme'] = $theme;
		}

		wp_cache_flush();
		delete_transient( 'global_styles' );
		return $theme;
	}

	/**
	 * Returns all folders inside /themes directory with a theme.json file inside it.
	 *
	 * @return array
	 */
	private function get_available_themes() {
		$current_theme = sanitize_key( wp_get_theme()->name );
		$dirs          = scandir( self::TJD_DIRECTORY . '/themes/' . $current_theme );
		$dirs          = array_filter(
			$dirs,
			function( $dir ) use ( $current_theme ) {
				return is_dir( self::TJD_DIRECTORY . '/themes/' . $current_theme . '/' . $dir ) && is_readable( self::TJD_DIRECTORY . '/themes/' . $current_theme . '/' . $dir . '/theme.json' );
			}
		);
		return array_values( $dirs );
	}

	/**
	 * Parses the theme folder name to a pretty name.
	 *
	 * @param string $folder The folder name.
	 * @return string
	 */
	private function get_theme_name_from_folder( $folder ) {
		return ucwords( preg_replace( '/[-_]/', ' ', $folder ) );
	}

	/**
	 * Returns the current theme from the session.
	 *
	 * @return string
	 */
	private function get_current_tjd_theme() {
		$this->start_session();
		return empty( $_SESSION['tjd_current_theme'] ) ? false : sanitize_key( $_SESSION['tjd_current_theme'] );
	}

	/**
	 * Starts the session if it hasn't yet.
	 *
	 * @return void
	 */
	private function start_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}
}

new ThemeJsonDemo();
