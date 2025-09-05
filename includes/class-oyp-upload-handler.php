<?php
/**
 * Upload handler for 3D models
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;

/**
 * OYP_Upload_Handler class.
 */
class OYP_Upload_Handler {

	/**
	 * Initialize upload handling.
	 */
	public static function init() {
		add_filter( 'upload_mimes', array( __CLASS__, 'add_3d_mime_types' ), 10, 1 );
		add_filter( 'wp_check_filetype_and_ext', array( __CLASS__, 'check_3d_filetype' ), 10, 5 );
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'handle_3d_upload_prefilter' ), 10, 1 );
		add_action( 'wp_handle_upload', array( __CLASS__, 'handle_3d_upload_complete' ), 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', array( __CLASS__, 'prepare_3d_attachment_js' ), 10, 3 );
		add_action( 'add_attachment', array( __CLASS__, 'set_3d_attachment_metadata' ) );
		
		// Increase upload limits for 3D models
		add_filter( 'upload_size_limit', array( __CLASS__, 'increase_upload_size_limit' ), 20, 1 );
		add_filter( 'wp_max_upload_size', array( __CLASS__, 'increase_wp_max_upload_size' ), 20, 1 );
		
		// Handle PHP configuration for 3D uploads
		add_action( 'wp_ajax_oyp_upload_3d_model', array( __CLASS__, 'adjust_php_limits_for_upload' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_adjust_php_limits' ), 1 );
	}

	/**
	 * Add 3D model MIME types to WordPress.
	 *
	 * @param array $mimes Existing MIME types.
	 * @return array Modified MIME types.
	 */
	public static function add_3d_mime_types( $mimes ) {
		$model_mimes = array(
			// glTF formats
			'gltf'     => 'model/gltf+json',
			'glb'      => 'model/gltf-binary',
			
			// Other 3D formats
			'obj'      => 'model/obj',
			'fbx'      => 'application/octet-stream',
			'dae'      => 'model/vnd.collada+xml',
			'3ds'      => 'application/x-3ds',
			'ply'      => 'application/octet-stream',
			'stl'      => 'application/octet-stream',
			'x3d'      => 'model/x3d+xml',
			'x3dv'     => 'model/x3d+vrml',
			'wrl'      => 'model/vrml',
			
			// USDZ (iOS AR)
			'usdz'     => 'model/usd',
			'usda'     => 'model/usd',
			'usdc'     => 'model/usd',
			
			// Other formats
			'3mf'      => 'model/3mf',
			'amf'      => 'application/x-amf',
		);

		// Only add 3D MIME types if user has the capability
		if ( current_user_can( 'upload_3d_models' ) || current_user_can( 'manage_3d_models' ) ) {
			$mimes = array_merge( $mimes, $model_mimes );
		}

		return $mimes;
	}

	/**
	 * Check 3D model file types and extensions.
	 *
	 * @param array  $wp_check_filetype_and_ext File data.
	 * @param string $file                      Full path to the file.
	 * @param string $filename                  The name of the file.
	 * @param array  $mimes                     Allowed MIME types.
	 * @param string $real_mime                 Real MIME type.
	 * @return array Modified file data.
	 */
	public static function check_3d_filetype( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime = null ) {
		if ( ! empty( $wp_check_filetype_and_ext['ext'] ) && ! empty( $wp_check_filetype_and_ext['type'] ) ) {
			return $wp_check_filetype_and_ext;
		}

		$filetype = wp_check_filetype( $filename, $mimes );
		$ext = $filetype['ext'];
		$type = $filetype['type'];

		// Handle specific 3D model formats
		if ( ! $type && ! empty( $ext ) ) {
			switch ( $ext ) {
				case 'gltf':
					$type = 'model/gltf+json';
					break;
				case 'glb':
					$type = 'model/gltf-binary';
					break;
				case 'obj':
					$type = 'model/obj';
					break;
				case 'fbx':
					$type = 'application/octet-stream';
					break;
				case 'dae':
					$type = 'model/vnd.collada+xml';
					break;
				case '3ds':
					$type = 'application/x-3ds';
					break;
				case 'ply':
					$type = 'application/octet-stream';
					break;
				case 'stl':
					$type = 'application/octet-stream';
					break;
				case 'usdz':
				case 'usda':
				case 'usdc':
					$type = 'model/usd';
					break;
				case '3mf':
					$type = 'model/3mf';
					break;
				case 'amf':
					$type = 'application/x-amf';
					break;
			}

			if ( $type && self::is_3d_model_extension( $ext ) ) {
				$wp_check_filetype_and_ext['ext'] = $ext;
				$wp_check_filetype_and_ext['type'] = $type;
				$wp_check_filetype_and_ext['proper_filename'] = $filename;
			}
		}

		return $wp_check_filetype_and_ext;
	}

	/**
	 * Handle 3D model upload prefilter.
	 *
	 * @param array $file File data.
	 * @return array Modified file data.
	 */
	public static function handle_3d_upload_prefilter( $file ) {
		$filetype = wp_check_filetype( $file['name'] );
		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		// Check if this is a 3D model file
		if ( self::is_3d_model_extension( $ext ) ) {
			// Validate file size
			$max_size = self::get_max_upload_size();
			if ( $file['size'] > $max_size ) {
				$file['error'] = sprintf(
					__( '3D model file is too large. Maximum allowed size is %s.', 'observe-yor-product' ),
					size_format( $max_size )
				);
				return $file;
			}

			// Skip complex validation for now to avoid file access issues
			// Additional validation can be added later after the file is properly processed

			// Set proper MIME type for 3D models
			if ( empty( $filetype['type'] ) ) {
				$mime_types = self::get_3d_mime_types();
				if ( isset( $mime_types[ $ext ] ) ) {
					$file['type'] = $mime_types[ $ext ];
				}
			}
		}

		return $file;
	}

	/**
	 * Handle completed 3D model upload.
	 *
	 * @param array $upload Upload data.
	 * @param array $context Upload context.
	 * @return array Upload data.
	 */
	public static function handle_3d_upload_complete( $upload, $context ) {
		if ( isset( $upload['file'] ) && isset( $upload['url'] ) ) {
			$ext = strtolower( pathinfo( $upload['file'], PATHINFO_EXTENSION ) );
			
			if ( self::is_3d_model_extension( $ext ) ) {
				// Log successful 3D model upload
				do_action( 'oyp_3d_model_uploaded', $upload, $context );
			}
		}

		return $upload;
	}

	/**
	 * Prepare 3D model attachment data for JavaScript.
	 *
	 * @param array   $response   Array of attachment data.
	 * @param WP_Post $attachment Attachment post object.
	 * @param array   $meta       Array of attachment metadata.
	 * @return array Modified attachment data.
	 */
	public static function prepare_3d_attachment_js( $response, $attachment, $meta ) {
		$ext = strtolower( pathinfo( $attachment->post_title, PATHINFO_EXTENSION ) );
		
		if ( self::is_3d_model_extension( $ext ) ) {
			$response['type'] = '3d-model';
			$response['subtype'] = $ext;
			$response['is_3d_model'] = true;
			
			// Add 3D-specific metadata
			if ( ! empty( $meta ) ) {
				$response['3d_metadata'] = array(
					'format' => $ext,
					'file_size' => isset( $meta['filesize'] ) ? $meta['filesize'] : filesize( get_attached_file( $attachment->ID ) ),
					'dimensions' => isset( $meta['dimensions'] ) ? $meta['dimensions'] : null,
				);
			}

			// Set icon for media library
			$response['icon'] = self::get_3d_model_icon_url( $ext );
		}

		return $response;
	}

	/**
	 * Set metadata for 3D model attachments.
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	public static function set_3d_attachment_metadata( $attachment_id ) {
		$file_path = get_attached_file( $attachment_id );
		$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		if ( self::is_3d_model_extension( $ext ) ) {
			$metadata = array(
				'format' => $ext,
				'file_size' => filesize( $file_path ),
				'is_3d_model' => true,
			);

			// Try to extract additional metadata for specific formats
			if ( in_array( $ext, array( 'gltf', 'glb' ) ) ) {
				$gltf_metadata = self::extract_gltf_metadata( $file_path, $ext );
				$metadata = array_merge( $metadata, $gltf_metadata );
			}

			update_post_meta( $attachment_id, '_oyp_3d_metadata', $metadata );
			update_post_meta( $attachment_id, '_oyp_is_3d_model', true );
		}
	}

	/**
	 * Get maximum upload size for 3D models.
	 *
	 * @return int Maximum size in bytes.
	 */
	public static function get_max_upload_size() {
		$max_size = get_option( 'oyp_max_file_size', 50 ); // Default 50MB
		$max_size_bytes = $max_size * 1024 * 1024; // Convert MB to bytes
		
		return $max_size_bytes;
	}

	/**
	 * Get 3D model MIME types array.
	 *
	 * @return array MIME types.
	 */
	private static function get_3d_mime_types() {
		return array(
			'gltf'     => 'model/gltf+json',
			'glb'      => 'model/gltf-binary',
			'obj'      => 'model/obj',
			'fbx'      => 'application/octet-stream',
			'dae'      => 'model/vnd.collada+xml',
			'3ds'      => 'application/x-3ds',
			'ply'      => 'application/octet-stream',
			'stl'      => 'application/octet-stream',
			'x3d'      => 'model/x3d+xml',
			'x3dv'     => 'model/x3d+vrml',
			'wrl'      => 'model/vrml',
			'usdz'     => 'model/usd',
			'usda'     => 'model/usd',
			'usdc'     => 'model/usd',
			'3mf'      => 'model/3mf',
			'amf'      => 'application/x-amf',
		);
	}

	/**
	 * Check if extension is a 3D model format.
	 *
	 * @param string $ext File extension.
	 * @return bool True if 3D model extension.
	 */
	public static function is_3d_model_extension( $ext ) {
		$model_extensions = array(
			'gltf', 'glb', 'obj', 'fbx', 'dae', '3ds', 'ply', 'stl',
			'x3d', 'x3dv', 'wrl', 'usdz', 'usda', 'usdc', '3mf', 'amf'
		);
		
		return in_array( strtolower( $ext ), $model_extensions );
	}

	/**
	 * Validate glTF/GLB file.
	 *
	 * @param string $file_path Path to file.
	 * @param string $ext File extension.
	 * @return array Validation result.
	 */
	private static function validate_gltf_file( $file_path, $ext ) {
		$result = array(
			'valid' => true,
			'error' => ''
		);

		// Skip validation if file doesn't exist (may have been moved already)
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			// Don't fail validation if file doesn't exist - may have been processed already
			return $result;
		}

		// Basic file size check
		$file_size = filesize( $file_path );
		if ( $file_size === false || $file_size === 0 ) {
			$result['valid'] = false;
			$result['error'] = __( 'File appears to be empty or corrupted.', 'observe-yor-product' );
			return $result;
		}

		if ( $ext === 'glb' ) {
			// Basic GLB validation - check magic number
			$handle = fopen( $file_path, 'rb' );
			if ( $handle ) {
				$magic = fread( $handle, 4 );
				fclose( $handle );
				
				if ( $magic && $magic !== 'glTF' ) {
					$result['valid'] = false;
					$result['error'] = __( 'Invalid GLB file format - missing glTF signature.', 'observe-yor-product' );
				}
			}
		} elseif ( $ext === 'gltf' ) {
			// Basic GLTF validation - check if valid JSON (first 1024 bytes)
			$handle = fopen( $file_path, 'r' );
			if ( $handle ) {
				$sample = fread( $handle, 1024 );
				fclose( $handle );
				
				// Look for basic JSON structure
				if ( $sample && ( strpos( $sample, '{' ) === false || strpos( $sample, 'asset' ) === false ) ) {
					$result['valid'] = false;
					$result['error'] = __( 'Invalid GLTF file format - does not appear to be a valid glTF JSON file.', 'observe-yor-product' );
				}
			}
		}

		return $result;
	}

	/**
	 * Extract metadata from glTF/GLB files.
	 *
	 * @param string $file_path Path to file.
	 * @param string $ext File extension.
	 * @return array Metadata.
	 */
	private static function extract_gltf_metadata( $file_path, $ext ) {
		$metadata = array();

		if ( $ext === 'gltf' ) {
			$content = file_get_contents( $file_path );
			$json = json_decode( $content, true );
			
			if ( $json && isset( $json['asset'] ) ) {
				$metadata['gltf_version'] = $json['asset']['version'] ?? null;
				$metadata['generator'] = $json['asset']['generator'] ?? null;
				$metadata['copyright'] = $json['asset']['copyright'] ?? null;
				
				// Count elements
				$metadata['scenes_count'] = isset( $json['scenes'] ) ? count( $json['scenes'] ) : 0;
				$metadata['nodes_count'] = isset( $json['nodes'] ) ? count( $json['nodes'] ) : 0;
				$metadata['meshes_count'] = isset( $json['meshes'] ) ? count( $json['meshes'] ) : 0;
				$metadata['materials_count'] = isset( $json['materials'] ) ? count( $json['materials'] ) : 0;
				$metadata['textures_count'] = isset( $json['textures'] ) ? count( $json['textures'] ) : 0;
				$metadata['animations_count'] = isset( $json['animations'] ) ? count( $json['animations'] ) : 0;
			}
		}

		return $metadata;
	}

	/**
	 * Get icon URL for 3D model type.
	 *
	 * @param string $ext File extension.
	 * @return string Icon URL.
	 */
	private static function get_3d_model_icon_url( $ext ) {
		// Check if plugin has custom icons
		$icon_path = OYP_PLUGIN_URL . 'assets/images/3d-icon-' . $ext . '.png';
		$icon_file = OYP_PLUGIN_PATH . 'assets/images/3d-icon-' . $ext . '.png';
		
		if ( file_exists( $icon_file ) ) {
			return $icon_path;
		}
		
		// Fallback to generic 3D icon
		$generic_icon = OYP_PLUGIN_URL . 'assets/images/3d-model-icon.png';
		$generic_file = OYP_PLUGIN_PATH . 'assets/images/3d-model-icon.png';
		
		if ( file_exists( $generic_file ) ) {
			return $generic_icon;
		}
		
		// WordPress default
		return includes_url( 'images/media/default.png' );
	}

	/**
	 * Increase upload size limit for 3D models.
	 *
	 * @param int $limit Current upload size limit.
	 * @return int Modified upload size limit.
	 */
	public static function increase_upload_size_limit( $limit ) {
		// Only increase limit when uploading 3D models
		if ( self::is_3d_upload_request() ) {
			$oyp_limit = self::get_max_upload_size();
			return max( $limit, $oyp_limit );
		}
		
		return $limit;
	}

	/**
	 * Increase WordPress max upload size for 3D models.
	 *
	 * @param int $max_size Current max upload size.
	 * @return int Modified max upload size.
	 */
	public static function increase_wp_max_upload_size( $max_size ) {
		// Only increase limit when uploading 3D models
		if ( self::is_3d_upload_request() ) {
			$oyp_limit = self::get_max_upload_size();
			return max( $max_size, $oyp_limit );
		}
		
		return $max_size;
	}

	/**
	 * Check if current request is for 3D model upload.
	 *
	 * @return bool True if 3D model upload request.
	 */
	private static function is_3d_upload_request() {
		// Check if this is our AJAX upload
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'oyp_upload_3d_model' ) {
				return true;
			}
		}
		
		// Check if uploaded file is a 3D model
		if ( isset( $_FILES ) && ! empty( $_FILES ) ) {
			foreach ( $_FILES as $file ) {
				if ( isset( $file['name'] ) ) {
					$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
					if ( self::is_3d_model_extension( $ext ) ) {
						return true;
					}
				}
			}
		}
		
		// Check if we're on the product edit page
		global $pagenow, $post_type;
		if ( is_admin() && $pagenow === 'post.php' && $post_type === 'product' ) {
			return true;
		}
		
		// Check if we're on media upload page with 3D context
		if ( is_admin() && $pagenow === 'media-upload.php' ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Adjust PHP limits for 3D model upload.
	 */
	public static function adjust_php_limits_for_upload() {
		if ( ! current_user_can( 'upload_3d_models' ) && ! current_user_can( 'manage_3d_models' ) ) {
			return;
		}
		
		self::increase_php_limits();
	}

	/**
	 * Maybe adjust PHP limits on admin pages.
	 */
	public static function maybe_adjust_php_limits() {
		if ( ! is_admin() ) {
			return;
		}
		
		// Only adjust on product pages or media upload pages
		global $pagenow, $post_type;
		
		$adjust_on_pages = array(
			'post.php',
			'post-new.php',
			'media-upload.php',
			'async-upload.php',
			'admin-ajax.php'
		);
		
		if ( in_array( $pagenow, $adjust_on_pages ) ) {
			if ( $post_type === 'product' || $pagenow === 'media-upload.php' || $pagenow === 'async-upload.php' || $pagenow === 'admin-ajax.php' ) {
				self::increase_php_limits();
			}
		}
	}

	/**
	 * Increase PHP limits for 3D model uploads.
	 */
	private static function increase_php_limits() {
		$max_size_mb = get_option( 'oyp_max_file_size', 50 );
		$max_size_bytes = $max_size_mb * 1024 * 1024;
		
		// Calculate required limits (add some buffer)
		$post_max_size = ( $max_size_mb + 10 ) . 'M'; // Add 10MB buffer for form data
		$upload_max_filesize = $max_size_mb . 'M';
		$memory_limit = ( $max_size_mb * 3 ) . 'M'; // 3x for processing
		$max_execution_time = 300; // 5 minutes
		$max_input_time = 300; // 5 minutes
		
		// Only increase limits, never decrease
		if ( self::get_bytes_from_ini( ini_get( 'post_max_size' ) ) < $max_size_bytes + ( 10 * 1024 * 1024 ) ) {
			@ini_set( 'post_max_size', $post_max_size );
		}
		
		if ( self::get_bytes_from_ini( ini_get( 'upload_max_filesize' ) ) < $max_size_bytes ) {
			@ini_set( 'upload_max_filesize', $upload_max_filesize );
		}
		
		if ( self::get_bytes_from_ini( ini_get( 'memory_limit' ) ) < ( $max_size_mb * 3 * 1024 * 1024 ) ) {
			@ini_set( 'memory_limit', $memory_limit );
		}
		
		if ( (int) ini_get( 'max_execution_time' ) < $max_execution_time && (int) ini_get( 'max_execution_time' ) !== 0 ) {
			@ini_set( 'max_execution_time', $max_execution_time );
		}
		
		if ( (int) ini_get( 'max_input_time' ) < $max_input_time && (int) ini_get( 'max_input_time' ) !== -1 ) {
			@ini_set( 'max_input_time', $max_input_time );
		}
	}

	/**
	 * Convert ini setting to bytes.
	 *
	 * @param string $val The ini setting value.
	 * @return int Bytes.
	 */
	private static function get_bytes_from_ini( $val ) {
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
}