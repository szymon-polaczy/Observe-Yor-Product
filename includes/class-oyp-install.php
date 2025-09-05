<?php
/**
 * Installation related functions and actions.
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;

/**
 * OYP_Install Class.
 */
class OYP_Install {

	/**
	 * Plugin activation.
	 */
	public static function install() {
		if ( ! defined( 'OYP_INSTALLING' ) ) {
			define( 'OYP_INSTALLING', true );
		}

		self::check_version();
		self::create_capabilities();
		self::create_options();
		self::maybe_update_db_version();

		do_action( 'oyp_installed' );
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		// Clean up any temporary data if needed
		do_action( 'oyp_deactivated' );
	}

	/**
	 * Check plugin version and run the updater if necessary.
	 */
	private static function check_version() {
		if ( get_option( 'oyp_version' ) !== OYP_VERSION ) {
			self::install();
			do_action( 'oyp_updated' );
		}
	}

	/**
	 * Create capabilities for the plugin.
	 */
	private static function create_capabilities() {
		// Add capabilities to shop manager and administrator
		$roles = array( 'shop_manager', 'administrator' );
		
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( 'manage_3d_models' );
				$role->add_cap( 'upload_3d_models' );
			}
		}
	}

	/**
	 * Default options.
	 *
	 * @return array
	 */
	private static function get_default_options() {
		return array(
			'oyp_enabled' => 'yes',
			'oyp_supported_formats' => array( 
				'gltf', 'glb', 'obj', 'fbx', 'dae', '3ds', 'ply', 'stl',
				'x3d', 'x3dv', 'wrl', 'usdz', 'usda', 'usdc', '3mf', 'amf'
			),
			'oyp_max_file_size' => 50, // MB
			'oyp_enable_encryption' => 'no',
			'oyp_enable_lazy_loading' => 'yes',
			'oyp_default_background_type' => 'gradient',
			'oyp_default_background_color1' => '#ffffff',
			'oyp_default_background_color2' => '#f0f0f0',
			'oyp_default_lighting_preset' => 'studio',
			'oyp_enable_zoom' => 'yes',
			'oyp_enable_pan' => 'yes',
			'oyp_enable_rotate' => 'yes',
			'oyp_zoom_min' => 0.5,
			'oyp_zoom_max' => 3.0,
			'oyp_autorotate_default' => 'no',
			'oyp_autorotate_speed' => 1.0,
		);
	}

	/**
	 * Create default options.
	 */
	private static function create_options() {
		$default_options = self::get_default_options();
		
		foreach ( $default_options as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $default_value );
			}
		}
	}

	/**
	 * Update DB version to current.
	 */
	private static function maybe_update_db_version() {
		if ( get_option( 'oyp_version' ) !== OYP_VERSION ) {
			update_option( 'oyp_version', OYP_VERSION );
		}
	}

	/**
	 * Get the current DB version.
	 *
	 * @return string
	 */
	public static function get_db_version() {
		return get_option( 'oyp_version', '1.0.0' );
	}
}