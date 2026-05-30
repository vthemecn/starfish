<?php
/**
 * Plugin Name: StarFish
 * Plugin URI: https://vtheme.cn/starfish
 * Description: 一个轻量级的 WordPress 配置框架
 * Version: 2.3.0
 * Author: VTHEME
 * Author URI: https://vtheme.cn
 * License: GPL v2 or later
 * Text Domain: starfish
 */

if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('VCONFIG_VERSION', '1.0.0');
define('VCONFIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VCONFIG_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * 加载配置文件
 */
function starfish_load_init() {
    require_once VCONFIG_PLUGIN_DIR . 'config.php';
}
add_action('plugins_loaded', 'starfish_load_init');

/**
 * 注册激活钩子
 */
function starfish_activate() {
    // 可以在这里添加激活时的逻辑
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'starfish_activate');

/**
 * 注册停用钩子
 */
function starfish_deactivate() {
    // 可以在这里添加停用时的逻辑
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'starfish_deactivate');
