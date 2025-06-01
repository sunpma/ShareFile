<?php
// upload.php

// 辅助函数：生成指定长度的随机字符串（小写字母和数字）
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

session_start();
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = ''; // 用于存储上传结果和链接
$show_links = false; // 控制是否显示链接部分
$image_links = []; // 存储图片链接数据

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // 从 $_FILES['file']['name'] 获取原始文件名
    $original_filename = basename($file['name']);
    // 从原始文件名获取扩展名
    $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

    $allowed_image_exts = ['jpg', 'png', 'gif', 'ico', 'jpeg'];
    $is_image = in_array($file_ext, $allowed_image_exts);

    // 根据是否为图片决定最终的文件名
    if ($is_image) {
        $new_name_without_ext = generateRandomString(8); // 生成8位随机字符串
        $filename = $new_name_without_ext . '.' . $file_ext; // 新文件名 = 随机字符串 + . + 原始扩展名
    } else {
        $filename = $original_filename; // 非图片文件使用原始名称
    }

    $target_dir = $is_image ? IMG_DIR : FILE_DIR;
    $target_file = $target_dir . $filename;

    $uploadOk = 1;

    // 检查文件是否存在 (使用处理后的 $filename 检查)
    if (file_exists($target_file)) {
        if ($is_image) {
            $message = "<p style='color: red;'>抱歉，生成的图片文件名 " . htmlspecialchars($filename) . " 已存在，这通常是小概率事件，请尝试重新上传。</p>";
        } else {
            // 对于非图片文件，文件名是原始的
            $message = "<p style='color: red;'>抱歉，文件 " . htmlspecialchars($original_filename) . " 已存在。</p>";
        }
        $uploadOk = 0;
    }

    // 检查文件大小 (可以根据需要调整)
    if ($uploadOk && $file['size'] > 50000000) { // 50MB
        $message = "<p style='color: red;'>抱歉，您的文件太大。</p>";
        $uploadOk = 0;
    }

    // 允许特定文件格式 (已在 $is_image 中处理，这里可以作为二次确认或扩展)
    // 对于图片，如果 $is_image 为 true，则 $file_ext 必然在 $allowed_image_exts 中。
    // 此处的 if ($is_image && !in_array(...)) 主要是防御性编程，一般不会触发。
    if ($uploadOk && $is_image && !in_array($file_ext, $allowed_image_exts)) {
        $message = "<p style='color: red;'>抱歉，只允许 JPG, PNG, GIF, ICO, JPEG 图片文件。</p>";
        $uploadOk = 0;
    } elseif ($uploadOk && !$is_image) {
         // 对于非图片文件，可以根据需要添加严格的类型限制
         // 例如：$allowed_file_exts = ['zip', 'rar', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
         // if (!in_array($file_ext, $allowed_file_exts)) { $message = "..."; $uploadOk = 0; }
    }


    if ($uploadOk == 0) {
        // $message 已在前面根据具体错误设置
        if (empty($message)) { // 确保 $message 不为空，以防有未覆盖的逻辑分支
             $message = "<p style='color: red;'>文件上传准备阶段发生未知错误。</p>";
        }
    } else {
        // 确保目标目录存在
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $message = "<p style='color: red;'>错误：无法创建上传目录 " . htmlspecialchars($target_dir) . "。请检查权限。</p>";
                $uploadOk = 0; 
            }
        }
        
        if ($uploadOk && move_uploaded_file($file['tmp_name'], $target_file)) {
            $success_display_name = $is_image ? htmlspecialchars($original_filename) . " (已保存为 " . htmlspecialchars($filename) . ")" : htmlspecialchars($filename);
            $message = "<p style='color: green;'>文件 " . $success_display_name . " 已上传成功。</p>";

            // 更新文件元数据
            $files_data = [];
            if (!is_dir(dirname(DATA_FILE))) {
                if (!mkdir(dirname(DATA_FILE), 0777, true)) {
                    $message .= "<p style='color: red;'>警告：无法创建数据存储目录 " . htmlspecialchars(dirname(DATA_FILE)) . "。</p>";
                }
            }

            if (file_exists(DATA_FILE)) {
                $files_data_content = file_get_contents(DATA_FILE);
                if ($files_data_content !== false) {
                    $decoded_data = json_decode($files_data_content, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $files_data = $decoded_data;
                    } else {
                        $message .= "<p style='color: orange;'>警告：数据文件 `files.json` 损坏，正在尝试重建。</p>";
                        $files_data = []; // 重建为空数组
                    }
                } else {
                     $message .= "<p style='color: orange;'>警告：无法读取数据文件 `files.json`。</p>";
                }
            }

            $current_time = date('Y-m-d H:i:s');
            $file_key = ($is_image ? 'img/' : 'file/') . $filename; // 使用处理后的 $filename

            // 元数据中的 'name' 存储实际的文件名（处理后的）
            $files_data[$file_key] = [
                'name' => $filename, 
                'type' => $is_image ? 'img' : 'file',
                'upload_time' => $current_time,
                'downloads' => 0
            ];
            
            if (file_put_contents(DATA_FILE, json_encode($files_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                $message .= "<p style='color: red;'>错误：无法写入文件元数据到 " . htmlspecialchars(DATA_FILE) . "。请检查权限。</p>";
            }


            if ($is_image) {
                $show_links = true;
                $image_url = SITE_BASE_URL . $file_key; // $file_key 包含处理后的文件名
                $image_name_for_links = $filename; // 链接中的 alt 和文件名部分使用新的随机文件名

                $image_links = [
                    'url' => htmlspecialchars($image_url),
                    'html' => htmlspecialchars('<img src="' . $image_url . '" alt="' . $image_name_for_links . '">'),
                    'bbcode' => htmlspecialchars('[img]' . $image_url . '[/img]'),
                    'markdown' => htmlspecialchars('![' . $image_name_for_links . '](' . $image_url . ')')
                ];
            }

        } else if ($uploadOk) { // $uploadOk 仍为 true，但 move_uploaded_file 失败
            $message = "<p style='color: red;'>抱歉，上传文件时发生错误 (无法移动文件到目标位置)。</p>";
            if ($file['error'] !== UPLOAD_ERR_OK) {
                 $upload_errors = array(
                    UPLOAD_ERR_INI_SIZE   => "文件大小超出 php.ini 中 upload_max_filesize 的限制。",
                    UPLOAD_ERR_FORM_SIZE  => "文件大小超出 HTML 表单中 MAX_FILE_SIZE 的限制。",
                    UPLOAD_ERR_PARTIAL    => "文件仅部分上传。",
                    UPLOAD_ERR_NO_FILE    => "没有文件被上传。",
                    UPLOAD_ERR_NO_TMP_DIR => "找不到临时文件夹。",
                    UPLOAD_ERR_CANT_WRITE => "文件写入失败。",
                    UPLOAD_ERR_EXTENSION  => "某个PHP扩展导致文件上传停止。",
                );
                $error_code = $file['error'];
                $message .= " 错误详情: " . ($upload_errors[$error_code] ?? "未知上传错误代码: $error_code");
             }
        }
        // 如果 $uploadOk 在此之前已为 0, $message 应该已经被相应的错误信息填充
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['file'])) {
    $message = "<p style='color: red;'>错误：没有选择文件或文件数据未成功发送到服务器。</p>";
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件上传</title>
    <link rel="icon" href="data/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="data/favicon.png">
    <link rel="stylesheet" href="css/upload_style.css"> <link rel="stylesheet" href="data/all.min.css">
</head>
<body>
    <div class="upload-area">
        <div class="header">
            <h2>文件上传</h2>
            <div class="actions">
                <a href="index.php" class="button">返回首页</a>
                <a href="login.php?action=logout" class="button logout">退出登录</a>
            </div>
        </div>

        <?php echo $message; // 显示上传结果消息 ?>

        <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="form-group">
                <label for="file_upload">选择文件:</label>
                <input type="file" name="file" id="file_upload" required>
            </div>
            <button type="submit" class="button">上传文件</button>
        </form>

        <?php if ($show_links && !empty($image_links)): ?>
            <div class="image-links-container">
                <h3>图片链接 (<?php echo htmlspecialchars($original_filename) . " -> " . htmlspecialchars($filename); ?>):</h3>
                <div class="link-item">
                    <label>URL:</label>
                    <input type='text' value='<?php echo $image_links['url']; ?>' readonly onclick="this.select()">
                </div>
                <div class="link-item">
                    <label>HTML:</label>
                    <input type='text' value='<?php echo $image_links['html']; ?>' readonly onclick="this.select()">
                </div>
                <div class="link-item">
                    <label>BBCode:</label>
                    <input type='text' value='<?php echo $image_links['bbcode']; ?>' readonly onclick="this.select()">
                </div>
                <div class="link-item">
                    <label>Markdown:</label>
                    <input type='text' value='<?php echo $image_links['markdown']; ?>' readonly onclick="this.select()">
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>