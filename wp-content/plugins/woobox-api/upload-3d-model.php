<?php
/**
 * Simple 3D Model Upload Script
 */

// Đảm bảo chỉ người dùng quản trị mới có thể sử dụng tính năng này
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('You do not have permission to access this page.');
}

// Xử lý tải lên file
$upload_success = false;
$upload_message = '';
$file_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['model_file'])) {
    $file = $_FILES['model_file'];
    
    // Kiểm tra lỗi
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_message = 'Upload error: ' . $file['error'];
    } else {
        // Kiểm tra phần mở rộng file
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'glb') {
            $upload_message = 'Chỉ chấp nhận file .glb';
        } else {
            // Tạo thư mục upload nếu chưa tồn tại
            $upload_dir = WP_CONTENT_DIR . '/uploads/3d-models';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Tạo tên file an toàn
            $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $upload_file = $upload_dir . '/' . $safe_filename;
            
            // Di chuyển file tải lên
            if (move_uploaded_file($file['tmp_name'], $upload_file)) {
                $upload_success = true;
                $file_url = content_url('/uploads/3d-models/' . $safe_filename);
                $upload_message = 'File đã được tải lên thành công!';
            } else {
                $upload_message = 'Không thể di chuyển file tải lên!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tải lên Model 3D</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px;
            padding: 0;
            background: #f1f1f1;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #23282d;
            font-size: 23px;
            font-weight: 400;
            margin: 0 0 20px;
            padding: 9px 0 4px;
            line-height: 1.3;
        }
        form {
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px 0;
        }
        button {
            background: #0085ba;
            border-color: #0073aa #006799 #006799;
            border-width: 1px;
            border-style: solid;
            border-radius: 3px;
            box-shadow: 0 1px 0 #006799;
            color: #fff;
            text-decoration: none;
            text-shadow: 0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;
            vertical-align: top;
            padding: 6px 14px;
            cursor: pointer;
        }
        .message {
            padding: 10px 15px;
            border-radius: 3px;
            margin: 20px 0;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        .copy-field {
            display: flex;
            margin-top: 20px;
        }
        .copy-field input[type="text"] {
            flex-grow: 1;
            padding: 6px 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .copy-field button {
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tải lên Model 3D</h1>
        
        <?php if ($upload_message): ?>
        <div class="message <?php echo $upload_success ? 'success' : 'error'; ?>">
            <?php echo $upload_message; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="model_file">Chọn file model 3D (.glb):</label>
                <input type="file" name="model_file" id="model_file" accept=".glb" required>
            </div>
            <button type="submit">Tải lên</button>
        </form>
        
        <?php if ($upload_success && $file_url): ?>
        <div class="copy-field">
            <input type="text" id="file_url" value="<?php echo $file_url; ?>" readonly>
            <button onclick="copyUrl()">Sao chép URL</button>
        </div>
        <script>
        function copyUrl() {
            var copyText = document.getElementById("file_url");
            copyText.select();
            document.execCommand("copy");
            alert("URL đã được sao chép!");
        }
        </script>
        <?php endif; ?>
        
        <p>
            <strong>Hướng dẫn:</strong>
            <ol>
                <li>Tải lên file model 3D (.glb)</li>
                <li>Sau khi tải lên thành công, sao chép URL</li>
                <li>Dán URL vào trường "3D Model File" trong trang chi tiết sản phẩm</li>
            </ol>
        </p>
    </div>
</body>
</html> 