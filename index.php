<?php
// index.php
session_start();
require_once 'config.php';

$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 读取文件元数据
$files_data = [];
if (file_exists(DATA_FILE)) {
    $files_data_content = file_get_contents(DATA_FILE);
    if ($files_data_content !== false) {
        $decoded_data = json_decode($files_data_content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $files_data = $decoded_data;
        }
    }
}

// 筛选出实际存在的文件，并过滤掉 files.json 中记录但实际已被删除的文件
$filtered_files_data = [];
foreach ($files_data as $path => $data) {
    // 检查文件是否存在于文件系统中
    if (file_exists($path) && is_file($path)) {
        // 健壮性检查：确保 $data 数组包含必要的键
        if (isset($data['name'], $data['type'], $data['upload_time'])) {
            $filtered_files_data[$path] = $data;
        }
    }
}

// 更新 files.json，移除不存在的文件记录（可选，但推荐保持数据同步）
// 注意：如果文件量大，每次加载都写文件可能影响性能。
// 对于单用户小规模应用，可以接受。
if (count($files_data) !== count($filtered_files_data)) {
    file_put_contents(DATA_FILE, json_encode($filtered_files_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $files_data = $filtered_files_data; // 更新当前页面的数据源
} else {
    $files_data = $filtered_files_data; // 否则直接使用过滤后的数据
}


// 区分文件和图片
$files = [];
$images = [];

foreach ($files_data as $path => $data) {
    if ($data['type'] === 'file') {
        $files[$path] = $data;
    } else {
        $images[$path] = $data;
    }
}

// 默认显示文件列表
$current_view = $_GET['view'] ?? 'file'; // 'file' 或 'img'
$display_data = ($current_view === 'img') ? $images : $files;

// 排序功能（可选）
if (isset($_GET['sort'])) {
    $sort_by = $_GET['sort'];
    $sort_order = $_GET['order'] ?? 'asc'; // 'asc' 或 'desc'

    usort($display_data, function($a, $b) use ($sort_by, $sort_order) {
        $val_a = $a[$sort_by] ?? '';
        $val_b = $b[$sort_by] ?? '';

        if ($sort_by === 'downloads') {
            $val_a = (int)$val_a;
            $val_b = (int)$val_b;
        }

        // 处理可能的空值或非预期类型，确保比较不会报错
        if (!is_scalar($val_a) || !is_scalar($val_b)) {
            return 0; // 无法比较，保持原顺序
        }

        if ($val_a == $val_b) {
            return 0;
        }

        if ($sort_order === 'asc') {
            return ($val_a < $val_b) ? -1 : 1;
        } else {
            return ($val_a > $val_b) ? -1 : 1;
        }
    });
}

// 图片缩略图的尺寸（固定为正方形，方便CSS布局）
define('THUMBNAIL_SIZE', '150');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件管理器</title>
    <link rel="icon" href="data/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="data/favicon.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="data/all.min.css"> </head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>目录</h2>
            </div>
            <ul>
                <li class="<?php echo ($current_view === 'file' ? 'active' : ''); ?>">
                    <a href="?view=file"><i class="fas fa-folder"></i> 文件</a>
                </li>
                <li class="<?php echo ($current_view === 'img' ? 'active' : ''); ?>">
                    <a href="?view=img"><i class="fas fa-image"></i> 图片</a>
                </li>
                </ul>
        </div>
        <div class="main-content">
            <div class="header">
                <h1><?php echo ($current_view === 'file' ? '文件列表' : '图片列表'); ?></h1>
                <div class="actions">
                    <?php if ($is_logged_in): ?>
                        <a href="upload.php" class="button"><i class="fas fa-upload"></i> 上传文件</a>
                        <a href="login.php?action=logout" class="button logout"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
                    <?php else: ?>
                        <a href="login.php" class="button login"><i class="fas fa-sign-in-alt"></i> 登录</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($current_view === 'file'): // 文件列表 ?>
                <div class="file-list">
                    <table>
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="name">
                                    名称
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                        <i class="fas fa-sort-up" style="display:none;"></i>
                                        <i class="fas fa-sort-down" style="display:none;"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-sort="upload_time">
                                    上传时间
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                        <i class="fas fa-sort-up" style="display:none;"></i>
                                        <i class="fas fa-sort-down" style="display:none;"></i>
                                    </span>
                                </th>
                                <th class="sortable" data-sort="downloads">
                                    下载次数
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                        <i class="fas fa-sort-up" style="display:none;"></i>
                                        <i class="fas fa-sort-down" style="display:none;"></i>
                                    </span>
                                </th>
                                <th>链接地址</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($display_data)): ?>
                                <tr><td colspan="4" style="text-align: center;">没有文件。</td></tr>
                            <?php else: ?>
                                <?php foreach ($display_data as $file_path => $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(isset($data['upload_time']) ? date('Y-m-d H:i:s', strtotime($data['upload_time'])) : ''); ?></td>
                                        <td><?php echo (int)($data['downloads'] ?? 0); ?></td>
                                        <td>
                                            <a href="#" class="show-link-card" data-link="<?php echo htmlspecialchars(SITE_BASE_URL . 'download.php?file=' . urlencode($file_path)); ?>">
                                                <i class="fas fa-link"></i> 获取链接
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: // 图片列表 ?>
                <div class="image-grid">
                    <?php if (empty($display_data)): ?>
                        <p style="text-align: center; grid-column: 1 / -1;">没有图片。</p>
                    <?php else: ?>
                        <?php foreach ($display_data as $file_path => $data):
                            $image_full_url = SITE_BASE_URL . $file_path;
                            $image_name = htmlspecialchars($data['name'] ?? '图片');
                        ?>
                            <div class="image-item"
                                 data-full-url="<?php echo htmlspecialchars($image_full_url); ?>"
                                 data-image-name="<?php echo $image_name; ?>">
                                <img src="<?php echo htmlspecialchars($image_full_url); ?>" alt="<?php echo $image_name; ?>" loading="lazy">
                                <div class="image-title"><?php echo $image_name; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; padding: 20px 0; clear: both; width: 100%;">
                <a style="color:#cecece; text-decoration: none;" target="_blank" href="https://suntl.com">SunPma'Blog -</a> <a style="color:#cecece; text-decoration: none;" target="_blank" href="https://github.com/sunpma/ShareFile">ShareFile</a>
            </div>

        </div> </div> <div id="linkModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>文件下载链接</h2>
            <div class="link-item">
                <label>直连地址:</label>
                <input type="text" id="fileDirectLink" readonly onclick="this.select()">
                <button class="copy-button" data-target="fileDirectLink"><i class="fas fa-copy"></i> 复制</button>
            </div>
            <a href="#" id="fileDirectLinkDownload" target="_blank" class="button download-button"><i class="fas fa-download"></i> 前往下载</a>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <div class="modal-content image-modal-content">
            <span class="close-button">&times;</span>
            <img id="modalImage" src="" alt="放大图片">
            <div class="image-links-container">
                <h3>图片链接:</h3>
                <div class="link-item">
                    <label>URL:</label>
                    <input type='text' id="imageUrl" readonly onclick="this.select()">
                    <button class="copy-button" data-target="imageUrl"><i class="fas fa-copy"></i> 复制</button>
                </div>
                <div class="link-item">
                    <label>HTML:</label>
                    <input type='text' id="imageHtml" readonly onclick="this.select()">
                    <button class="copy-button" data-target="imageHtml"><i class="fas fa-copy"></i> 复制</button>
                </div>
                <div class="link-item">
                    <label>BBCode:</label>
                    <input type='text' id="imageBbcode" readonly onclick="this.select()">
                    <button class="copy-button" data-target="imageBbcode"><i class="fas fa-copy"></i> 复制</button>
                </div>
                <div class="link-item">
                    <label>Markdown:</label>
                    <input type='text' id="imageMarkdown" readonly onclick="this.select()">
                    <button class="copy-button" data-target="imageMarkdown"><i class="fas fa-copy"></i> 复制</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
