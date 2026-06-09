<?php
/**
 * StarFish - WordPress Configuration Framework Core Class
 * License: GPL v2 or later
 * Text Domain: starfish
 * @package StarFish
 * @version 2.9.0
 * @author vthemecn <mail@vtheme.cn>
 * @link https://vtheme.cn
 */

defined('STARFISH_VERSION') or define('STARFISH_VERSION', '2.7.1');

if (!defined('ABSPATH')) { exit; }

if (!class_exists('StarFish')) {
    class StarFish {
        
        private static $instance = null;
        private $config = array();
        private $options = array();
        
        /**
         * 获取单例实例
         */
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
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
            $option_name = $this->get_global_option_name();
            
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
            $option_name = $this->get_global_option_name();
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
            $option_name = $this->get_global_option_name();
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
                    $this->render_text_field($field, $field_name, $field_id, $value);
                    break;
                case 'textarea':
                    $this->render_textarea_field($field, $field_name, $field_id, $value);
                    break;
                case 'number':
                    $this->render_number_field($field, $field_name, $field_id, $value);
                    break;
                case 'select':
                    $this->render_select_field($field, $field_name, $field_id, $value);
                    break;
                case 'radio':
                    $this->render_radio_field($field, $field_name, $field_id, $value);
                    break;
                case 'checkbox':
                    $this->render_checkbox_field($field, $field_name, $field_id, $value);
                    break;
                case 'switcher':
                    $this->render_switcher_field($field, $field_name, $field_id, $value);
                    break;
                case 'slider':
                    $this->render_slider_field($field, $field_name, $field_id, $value);
                    break;
                case 'color':
                    $this->render_color_field($field, $field_name, $field_id, $value);
                    break;
                case 'upload':
                    $this->render_upload_field($field, $field_name, $field_id, $value);
                    break;
                case 'image':
                    $this->render_image_field($field, $field_name, $field_id, $value);
                    break;
                case 'gallery':
                    $this->render_gallery_field($field, $field_name, $field_id, $value);
                    break;
                case 'group':
                    $this->render_group_field($field, $field_name, $field_id, $value, $page_id);
                    break;
                case 'sorter':
                    $this->render_sorter_field($field, $field_name, $field_id, $value);
                    break;
                case 'backup':
                    $this->render_backup_field($field, $option_name, $page_id);
                    break;
                default:
                    do_action('starfish_render_custom_field', $field, $field_name, $field_id, $value);
                    break;
            }
        }
        
        /**
         * 文本字段
         * 绝对不能直接 echo $value。即使你关闭了数据库层面的过滤，也必须使用 esc_attr() 
         * 否则，如果用户输入了双引号 "，就会破坏 HTML 结构，导致严重的 XSS 漏洞。
         */
        private function render_text_field($field, $name, $id, $value) {
            $attributes = $this->get_field_attributes($field);
            ?>
            <input type="text" 
                name="<?php echo esc_attr($name); ?>" 
                id="<?php echo esc_attr($id); ?>" 
                value="<?php echo esc_attr($value); ?>"
                class="regular-text"
                <?php echo $attributes; ?>>
            <?php
        }
        
        /**
         * 渲染文本域字段
         */
        private function render_textarea_field($field, $name, $id, $value) {
            $rows = isset($field['rows']) ? intval($field['rows']) : 5;
            $attributes = $this->get_field_attributes($field);
            
            // 判断是否开启了 sanitize
            $output_value = (isset($field['sanitize']) && $field['sanitize'] === false) ? $value : esc_textarea($value);
            ?>
            <textarea name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" rows="<?php echo esc_attr($rows); ?>" class="large-text" <?php echo $attributes; ?>><?php echo $output_value; ?></textarea>
            <?php
        }
        
        /**
         * 数字字段
         */
        private function render_number_field($field, $name, $id, $value) {
            $min = isset($field['min']) ? intval($field['min']) : '';
            $max = isset($field['max']) ? intval($field['max']) : '';
            $step = isset($field['step']) ? intval($field['step']) : 1;
            $attributes = $this->get_field_attributes($field);
            ?>
            <input type="number" 
                name="<?php echo esc_attr($name); ?>" 
                id="<?php echo esc_attr($id); ?>" 
                value="<?php echo esc_attr($value); ?>"
                min="<?php echo esc_attr($min); ?>"
                max="<?php echo esc_attr($max); ?>"
                step="<?php echo esc_attr($step); ?>"
                class="small-text"
                <?php echo $attributes; ?>>
            <?php
        }
        
        /**
         * 下拉选择字段
         */
        private function render_select_field($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            
            // 如果设置了 query_args，动态获取数据
            if (isset($field['query_args'])) {
                $options = $this->get_query_options($field['query_args']);
            }
            
            $multiple = isset($field['multiple']) && $field['multiple'] ? ' multiple' : '';
            $attributes = $this->get_field_attributes($field);
            
            // 确保 value 是正确的类型
            if ($multiple) {
                // 多选模式：确保 value 是数组
                if (!is_array($value)) {
                    $value = !empty($value) ? array($value) : array();
                }
            } else {
                // 单选模式：确保 value 是字符串
                if (is_array($value)) {
                    $value = !empty($value) ? reset($value) : '';
                }
            }
            ?>
            <select name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>" 
                    id="<?php echo esc_attr($id); ?>"
                    class="regular-text"
                    <?php echo $multiple . ' ' . $attributes; ?>>
                <?php foreach ($options as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" 
                            <?php 
                            if ($multiple) {
                                // 多选模式：检查值是否在数组中
                                selected(in_array(strval($key), array_map('strval', $value)), true);
                            } else {
                                // 单选模式：直接比较（转换为字符串）
                                selected(strval($value), strval($key));
                            }
                            ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
        
        /**
         * 根据 query_args 获取选项
         */
        private function get_query_options($query_args) {
            $options = array();
            
            if ($query_args === 'categories') {
                // 获取所有分类
                $categories = get_categories(array(
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $options[$category->term_id] = $category->name;
                    }
                }
            } elseif ($query_args === 'pages') {
                // 获取所有页面
                $pages = get_pages(array(
                    'sort_column' => 'post_title',
                    'sort_order' => 'asc'
                ));
                
                if (!empty($pages)) {
                    foreach ($pages as $page) {
                        $options[$page->ID] = $page->post_title;
                    }
                }
            } elseif ($query_args === 'posts') {
                // 获取所有文章
                $posts = get_posts(array(
                    'numberposts' => -1,
                    'post_type' => 'post',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                if (!empty($posts)) {
                    foreach ($posts as $post) {
                        $options[$post->ID] = $post->post_title;
                    }
                }
            } elseif (is_array($query_args)) {
                // 支持自定义查询参数
                $items = get_terms($query_args);
                
                if (!empty($items) && !is_wp_error($items)) {
                    foreach ($items as $item) {
                        $options[$item->term_id] = $item->name;
                    }
                }
            }
            
            return $options;
        }
        
        /**
         * 单选字段
         */
        private function render_radio_field($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            $inline = isset($field['inline']) && $field['inline'];
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-radio-group <?php echo $inline ? 'starfish-inline' : ''; ?>">
                <?php foreach ($options as $key => $label): ?>
                    <label class="starfish-radio-label">
                        <input type="radio" 
                            name="<?php echo esc_attr($name); ?>" 
                            value="<?php echo esc_attr($key); ?>"
                            <?php checked($value, $key); ?>
                            <?php echo $attributes; ?>>
                        <span class="starfish-radio-text"><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php
        }
        
        /**
         * 复选框字段
         */
        private function render_checkbox_field($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            $inline = isset($field['inline']) && $field['inline'];
            $attributes = $this->get_field_attributes($field);
            
            if (empty($options)) {
                // 单个复选框
                ?>
                <label class="starfish-checkbox-label">
                    <input type="checkbox" 
                        name="<?php echo esc_attr($name); ?>" 
                        value="1"
                        <?php checked($value, '1'); ?>
                        <?php echo $attributes; ?>>
                    <span class="starfish-checkbox-text"><?php echo isset($field['checkbox_label']) ? esc_html($field['checkbox_label']) : ''; ?></span>
                </label>
                <?php
            } else {
                // 多个复选框
                $value = is_array($value) ? $value : array();
                ?>
                <div class="starfish-checkbox-group <?php echo $inline ? 'starfish-inline' : ''; ?>">
                    <?php foreach ($options as $key => $label): ?>
                        <label class="starfish-checkbox-label">
                            <input type="checkbox" 
                                name="<?php echo esc_attr($name); ?>[]" 
                                value="<?php echo esc_attr($key); ?>"
                                <?php checked(in_array($key, $value)); ?>
                                <?php echo $attributes; ?>>
                            <span class="starfish-checkbox-text"><?php echo esc_html($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }
        
        /**
         * 开关切换器字段
         */
        private function render_switcher_field($field, $name, $id, $value) {
            $attributes = $this->get_field_attributes($field);
            ?>
            <label class="starfish-switcher">
                <input type="checkbox" 
                    name="<?php echo esc_attr($name); ?>" 
                    value="1"
                    <?php checked($value, '1'); ?>
                    <?php echo $attributes; ?>>
                <span class="starfish-switcher-slider"></span>
            </label>
            <?php
        }
        
        /**
         * 滑块字段
         */
        private function render_slider_field($field, $name, $id, $value) {
            $min = isset($field['min']) ? intval($field['min']) : 0;
            $max = isset($field['max']) ? intval($field['max']) : 100;
            $step = isset($field['step']) ? intval($field['step']) : 1;
            $unit = isset($field['unit']) ? esc_html($field['unit']) : '';
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-slider-wrapper">
                <input type="range" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    min="<?php echo esc_attr($min); ?>"
                    max="<?php echo esc_attr($max); ?>"
                    step="<?php echo esc_attr($step); ?>"
                    class="starfish-slider"
                    <?php echo $attributes; ?>>
                <span class="starfish-slider-value"><?php echo esc_html($value); ?><?php echo $unit; ?></span>
            </div>
            <?php
        }
        
        /**
         * 颜色选择器字段
         */
        private function render_color_field($field, $name, $id, $value) {
            $default = isset($field['default']) ? $field['default'] : '#000000';
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-color-picker-wrapper">
                <input type="text" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    class="starfish-color-picker"
                    data-default-color="<?php echo esc_attr($default); ?>"
                    <?php echo $attributes; ?>>
            </div>
            <?php
        }
        
        /**
         * 上传字段
         */
        private function render_upload_field($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : __('Select File', 'starfish');
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-upload-wrapper">
                <input type="text" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    class="regular-text starfish-upload-url"
                    <?php echo $attributes; ?>>
                <button type="button" class="button starfish-upload-button" data-field-id="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($button_text); ?>
                </button>
                <?php if (!empty($value)): ?>
                    <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($id); ?>">
                        <?php _e('Remove', 'starfish'); ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php
        }
        
        /**
         * 图片选择字段
         */
        private function render_image_field($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : __('Select Image', 'starfish');
            $preview_size = isset($field['preview_size']) ? $field['preview_size'] : 'thumbnail';
            $show_preview = isset($field['preview']) && $field['preview'] === true; 
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-image-wrapper">
                <?php if ($show_preview): ?>
                    <div class="starfish-image-preview">
                        <?php if (!empty($value)): ?>
                            <?php 
                            $attachment_id = attachment_url_to_postid($value);
                            if ($attachment_id) {
                                echo wp_get_attachment_image($attachment_id, $preview_size);
                            } else {
                                echo '<img src="' . esc_url($value) . '" style="max-width: 150px; height: auto;">';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="starfish-image-action">
                    <input type="text" 
                        name="<?php echo esc_attr($name); ?>" 
                        id="<?php echo esc_attr($id); ?>" 
                        value="<?php echo esc_attr($value); ?>"
                        class="starfish-image-url"
                        <?php echo $attributes; ?>>
                    <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($id); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                    <?php if (!empty($value)): ?>
                        <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($id); ?>">
                            <?php _e('Remove', 'starfish'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
        
        /**
         * 画廊字段
         */
        private function render_gallery_field($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : 'Manage Gallery';
            
            // 处理 value：确保是数组格式
            if (is_string($value) && !empty($value)) {
                $images = array_filter(explode(',', $value));
            } else if (is_array($value)) {
                $images = $value;
            } else {
                $images = array();
            }
            
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-gallery-wrapper">
                <div class="starfish-gallery-preview">
                    <?php foreach ($images as $image_url): ?>
                        <?php if (!empty($image_url)): ?>
                            <div class="starfish-gallery-item">
                                <img src="<?php echo esc_url($image_url); ?>" style="max-width: 100px; height: auto;">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>" 
                    value="<?php echo esc_attr(is_array($images) ? implode(',', $images) : $images); ?>"
                    class="starfish-gallery-urls"
                    <?php echo $attributes; ?>>
                <button type="button" class="button starfish-gallery-button" data-field-id="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($button_text); ?>
                </button>
            </div>
            <?php
        }
        
        /**
         * 群组字段
         */
        private function render_group_field($field, $name, $id, $value, $page_id) {
            $fields = isset($field['fields']) ? $field['fields'] : array();
            $button_title = isset($field['button_title']) ? $field['button_title'] : __('Add New Item', 'starfish');
            $value = is_array($value) ? $value : array();
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-group-wrapper" data-field-id="<?php echo esc_attr($id); ?>">
                <!-- 隐藏字段存储 Group 的 JSON 值 -->
                <input type="hidden" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id . '_hidden_value'); ?>" 
                    value="<?php echo esc_attr(json_encode($value)); ?>"
                    class="starfish-group-hidden-value" />
                
                <div class="starfish-group-items">
                    <?php if (!empty($value)): ?>
                        <?php foreach ($value as $index => $group_data): ?>
                            <div class="starfish-group-item" data-index="<?php echo esc_attr($index); ?>">
                                <div class="starfish-group-header">
                                    <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                    <span class="starfish-group-title"><?php printf(__('Item #%d', 'starfish'), $index + 1); ?></span>
                                    <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                        <span class="dashicons dashicons-menu"></span>
                                    </span>
                                    <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                    </button>
                                </div>
                                <div class="starfish-group-content">
                                    <!-- .starfish-group-collapsed -->
                                    <?php foreach ($fields as $sub_field): ?>
                                        <?php 
                                        $sub_field_name = $name . '[' . $index . '][' . $sub_field['id'] . ']';
                                        $sub_field_id = $id . '_' . $index . '_' . $sub_field['id'];
                                        $sub_value = isset($group_data[$sub_field['id']]) ? $group_data[$sub_field['id']] : '';
                                        
                                        // 直接渲染，不调用render_field_input
                                        switch ($sub_field['type']) {
                                            case 'text':
                                            case 'email':
                                            case 'url':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text"
                                                        <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'textarea':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                            id="<?php echo esc_attr($sub_field_id); ?>" 
                                                            rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                            class="large-text"><?php echo esc_textarea($sub_value); ?></textarea>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'image':
                                            case 'upload':
                                                $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                                $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                                $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                                ?>
                                                <div class="starfish-image-wrapper starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <div style="display: inline-block;">
                                                        <?php if ($sub_show_preview): ?>
                                                            <div class="starfish-image-preview">
                                                                <?php if (!empty($sub_value)): ?>
                                                                    <?php 
                                                                    $sub_attachment_id = attachment_url_to_postid($sub_value);
                                                                    if ($sub_attachment_id) {
                                                                        echo wp_get_attachment_image($sub_attachment_id, $sub_preview_size);
                                                                    } else {
                                                                        echo '<img src="' . esc_url($sub_value) . '" style="max-width: 150px; height: auto;">';
                                                                    }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="starfish-image-action">
                                                            <input type="text" 
                                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                                value="<?php echo esc_attr($sub_value); ?>"
                                                                class="starfish-image-url"
                                                                placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                            <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                <?php echo esc_html($sub_button_text); ?>
                                                            </button>
                                                            <?php if (!empty($sub_value)): ?>
                                                                <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                    <?php _e('Remove', 'starfish'); ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($sub_field['desc'])): ?>
                                                            <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php
                                                break;
                                            default:
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text">
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="starfish-group-add-widget">
                    <button type="button" class="button starfish-group-add" data-field-id="<?php echo esc_attr($id); ?>" <?php echo $attributes; ?>>
                        <?php echo esc_html($button_title); ?>
                    </button>
                </div>
                
                <!-- 模板 -->
                <script type="text/template" class="starfish-group-template" data-field-id="<?php echo esc_attr($id); ?>">
                    <div class="starfish-group-item" data-index="__INDEX__">
                        <div class="starfish-group-header">
                            <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <span class="starfish-group-title"><?php _e('New Item', 'starfish'); ?></span>
                            <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="starfish-group-content">
                            <?php foreach ($fields as $sub_field): ?>
                                <?php 
                                // 构建子字段的name和id
                                $sub_field_name = $name . '[__INDEX__][' . $sub_field['id'] . ']';
                                $sub_field_id = $id . '_INDEX_' . $sub_field['id'];
                                
                                // 根据类型直接渲染
                                switch ($sub_field['type']) {
                                    case 'text':
                                    case 'email':
                                    case 'url':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text"
                                                <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'textarea':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                    id="<?php echo esc_attr($sub_field_id); ?>" 
                                                    rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                    class="large-text"><?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : ''; ?></textarea>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'image':
                                    case 'upload':
                                        $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                        $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                        $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                        ?>
                                        <div class="starfish-image-wrapper starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <div style="display: inline-block;">
                                                <?php if ($sub_show_preview): ?>
                                                    <div class="starfish-image-preview">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="starfish-image-action">
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value=""
                                                        class="starfish-image-url"
                                                        placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                    <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                        <?php echo esc_html($sub_button_text); ?>
                                                    </button>
                                                    <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>" style="display: none;">
                                                        <?php _e('Remove', 'starfish'); ?>
                                                    </button>
                                                </div>
                                                <?php if (!empty($sub_field['desc'])): ?>
                                                    <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php
                                        break;
                                    default:
                                        // 其他类型使用text input
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text">
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </script>
            </div>
            <?php
        }
        
        /**
         * 排序器字段（双列表拖拽）
         */
        private function render_sorter_field($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            $enabled_title = isset($field['enabled_title']) ? $field['enabled_title'] : __('Enabled Modules', 'starfish');
            $disabled_title = isset($field['disabled_title']) ? $field['disabled_title'] : __('Disabled Modules', 'starfish');
            
            // 处理 value：如果是 JSON 字符串则解码，否则直接使用
            if (is_string($value) && !empty($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                } else {
                    $value = array_keys($options);
                }
            } else if (!is_array($value)) {
                $value = array_keys($options);
            }
            
            // 分离已启用和已禁用的模块
            $enabled_modules = $value;
            $disabled_modules = array_diff(array_keys($options), $enabled_modules);
            
            $attributes = $this->get_field_attributes($field);
            ?>
            <div class="starfish-sorter-dual-wrapper" data-field-id="<?php echo esc_attr($id); ?>">
                <div class="starfish-sorter-containers">
                    <!-- 左侧：已启用模块 -->
                    <div class="starfish-sorter-container starfish-sorter-enabled">
                        <h3 class="starfish-sorter-title"><?php echo esc_html($enabled_title); ?></h3>
                        <ul class="starfish-sorter-list" id="<?php echo esc_attr($id); ?>_enabled">
                            <?php foreach ($enabled_modules as $key): ?>
                                <?php if (isset($options[$key])): ?>
                                    <li class="starfish-sorter-item" 
                                        data-value="<?php echo esc_attr($key); ?>" 
                                        draggable="true">
                                        <span class="starfish-sorter-handle"><span class="dashicons dashicons-menu"></span></span>
                                        <span class="starfish-sorter-label"><?php echo esc_html($options[$key]); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- 右侧：已禁用模块 -->
                    <div class="starfish-sorter-container starfish-sorter-disabled">
                        <h3 class="starfish-sorter-title"><?php echo esc_html($disabled_title); ?></h3>
                        <ul class="starfish-sorter-list" id="<?php echo esc_attr($id); ?>_disabled">
                            <?php foreach ($disabled_modules as $key): ?>
                                <?php if (isset($options[$key])): ?>
                                    <li class="starfish-sorter-item" 
                                        data-value="<?php echo esc_attr($key); ?>" 
                                        draggable="true">
                                        <span class="starfish-sorter-handle"><span class="dashicons dashicons-menu"></span></span>
                                        <span class="starfish-sorter-label"><?php echo esc_html($options[$key]); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- 隐藏域：存储已启用模块的顺序（JSON格式） -->
                <input type="hidden" 
                    class="starfish-sorter-output" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>_output"
                    value="<?php echo esc_attr(json_encode($enabled_modules)); ?>" 
                    <?php echo $attributes; ?>>
            </div>
            <?php
        }
        
        /**
         * 备份字段
         */
        private function render_backup_field($field, $option_name, $page_id) {
            $title = isset($field['title']) ? $field['title'] : __('Data Backup & Restore', 'starfish');
            $desc = isset($field['desc']) ? $field['desc'] : __('You can export the current settings as a JSON file for backup, or restore settings from a backup file.', 'starfish');
            
            // 获取全局选项名称并读取所有数据（扁平结构）
            $global_option_name = $this->get_global_option_name();
            $all_options = get_option($global_option_name, array());
            
            $backup_data = json_encode($all_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            ?>

            <div class="starfish-backup-wrapper">
                <!-- 导出部分 -->
                <div class="starfish-backup-section starfish-backup-export">
                    <h4><?php _e('Export Data', 'starfish'); ?></h4>
                    <p><?php _e('Export all current settings as a JSON file for backup and migration.', 'starfish'); ?></p>
                    <button type="button" class="button button-primary starfish-export-button" id="starfish-export-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Settings', 'starfish'); ?>
                    </button>
                    
                    <!-- 隐藏的数据域 -->
                    <textarea id="starfish-backup-data" style="display:none;"><?php echo esc_textarea($backup_data); ?></textarea>
                </div>
                
                <!-- 导入部分 -->
                <div class="starfish-backup-section starfish-backup-import">
                    <h4><?php _e('Import Data', 'starfish'); ?></h4>
                    <p><?php _e('Restore settings from a previously exported JSON file. Note: This will overwrite all current settings!', 'starfish'); ?></p>
                    
                    <div class="starfish-import-form">
                        <input type="file" 
                                name="starfish_import_file" 
                                id="starfish-import-file" 
                                accept=".json" 
                                class="starfish-import-input">
                        
                        <button type="button" 
                                class="button button-secondary starfish-import-button" 
                                id="starfish-import-btn">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Import Settings', 'starfish'); ?>
                        </button>
                        
                        <button type="button" 
                                class="button button-secondary starfish-reset-button" 
                                id="starfish-reset-btn"
                                style="margin-left: 10px;">
                            <span class="dashicons dashicons-undo"></span>
                            <?php _e('Reset Settings', 'starfish'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
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
            $option_name = $this->get_global_option_name();
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
            $option_name = $this->get_global_option_name();
            $saved_options = get_option($option_name, array());
            
            // 确保是数组格式
            if (!is_array($saved_options)) {
                $saved_options = array();
            }
            
            // 遍历所有页面和字段，只更新提交的字段（扁平结构）
            foreach ($this->config['pages'] as $page) {
                if (empty($page['fields'])) {
                    continue;
                }
                
                foreach ($page['fields'] as $field) {
                    if (!isset($field['id'])) {
                        continue;
                    }
                    
                    $field_id = $field['id'];
                    
                    // 处理 checkbox 和 switcher 类型：未提交时设置为空字符串
                    if (in_array($field['type'], array('checkbox', 'switcher'))) {
                        if (isset($options[$field_id])) {
                            $value = $options[$field_id];
                            $saved_options[$field_id] = $this->sanitize_field_value($field, $value);
                        } else {
                            // checkbox/switcher 未选中时，显式设置为空字符串
                            $saved_options[$field_id] = '';
                        }
                    }
                    // 其他字段类型：只处理提交的字段
                    elseif (isset($options[$field_id])) {
                        $value = $options[$field_id];
                        // 根据字段类型进行清理，直接保存到根级别
                        $saved_options[$field_id] = $this->sanitize_field_value($field, $value);
                    }
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
            
            // 调试：输出当前 screen ID 和有效页面列表
            // error_log('Current Screen ID: ' . $screen->id);
            // error_log('Valid Pages: ' . print_r($valid_pages, true));
            
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
            
            // 加载 CSS
            wp_enqueue_style(
                'starfish-style',
                plugin_dir_url(__FILE__) . 'style.css',
                array(),
                defined('STARFISH_VERSION') ? STARFISH_VERSION : '1.0.0'
            );
            
            // 加载 Sortable.js
            wp_enqueue_script(
                'sortable-js',
                plugin_dir_url(__FILE__) . 'sortable.min.js',
                array(),
                '1.15.0',
                true
            );
            
            // 加载 JS
            wp_enqueue_script(
                'starfish-script',
                plugin_dir_url(__FILE__) . 'index.js',
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
         * 获取单个页面选项名称（旧版兼容，内部不再用于注册设置）
         */
        private function get_option_name($page_id) {
            return 'starfish_' . sanitize_title($page_id);
        }

        /**
         * 获取全局选项名称
         */
        private function get_global_option_name() {
            return isset($this->config['option_name']) ? $this->config['option_name'] : 'starfish_settings';
        }
        
        /**
         * 获取选项值（全局辅助函数）
         */
        public static function get_option($field_id, $default = '') {
            $instance = self::get_instance();
            $option_name = $instance->get_global_option_name();
            $all_options = get_option($option_name, array());
            
            if (isset($all_options[$field_id])) {
                return $all_options[$field_id];
            }
            
            return $default;
        }
        
        /**
         * 获取所有选项
         */
        public static function get_all_options($page_id) {
            $option_name = 'starfish_settings';
            $all_options = get_option($option_name, array());
            return isset($all_options[$page_id]) ? $all_options[$page_id] : array();
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
            
            $global_option_name = $this->get_global_option_name();
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
            
            // 验证 MIME 类型
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);
            $allowed_mime_types = array('application/json', 'text/plain', 'text/json');
            if (!in_array($mime_type, $allowed_mime_types)) {
                wp_send_json_error(array(
                    'message' => __('Invalid file type. Only JSON files are allowed.', 'starfish')
                ));
                return;
            }
            
            // 使用 WordPress 的文件类型检查进行额外验证
            $wp_filetype = wp_check_filetype($file['name']);
            if ($wp_filetype['ext'] !== 'json') {
                wp_send_json_error(array(
                    'message' => __('Invalid file extension.', 'starfish')
                ));
                return;
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
            $global_option_name = $this->get_global_option_name();
            
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

    }
}
