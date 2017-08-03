<?php
/**
 * Plugin Name:     Easy Digital Downloads - Simplify Commerce Gateway
 * Plugin URI:      https://wordpress.org/plugins/edd-simplify-commerce-gateway
 * Description:     Adds a payment gateway for Simplify Commerce to Easy Digital Downloads
 * Version:         1.0.3
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-simplify-commerce
 *
 * @package         EDD\Gateway\SimplifyCommerce
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( ! class_exists( 'EDD_Simplify_Commerce' ) ) {


	/**
	 * Main EDD_Simplify_Commerce class
	 *
	 * @since       1.0.0
	 */
	class EDD_Simplify_Commerce {


		/**
		 * @var         EDD_Simplify_Commerce $instance The one true EDD_Simplify_Commerce
		 * @since       1.0.0
		 */
		private static $instance;


		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      self::$instance The one true EDD_Simplify_Commerce
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new EDD_Simplify_Commerce();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
			}

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function setup_constants() {
			// Plugin version
			define( 'EDD_SIMPLIFY_COMMERCE_VERSION', '1.0.2' );

			// Plugin path
			define( 'EDD_SIMPLIFY_COMMERCE_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_SIMPLIFY_COMMERCE_URL', plugin_dir_url( __FILE__ ) );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.0.0
		 * @return      void
		 */
		private function includes() {
			require_once EDD_SIMPLIFY_COMMERCE_DIR . 'includes/libraries/Simplify/Simplify.php';
			require_once EDD_SIMPLIFY_COMMERCE_DIR . 'includes/gateway.php';
		}


		/**
		 * Internationalization
		 *
		 * @access      public
		 * @since       1.0.0
		 * @return      void
		 */
		public function load_textdomain() {
			// Set filter for language directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_simplify_commerce_language_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), '' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-simplify-commerce', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-simplify-commerce/' . $mofile;

			if( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-simplify-commerce/ folder
				load_textdomain( 'edd-simplify-commerce', $mofile_global );
			} elseif( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-simplify-commerce/languages/ folder
				load_textdomain( 'edd-simplify-commerce', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'edd-simplify-commerce', false, $lang_dir );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_Simplify_Commerce
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Simplify_Commerce The one true EDD_Simplify_Commerce
 */
function EDD_Simplify_Commerce_load() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once 'includes/class.s214-edd-activation.php';
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		return EDD_Simplify_Commerce::instance();
	} else {
		return EDD_Simplify_Commerce::instance();
	}
}
add_action( 'plugins_loaded', 'EDD_Simplify_Commerce_load' );
