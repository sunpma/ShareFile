<?php
// config.php

define('USERNAME', 'admin'); // 您的用户名
define('PASSWORD', 'admin123'); // 您的密码，请务必修改为一个强密码

define('FILE_DIR', './file/');
define('IMG_DIR', './img/');
define('DATA_FILE', './data/files.json'); // 存储文件元数据
define('SITE_BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . '/'); // 网站基础URL，自动获取当前目录
?>