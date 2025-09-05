<?php
/**
 * Admin class
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;

/**
 * OYP_Admin class.
 */
class OYP_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_notices', array( $this, 'php_limits_admin_notice' ) );
		
		// Add product metaboxes
		add_action( 'add_meta_boxes', array( $this, 'add_product_metaboxes' ) );
		add_action( 'save_post', array( $this, 'save_product_meta' ) );
		
		// Handle 3D model uploads
		add_action( 'wp_ajax_oyp_upload_3d_model', array( $this, 'handle_3d_model_upload' ) );
		add_action( 'wp_ajax_oyp_delete_3d_model', array( $this, 'handle_3d_model_delete' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( '3D Viewer Settings', 'observe-yor-product' ),
			__( '3D Viewer', 'observe-yor-product' ),
			'manage_3d_models',
			'oyp-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Initialize admin settings.
	 */
	public function admin_init() {
		register_setting( 'oyp_settings', 'oyp_settings' );
		
		// Register individual settings
		register_setting( 'oyp_settings', 'oyp_max_file_size', array(
			'type' => 'integer',
			'default' => 50,
			'sanitize_callback' => array( $this, 'sanitize_max_file_size' )
		) );
		register_setting( 'oyp_settings', 'oyp_enable_lazy_loading' );
		register_setting( 'oyp_settings', 'oyp_enable_encryption' );
		register_setting( 'oyp_settings', 'oyp_default_background_type' );
		register_setting( 'oyp_settings', 'oyp_default_background_color1' );
		register_setting( 'oyp_settings', 'oyp_default_background_color2' );
		register_setting( 'oyp_settings', 'oyp_default_lighting_preset' );
		register_setting( 'oyp_settings', 'oyp_enable_zoom' );
		register_setting( 'oyp_settings', 'oyp_enable_pan' );
		register_setting( 'oyp_settings', 'oyp_enable_rotate' );
		register_setting( 'oyp_settings', 'oyp_zoom_min' );
		register_setting( 'oyp_settings', 'oyp_zoom_max' );
		register_setting( 'oyp_settings', 'oyp_autorotate_default' );
		register_setting( 'oyp_settings', 'oyp_autorotate_speed' );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function admin_scripts( $hook ) {
		global $post_type;

		// Only load on product pages and our settings page
		if ( 'product' === $post_type || 'oyp-settings' === $hook ) {
			wp_enqueue_media();
			
			wp_enqueue_script(
				'oyp-admin',
				OYP_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery', 'wp-util' ),
				OYP_VERSION,
				true
			);

			wp_enqueue_style(
				'oyp-admin',
				OYP_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				OYP_VERSION
			);

			$supported_formats = get_option( 'oyp_supported_formats', array( 'gltf', 'glb', 'obj', 'fbx', 'dae', '3ds', 'ply', 'stl', 'x3d', 'x3dv', 'wrl', 'usdz', 'usda', 'usdc', '3mf', 'amf' ) );
			
			wp_localize_script( 'oyp-admin', 'oyp_admin', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'oyp_admin_nonce' ),
				'supported_formats' => $supported_formats,
				'max_file_size' => OYP_Upload_Handler::get_max_upload_size(),
				'max_file_size_mb' => get_option( 'oyp_max_file_size', 50 ),
				'strings' => array(
					'upload_3d_model' => __( 'Upload 3D Model', 'observe-yor-product' ),
					'select_3d_model' => __( 'Select 3D Model', 'observe-yor-product' ),
					'remove_model' => __( 'Remove Model', 'observe-yor-product' ),
					'supported_formats' => sprintf( __( 'Supported formats: %s', 'observe-yor-product' ), strtoupper( implode( ', ', $supported_formats ) ) ),
					'file_too_large' => sprintf( __( 'File is too large. Maximum size is %sMB.', 'observe-yor-product' ), get_option( 'oyp_max_file_size', 50 ) ),
					'invalid_file_type' => sprintf( __( 'Invalid file type. Supported formats: %s', 'observe-yor-product' ), strtoupper( implode( ', ', $supported_formats ) ) ),
				)
			) );
		}
	}

	/**
	 * Add product metaboxes.
	 */
	public function add_product_metaboxes() {
		add_meta_box(
			'oyp-3d-viewer',
			__( '3D Viewer Settings', 'observe-yor-product' ),
			array( $this, 'product_3d_viewer_metabox' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * Product 3D viewer metabox content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function product_3d_viewer_metabox( $post ) {
		wp_nonce_field( 'oyp_save_product_meta', 'oyp_product_meta_nonce' );
		
		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		$default_settings = array(
			'enabled' => false,
			'model_id' => '',
			'model_url' => '',
			'model_filename' => '',
			'background_type' => 'gradient',
			'background_color1' => '#ffffff',
			'background_color2' => '#f0f0f0',
			'lighting_preset' => 'studio',
			'enable_zoom' => true,
			'enable_pan' => true,
			'enable_rotate' => true,
			'zoom_min' => 0.5,
			'zoom_max' => 3.0,
			'autorotate' => false,
			'autorotate_speed' => 1.0,
			'scale_unit' => 'cm',
			'scale_dimensions' => array(
				'width' => '',
				'height' => '',
				'depth' => ''
			),
			'annotations' => array()
		);
		
		$settings = wp_parse_args( $settings, $default_settings );
		
		include_once OYP_PLUGIN_PATH . 'templates/admin/product-3d-viewer-metabox.php';
	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['oyp_product_meta_nonce'] ) || ! wp_verify_nonce( $_POST['oyp_product_meta_nonce'], 'oyp_save_product_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'manage_3d_models' ) ) {
			return;
		}

		if ( isset( $_POST['oyp_3d_settings'] ) ) {
			$settings = array();
			$settings['enabled'] = isset( $_POST['oyp_3d_settings']['enabled'] );
			$settings['model_id'] = sanitize_text_field( $_POST['oyp_3d_settings']['model_id'] ?? '' );
			$settings['model_url'] = esc_url_raw( $_POST['oyp_3d_settings']['model_url'] ?? '' );
			$settings['model_filename'] = sanitize_file_name( $_POST['oyp_3d_settings']['model_filename'] ?? '' );
			$settings['background_type'] = sanitize_text_field( $_POST['oyp_3d_settings']['background_type'] ?? 'gradient' );
			$settings['background_color1'] = sanitize_hex_color( $_POST['oyp_3d_settings']['background_color1'] ?? '#ffffff' );
			$settings['background_color2'] = sanitize_hex_color( $_POST['oyp_3d_settings']['background_color2'] ?? '#f0f0f0' );
			$settings['lighting_preset'] = sanitize_text_field( $_POST['oyp_3d_settings']['lighting_preset'] ?? 'studio' );
			$settings['enable_zoom'] = isset( $_POST['oyp_3d_settings']['enable_zoom'] );
			$settings['enable_pan'] = isset( $_POST['oyp_3d_settings']['enable_pan'] );
			$settings['enable_rotate'] = isset( $_POST['oyp_3d_settings']['enable_rotate'] );
			$settings['zoom_min'] = floatval( $_POST['oyp_3d_settings']['zoom_min'] ?? 0.5 );
			$settings['zoom_max'] = floatval( $_POST['oyp_3d_settings']['zoom_max'] ?? 3.0 );
			$settings['autorotate'] = isset( $_POST['oyp_3d_settings']['autorotate'] );
			$settings['autorotate_speed'] = floatval( $_POST['oyp_3d_settings']['autorotate_speed'] ?? 1.0 );
			$settings['scale_unit'] = sanitize_text_field( $_POST['oyp_3d_settings']['scale_unit'] ?? 'cm' );
			
			// Handle scale dimensions
			$settings['scale_dimensions'] = array(
				'width' => floatval( $_POST['oyp_3d_settings']['scale_dimensions']['width'] ?? 0 ),
				'height' => floatval( $_POST['oyp_3d_settings']['scale_dimensions']['height'] ?? 0 ),
				'depth' => floatval( $_POST['oyp_3d_settings']['scale_dimensions']['depth'] ?? 0 )
			);

			update_post_meta( $post_id, '_oyp_3d_settings', $settings );
		}
	}

	/**
	 * Handle 3D model upload via AJAX.
	 */
	public function handle_3d_model_upload() {
		check_ajax_referer( 'oyp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_3d_models' ) ) {
			wp_send_json_error( __( 'You do not have permission to upload 3D models.', 'observe-yor-product' ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( __( 'No file uploaded.', 'observe-yor-product' ) );
		}

		$file = $_FILES['file'];
		$allowed_types = get_option( 'oyp_supported_formats', array( 'gltf', 'glb', 'obj', 'fbx', 'dae', '3ds', 'ply', 'stl', 'x3d', 'x3dv', 'wrl', 'usdz', 'usda', 'usdc', '3mf', 'amf' ) );
		$max_size = OYP_Upload_Handler::get_max_upload_size();

		// Check file size
		if ( $file['size'] > $max_size ) {
			wp_send_json_error( sprintf( __( 'File is too large. Maximum size is %s.', 'observe-yor-product' ), size_format( $max_size ) ) );
		}

		// Check file extension
		$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( ! OYP_Upload_Handler::is_3d_model_extension( $file_extension ) || ! in_array( $file_extension, $allowed_types ) ) {
			wp_send_json_error( sprintf( __( 'Invalid file type. Supported formats: %s', 'observe-yor-product' ), implode( ', ', array_map( 'strtoupper', $allowed_types ) ) ) );
		}

		// Define MIME types for 3D models
		$mime_types = array(
			'gltf' => 'model/gltf+json',
			'glb' => 'model/gltf-binary',
			'obj' => 'model/obj',
			'fbx' => 'application/octet-stream',
			'dae' => 'model/vnd.collada+xml',
			'3ds' => 'application/x-3ds',
			'ply' => 'application/octet-stream',
			'stl' => 'application/octet-stream',
			'x3d' => 'model/x3d+xml',
			'x3dv' => 'model/x3d+vrml',
			'wrl' => 'model/vrml',
			'usdz' => 'model/usd',
			'usda' => 'model/usd',
			'usdc' => 'model/usd',
			'3mf' => 'model/3mf',
			'amf' => 'application/x-amf',
		);

		$mime_type = isset( $mime_types[ $file_extension ] ) ? $mime_types[ $file_extension ] : 'application/octet-stream';

		// Handle the upload with custom overrides for 3D models
		add_filter( 'upload_mimes', array( 'OYP_Upload_Handler', 'add_3d_mime_types' ), 999 );
		
		$upload_overrides = array( 
			'test_form' => false
		);
		
		$upload = wp_handle_upload( $file, $upload_overrides );
		
		remove_filter( 'upload_mimes', array( 'OYP_Upload_Handler', 'add_3d_mime_types' ), 999 );
		
		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( $upload['error'] );
		}

		// Create attachment
		$attachment = array(
			'post_mime_type' => $mime_type,
			'post_title' => sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'] );
		
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( __( 'Failed to create attachment.', 'observe-yor-product' ) );
		}

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		wp_send_json_success( array(
			'attachment_id' => $attachment_id,
			'url' => $upload['url'],
			'filename' => $file['name']
		) );
	}

	/**
	 * Handle 3D model deletion via AJAX.
	 */
	public function handle_3d_model_delete() {
		check_ajax_referer( 'oyp_admin_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_3d_models' ) ) {
			wp_send_json_error( __( 'You do not have permission to delete 3D models.', 'observe-yor-product' ) );
		}

		$attachment_id = intval( $_POST['attachment_id'] ?? 0 );
		
		if ( ! $attachment_id || ! wp_delete_attachment( $attachment_id, true ) ) {
			wp_send_json_error( __( 'Failed to delete 3D model.', 'observe-yor-product' ) );
		}

		wp_send_json_success();
	}

	/**
	 * Sanitize max file size setting.
	 *
	 * @param int $value The file size value.
	 * @return int Sanitized file size.
	 */
	public function sanitize_max_file_size( $value ) {
		$value = intval( $value );
		
		// Ensure reasonable limits
		if ( $value < 1 ) {
			$value = 1;
		} elseif ( $value > 500 ) {
			$value = 500;
		}
		
		return $value;
	}

	/**
	 * Show admin notice if PHP limits are too low.
	 */
	public function php_limits_admin_notice() {
		global $pagenow, $post_type;
		
		// Only show on product pages and plugin settings
		if ( ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) && $post_type !== 'product' ) {
			return;
		}
		
		if ( $pagenow !== 'post.php' && $pagenow !== 'post-new.php' && ! isset( $_GET['page'] ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && $_GET['page'] !== 'oyp-settings' ) {
			return;
		}
		
		$required_size = get_option( 'oyp_max_file_size', 50 ) * 1024 * 1024; // Convert to bytes
		$post_max_size = $this->get_bytes_from_ini( ini_get( 'post_max_size' ) );
		$upload_max_filesize = $this->get_bytes_from_ini( ini_get( 'upload_max_filesize' ) );
		
		$issues = array();
		
		if ( $post_max_size < $required_size + ( 10 * 1024 * 1024 ) ) { // Add 10MB buffer
			$issues[] = sprintf( 
				__( 'PHP post_max_size (%s) should be at least %s for optimal 3D model uploads.', 'observe-yor-product' ),
				ini_get( 'post_max_size' ),
				size_format( $required_size + ( 10 * 1024 * 1024 ) )
			);
		}
		
		if ( $upload_max_filesize < $required_size ) {
			$issues[] = sprintf(
				__( 'PHP upload_max_filesize (%s) should be at least %s for 3D model uploads.', 'observe-yor-product' ),
				ini_get( 'upload_max_filesize' ),
				size_format( $required_size )
			);
		}
		
		if ( ! empty( $issues ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><strong><?php esc_html_e( '3D Viewer Upload Notice:', 'observe-yor-product' ); ?></strong></p>
				<ul>
					<?php foreach ( $issues as $issue ) : ?>
						<li><?php echo esc_html( $issue ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p>
					<?php esc_html_e( 'The plugin will try to adjust these limits automatically, but you may need to contact your hosting provider if uploads still fail.', 'observe-yor-product' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=oyp-settings' ) ); ?>"><?php esc_html_e( 'View current limits', 'observe-yor-product' ); ?></a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Convert ini setting to bytes (helper method).
	 *
	 * @param string $val The ini setting value.
	 * @return int Bytes.
	 */
	private function get_bytes_from_ini( $val ) {
		$val = trim( $val );
		$last = strtolower( $val[ strlen( $val ) - 1 ] );
		$val = (int) $val;
		
		switch ( $last ) {
			case 'g':
				$val *= 1024;
				// Fall through.
			case 'm':
				$val *= 1024;
				// Fall through.
			case 'k':
				$val *= 1024;
		}
		
		return $val;
	}

	/**
	 * Settings page content.
	 */
	public function settings_page() {
		include_once OYP_PLUGIN_PATH . 'templates/admin/settings-page.php';
	}
}