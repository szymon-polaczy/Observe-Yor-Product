<?php
/**
 * Admin Settings Page Template
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<h1><?php esc_html_e( '3D Viewer Settings', 'observe-yor-product' ); ?></h1>
	
	<form method="post" action="options.php">
		<?php settings_fields( 'oyp_settings' ); ?>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="oyp_max_file_size"><?php esc_html_e( 'Maximum File Size (MB)', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="number" id="oyp_max_file_size" name="oyp_max_file_size" value="<?php echo esc_attr( get_option( 'oyp_max_file_size', 50 ) ); ?>" min="1" max="500" />
						<p class="description">
							<?php esc_html_e( 'Maximum allowed file size for 3D model uploads. Default: 50MB', 'observe-yor-product' ); ?><br>
							<strong><?php esc_html_e( 'Current WordPress limit:', 'observe-yor-product' ); ?></strong> <?php echo esc_html( size_format( wp_max_upload_size() ) ); ?><br>
							<strong><?php esc_html_e( 'Current 3D model limit:', 'observe-yor-product' ); ?></strong> <?php echo esc_html( size_format( OYP_Upload_Handler::get_max_upload_size() ) ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="oyp_enable_lazy_loading"><?php esc_html_e( 'Enable Lazy Loading', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="oyp_enable_lazy_loading" name="oyp_enable_lazy_loading" value="yes" <?php checked( get_option( 'oyp_enable_lazy_loading', 'yes' ), 'yes' ); ?> />
						<p class="description"><?php esc_html_e( 'Load 3D models only when they come into view.', 'observe-yor-product' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="oyp_enable_encryption"><?php esc_html_e( 'Enable Model Encryption', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="oyp_enable_encryption" name="oyp_enable_encryption" value="yes" <?php checked( get_option( 'oyp_enable_encryption', 'no' ), 'yes' ); ?> />
						<p class="description"><?php esc_html_e( 'Encrypt 3D model files for basic protection (experimental feature).', 'observe-yor-product' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Default Settings', 'observe-yor-product' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Default 3D Viewer Settings', 'observe-yor-product' ); ?></legend>
							
							<label>
								<strong><?php esc_html_e( 'Background:', 'observe-yor-product' ); ?></strong><br>
								<select name="oyp_default_background_type">
									<option value="solid" <?php selected( get_option( 'oyp_default_background_type', 'gradient' ), 'solid' ); ?>><?php esc_html_e( 'Solid Color', 'observe-yor-product' ); ?></option>
									<option value="gradient" <?php selected( get_option( 'oyp_default_background_type', 'gradient' ), 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'observe-yor-product' ); ?></option>
								</select>
							</label><br><br>

							<label>
								<strong><?php esc_html_e( 'Background Color 1:', 'observe-yor-product' ); ?></strong><br>
								<input type="color" name="oyp_default_background_color1" value="<?php echo esc_attr( get_option( 'oyp_default_background_color1', '#ffffff' ) ); ?>" />
							</label><br><br>

							<label>
								<strong><?php esc_html_e( 'Background Color 2:', 'observe-yor-product' ); ?></strong><br>
								<input type="color" name="oyp_default_background_color2" value="<?php echo esc_attr( get_option( 'oyp_default_background_color2', '#f0f0f0' ) ); ?>" />
							</label><br><br>

							<label>
								<strong><?php esc_html_e( 'Lighting Preset:', 'observe-yor-product' ); ?></strong><br>
								<select name="oyp_default_lighting_preset">
									<option value="studio" <?php selected( get_option( 'oyp_default_lighting_preset', 'studio' ), 'studio' ); ?>><?php esc_html_e( 'Studio', 'observe-yor-product' ); ?></option>
									<option value="outdoor" <?php selected( get_option( 'oyp_default_lighting_preset', 'studio' ), 'outdoor' ); ?>><?php esc_html_e( 'Outdoor', 'observe-yor-product' ); ?></option>
									<option value="soft" <?php selected( get_option( 'oyp_default_lighting_preset', 'studio' ), 'soft' ); ?>><?php esc_html_e( 'Soft', 'observe-yor-product' ); ?></option>
								</select>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Default Controls', 'observe-yor-product' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Default Control Settings', 'observe-yor-product' ); ?></legend>
							
							<label>
								<input type="checkbox" name="oyp_enable_zoom" value="yes" <?php checked( get_option( 'oyp_enable_zoom', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Enable Zoom', 'observe-yor-product' ); ?>
							</label><br>

							<label>
								<input type="checkbox" name="oyp_enable_pan" value="yes" <?php checked( get_option( 'oyp_enable_pan', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Enable Pan', 'observe-yor-product' ); ?>
							</label><br>

							<label>
								<input type="checkbox" name="oyp_enable_rotate" value="yes" <?php checked( get_option( 'oyp_enable_rotate', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Enable Rotate', 'observe-yor-product' ); ?>
							</label><br><br>

							<label>
								<strong><?php esc_html_e( 'Zoom Min:', 'observe-yor-product' ); ?></strong>
								<input type="number" name="oyp_zoom_min" value="<?php echo esc_attr( get_option( 'oyp_zoom_min', 0.5 ) ); ?>" min="0.1" max="5" step="0.1" />
							</label><br>

							<label>
								<strong><?php esc_html_e( 'Zoom Max:', 'observe-yor-product' ); ?></strong>
								<input type="number" name="oyp_zoom_max" value="<?php echo esc_attr( get_option( 'oyp_zoom_max', 3.0 ) ); ?>" min="0.1" max="10" step="0.1" />
							</label><br><br>

							<label>
								<input type="checkbox" name="oyp_autorotate_default" value="yes" <?php checked( get_option( 'oyp_autorotate_default', 'no' ), 'yes' ); ?> />
								<?php esc_html_e( 'Enable Auto Rotate by Default', 'observe-yor-product' ); ?>
							</label><br>

							<label>
								<strong><?php esc_html_e( 'Auto Rotate Speed:', 'observe-yor-product' ); ?></strong>
								<input type="number" name="oyp_autorotate_speed" value="<?php echo esc_attr( get_option( 'oyp_autorotate_speed', 1.0 ) ); ?>" min="0.1" max="5" step="0.1" />
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>

	<div class="oyp-info-section">
		<h2><?php esc_html_e( 'System Information', 'observe-yor-product' ); ?></h2>
		<table class="widefat" style="max-width: 600px;">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Plugin Version:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( OYP_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WordPress Version:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WooCommerce Version:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : esc_html__( 'Not Available', 'observe-yor-product' ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Version:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( PHP_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Upload Max Filesize:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP post_max_size:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( ini_get( 'post_max_size' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP upload_max_filesize:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP memory_limit:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP max_execution_time:', 'observe-yor-product' ); ?></strong></td>
					<td><?php echo esc_html( ini_get( 'max_execution_time' ) . 's' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>