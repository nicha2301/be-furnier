<?php
/**
 * 3D Model Viewer Page
 * 
 * Provides a dedicated fullscreen experience for viewing 3D models
 */

// Load WordPress
require_once('../../../wp-load.php');

// Get model path from query string
$model_id = isset($_GET['model_id']) ? absint($_GET['model_id']) : 0;
$model_url = isset($_GET['url']) ? esc_url_raw($_GET['url']) : '';

// Get model data
$model_data = array(
    'url' => $model_url,
    'name' => 'Model Preview',
    'background_color' => '#ffffff',
    'autorotate' => true,
);

// If model ID is provided, get model details from product
if ($model_id > 0) {
    $model_url = get_post_meta($model_id, 'woobox_3d_model_file', true);
    $model_data['url'] = $model_url;
    $model_data['name'] = get_the_title($model_id);
    $model_data['autorotate'] = get_post_meta($model_id, 'woobox_3d_model_autorotate', true) === 'yes';
    $model_data['poster'] = get_post_meta($model_id, 'woobox_3d_model_poster', true);
}

if (empty($model_data['url'])) {
    wp_die('No model URL provided.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($model_data['name']); ?> - 3D Viewer</title>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            overflow: hidden;
            height: 100vh;
            background-color: #f5f5f5;
        }
        .viewer-container {
            position: relative;
            width: 100%;
            height: 100vh;
        }
        model-viewer {
            width: 100%;
            height: 100%;
            --poster-color: transparent;
            background-color: var(--bg-color);
        }
        .controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            display: flex;
            padding: 10px;
            z-index: 100;
            align-items: center;
            justify-content: space-between;
        }
        .controls-left, .controls-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .control-group {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .control-group label {
            margin-right: 5px;
        }
        .control-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .control-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .control-btn.active {
            background: #3498db;
        }
        .title {
            font-size: 16px;
            font-weight: 500;
            flex: 1;
            text-align: center;
        }
        .hotspot {
            display: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            background-color: rgba(0, 128, 200, 0.5);
            box-sizing: border-box;
            pointer-events: none;
        }
        .hotspot[slot="hotspot-1"] { --position-x: 0; --position-y: 0; --position-z: 0; }
    </style>
</head>
<body>
    <div class="viewer-container">
        <model-viewer 
            id="model-viewer"
            src="<?php echo esc_url($model_data['url']); ?>"
            alt="<?php echo esc_attr($model_data['name']); ?>"
            <?php echo $model_data['autorotate'] ? 'auto-rotate' : ''; ?>
            camera-controls
            shadow-intensity="1"
            ar
            <?php echo !empty($model_data['poster']) ? 'poster="'.esc_url($model_data['poster']).'"' : ''; ?>
            data-name="<?php echo esc_attr($model_data['name']); ?>"
            style="--bg-color: <?php echo esc_attr($model_data['background_color']); ?>;">

            <div class="hotspot" slot="hotspot-1" data-position="0 0 0"></div>
        </model-viewer>

        <div class="controls">
            <div class="controls-left">
                <div class="control-group">
                    <button class="control-btn toggle-auto-rotate" title="Toggle Auto-rotate">
                        <span class="dashicons dashicons-update"></span> Rotate
                    </button>
                </div>
                <div class="control-group">
                    <label for="bg-color">Background:</label>
                    <input type="color" id="bg-color" value="<?php echo esc_attr($model_data['background_color']); ?>">
                </div>
            </div>
            
            <div class="title"><?php echo esc_html($model_data['name']); ?></div>
            
            <div class="controls-right">
                <button class="control-btn toggle-ar" title="View in AR (if supported)">
                    <span class="dashicons dashicons-smartphone"></span> AR
                </button>
                <button class="control-btn toggle-fullscreen" title="Toggle Fullscreen">
                    <span class="dashicons dashicons-fullscreen"></span>
                </button>
                <button class="control-btn close-viewer" title="Close">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewer = document.getElementById('model-viewer');
        const bgColorInput = document.getElementById('bg-color');
        
        // Toggle auto-rotate
        document.querySelector('.toggle-auto-rotate').addEventListener('click', function() {
            viewer.autoRotate = !viewer.autoRotate;
            this.classList.toggle('active');
        });
        
        // Change background color
        bgColorInput.addEventListener('input', function() {
            viewer.style.setProperty('--bg-color', this.value);
        });
        
        // Toggle AR mode
        document.querySelector('.toggle-ar').addEventListener('click', function() {
            viewer.activateAR();
        });
        
        // Toggle fullscreen
        document.querySelector('.toggle-fullscreen').addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                this.innerHTML = '<span class="dashicons dashicons-exit"></span>';
            } else {
                document.exitFullscreen();
                this.innerHTML = '<span class="dashicons dashicons-fullscreen"></span>';
            }
        });
        
        // Close button
        document.querySelector('.close-viewer').addEventListener('click', function() {
            if (history.length > 1) {
                history.back();
            } else {
                window.close();
            }
        });
        
        // If auto-rotate is enabled, mark the button as active
        if (viewer.autoRotate) {
            document.querySelector('.toggle-auto-rotate').classList.add('active');
        }
    });
    </script>
</body>
</html> 