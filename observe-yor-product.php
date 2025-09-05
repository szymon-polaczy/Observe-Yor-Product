<?php
/**
 * Plugin Name: Observe-Yor-Product (OYP) — 3D Viewer for WooCommerce
 * Plugin URI: https://github.com/your-repo/observe-yor-product
 * Description: A WordPress/WooCommerce plugin that adds a high-quality, configurable 3D model viewer to product pages with full integration into the default WooCommerce gallery/slider.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: observe-yor-product
 * Domain Path: /languages
 * Requires at least: 6.3
 * Tested up to: 6.5
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'OYP_PLUGIN_FILE' ) ) {
	define( 'OYP_PLUGIN_FILE', __FILE__ );
}

define( 'OYP_VERSION', '1.0.0' );
define( 'OYP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'OYP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OYP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
final class Observe_Yor_Product {

	/**
	 * The single instance of the class.
	 *
	 * @var Observe_Yor_Product|null
	 */
	private static $instance = null;

	/**
	 * Gets the main instance.
	 *
	 * @return Observe_Yor_Product
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants() {
		define( 'OYP_MIN_WC_VERSION', '6.0' );
		define( 'OYP_MIN_PHP_VERSION', '7.4' );
		define( 'OYP_MIN_WP_VERSION', '5.8' );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		// Core includes - always needed
		require_once OYP_PLUGIN_PATH . 'includes/class-oyp-install.php';
		require_once OYP_PLUGIN_PATH . 'includes/class-oyp-upload-handler.php';
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( OYP_PLUGIN_FILE, array( 'OYP_Install', 'install' ) );
		register_deactivation_hook( OYP_PLUGIN_FILE, array( 'OYP_Install', 'deactivate' ) );
		
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		// Check if WooCommerce is active
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Check minimum requirements
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Initialize upload handler for all contexts
		OYP_Upload_Handler::init();

		// Include and initialize components after WordPress is loaded
		if ( $this->is_request( 'admin' ) ) {
			require_once OYP_PLUGIN_PATH . 'includes/admin/class-oyp-admin.php';
			new OYP_Admin();
		}

		if ( $this->is_request( 'frontend' ) ) {
			require_once OYP_PLUGIN_PATH . 'includes/class-oyp-frontend.php';
			new OYP_Frontend();
		}

		do_action( 'oyp_loaded' );
	}

	/**
	 * Load plugin textdomain for translations.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'observe-yor-product',
			false,
			dirname( OYP_PLUGIN_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check minimum requirements.
	 *
	 * @return bool
	 */
	private function check_requirements() {
		$errors = array();

		// Check PHP version
		if ( version_compare( PHP_VERSION, OYP_MIN_PHP_VERSION, '<' ) ) {
			/* translators: %1$s: current PHP version, %2$s: required PHP version */
			$errors[] = sprintf( __( 'PHP version %1$s is not supported. Please upgrade to PHP %2$s or higher.', 'observe-yor-product' ), PHP_VERSION, OYP_MIN_PHP_VERSION );
		}

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), OYP_MIN_WP_VERSION, '<' ) ) {
			/* translators: %1$s: current WordPress version, %2$s: required WordPress version */
			$errors[] = sprintf( __( 'WordPress version %1$s is not supported. Please upgrade to WordPress %2$s or higher.', 'observe-yor-product' ), get_bloginfo( 'version' ), OYP_MIN_WP_VERSION );
		}

		// Check WooCommerce version
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, OYP_MIN_WC_VERSION, '<' ) ) {
			/* translators: %1$s: current WooCommerce version, %2$s: required WooCommerce version */
			$errors[] = sprintf( __( 'WooCommerce version %1$s is not supported. Please upgrade to WooCommerce %2$s or higher.', 'observe-yor-product' ), WC_VERSION, OYP_MIN_WC_VERSION );
		}

		if ( ! empty( $errors ) ) {
			add_action( 'admin_notices', function() use ( $errors ) {
				$this->requirements_notice( $errors );
			} );
			return false;
		}

		return true;
	}

	/**
	 * Show WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Observe-Yor-Product requires WooCommerce to be installed and active.', 'observe-yor-product' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Show requirements notice.
	 *
	 * @param array $errors Array of error messages.
	 */
	private function requirements_notice( $errors ) {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Observe-Yor-Product cannot run due to the following requirements:', 'observe-yor-product' ); ?></p>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return OYP_VERSION;
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( OYP_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin URL.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( OYP_PLUGIN_FILE ) );
	}
}

/**
 * Main instance of the plugin.
 *
 * @return Observe_Yor_Product
 */
function OYP() {
	return Observe_Yor_Product::instance();
}

// Initialize the plugin safely
add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	
	if ( ! defined( 'OYP_LOADED' ) ) {
		define( 'OYP_LOADED', true );
		OYP();
	}
}, 10 );