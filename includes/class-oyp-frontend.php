<?php
/**
 * Frontend class
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;

/**
 * OYP_Frontend class.
 */
class OYP_Frontend {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// WooCommerce gallery integration
		add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'add_3d_model_to_gallery' ), 10, 2 );
		add_action( 'woocommerce_product_thumbnails', array( $this, 'add_3d_thumbnail' ) );
		
		// Add 3D viewer to product images
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'maybe_add_3d_viewer' ), 25 );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_product() ) {
			return;
		}

		global $post;
		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		// Only load if 3D viewer is enabled for this product
		if ( empty( $settings['enabled'] ) || empty( $settings['model_url'] ) ) {
			return;
		}

		// Three.js library
		wp_enqueue_script(
			'threejs',
			'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
			array(),
			'128',
			true
		);

		// GLTFLoader
		wp_enqueue_script(
			'threejs-gltf-loader',
			'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js',
			array( 'threejs' ),
			'128',
			true
		);

		// OrbitControls
		wp_enqueue_script(
			'threejs-orbit-controls',
			'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js',
			array( 'threejs' ),
			'128',
			true
		);

		// Main 3D viewer script
		wp_enqueue_script(
			'oyp-3d-viewer',
			OYP_PLUGIN_URL . 'assets/js/3d-viewer.js',
			array( 'threejs', 'threejs-gltf-loader', 'threejs-orbit-controls' ),
			OYP_VERSION,
			true
		);

		// Frontend styles
		wp_enqueue_style(
			'oyp-frontend',
			OYP_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			OYP_VERSION
		);

		// Localize script data
		wp_localize_script( 'oyp-3d-viewer', 'oyp_viewer', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'product_id' => $post->ID,
			'settings' => $settings,
			'strings' => array(
				'loading' => __( 'Loading 3D model...', 'observe-yor-product' ),
				'error' => __( 'Error loading 3D model', 'observe-yor-product' ),
				'webgl_not_supported' => __( 'WebGL not supported', 'observe-yor-product' ),
				'reset_view' => __( 'Reset View', 'observe-yor-product' ),
				'3d_model' => __( '3D Model', 'observe-yor-product' ),
				'drag_to_rotate' => __( 'Drag to rotate', 'observe-yor-product' ),
				'scroll_to_zoom' => __( 'Scroll to zoom', 'observe-yor-product' ),
				'click_and_drag' => __( 'Click and drag to interact with the 3D model', 'observe-yor-product' ),
			)
		) );
	}

	/**
	 * Add 3D model slide to WooCommerce gallery.
	 *
	 * @param string $html The current thumbnail HTML.
	 * @param int    $attachment_id The attachment ID.
	 * @return string Modified HTML.
	 */
	public function add_3d_model_to_gallery( $html, $attachment_id ) {
		global $post;
		
		if ( ! $post ) {
			return $html;
		}

		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		if ( empty( $settings['enabled'] ) || empty( $settings['model_url'] ) ) {
			return $html;
		}

		// Get the first image attachment ID to insert 3D model after it
		$attachment_ids = $this->get_gallery_image_ids();
		
		if ( ! empty( $attachment_ids ) && $attachment_id === $attachment_ids[0] ) {
			$html .= $this->get_3d_model_slide_html();
		}

		return $html;
	}

	/**
	 * Add 3D thumbnail to product gallery thumbnails.
	 */
	public function add_3d_thumbnail() {
		global $post;
		
		if ( ! $post ) {
			return;
		}

		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		if ( empty( $settings['enabled'] ) || empty( $settings['model_url'] ) ) {
			return;
		}

		echo $this->get_3d_thumbnail_html();
	}

	/**
	 * Maybe add 3D viewer to product summary.
	 */
	public function maybe_add_3d_viewer() {
		global $post;
		
		if ( ! $post ) {
			return;
		}

		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		if ( empty( $settings['enabled'] ) || empty( $settings['model_url'] ) ) {
			return;
		}

		// Add hidden 3D viewer container that will be shown when thumbnail is clicked
		echo '<div id="oyp-3d-viewer-container" class="oyp-3d-viewer-container" style="display: none;">';
		echo $this->get_3d_viewer_html();
		echo '</div>';
	}

	/**
	 * Get gallery image IDs.
	 *
	 * @return array
	 */
	private function get_gallery_image_ids() {
		global $product;
		
		if ( ! $product ) {
			return array();
		}

		return $product->get_gallery_image_ids();
	}

	/**
	 * Get 3D model slide HTML for gallery.
	 *
	 * @return string
	 */
	private function get_3d_model_slide_html() {
		global $post;
		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		// Create a placeholder image for PhotoSwipe compatibility
		$placeholder_image = OYP_PLUGIN_URL . 'assets/images/3d-model-placeholder.svg';
		
		ob_start();
		?>
		<div class="woocommerce-product-gallery__image oyp-3d-slide" data-oyp-3d-model="true">
			<a href="<?php echo esc_url( $placeholder_image ); ?>" 
			   class="oyp-3d-gallery-link" 
			   data-3d-model="true"
			   data-model-url="<?php echo esc_url( $settings['model_url'] ); ?>"
			   data-size="800x600"
			   data-caption="Interactive 3D Model"
			   data-product-id="<?php echo esc_attr( $post->ID ); ?>">
				<div class="oyp-3d-viewer-placeholder">
					<div class="oyp-3d-badge">
						<span class="oyp-3d-badge-text"><?php esc_html_e( '3D', 'observe-yor-product' ); ?></span>
						<div class="oyp-3d-help-tooltip">
							<p><?php esc_html_e( 'Click and drag to interact with the 3D model', 'observe-yor-product' ); ?></p>
						</div>
					</div>
					<div class="oyp-3d-loading">
						<div class="oyp-3d-spinner"></div>
						<p><?php esc_html_e( 'Loading 3D model...', 'observe-yor-product' ); ?></p>
					</div>
					<div id="oyp-3d-viewer-<?php echo esc_attr( $post->ID ); ?>" 
						 class="oyp-3d-viewer" 
						 data-model-url="<?php echo esc_url( $settings['model_url'] ); ?>"
						 data-product-id="<?php echo esc_attr( $post->ID ); ?>">
					</div>
					<div class="oyp-3d-controls">
						<button type="button" class="oyp-reset-view" title="<?php esc_attr_e( 'Reset View', 'observe-yor-product' ); ?>">
							<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
								<path d="M8 2L8 6L5 3L8 2Z" fill="currentColor"/>
								<path d="M8 14L8 10L11 13L8 14Z" fill="currentColor"/>
								<path d="M2 8L6 8L3 11L2 8Z" fill="currentColor"/>
								<circle cx="8" cy="8" r="2" stroke="currentColor" fill="none"/>
							</svg>
						</button>
					</div>
				</div>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get 3D thumbnail HTML.
	 *
	 * @return string
	 */
	private function get_3d_thumbnail_html() {
		global $post;
		
		ob_start();
		?>
		<div class="oyp-3d-thumbnail" data-oyp-3d-thumb="true">
			<div class="oyp-3d-thumb-content">
				<div class="oyp-3d-badge">
					<span class="oyp-3d-badge-text"><?php esc_html_e( '3D', 'observe-yor-product' ); ?></span>
				</div>
				<div class="oyp-3d-thumb-icon">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M12 2L22 8.5V15.5L12 22L2 15.5V8.5L12 2Z" stroke="currentColor" stroke-width="2" fill="none"/>
						<path d="M12 22V12" stroke="currentColor" stroke-width="2"/>
						<path d="M2 8.5L12 12L22 8.5" stroke="currentColor" stroke-width="2"/>
					</svg>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get 3D viewer HTML.
	 *
	 * @return string
	 */
	private function get_3d_viewer_html() {
		global $post;
		$settings = get_post_meta( $post->ID, '_oyp_3d_settings', true );
		
		ob_start();
		?>
		<div class="oyp-3d-viewer-wrapper">
			<div class="oyp-3d-viewer" 
				 id="oyp-3d-viewer-main-<?php echo esc_attr( $post->ID ); ?>"
				 data-model-url="<?php echo esc_url( $settings['model_url'] ); ?>"
				 data-product-id="<?php echo esc_attr( $post->ID ); ?>">
			</div>
			<div class="oyp-3d-ui-overlay">
				<div class="oyp-3d-controls">
					<button type="button" class="oyp-reset-view" title="<?php esc_attr_e( 'Reset View', 'observe-yor-product' ); ?>">
						<?php esc_html_e( 'Reset View', 'observe-yor-product' ); ?>
					</button>
				</div>
				<?php if ( ! empty( $settings['scale_dimensions']['width'] ) ): ?>
				<div class="oyp-3d-scale-ruler">
					<div class="oyp-scale-ruler-line"></div>
					<div class="oyp-scale-ruler-label">
						<span class="oyp-scale-value">--</span>
						<span class="oyp-scale-unit"><?php echo esc_html( $settings['scale_unit'] ?? 'cm' ); ?></span>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}