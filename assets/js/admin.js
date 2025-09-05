/**
 * Admin JavaScript for Observe-Yor-Product
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize admin interface
        initMetaboxTabs();
        initConditionalFields();
        init3DModelUpload();
        initBackgroundTypeToggle();
        initZoomControls();
        initAutorotateControls();
    });

    /**
     * Initialize metabox tabs
     */
    function initMetaboxTabs() {
        $('.oyp-tab-button').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tab = $button.data('tab');
            var $wrapper = $button.closest('.oyp-settings-tabs');
            
            // Update active tab button
            $wrapper.find('.oyp-tab-button').removeClass('active');
            $button.addClass('active');
            
            // Show corresponding tab content
            $wrapper.find('.oyp-tab-content').removeClass('active');
            $wrapper.find('.oyp-tab-content[data-tab="' + tab + '"]').addClass('active');
        });
    }

    /**
     * Initialize conditional field display
     */
    function initConditionalFields() {
        function toggleConditionalFields() {
            var $enabled = $('#oyp_3d_enabled');
            var isEnabled = $enabled.is(':checked');
            
            $('.oyp-conditional-row, .oyp-settings-tabs').toggle(isEnabled);
        }

        $('#oyp_3d_enabled').on('change', toggleConditionalFields);
        toggleConditionalFields(); // Initial state
    }

    /**
     * Initialize 3D model upload functionality
     */
    function init3DModelUpload() {
        var $uploadBtn = $('.oyp-upload-model-btn');
        var $removeBtn = $('.oyp-remove-model');
        var $preview = $('.oyp-model-preview');
        var $uploadArea = $('.oyp-model-upload');
        var $modelId = $('.oyp-model-id');
        var $modelUrl = $('.oyp-model-url');
        var $modelFilename = $('.oyp-model-filename');
        var $filenameDisplay = $('.oyp-model-filename');

        // Handle upload button click
        $uploadBtn.on('click', function(e) {
            e.preventDefault();
            
            var frame = wp.media({
                title: oyp_admin.strings.select_3d_model,
                multiple: false,
                library: {
                    type: ['model/glb', 'model/gltf', 'application/octet-stream']
                }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                
                // Validate file type
                var extension = attachment.filename.split('.').pop().toLowerCase();
                var supportedFormats = oyp_admin.supported_formats || ['gltf', 'glb'];
                
                if (supportedFormats.indexOf(extension) === -1) {
                    alert(oyp_admin.strings.invalid_file_type);
                    return;
                }

                // Update fields
                $modelId.val(attachment.id);
                $modelUrl.val(attachment.url);
                $modelFilename.val(attachment.filename);
                $filenameDisplay.text(attachment.filename);
                
                // Enable 3D viewer checkbox automatically
                $('#oyp_3d_enabled').prop('checked', true).trigger('change');
                
                // Show preview, hide upload
                $preview.show();
                $uploadArea.hide();
                
                // Show success message
                var $notice = $('<div class="notice notice-success is-dismissible"><p><strong>3D model selected successfully!</strong> Don\'t forget to save the product to apply the changes.</p></div>');
                $('.oyp-metabox-wrapper').prepend($notice);
                
                // Auto-dismiss notice after 5 seconds
                setTimeout(function() {
                    $notice.fadeOut();
                }, 5000);
            });

            frame.open();
        });

        // Handle remove button click
        $removeBtn.on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove the 3D model?')) {
                // Clear fields
                $modelId.val('');
                $modelUrl.val('');
                $modelFilename.val('');
                
                // Hide preview, show upload
                $preview.hide();
                $uploadArea.show();
            }
        });

        // Handle direct file upload via drag/drop or file input
        $('.oyp-model-upload-area').on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('oyp-drag-over');
        });

        $('.oyp-model-upload-area').on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('oyp-drag-over');
        });

        $('.oyp-model-upload-area').on('drop', function(e) {
            e.preventDefault();
            
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                upload3DModelFile(files[0]);
            }
        });
    }

    /**
     * Upload 3D model file via AJAX
     */
    function upload3DModelFile(file) {
        // Validate file type
        var extension = file.name.split('.').pop().toLowerCase();
        var supportedFormats = oyp_admin.supported_formats || ['gltf', 'glb'];
        
        if (supportedFormats.indexOf(extension) === -1) {
            alert(oyp_admin.strings.invalid_file_type);
            return;
        }

        // Validate file size (client-side check for user feedback)
        var maxSize = oyp_admin.max_file_size || (50 * 1024 * 1024); // Default 50MB in bytes
        if (file.size > maxSize) {
            alert(oyp_admin.strings.file_too_large);
            return;
        }

        var formData = new FormData();
        formData.append('action', 'oyp_upload_3d_model');
        formData.append('nonce', oyp_admin.nonce);
        formData.append('file', file);

        // Show loading state
        var $uploadBtn = $('.oyp-upload-model-btn');
        var originalText = $uploadBtn.text();
        $uploadBtn.text('Uploading...').prop('disabled', true);

        $.ajax({
            url: oyp_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Update fields
                    $('.oyp-model-id').val(data.attachment_id);
                    $('.oyp-model-url').val(data.url);
                    $('.oyp-model-filename').val(data.filename);
                    $('.oyp-model-filename').text(data.filename);
                    
                    // Enable 3D viewer checkbox automatically
                    $('#oyp_3d_enabled').prop('checked', true).trigger('change');
                    
                    // Show preview, hide upload
                    $('.oyp-model-preview').show();
                    $('.oyp-model-upload').hide();
                    
                    // Show success message
                    var $notice = $('<div class="notice notice-success is-dismissible"><p><strong>3D model uploaded successfully!</strong> Don\'t forget to save the product to apply the changes.</p></div>');
                    $('.oyp-metabox-wrapper').prepend($notice);
                    
                    // Auto-dismiss notice after 5 seconds
                    setTimeout(function() {
                        $notice.fadeOut();
                    }, 5000);
                } else {
                    alert(response.data || 'Upload failed');
                }
            },
            error: function() {
                alert('Upload failed. Please try again.');
            },
            complete: function() {
                $uploadBtn.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Initialize background type toggle
     */
    function initBackgroundTypeToggle() {
        function toggleGradientFields() {
            var backgroundType = $('#oyp_background_type').val();
            $('.oyp-gradient-row').toggle(backgroundType === 'gradient');
        }

        $('#oyp_background_type').on('change', toggleGradientFields);
        toggleGradientFields(); // Initial state
    }

    /**
     * Initialize zoom controls
     */
    function initZoomControls() {
        function toggleZoomFields() {
            var isEnabled = $('input[name="oyp_3d_settings[enable_zoom]"]').is(':checked');
            $('.oyp-zoom-row').toggle(isEnabled);
        }

        $('input[name="oyp_3d_settings[enable_zoom]"]').on('change', toggleZoomFields);
        toggleZoomFields(); // Initial state
    }

    /**
     * Initialize autorotate controls
     */
    function initAutorotateControls() {
        function toggleAutorotateFields() {
            var isEnabled = $('#oyp_autorotate').is(':checked');
            $('.oyp-autorotate-row').toggle(isEnabled);
        }

        $('#oyp_autorotate').on('change', toggleAutorotateFields);
        toggleAutorotateFields(); // Initial state
    }

})(jQuery);