/**
 * 3D Viewer JavaScript for Observe-Yor-Product
 */
(function() {
    'use strict';

    // Global viewer instances and cached models
    var viewers = {};
    var cachedModels = {};
    var isIntersectionObserverSupported = 'IntersectionObserver' in window;

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initViewers();
        initGalleryIntegration();
    });

    /**
     * Initialize all 3D viewers on the page
     */
    function initViewers() {
        var viewerElements = document.querySelectorAll('.oyp-3d-viewer');
        
        viewerElements.forEach(function(element) {
            var productId = element.dataset.productId;
            var modelUrl = element.dataset.modelUrl;
            
            if (!productId || !modelUrl) {
                return;
            }

            if (isIntersectionObserverSupported && oyp_viewer.settings.enable_lazy_loading !== false) {
                initLazyLoading(element);
            } else {
                initViewer(element);
            }
        });
    }

    /**
     * Initialize lazy loading for a viewer
     */
    function initLazyLoading(element) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    observer.unobserve(entry.target);
                    initViewer(entry.target);
                }
            });
        }, {
            rootMargin: '50px'
        });

        observer.observe(element);
    }

    /**
     * Initialize a single 3D viewer
     */
    function initViewer(element) {
        var productId = element.dataset.productId;
        var modelUrl = element.dataset.modelUrl;
        
        if (viewers[productId]) {
            return; // Already initialized
        }

        try {
            viewers[productId] = new OYP3DViewer(element, {
                modelUrl: modelUrl,
                productId: productId,
                settings: oyp_viewer.settings || {}
            });
        } catch (error) {
            console.error('Failed to initialize 3D viewer:', error);
            showError(element, oyp_viewer.strings.error);
        }
    }

    /**
     * Initialize WooCommerce gallery integration
     */
    function initGalleryIntegration() {
        // Handle 3D thumbnail clicks
        var thumbnails = document.querySelectorAll('[data-oyp-3d-thumb]');
        thumbnails.forEach(function(thumb) {
            thumb.addEventListener('click', function(e) {
                e.preventDefault();
                show3DSlide();
            });
        });

        // Handle gallery navigation
        var galleryImages = document.querySelectorAll('.woocommerce-product-gallery__image');
        galleryImages.forEach(function(image, index) {
            if (image.classList.contains('oyp-3d-slide')) {
                // This is our 3D slide
                var viewer = image.querySelector('.oyp-3d-viewer');
                if (viewer && !viewers[viewer.dataset.productId]) {
                    initViewer(viewer);
                }
            }
        });

        // Prevent 3D model links from opening in lightbox
        var modelLinks = document.querySelectorAll('a[data-3d-model="true"]');
        modelLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // Instead of following the link, just activate the slide
                var slide = link.closest('.woocommerce-product-gallery__image');
                if (slide) {
                    activateGallerySlide(slide);
                }
            });
        });

        // Handle PhotoSwipe/lightbox integration
        initLightboxIntegration();
        
        // Add additional aggressive event interceptor for WooCommerce zoom buttons
        initAggressiveZoomInterceptor();
    }

    /**
     * Show the 3D slide in the gallery
     */
    function show3DSlide() {
        var slide3d = document.querySelector('.oyp-3d-slide');
        if (slide3d) {
            activateGallerySlide(slide3d);
        }
    }

    /**
     * Activate a specific gallery slide
     */
    function activateGallerySlide(slide) {
        var gallery = document.querySelector('.woocommerce-product-gallery');
        
        if (!gallery || !slide) {
            return;
        }

        // If using Flexslider (default WooCommerce)
        if (gallery.flexslider) {
            var slideIndex = Array.from(gallery.querySelectorAll('.woocommerce-product-gallery__image')).indexOf(slide);
            if (slideIndex >= 0) {
                gallery.flexslider.flexslider(slideIndex);
            }
        }
        
        // Handle other gallery types as needed
        // For manual navigation, you can trigger a click on the corresponding thumbnail
        var thumbnails = document.querySelectorAll('.flex-control-thumbs li');
        if (thumbnails.length > 0) {
            var slideIndex = Array.from(gallery.querySelectorAll('.woocommerce-product-gallery__image')).indexOf(slide);
            if (thumbnails[slideIndex]) {
                thumbnails[slideIndex].click();
            }
        }
    }

    /**
     * Initialize lightbox integration
     */
    function initLightboxIntegration() {
        // Simple approach: just override clicks on 3D model triggers
        initCustomLightboxIntegration();
        
        // Also specifically disable PhotoSwipe for 3D slides
        disablePhotoSwipeFor3DSlides();
    }

    /**
     * Disable PhotoSwipe specifically for 3D slides
     */
    function disablePhotoSwipeFor3DSlides() {
        // Wait for DOM to be ready and gallery to initialize
        setTimeout(function() {
            console.log('Setting up 3D slide PhotoSwipe override...');
            var slides3D = document.querySelectorAll('.oyp-3d-slide');
            console.log('Found 3D slides:', slides3D.length);
            
            slides3D.forEach(function(slide) {
                var link = slide.querySelector('a[data-3d-model="true"]');
                var trigger = slide.querySelector('.woocommerce-product-gallery__trigger');
                
                console.log('3D slide - link:', !!link, 'trigger:', !!trigger);
                
                if (link && trigger) {
                    // Remove PhotoSwipe data attributes to prevent PhotoSwipe from handling it
                    link.removeAttribute('data-size');
                    link.removeAttribute('data-med');
                    link.removeAttribute('data-med-size');
                    
                    console.log('Setting up trigger click handler for 3D slide');
                    
                    // Add our own click handler directly to the trigger with high priority
                    trigger.addEventListener('click', function(e) {
                        console.log('3D trigger clicked!');
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        showFullscreen3DViewer(link);
                        return false;
                    }, true); // Use capture phase
                    
                    // Also handle clicks on the link itself
                    link.addEventListener('click', function(e) {
                        console.log('3D link clicked!');
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        showFullscreen3DViewer(link);
                        return false;
                    }, true); // Use capture phase
                }
            });
        }, 1000); // Give WooCommerce more time to fully initialize
    }

    /**
     * Fallback custom lightbox integration
     */
    function initCustomLightboxIntegration() {
        // Handle 3D model clicks for fullscreen - use more specific targeting
        document.addEventListener('click', function(e) {
            // Only handle direct clicks on 3D model links or their immediate children
            var link = null;
            if (e.target.matches('a[data-3d-model="true"]')) {
                link = e.target;
            } else if (e.target.closest('a[data-3d-model="true"]')) {
                var parentLink = e.target.closest('a[data-3d-model="true"]');
                // Only if the click is within the 3D viewer area, not on gallery navigation
                if (e.target.closest('.oyp-3d-viewer-placeholder')) {
                    link = parentLink;
                }
            }
            
            if (link) {
                e.preventDefault();
                e.stopPropagation();
                showFullscreen3DViewer(link);
            }
        });
        
        // Also handle zoom/magnify icon clicks - but only for 3D slides
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.woocommerce-product-gallery__trigger');
            if (trigger) {
                var slide = trigger.closest('.oyp-3d-slide');
                if (slide) {
                    console.log('Fallback: 3D zoom trigger clicked!');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    var link = slide.querySelector('a[data-3d-model="true"]');
                    if (link) {
                        showFullscreen3DViewer(link);
                    }
                    return false;
                }
            }
        }, true); // Use capture phase to intercept before PhotoSwipe
    }

    /**
     * Aggressive interceptor for WooCommerce zoom buttons
     */
    function initAggressiveZoomInterceptor() {
        // Since there's only one .woocommerce-product-gallery__trigger that WooCommerce manages,
        // we need to intercept it when the active slide is our 3D slide
        
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.woocommerce-product-gallery__trigger');
            if (trigger) {
                console.log('WooCommerce trigger clicked, checking if active slide is 3D...');
                
                // Check if the currently active slide is our 3D slide
                var activeSlide = document.querySelector('.woocommerce-product-gallery__image.flex-active-slide');
                if (!activeSlide) {
                    // Fallback: look for other indicators of active slide
                    activeSlide = document.querySelector('.woocommerce-product-gallery .is-selected') ||
                                document.querySelector('.woocommerce-product-gallery .selected') ||
                                document.querySelector('.woocommerce-product-gallery__image:first-child');
                }
                
                console.log('Active slide found:', !!activeSlide);
                console.log('Active slide is 3D:', activeSlide && activeSlide.classList.contains('oyp-3d-slide'));
                
                if (activeSlide && activeSlide.classList.contains('oyp-3d-slide')) {
                    console.log('Intercepting zoom click for 3D slide');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    var link = activeSlide.querySelector('a[data-3d-model="true"]');
                    if (link) {
                        showFullscreen3DViewer(link);
                    }
                    return false;
                }
            }
        }, true); // Use capture phase to intercept before PhotoSwipe
        
        // Also monitor for when the 3D slide becomes active
        setTimeout(function() {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        var element = mutation.target;
                        if (element.classList.contains('oyp-3d-slide') && 
                            (element.classList.contains('flex-active-slide') || element.classList.contains('selected') || element.classList.contains('is-selected'))) {
                            console.log('3D slide became active, setting up trigger interception');
                        }
                    }
                });
            });
            
            // Observe all gallery slides for class changes
            var slides = document.querySelectorAll('.woocommerce-product-gallery__image');
            slides.forEach(function(slide) {
                observer.observe(slide, { attributes: true, attributeFilter: ['class'] });
            });
        }, 1000);
    }

    /**
     * Show fullscreen 3D viewer
     */
    function showFullscreen3DViewer(link) {
        console.log('showFullscreen3DViewer called with link:', link);
        var modelUrl = link.dataset.modelUrl; // Use the actual model URL, not the placeholder image
        var productId = link.dataset.productId;
        var fullscreenViewerId = productId + '_fullscreen';
        
        console.log('Model URL:', modelUrl, 'Product ID:', productId);
        
        // Check if we already have a cached fullscreen viewer
        if (viewers[fullscreenViewerId] && viewers[fullscreenViewerId].overlay) {
            // Reuse existing viewer
            document.body.appendChild(viewers[fullscreenViewerId].overlay);
            document.body.style.overflow = 'hidden';
            return;
        }
        
        // Create fullscreen overlay
        var overlay = document.createElement('div');
        overlay.className = 'oyp-3d-fullscreen-overlay';
        overlay.innerHTML = `
            <div class="oyp-3d-fullscreen-container">
                <button class="oyp-3d-fullscreen-close" aria-label="Close">&times;</button>
                <div class="oyp-3d-viewer oyp-3d-viewer-fullscreen" 
                     id="oyp-3d-viewer-fullscreen-${productId}"
                     data-model-url="${modelUrl}"
                     data-product-id="${productId}">
                </div>
                <div class="oyp-3d-fullscreen-loading">
                    <div class="oyp-3d-spinner"></div>
                    <p>Loading 3D model...</p>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        
        // Initialize 3D viewer in fullscreen
        var fullscreenViewer = overlay.querySelector('.oyp-3d-viewer-fullscreen');
        try {
            var viewerInstance = new OYP3DViewer(fullscreenViewer, {
                modelUrl: modelUrl,
                productId: fullscreenViewerId,
                settings: oyp_viewer.settings || {},
                useCache: true
            });
            
            // Store both viewer and overlay for reuse
            viewers[fullscreenViewerId] = viewerInstance;
            viewers[fullscreenViewerId].overlay = overlay;
        } catch (error) {
            console.error('Failed to initialize fullscreen 3D viewer:', error);
            overlay.querySelector('.oyp-3d-fullscreen-loading p').textContent = 'Error loading 3D model';
        }
        
        // Handle close button
        overlay.querySelector('.oyp-3d-fullscreen-close').addEventListener('click', function() {
            hideFullscreen3DViewer(fullscreenViewerId);
        });
        
        // Handle escape key
        var escapeHandler = function(e) {
            if (e.key === 'Escape') {
                hideFullscreen3DViewer(fullscreenViewerId);
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        // Handle click outside
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                hideFullscreen3DViewer(fullscreenViewerId);
            }
        });
    }

    /**
     * Hide fullscreen 3D viewer (keep cached for reuse)
     */
    function hideFullscreen3DViewer(viewerId) {
        document.body.style.overflow = '';
        
        if (viewers[viewerId] && viewers[viewerId].overlay) {
            // Remove from DOM but keep in memory
            viewers[viewerId].overlay.remove();
        }
    }

    /**
     * Close fullscreen 3D viewer (legacy function for compatibility)
     */
    function closeFullscreen3DViewer(overlay, viewerId) {
        hideFullscreen3DViewer(viewerId);
    }

    /**
     * Show error message
     */
    function showError(element, message) {
        element.innerHTML = '<div class="oyp-3d-error"><p>' + message + '</p></div>';
    }

    /**
     * 3D Viewer Class
     */
    function OYP3DViewer(element, options) {
        this.element = element;
        this.options = options || {};
        this.settings = this.options.settings || {};
        
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.model = null;
        this.mixer = null;
        this.clock = new THREE.Clock();
        
        this.defaultCameraPosition = { x: 0, y: 0, z: 5 };
        this.defaultControlsTarget = { x: 0, y: 0, z: 0 };
        
        this.init();
    }

    OYP3DViewer.prototype = {
        init: function() {
            this.showLoading();
            this.createScene();
            this.createCamera();
            this.createRenderer();
            this.createLights();
            this.createControls();
            this.setupBackground();
            this.loadModel();
            this.setupEventListeners();
            this.animate();
        },

        showLoading: function() {
            var loadingEl = this.element.parentElement.querySelector('.oyp-3d-loading');
            if (loadingEl) {
                loadingEl.style.display = 'block';
            }
        },

        hideLoading: function() {
            var loadingEl = this.element.parentElement.querySelector('.oyp-3d-loading');
            if (loadingEl) {
                loadingEl.style.display = 'none';
            }
        },

        createScene: function() {
            this.scene = new THREE.Scene();
        },

        createCamera: function() {
            var aspect = this.element.clientWidth / this.element.clientHeight;
            this.camera = new THREE.PerspectiveCamera(75, aspect, 0.1, 1000);
            this.camera.position.set(
                this.defaultCameraPosition.x,
                this.defaultCameraPosition.y,
                this.defaultCameraPosition.z
            );
        },

        createRenderer: function() {
            if (!this.isWebGLSupported()) {
                throw new Error(oyp_viewer.strings.webgl_not_supported);
            }

            this.renderer = new THREE.WebGLRenderer({ 
                antialias: true,
                alpha: true 
            });
            this.renderer.setSize(this.element.clientWidth, this.element.clientHeight);
            this.renderer.setPixelRatio(window.devicePixelRatio);
            this.renderer.outputEncoding = THREE.sRGBEncoding;
            this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
            this.renderer.toneMappingExposure = 1;
            this.renderer.shadowMap.enabled = true;
            this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            
            this.element.appendChild(this.renderer.domElement);
        },

        createLights: function() {
            var preset = this.settings.lighting_preset || 'studio';
            
            switch (preset) {
                case 'studio':
                    this.createStudioLighting();
                    break;
                case 'outdoor':
                    this.createOutdoorLighting();
                    break;
                case 'soft':
                    this.createSoftLighting();
                    break;
                default:
                    this.createStudioLighting();
            }
        },

        createStudioLighting: function() {
            // Key light
            var keyLight = new THREE.DirectionalLight(0xffffff, 1);
            keyLight.position.set(5, 5, 5);
            keyLight.castShadow = true;
            keyLight.shadow.mapSize.width = 2048;
            keyLight.shadow.mapSize.height = 2048;
            this.scene.add(keyLight);

            // Fill light
            var fillLight = new THREE.DirectionalLight(0xffffff, 0.5);
            fillLight.position.set(-5, 0, 5);
            this.scene.add(fillLight);

            // Ambient light
            var ambientLight = new THREE.AmbientLight(0x404040, 0.4);
            this.scene.add(ambientLight);
        },

        createOutdoorLighting: function() {
            // Sun light
            var sunLight = new THREE.DirectionalLight(0xffffff, 2);
            sunLight.position.set(10, 10, 10);
            sunLight.castShadow = true;
            this.scene.add(sunLight);

            // Sky light
            var skyLight = new THREE.HemisphereLight(0x87CEEB, 0x8B4513, 0.6);
            this.scene.add(skyLight);
        },

        createSoftLighting: function() {
            // Soft ambient light
            var ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
            this.scene.add(ambientLight);

            // Subtle directional light
            var dirLight = new THREE.DirectionalLight(0xffffff, 0.3);
            dirLight.position.set(2, 5, 2);
            this.scene.add(dirLight);
        },

        createControls: function() {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.target.set(
                this.defaultControlsTarget.x,
                this.defaultControlsTarget.y,
                this.defaultControlsTarget.z
            );
            
            // Configure controls based on settings
            this.controls.enableZoom = this.settings.enable_zoom !== false;
            this.controls.enablePan = this.settings.enable_pan !== false;
            this.controls.enableRotate = this.settings.enable_rotate !== false;
            
            if (this.settings.zoom_min !== undefined) {
                this.controls.minDistance = this.settings.zoom_min;
            }
            if (this.settings.zoom_max !== undefined) {
                this.controls.maxDistance = this.settings.zoom_max;
            }
            
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            
            // Auto rotate
            if (this.settings.autorotate) {
                this.controls.autoRotate = true;
                this.controls.autoRotateSpeed = this.settings.autorotate_speed || 1.0;
            }
        },

        setupBackground: function() {
            var bgType = this.settings.background_type || 'gradient';
            var color1 = this.settings.background_color1 || '#ffffff';
            var color2 = this.settings.background_color2 || '#f0f0f0';
            
            if (bgType === 'solid') {
                this.scene.background = new THREE.Color(color1);
            } else {
                // Create gradient background
                var canvas = document.createElement('canvas');
                canvas.width = 512;
                canvas.height = 512;
                var context = canvas.getContext('2d');
                var gradient = context.createLinearGradient(0, 0, 0, 512);
                gradient.addColorStop(0, color1);
                gradient.addColorStop(1, color2);
                context.fillStyle = gradient;
                context.fillRect(0, 0, 512, 512);
                
                var texture = new THREE.CanvasTexture(canvas);
                this.scene.background = texture;
            }
        },

        loadModel: function() {
            var self = this;
            var modelUrl = this.options.modelUrl;
            
            // Check if we have a cached model
            if (this.options.useCache && cachedModels[modelUrl]) {
                // Clone the cached model
                var cachedGltf = cachedModels[modelUrl];
                var clonedScene = cachedGltf.scene.clone();
                
                // Create a new gltf object with cloned scene
                var clonedGltf = {
                    scene: clonedScene,
                    animations: cachedGltf.animations,
                    asset: cachedGltf.asset,
                    cameras: cachedGltf.cameras,
                    parser: cachedGltf.parser,
                    userData: cachedGltf.userData
                };
                
                // Load immediately from cache
                setTimeout(function() {
                    self.onModelLoaded(clonedGltf);
                }, 10); // Small delay to show loading state briefly
                return;
            }
            
            var loader = new THREE.GLTFLoader();
            
            loader.load(
                modelUrl,
                function(gltf) {
                    // Cache the model if caching is enabled
                    if (self.options.useCache) {
                        cachedModels[modelUrl] = gltf;
                    }
                    self.onModelLoaded(gltf);
                },
                function(progress) {
                    self.onModelProgress(progress);
                },
                function(error) {
                    self.onModelError(error);
                }
            );
        },

        onModelLoaded: function(gltf) {
            this.model = gltf.scene;
            this.scene.add(this.model);
            
            // Center and scale the model
            this.centerModel();
            this.scaleModel();
            
            // Setup animations if present
            if (gltf.animations && gltf.animations.length > 0) {
                this.mixer = new THREE.AnimationMixer(this.model);
                gltf.animations.forEach(function(clip) {
                    this.mixer.clipAction(clip).play();
                }.bind(this));
            }
            
            this.hideLoading();
            
            // Update camera and controls for the model
            this.updateCameraForModel();
            
            // Hide fullscreen loading if this is a fullscreen viewer
            var fullscreenLoading = document.querySelector('.oyp-3d-fullscreen-loading');
            if (fullscreenLoading && this.element.classList.contains('oyp-3d-viewer-fullscreen')) {
                fullscreenLoading.style.display = 'none';
            }
            
            // Hide PhotoSwipe loading if this is a PhotoSwipe viewer
            var photoswipeLoading = document.querySelector('.oyp-3d-loading-photoswipe');
            if (photoswipeLoading && this.element.classList.contains('oyp-3d-viewer-photoswipe')) {
                photoswipeLoading.style.display = 'none';
            }
        },

        onModelProgress: function(progress) {
            var percentComplete = (progress.loaded / progress.total) * 100;
            // Update loading progress if needed
        },

        onModelError: function(error) {
            console.error('Error loading 3D model:', error);
            this.hideLoading();
            showError(this.element, oyp_viewer.strings.error);
        },

        centerModel: function() {
            var box = new THREE.Box3().setFromObject(this.model);
            var center = box.getCenter(new THREE.Vector3());
            this.model.position.sub(center);
        },

        scaleModel: function() {
            var box = new THREE.Box3().setFromObject(this.model);
            var size = box.getSize(new THREE.Vector3());
            var maxDimension = Math.max(size.x, size.y, size.z);
            var desiredSize = 2; // Desired model size in scene units
            var scale = desiredSize / maxDimension;
            this.model.scale.setScalar(scale);
        },

        updateCameraForModel: function() {
            if (!this.model) return;
            
            var box = new THREE.Box3().setFromObject(this.model);
            var size = box.getSize(new THREE.Vector3());
            var maxDimension = Math.max(size.x, size.y, size.z);
            
            // Position camera based on model size
            var distance = maxDimension * 2;
            this.camera.position.set(distance, distance * 0.5, distance);
            this.camera.lookAt(0, 0, 0);
            
            this.controls.update();
        },

        setupEventListeners: function() {
            var self = this;
            
            // Handle window resize
            window.addEventListener('resize', function() {
                self.onWindowResize();
            });
            
            // Reset view button
            var resetBtn = this.element.parentElement.querySelector('.oyp-reset-view');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    self.resetView();
                });
            }
        },

        onWindowResize: function() {
            var width = this.element.clientWidth;
            var height = this.element.clientHeight;
            
            this.camera.aspect = width / height;
            this.camera.updateProjectionMatrix();
            
            this.renderer.setSize(width, height);
        },

        resetView: function() {
            if (this.controls) {
                this.controls.reset();
                if (this.model) {
                    this.updateCameraForModel();
                }
            }
        },

        animate: function() {
            var self = this;
            
            function render() {
                requestAnimationFrame(render);
                
                var delta = self.clock.getDelta();
                
                if (self.mixer) {
                    self.mixer.update(delta);
                }
                
                if (self.controls) {
                    self.controls.update();
                }
                
                if (self.renderer && self.scene && self.camera) {
                    self.renderer.render(self.scene, self.camera);
                }
            }
            
            render();
        },

        isWebGLSupported: function() {
            try {
                var canvas = document.createElement('canvas');
                return !!(window.WebGLRenderingContext && 
                         (canvas.getContext('webgl') || 
                          canvas.getContext('experimental-webgl')));
            } catch(e) {
                return false;
            }
        },

        destroy: function() {
            if (this.renderer) {
                this.renderer.dispose();
            }
            if (this.controls) {
                this.controls.dispose();
            }
            // Clean up any other resources
        }
    };

    // Make viewer class globally available
    window.OYP3DViewer = OYP3DViewer;

})();