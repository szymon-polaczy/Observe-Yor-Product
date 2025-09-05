/**
 * 3D Viewer JavaScript for Observe-Yor-Product
 */
(function() {
    'use strict';

    // Global viewer instances
    var viewers = {};
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
    }

    /**
     * Show the 3D slide in the gallery
     */
    function show3DSlide() {
        var gallery = document.querySelector('.woocommerce-product-gallery');
        var slide3d = document.querySelector('.oyp-3d-slide');
        
        if (!gallery || !slide3d) {
            return;
        }

        // If using Flexslider (default WooCommerce)
        if (gallery.flexslider) {
            var slideIndex = Array.from(gallery.querySelectorAll('.woocommerce-product-gallery__image')).indexOf(slide3d);
            gallery.flexslider.flexslider(slideIndex);
        }
        // Handle other gallery types as needed
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
            var loader = new THREE.GLTFLoader();
            var self = this;
            
            loader.load(
                this.options.modelUrl,
                function(gltf) {
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