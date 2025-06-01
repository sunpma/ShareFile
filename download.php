<?php
// download.php
require_once 'config.php';

if (isset($_GET['file'])) {
    $file_path = urldecode($_GET['file']); // 解码URL参数

    // 简单的安全检查，防止目录遍历攻击
    if (strpos($file_path, '..') !== false || strpos($file_path, '/') === 0) {
        die('Invalid file path.');
    }

    $full_path = $file_path; // $file_path 已经是相对路径 'file/xxx' 或 'img/xxx'

    // 检查文件是否存在
    if (file_exists($full_path) && is_file($full_path)) {
        // 更新下载次数（仅对非图片文件）
        if (strpos($file_path, 'file/') === 0) { // 确保是 file 文件夹下的文件
            $files_data = [];
            if (file_exists(DATA_FILE)) {
                $files_data = json_decode(file_get_contents(DATA_FILE), true);
            }

            if (isset($files_data[$file_path])) {
                $files_data[$file_path]['downloads'] = ($files_data[$file_path]['downloads'] ?? 0) + 1;
                file_put_contents(DATA_FILE, json_encode($files_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        // 提供文件下载
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    } else {
        die('文件未找到。');
    }
} else {
    die('无效的请求。');
}
?>