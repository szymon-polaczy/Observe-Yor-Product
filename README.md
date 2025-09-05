# Observe-Yor-Product (OYP) — 3D Viewer for WooCommerce

A WordPress/WooCommerce plugin that adds a high-quality, configurable 3D model viewer to product pages with full integration into the default WooCommerce gallery/slider.

## Features

### Core Functionality
- **WooCommerce Gallery Integration**: 3D models appear as slides in the default WooCommerce product gallery
- **3D Model Support**: Supports GLB and GLTF formats with Three.js rendering
- **Interactive Controls**: Zoom, pan, and rotate controls with configurable constraints
- **Lazy Loading**: Models load only when visible using Intersection Observer
- **Reset View**: Always-present reset button to restore default camera position
- **3D Badge**: Clear visual indicator with hover help for interactive content

### Admin Features
- **Product Metabox**: Easy-to-use interface for uploading and configuring 3D models per product
- **Background Customization**: Solid colors or gradients with color picker
- **Lighting Presets**: Studio, outdoor, and soft lighting configurations
- **Control Settings**: Enable/disable zoom, pan, rotate with min/max constraints
- **Auto Rotation**: Optional autorotation with configurable speed
- **Scale Ruler**: Dynamic scale ruler showing real-world dimensions

### Advanced Features
- **Variation Support**: Different 3D models per product variation (planned)
- **Annotations System**: Interactive hotspots anchored to model space (planned)
- **Security Features**: Optional encryption and hotlink protection (planned)
- **Performance Optimized**: Conditional asset loading and WebGL fallback

## Installation

1. Upload the plugin files to `/wp-content/plugins/observe-yor-product/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce is installed and active
4. Go to WooCommerce > 3D Viewer to configure global settings

## Requirements

- WordPress 6.3+
- PHP 8.1+
- WooCommerce 8.0+
- Modern browser with WebGL support

## Usage

### Adding 3D Models to Products

1. Edit a WooCommerce product
2. Scroll to the "3D Viewer Settings" metabox
3. Check "Enable 3D Viewer"
4. Upload your GLB or GLTF file (max 50MB)
5. Configure appearance and controls as needed
6. Set real-world dimensions for the scale ruler
7. Save the product

### Configuring Appearance

**Background Options:**
- Solid Color: Single color background
- Gradient: Two-color gradient background

**Lighting Presets:**
- Studio: Professional three-point lighting setup
- Outdoor: Natural sun and sky lighting
- Soft: Ambient lighting for delicate products

**Control Settings:**
- Enable/disable zoom, pan, and rotation
- Set zoom limits (min/max distance)
- Configure auto-rotation speed

### Scale Ruler

Enter the real-world dimensions of your product to enable the dynamic scale ruler:
- Width, Height, Depth in your chosen unit
- Supports mm, cm, m, inches, and feet
- Updates dynamically as users zoom in/out

## File Structure

```
observe-yor-product/
├── observe-yor-product.php          # Main plugin file
├── includes/
│   ├── class-oyp-install.php        # Installation and activation
│   ├── class-oyp-frontend.php       # Frontend functionality
│   └── admin/
│       └── class-oyp-admin.php      # Admin interface
├── assets/
│   ├── js/
│   │   ├── admin.js                 # Admin JavaScript
│   │   └── 3d-viewer.js            # 3D viewer functionality
│   └── css/
│       ├── admin.css                # Admin styles
│       └── frontend.css             # Frontend styles
├── templates/
│   └── admin/
│       ├── product-3d-viewer-metabox.php  # Product metabox
│       └── settings-page.php              # Settings page
└── languages/                       # Translation files
```

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

WebGL is required. The plugin gracefully degrades to show error messages on unsupported browsers.

## Performance

- **Conditional Loading**: Scripts and styles load only on product pages with 3D content
- **Lazy Loading**: 3D models load only when visible in viewport
- **Optimized Rendering**: Uses efficient Three.js rendering with proper disposal
- **Progressive Enhancement**: Works alongside existing WooCommerce gallery features

## Development

### File Upload Handling

The plugin handles 3D model uploads through the WordPress Media Library with custom validation:
- File type validation (GLB/GLTF only)
- File size limits (configurable, default 50MB)
- MIME type verification
- Secure file storage

### WooCommerce Integration

- Hooks into existing product gallery system
- Maintains compatibility with gallery navigation
- Preserves existing thumbnails and zoom functionality
- Works with most WooCommerce themes

### JavaScript Architecture

- Modular design with initialization detection
- Proper cleanup and memory management
- Event-driven communication between components
- Responsive design with window resize handling

## Security

- **File Type Validation**: Strict checking of uploaded 3D models
- **Capability Checks**: Proper WordPress capability verification
- **Nonce Verification**: CSRF protection on all AJAX requests
- **Sanitization**: All user inputs properly sanitized and validated

## Troubleshooting

### 3D Model Not Loading
1. Check browser WebGL support
2. Verify file format (GLB/GLTF only)
3. Check file size limits
4. Review browser console for errors

### Gallery Integration Issues
1. Check theme compatibility
2. Verify WooCommerce version
3. Test with default WooCommerce theme
4. Check for JavaScript conflicts

### Upload Problems
1. Check PHP upload limits
2. Verify file permissions
3. Check available disk space
4. Review server error logs

## Contributing

This plugin follows WordPress coding standards and best practices:
- PSR-4 autoloading structure
- WordPress hooks and filters
- Proper sanitization and validation
- Responsive design principles
- Accessibility considerations

## License

GPL v3 or later

## Changelog

### 1.0.0
- Initial release
- Basic 3D viewer functionality
- WooCommerce gallery integration
- Admin interface with metabox
- Lazy loading and performance optimization
- Scale ruler and controls
- Multiple lighting presets