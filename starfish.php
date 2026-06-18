<?php
/**
 * StarFish - WordPress Configuration Framework Core Class
 * License: GPL v2 or later
 * Text Domain: starfish
 * @package StarFish
 * @version 2.12.1
 * @author vthemecn <mail@vtheme.cn>
 * @link https://vtheme.cn
 */

defined('STARFISH_VERSION') or define('STARFISH_VERSION', '2.10.0');

if (!defined('ABSPATH')) { exit; }

// 自动加载渲染类
if (!function_exists('starfish_load_renderers')) {
    function starfish_load_renderers() {
        $renderer_dir = dirname(__FILE__) . '/inc';
        
        if (is_dir($renderer_dir)) {
            $files = glob($renderer_dir . '/class-*.php');
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        }
    }
    
    // 立即加载渲染类
    starfish_load_renderers();
}

if (!class_exists('StarFish')) {
    class StarFish {
        
        private $config = array();
        private $options = array();
        
        /**
         * 初始化配置
         */
        public function init($config) {
            $this->config = $config;
            $this->load_options();
            $this->register_hooks();
            $this->load_textdomain();
            
            // 如果数据库中没有该 option_name 的记录，创建默认值
            $this->maybe_create_default_options();
        }

        /**
         * 如果不存在则创建默认选项
         */
        private function maybe_create_default_options() {
            $option_name = $this->get_option_name();
            
            // 检查数据库中是否已存在该选项
            if (get_option($option_name, false) === false) {
                // 构建默认值数组
                $default_options = array();
                
                foreach ($this->config['pages'] as $page) {
                    if (empty($page['fields'])) {
                        continue;
                    }
                    
                    foreach ($page['fields'] as $field) {
                        if (!isset($field['id'])) {
                            continue;
                        }
                        
                        // 使用字段的默认值
                        $default_options[$field['id']] = $this->get_default_value($field);
                    }
                }
                
                // 保存到数据库
                add_option($option_name, $default_options);
                
                // 更新当前实例的 options
                $this->options = $default_options;
            }
        }
        
        /**
         * 加载已保存的选项
         */
        private function load_options() {
            $option_name = $this->get_option_name();
            $this->options = get_option($option_name, array());
            
            // 确保是数组格式
            if (!is_array($this->options)) {
                $this->options = array();
            }
        }
        
        /**
         * 注册 WordPress Hooks
         */
        private function register_hooks() {
            add_action('admin_menu', array($this, 'register_admin_menus'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_init', array($this, 'register_settings'));
            
            // 注册 AJAX 处理函数
            add_action('wp_ajax_starfish_import_settings', array($this, 'ajax_import_settings'));
            add_action('wp_ajax_starfish_reset_settings', array($this, 'ajax_reset_settings'));
        }
        
        /**
         * 加载翻译文本域
         */
        private function load_textdomain() {
            // 指定路径加载
            $mo_file = WP_PLUGIN_DIR . '/starfish/languages/zh_CN.mo';
            if (file_exists($mo_file)) {
                load_textdomain('starfish', $mo_file);
            }

            // 按照 {文本域}-{语言代码}.mo 加载 starfish-zh_CN.mo
            // load_plugin_textdomain('starfish', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }
        
        /**
         * 注册后台菜单
         */
        public function register_admin_menus() {
            if (empty($this->config['pages'])) {
                return;
            }
            
            $first_page = reset($this->config['pages']);
            $menu_slug = sanitize_title($first_page['id']);
            $menu_title = isset($this->config['menu_title']) ? $this->config['menu_title'] : 'StarFish';
            $menu_icon = isset($this->config['menu_icon']) ? $this->config['menu_icon'] : 'dashicons-admin-generic';
            
            add_menu_page(
                $menu_title,
                $menu_title,
                'manage_options',
                $menu_slug,
                array($this, 'render_admin_page'),
                $menu_icon,
                isset($this->config['menu_position']) ? $this->config['menu_position'] : null
            );
            
            // 添加第一个页面作为子菜单，使其标题独立于顶级菜单
            add_submenu_page(
                $menu_slug,
                $first_page['title'],
                $first_page['title'],
                'manage_options',
                $menu_slug,
                array($this, 'render_admin_page')
            );
            
            // 从第二个页面开始添加子菜单（跳过有 parent 的子页面）
            $pages = array_values($this->config['pages']);
            for ($i = 1; $i < count($pages); $i++) {
                $page = $pages[$i];
                
                // 如果有 parent 字段，说明是子页面（tab），不创建独立菜单
                if (!empty($page['parent'])) {
                    continue;
                }
                
                $page_slug = sanitize_title($page['id']);
                add_submenu_page(
                    $menu_slug,
                    $page['title'],
                    $page['title'],
                    'manage_options',
                    $page_slug,
                    array($this, 'render_admin_page')
                );
            }
        }
        
        /**
         * 渲染管理页面
         */
        public function render_admin_page() {
            $current_screen = get_current_screen();
            
            // 获取所有页面的 slug
            $first_page = reset($this->config['pages']);
            $menu_slug = sanitize_title($first_page['id']);
            
            // 从 screen ID 中提取当前页面 ID
            $current_page_id = '';
            
            // WordPress 的 screen ID 格式：
            // - 顶级菜单: toplevel_page_{slug}
            // - 子菜单: {parent_slug}_page_{slug}
            // 统一使用 _page_ 分割并取最后一部分
            $parts = explode('_page_', $current_screen->id);
            if (count($parts) > 1) {
                $current_page_id = end($parts);
            }
            
            // 查找当前页面对应的配置
            $current_page = null;
            foreach ($this->config['pages'] as $page) {
                if (isset($page['id']) && ($page['id'] === $current_page_id || sanitize_title($page['id']) === $current_page_id)) {
                    $current_page = $page;
                    break;
                }
            }
            
            if (!$current_page) {
                $current_page = reset($this->config['pages']);
            }
            
            // 获取当前 tab 参数
            $current_tab = isset($_GET['tab']) ? sanitize_title(sanitize_text_field($_GET['tab'])) : '';

            // 查找当前页面的所有子页面（tabs）
            $child_pages = array();
            foreach ($this->config['pages'] as $page) {
                if (!empty($page['parent']) && $page['parent'] === $current_page['id']) {
                    $child_pages[] = $page;
                }
            }
            
            // 确定实际要显示的页面
            $display_page = $current_page;
    
            // 如果有 tab 参数且对应子页面存在，显示子页面
            if (!empty($current_tab)) {
                foreach ($child_pages as $child) {
                    if (sanitize_title($child['title']) === $current_tab) {
                        $display_page = $child;
                        break;
                    }
                }
            }
            // 如果父页面没有 fields 但有子页面，自动显示第一个子页面
            elseif (empty($current_page['fields']) && !empty($child_pages)) {
                $display_page = $child_pages[0];
                $current_tab = sanitize_title($display_page['title']);
            }

            // 使用全局选项名称
            $option_name = $this->get_option_name();
            ?>
            <div class="wrap starfish-wrapper">
                <h1><?php echo esc_html($display_page['title']); ?></h1>

                <?php 
                // 如果有子页面，渲染选项卡
                if (!empty($child_pages)): 
                ?>
                    <h2 class="nav-tab-wrapper starfish-tab-wrapper">
                        <?php 
                        // 如果没有 tab 参数且父页面有 fields，父页面作为第一个 tab
                        if (!empty($current_page['fields'])): 
                        ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=' . sanitize_title($current_page['id']))); ?>" 
                            class="nav-tab <?php echo empty($current_tab) ? 'nav-tab-active' : ''; ?>">
                                <?php echo esc_html($current_page['title']); ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php foreach ($child_pages as $child): ?>
                            <?php 
                            $child_tab_slug = sanitize_title($child['title']);
                            $is_active = $current_tab === $child_tab_slug;
                            ?>
                            <a href="<?php echo esc_url(add_query_arg(array('page' => sanitize_title($current_page['id']), 'tab' => $child_tab_slug), admin_url('admin.php'))); ?>" 
                            class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>">
                                <?php echo esc_html($child['title']); ?>
                            </a>
                        <?php endforeach; ?>

                    </h2>
                <?php endif; ?>

                
                <?php settings_errors($option_name); ?>
                
                <form method="post" action="options.php" class="starfish-form">
                    <?php settings_fields($option_name); ?>
                    
                    <!-- 添加当前页面标识 -->
                    <input type="hidden" name="starfish_current_page" value="<?php echo esc_attr($display_page['id']); ?>" />
                    
                    <?php if (!empty($display_page['fields'])): ?>
                        <table class="form-table starfish-form-table" role="presentation">
                            <tbody>
                            <?php foreach ($display_page['fields'] as $field): ?>
                                <?php 
                                $page_id_for_render = isset($display_page['id']) ? $display_page['id'] : '';
                                $this->render_field($field, $option_name, $page_id_for_render); 
                                ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    
                    <?php submit_button(__('Save Settings', 'starfish')); ?>
                </form>
            </div>
            <?php
        }
        
        /**
         * 渲染单个字段
         */
        private function render_field($field, $option_name, $page_id) {
            if (!isset($field['id']) || !isset($field['type'])) {
                return;
            }
            
            // 扁平结构：直接从根级别读取字段值
            $value = isset($this->options[$field['id']]) ? $this->options[$field['id']] : $this->get_default_value($field);
            $dependency = isset($field['dependency']) ? $field['dependency'] : null;
            $field_class = 'starfish-field starfish-field-' . $field['type'];
            if ($dependency) {
                $field_class .= ' starfish-has-dependency';
            }
            
            // 解析依赖配置（新格式：array('field_name', 'operator', 'value')）
            $dep_field = '';
            $dep_operator = '==';
            $dep_value = '';
            if ($dependency && is_array($dependency)) {
                $dep_field = isset($dependency[0]) ? $dependency[0] : '';
                $dep_operator = isset($dependency[1]) ? $dependency[1] : '==';
                $dep_value = isset($dependency[2]) ? $dependency[2] : '';
            }
            
            // 从数据库获取依赖字段的值并判断
            if ($dep_field) {
                $dep_field_value = isset($this->options[$dep_field]) ? $this->options[$dep_field] : '';
                
                // 根据运算符判断是否隐藏
                $should_hide = false;
                switch ($dep_operator) {
                    case '>': $should_hide = !($dep_field_value > $dep_value); break;
                    case '<': $should_hide = !($dep_field_value < $dep_value); break;
                    case '>=': $should_hide = !($dep_field_value >= $dep_value); break;
                    case '<=': $should_hide = !($dep_field_value <= $dep_value); break;
                    case '!=': $should_hide = !($dep_field_value != $dep_value); break;
                    case '==':
                    default: $should_hide = !($dep_field_value == $dep_value); break;
                }
                
                if ($should_hide) {
                    $field_class .= ' starfish-hidden';
                }
            }
            ?>
            <tr class="<?php echo esc_attr($field_class); ?>" 
                data-field-id="<?php echo esc_attr($field['id']); ?>"
                <?php if ($dep_field): ?>
                data-dependency-field="<?php echo esc_attr($dep_field); ?>"
                data-dependency-operator="<?php echo esc_attr($dep_operator); ?>"
                data-dependency-value="<?php echo esc_attr($dep_value); ?>"
                <?php endif; ?>>
                
                <th scope="row">
                    <?php if (isset($field['title'])): ?>
                        <label for="<?php echo esc_attr($option_name . '[' . $field['id'] . ']'); ?>">
                            <?php echo esc_html($field['title']); ?>
                        </label>
                    <?php endif; ?>
                </th>
                
                <td>
                    <div class="starfish-field-content">
                        <?php $this->render_field_input($field, $option_name, $value, $page_id); ?>
                        
                        <?php if (isset($field['desc'])): ?>
                            <p class="description"><?php echo esc_html($field['desc']); ?></p>
                        <?php endif; ?>
                    </div>
                </td>
                
            </tr>
            <?php
        }
        
        /**
         * 渲染字段输入控件
         */
        private function render_field_input($field, $option_name, $value, $page_id) {
            $field_name = $option_name . '[' . $field['id'] . ']';
            $field_id = sanitize_title($field['id']);
            
            switch ($field['type']) {
                case 'text':
                    StarFish_TextField::render($field, $field_name, $field_id, $value);
                    break;
                case 'textarea':
                    StarFish_TextareaField::render($field, $field_name, $field_id, $value);
                    break;
                case 'number':
                    StarFish_NumberField::render($field, $field_name, $field_id, $value);
                    break;
                case 'select':
                    StarFish_SelectField::render($field, $field_name, $field_id, $value, $this);
                    break;
                case 'radio':
                    StarFish_RadioField::render($field, $field_name, $field_id, $value);
                    break;
                case 'checkbox':
                    StarFish_CheckboxField::render($field, $field_name, $field_id, $value);
                    break;
                case 'switcher':
                    StarFish_SwitcherField::render($field, $field_name, $field_id, $value);
                    break;
                case 'slider':
                    StarFish_SliderField::render($field, $field_name, $field_id, $value);
                    break;
                case 'color':
                    StarFish_ColorField::render($field, $field_name, $field_id, $value);
                    break;
                case 'upload':
                    StarFish_UploadField::render($field, $field_name, $field_id, $value);
                    break;
                case 'image':
                    StarFish_ImageField::render($field, $field_name, $field_id, $value);
                    break;
                case 'gallery':
                    StarFish_GalleryField::render($field, $field_name, $field_id, $value);
                    break;
                case 'group':
                    StarFish_GroupField::render($field, $field_name, $field_id, $value, $page_id);
                    break;
                case 'sorter':
                    StarFish_SorterField::render($field, $field_name, $field_id, $value);
                    break;
                case 'backup':
                    StarFish_BackupField::render($field, $option_name, $this);
                    break;
                default:
                    do_action('starfish_render_custom_field', $field, $field_name, $field_id, $value);
                    break;
            }
        }
        
        /**
         * 获取字段属性字符串
         */
        private function get_field_attributes($field) {
            $attributes = array();
            
            if (isset($field['required']) && $field['required']) {
                $attributes[] = 'required';
            }
            
            if (isset($field['placeholder'])) {
                $attributes[] = 'placeholder="' . esc_attr($field['placeholder']) . '"';
            }
            
            if (isset($field['class'])) {
                $attributes[] = 'class="' . esc_attr($field['class']) . '"';
            }
            
            if (isset($field['readonly']) && $field['readonly']) {
                $attributes[] = 'readonly';
            }
            
            if (isset($field['disabled']) && $field['disabled']) {
                $attributes[] = 'disabled';
            }
            
            return implode(' ', $attributes);
        }
        
        /**
         * 获取默认值
         */
        private function get_default_value($field) {
            if (isset($field['default'])) {
                return $field['default'];
            }
            
            switch ($field['type']) {
                case 'checkbox':
                case 'switcher':
                    return '';
                case 'group':
                    return array();
                case 'sorter':
                    return isset($field['options']) ? array_keys($field['options']) : array();
                default:
                    return '';
            }
        }
        
        /**
         * 注册设置
         */
        public function register_settings() {
            // 只注册一个全局设置项
            $option_name = $this->get_option_name();
            register_setting(
                $option_name,
                $option_name,
                array(
                    'type' => 'array',
                    'sanitize_callback' => array($this, 'sanitize_options'),
                    'default' => array()
                )
            );
        }
        
        /**
         * 清理选项数据
         */
        public function sanitize_options($options) {
            if (!is_array($options)) {
                return array();
            }
            
            // 获取已保存的选项，以保留未提交页面的数据
            $option_name = $this->get_option_name();
            $saved_options = get_option($option_name, array());
            
            // 确保是数组格式
            if (!is_array($saved_options)) {
                $saved_options = array();
            }
            
            // 获取当前提交的页面 ID
            $current_page_id = isset($_POST['starfish_current_page']) ? $_POST['starfish_current_page'] : '';
            
            // 遍历所有页面和字段，只更新当前页面提交的字段
            foreach ($this->config['pages'] as $page) {
                // 如果不是当前页面，跳过
                if (!empty($current_page_id) && isset($page['id']) && $page['id'] !== $current_page_id) {
                    continue;
                }
                
                if (empty($page['fields'])) {
                    continue;
                }
                
                foreach ($page['fields'] as $field) {
                    if (!isset($field['id'])) {
                        continue;
                    }
                    
                    $field_id = $field['id'];
                    
                    // 处理 checkbox 和 switcher 类型
                    if (in_array($field['type'], array('checkbox', 'switcher'))) {
                        if (isset($options[$field_id])) {
                            // 字段已提交（选中状态）
                            $value = $options[$field_id];
                            $saved_options[$field_id] = $this->sanitize_field_value($field, $value);
                        } else {
                            // 字段未提交（未选中状态），设置为空字符串
                            $saved_options[$field_id] = '';
                        }
                    }
                    // 其他字段类型：只处理提交的字段
                    elseif (isset($options[$field_id])) {
                        $value = $options[$field_id];
                        // 根据字段类型进行清理，直接保存到根级别
                        $saved_options[$field_id] = $this->sanitize_field_value($field, $value);
                    }
                    // 注意：其他类型的字段如果未提交，保持原值不变
                }
            }
            
            return $saved_options;
        }
        
        /**
         * 清理单个字段值
         */
        private function sanitize_field_value($field, $value) {
            if (isset($field['sanitize_callback']) && is_callable($field['sanitize_callback'])) {
                return call_user_func($field['sanitize_callback'], $value);
            }
            
            switch ($field['type']) {
                case 'text':
                case 'textarea':
                    if (isset($field['sanitize']) && $field['sanitize'] === false) {
                        return $value; 
                    }
                    return sanitize_text_field($value);
                case 'number':
                    return intval($value);
                case 'email':
                    return sanitize_email($value);
                case 'url':
                    return esc_url_raw($value);
                case 'color':
                    return sanitize_hex_color($value);
                case 'checkbox':
                    // 多个复选框（有 options）
                    if (isset($field['options']) && is_array($field['options'])) {
                        if (!is_array($value)) {
                            return array();
                        }
                        return array_map('sanitize_text_field', $value);
                    }
                    // 单个复选框
                    return $value ? '1' : '';
                case 'switcher':
                    return $value ? '1' : '';
                case 'gallery':
                    // 处理逗号分隔的字符串格式
                    if (is_string($value) && !empty($value)) {
                        $images = array_filter(explode(',', $value));
                        return array_map('sanitize_text_field', $images);
                    }
                    // 处理数组格式
                    if (is_array($value)) {
                        return array_map('sanitize_text_field', $value);
                    }
                    return array();
                case 'sorter':
                    // 处理 JSON 字符串格式
                    if (is_string($value) && !empty($value)) {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            return array_map('sanitize_text_field', $decoded);
                        }
                    }
                    // 处理数组格式
                    if (is_array($value)) {
                        return array_map('sanitize_text_field', $value);
                    }
                    return array();
                case 'select':
                    // 多选模式
                    if (isset($field['multiple']) && $field['multiple']) {
                        if (!is_array($value)) {
                            return array();
                        }
                        return array_map('sanitize_text_field', $value);
                    }
                    // 单选模式
                    return sanitize_text_field($value);
                case 'group':
                    // 如果是 JSON 字符串，解码为数组
                    if (is_string($value) && !empty($value)) {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            $value = $decoded;
                        } else {
                            return array();
                        }
                    }
                    
                    if (!is_array($value)) {
                        return array();
                    }
                    
                    $sanitized_group = array();
                    foreach ($value as $index => $group_item) {
                        if (!is_array($group_item)) {
                            continue;
                        }
                        
                        $sanitized_item = array();
                        if (isset($field['fields'])) {
                            foreach ($field['fields'] as $sub_field) {
                                if (!isset($sub_field['id'])) {
                                    continue;
                                }
                                
                                $sub_field_id = $sub_field['id'];
                                $sub_value = isset($group_item[$sub_field_id]) ? $group_item[$sub_field_id] : '';
                                $sanitized_item[$sub_field_id] = $this->sanitize_field_value($sub_field, $sub_value);
                            }
                        }
                        
                        if (!empty($sanitized_item)) {
                            $sanitized_group[] = $sanitized_item;
                        }
                    }
                    
                    return $sanitized_group;
                default:
                    return sanitize_text_field($value);
            }
        }
        
        /**
         * 加载管理后台资源
         */
        public function enqueue_admin_assets($hook) {
            // 检查是否是插件的设置页面
            $screen = get_current_screen();
            
            // 获取所有页面的 slug
            $valid_pages = array();
            if (!empty($this->config['pages'])) {
                $first_page = reset($this->config['pages']);
                $menu_slug = sanitize_title($first_page['id']);
                $valid_pages[] = 'toplevel_page_' . $menu_slug;
                
                foreach ($this->config['pages'] as $page) {
                    // 只处理有 id 字段的页面（跳过有 parent 的子页面）
                    if (isset($page['id']) && !isset($page['parent'])) {
                        $page_slug = sanitize_title($page['id']);
                        $valid_pages[] = $menu_slug . '_page_' . $page_slug;
                    }
                }
            }
            
            // 检查当前页面是否在有效页面列表中（使用更宽松的检查）
            $is_valid_page = false;
            
            if (in_array($screen->id, $valid_pages)) {
                $is_valid_page = true;
            } else {
                // 尝试其他方式匹配：检查 screen->id 是否包含我们的菜单 slug
                if (!empty($this->config['pages'])) {
                    $first_page = reset($this->config['pages']);
                    $menu_slug = sanitize_title($first_page['id']);
                    
                    // 检查是否是顶级页面或子页面
                    if (strpos($screen->id, 'toplevel_page_' . $menu_slug) === 0 || 
                        strpos($screen->id, $menu_slug . '_page_') !== false) {
                        $is_valid_page = true;
                    }
                    
                    // 额外检查：遍历所有页面 ID，看是否匹配
                    if (!$is_valid_page) {
                        foreach ($this->config['pages'] as $page) {
                            // 只处理有 id 字段的页面
                            if (isset($page['id']) && !empty($page['id'])) {
                                $page_slug = sanitize_title($page['id']);
                                if (!empty($page_slug) && strpos($screen->id, $page_slug) !== false) {
                                    $is_valid_page = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            
            if (!$is_valid_page) {
                return;
            }
            
            $assets_url = $this->starfish_get_assets_url();
            // 加载 CSS
            wp_enqueue_style(
                'starfish-style',
                $assets_url . '/style.css',
                array(),
                defined('STARFISH_VERSION') ? STARFISH_VERSION : '1.0.0'
            );
            
            // 加载 Sortable.js
            wp_enqueue_script(
                'sortable-js',
                $assets_url . '/sortable.min.js',
                array(),
                '1.15.0',
                true
            );
            
            // 加载 JS
            wp_enqueue_script(
                'starfish-script',
                $assets_url . '/index.js',
                array('sortable-js'),
                defined('STARFISH_VERSION') ? STARFISH_VERSION : '1.0.0',
                true
            );
            
            // 本地化脚本数据
            wp_localize_script('starfish-script', 'starfishData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('starfish_nonce'),
                'strings' => array(
                    'remove' => __('Remove', 'starfish'),
                    'add' => __('Add', 'starfish'),
                    'confirmDelete' => __('Are you sure you want to delete?', 'starfish'),
                    'confirmImport' => __('Are you sure you want to import settings? This will overwrite the current configuration.', 'starfish'),
                    'confirmReset' => __('Are you sure you want to reset to default settings? This will clear all custom configurations!', 'starfish'),
                    'selectFileFirst' => __('Please select a JSON file first', 'starfish'),
                    'selectFile' => __('Select File', 'starfish'),
                    'useThisFile' => __('Use This File', 'starfish'),
                    'selectImage' => __('Select Image', 'starfish'),
                    'useThisImage' => __('Use This Image', 'starfish'),
                    'manageGallery' => __('Manage Gallery', 'starfish'),
                    'addToGallery' => __('Add to Gallery', 'starfish'),
                )
            ));
            
            // 加载 WordPress 颜色选择器
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // 加载媒体上传器
            wp_enqueue_media();
        }

        
        /**
         * 获取全局选项名称（公共方法，供渲染类使用）
         */
        public function get_option_name_public() {
            return $this->get_option_name();
        }
        
        /**
         * 获取全局选项名称
         */
        private function get_option_name() {
            if (isset($this->config['option_name']) && !empty($this->config['option_name'])) {
                return $this->config['option_name'];
            }
            
            return 'starfish_settings';
        }
        
        
        /**
         * 重置设置为默认值
         */
        public function reset_to_defaults() {
            $defaults = array();
            
            foreach ($this->config['pages'] as $page) {
                if (empty($page['fields'])) {
                    continue;
                }
                
                foreach ($page['fields'] as $field) {
                    if (!isset($field['id'])) {
                        continue;
                    }
                    
                    // 使用字段的默认值
                    $defaults[$field['id']] = $this->get_default_value($field);
                }
            }
            
            $global_option_name = $this->get_option_name();
            update_option($global_option_name, $defaults);
            $this->options = $defaults;
        }
        
        /**
         * AJAX 导入设置处理
         */
        public function ajax_import_settings() {
            // 验证 nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'starfish_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed', 'starfish')
                ));
                return;
            }
            
            // 检查权限
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action', 'starfish')
                ));
                return;
            }
            
            // 检查文件是否上传
            if (!isset($_FILES['starfish_import_file']) || $_FILES['starfish_import_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(array(
                    'message' => __('File upload failed, please try again.', 'starfish')
                ));
                return;
            }
            
            $file = $_FILES['starfish_import_file'];
            
            // 验证文件大小（限制为1MB）
            $max_file_size = 1 * 1024 * 1024; // 1MB
            if ($file['size'] > $max_file_size) {
                wp_send_json_error(array(
                    'message' => __('File size exceeds the 1MB limit.', 'starfish')
                ));
                return;
            }
            
            // 验证文件扩展名
            if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
                wp_send_json_error(array(
                    'message' => __('Only JSON format backup files are supported.', 'starfish')
                ));
                return;
            }
            
            // 验证 MIME 类型（更宽松的验证）
            $mime_type_valid = true;
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($file['tmp_name']);
                $allowed_mime_types = array('application/json', 'text/plain', 'text/json', 'application/octet-stream');
                
                // 如果 MIME 类型不在允许列表中，但扩展名是 .json，我们仍然允许
                // 因为某些系统可能无法正确识别 JSON 文件的 MIME 类型
                if (!in_array($mime_type, $allowed_mime_types)) {
                    // 记录日志但不阻止上传
                    error_log('StarFish Import: MIME type detected: ' . $mime_type . ' for file: ' . $file['name']);
                }
            }
            
            // 读取文件内容
            $json_content = file_get_contents($file['tmp_name']);
            $data = json_decode($json_content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                wp_send_json_error(array(
                    'message' => __('JSON file format error, unable to parse.', 'starfish')
                ));
                return;
            }
            
            // 导入数据到全局选项
            $global_option_name = $this->get_option_name();
            
            // 直接使用导入的数据覆盖现有数据
            update_option($global_option_name, $data);
            
            // 更新当前实例的 options
            $this->options = $data;
            
            wp_send_json_success(array(
                'message' => __('Settings imported successfully!', 'starfish')
            ));
        }
        
        /**
         * AJAX 重置设置处理
         */
        public function ajax_reset_settings() {
            // 验证 nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'starfish_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed', 'starfish')
                ));
                return;
            }
            
            // 检查权限
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action', 'starfish')
                ));
                return;
            }
            
            // 重置为默认值
            $this->reset_to_defaults();
            
            wp_send_json_success(array(
                'message' => __('Successfully reset to default settings!', 'starfish')
            ));
        }

        /**
         * 获取当前文件所在目录的 URL
         * 原理：利用 $_SERVER['DOCUMENT_ROOT'] 和当前文件的物理路径进行替换
         */
        function starfish_get_assets_url() {
            $current_dir = dirname(__FILE__);
            $document_root = $_SERVER['DOCUMENT_ROOT'];
            $relative_path = str_replace($document_root, '', $current_dir);
            $relative_path = str_replace('\\', '/', $relative_path);
            $base_url = site_url() . $relative_path;
            return $base_url;
        }

    }
}
