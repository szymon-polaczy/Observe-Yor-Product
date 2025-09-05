<?php
/**
 * Product 3D Viewer Metabox Template
 *
 * @package ObserveYorProduct
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="oyp-metabox-wrapper">
	<table class="form-table">
		<tr>
			<th scope="row">
				<label for="oyp_3d_enabled"><?php esc_html_e( 'Enable 3D Viewer', 'observe-yor-product' ); ?></label>
			</th>
			<td>
				<input type="checkbox" id="oyp_3d_enabled" name="oyp_3d_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?> />
				<p class="description"><?php esc_html_e( 'Enable 3D model viewer for this product.', 'observe-yor-product' ); ?></p>
			</td>
		</tr>

		<tr class="oyp-conditional-row" data-depends="oyp_3d_enabled">
			<th scope="row">
				<label for="oyp_3d_model"><?php esc_html_e( '3D Model', 'observe-yor-product' ); ?></label>
			</th>
			<td>
				<div class="oyp-model-upload-area">
					<div class="oyp-model-preview" <?php echo $settings['model_url'] ? '' : 'style="display: none;"'; ?>>
						<div class="oyp-model-info">
							<strong><?php esc_html_e( 'Current Model:', 'observe-yor-product' ); ?></strong>
							<span class="oyp-model-filename"><?php echo esc_html( $settings['model_filename'] ); ?></span>
						</div>
						<button type="button" class="button oyp-remove-model"><?php esc_html_e( 'Remove Model', 'observe-yor-product' ); ?></button>
					</div>
					
					<div class="oyp-model-upload" <?php echo $settings['model_url'] ? 'style="display: none;"' : ''; ?>>
						<button type="button" class="button button-primary oyp-upload-model-btn">
							<?php esc_html_e( 'Upload 3D Model', 'observe-yor-product' ); ?>
						</button>
						<p class="description">
							<?php 
							$supported_formats = get_option( 'oyp_supported_formats', array( 'gltf', 'glb', 'obj', 'fbx', 'dae', '3ds', 'ply', 'stl', 'x3d', 'x3dv', 'wrl', 'usdz', 'usda', 'usdc', '3mf', 'amf' ) );
							printf( __( 'Supported formats: %s. Maximum file size: %s', 'observe-yor-product' ), 
								strtoupper( implode( ', ', $supported_formats ) ), 
								size_format( OYP_Upload_Handler::get_max_upload_size() )
							); 
							?>
						</p>
					</div>
					
					<input type="hidden" name="oyp_3d_settings[model_id]" value="<?php echo esc_attr( $settings['model_id'] ); ?>" class="oyp-model-id" />
					<input type="hidden" name="oyp_3d_settings[model_url]" value="<?php echo esc_attr( $settings['model_url'] ); ?>" class="oyp-model-url" />
					<input type="hidden" name="oyp_3d_settings[model_filename]" value="<?php echo esc_attr( $settings['model_filename'] ); ?>" class="oyp-model-filename" />
				</div>
			</td>
		</tr>
	</table>

	<div class="oyp-settings-tabs" data-depends="oyp_3d_enabled">
		<div class="oyp-tab-nav">
			<button type="button" class="oyp-tab-button active" data-tab="appearance"><?php esc_html_e( 'Appearance', 'observe-yor-product' ); ?></button>
			<button type="button" class="oyp-tab-button" data-tab="controls"><?php esc_html_e( 'Controls', 'observe-yor-product' ); ?></button>
			<button type="button" class="oyp-tab-button" data-tab="scale"><?php esc_html_e( 'Scale', 'observe-yor-product' ); ?></button>
		</div>

		<div class="oyp-tab-content active" data-tab="appearance">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="oyp_background_type"><?php esc_html_e( 'Background Type', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<select id="oyp_background_type" name="oyp_3d_settings[background_type]">
							<option value="solid" <?php selected( $settings['background_type'], 'solid' ); ?>><?php esc_html_e( 'Solid Color', 'observe-yor-product' ); ?></option>
							<option value="gradient" <?php selected( $settings['background_type'], 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'observe-yor-product' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="oyp_background_color1"><?php esc_html_e( 'Background Color 1', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="color" id="oyp_background_color1" name="oyp_3d_settings[background_color1]" value="<?php echo esc_attr( $settings['background_color1'] ); ?>" />
					</td>
				</tr>

				<tr class="oyp-gradient-row">
					<th scope="row">
						<label for="oyp_background_color2"><?php esc_html_e( 'Background Color 2', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="color" id="oyp_background_color2" name="oyp_3d_settings[background_color2]" value="<?php echo esc_attr( $settings['background_color2'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Second color for gradient background.', 'observe-yor-product' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="oyp_lighting_preset"><?php esc_html_e( 'Lighting Preset', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<select id="oyp_lighting_preset" name="oyp_3d_settings[lighting_preset]">
							<option value="studio" <?php selected( $settings['lighting_preset'], 'studio' ); ?>><?php esc_html_e( 'Studio', 'observe-yor-product' ); ?></option>
							<option value="outdoor" <?php selected( $settings['lighting_preset'], 'outdoor' ); ?>><?php esc_html_e( 'Outdoor', 'observe-yor-product' ); ?></option>
							<option value="soft" <?php selected( $settings['lighting_preset'], 'soft' ); ?>><?php esc_html_e( 'Soft', 'observe-yor-product' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
		</div>

		<div class="oyp-tab-content" data-tab="controls">
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled Controls', 'observe-yor-product' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="oyp_3d_settings[enable_zoom]" value="1" <?php checked( $settings['enable_zoom'] ); ?> />
							<?php esc_html_e( 'Zoom', 'observe-yor-product' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="oyp_3d_settings[enable_pan]" value="1" <?php checked( $settings['enable_pan'] ); ?> />
							<?php esc_html_e( 'Pan', 'observe-yor-product' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="oyp_3d_settings[enable_rotate]" value="1" <?php checked( $settings['enable_rotate'] ); ?> />
							<?php esc_html_e( 'Rotate', 'observe-yor-product' ); ?>
						</label>
					</td>
				</tr>

				<tr class="oyp-zoom-row">
					<th scope="row">
						<label for="oyp_zoom_min"><?php esc_html_e( 'Zoom Range', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<label>
							<?php esc_html_e( 'Min:', 'observe-yor-product' ); ?>
							<input type="number" id="oyp_zoom_min" name="oyp_3d_settings[zoom_min]" value="<?php echo esc_attr( $settings['zoom_min'] ); ?>" min="0.1" max="5" step="0.1" />
						</label>
						<label>
							<?php esc_html_e( 'Max:', 'observe-yor-product' ); ?>
							<input type="number" id="oyp_zoom_max" name="oyp_3d_settings[zoom_max]" value="<?php echo esc_attr( $settings['zoom_max'] ); ?>" min="0.1" max="10" step="0.1" />
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="oyp_autorotate"><?php esc_html_e( 'Auto Rotate', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="oyp_autorotate" name="oyp_3d_settings[autorotate]" value="1" <?php checked( $settings['autorotate'] ); ?> />
						<p class="description"><?php esc_html_e( 'Automatically rotate the model when idle.', 'observe-yor-product' ); ?></p>
					</td>
				</tr>

				<tr class="oyp-autorotate-row">
					<th scope="row">
						<label for="oyp_autorotate_speed"><?php esc_html_e( 'Auto Rotate Speed', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<input type="number" id="oyp_autorotate_speed" name="oyp_3d_settings[autorotate_speed]" value="<?php echo esc_attr( $settings['autorotate_speed'] ); ?>" min="0.1" max="5" step="0.1" />
						<p class="description"><?php esc_html_e( 'Speed of auto rotation (1.0 = normal speed).', 'observe-yor-product' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="oyp-tab-content" data-tab="scale">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="oyp_scale_unit"><?php esc_html_e( 'Scale Unit', 'observe-yor-product' ); ?></label>
					</th>
					<td>
						<select id="oyp_scale_unit" name="oyp_3d_settings[scale_unit]">
							<option value="mm" <?php selected( $settings['scale_unit'], 'mm' ); ?>><?php esc_html_e( 'Millimeters (mm)', 'observe-yor-product' ); ?></option>
							<option value="cm" <?php selected( $settings['scale_unit'], 'cm' ); ?>><?php esc_html_e( 'Centimeters (cm)', 'observe-yor-product' ); ?></option>
							<option value="m" <?php selected( $settings['scale_unit'], 'm' ); ?>><?php esc_html_e( 'Meters (m)', 'observe-yor-product' ); ?></option>
							<option value="in" <?php selected( $settings['scale_unit'], 'in' ); ?>><?php esc_html_e( 'Inches (in)', 'observe-yor-product' ); ?></option>
							<option value="ft" <?php selected( $settings['scale_unit'], 'ft' ); ?>><?php esc_html_e( 'Feet (ft)', 'observe-yor-product' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Real-world Dimensions', 'observe-yor-product' ); ?></th>
					<td>
						<label>
							<?php esc_html_e( 'Width:', 'observe-yor-product' ); ?>
							<input type="number" name="oyp_3d_settings[scale_dimensions][width]" value="<?php echo esc_attr( $settings['scale_dimensions']['width'] ); ?>" min="0" step="0.01" />
						</label><br>
						<label>
							<?php esc_html_e( 'Height:', 'observe-yor-product' ); ?>
							<input type="number" name="oyp_3d_settings[scale_dimensions][height]" value="<?php echo esc_attr( $settings['scale_dimensions']['height'] ); ?>" min="0" step="0.01" />
						</label><br>
						<label>
							<?php esc_html_e( 'Depth:', 'observe-yor-product' ); ?>
							<input type="number" name="oyp_3d_settings[scale_dimensions][depth]" value="<?php echo esc_attr( $settings['scale_dimensions']['depth'] ); ?>" min="0" step="0.01" />
						</label>
						<p class="description"><?php esc_html_e( 'Enter the real-world dimensions of your product to enable the dynamic scale ruler.', 'observe-yor-product' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>