# Observe-Yor-Product — 3D Viewer for WooCommerce

Add interactive 3D models to your WooCommerce products with seamless gallery integration.

## Features

- **Upload 3D Models** - Supports GLB and GLTF files up to 50MB
- **WooCommerce Integration** - 3D models appear in product gallery alongside images
- **Interactive Controls** - Zoom, pan, rotate with fullscreen viewing
- **Customizable Appearance** - Multiple lighting presets and background options
- **Admin-Friendly** - Simple upload interface in product edit screen

## Quick Start

1. **Install & Activate** the plugin
2. **Edit a WooCommerce product**
3. **Upload a 3D model** in the "3D Viewer Settings" metabox
4. **Save the product** - 3D model automatically appears in gallery

## Requirements

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4+
- WebGL-enabled browser

## Usage

### Adding 3D Models
1. Go to Products → Edit Product
2. Find "3D Viewer Settings" metabox
3. Upload your GLB/GLTF file
4. Customize appearance (optional)
5. Save product

### Customization Options
- **Background**: Solid colors or gradients
- **Lighting**: Studio, outdoor, or soft presets  
- **Controls**: Enable/disable zoom, pan, rotation
- **Scale Ruler**: Show real-world dimensions

## Supported Formats

- **GLB** (recommended) - Binary glTF format
- **GLTF** - Text-based glTF format

## Troubleshooting

**3D model not showing?**
- Check browser supports WebGL
- Verify file is GLB or GLTF format
- Ensure file size is under 50MB

**Upload failing?**
- Check PHP upload limits in WP Admin
- Verify file permissions
- Review server error logs