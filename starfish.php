<?php
/**
 * StarFish - WordPress 配置框架核心类
 * 
 * @package StarFish
 * @version 2.3.0
 * @author vthemecn <mail@vtheme.cn>
 * @link https://vtheme.cn
 */


if (!defined('ABSPATH')) {
    exit;
}

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
        
        // 从第二个页面开始添加子菜单
        $pages = array_values($this->config['pages']);
        for ($i = 1; $i < count($pages); $i++) {
            $page = $pages[$i];
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
        
        // 尝试多种方式提取页面 ID
        if ($current_screen->id === 'toplevel_page_' . $menu_slug) {
            // 第一个页面（顶级菜单）
            $current_page_id = $first_page['id'];
        } else {
            // 方法1: 尝试标准格式 {menu_slug}_page_{page_id}
            $prefix = $menu_slug . '_page_';
            if (strpos($current_screen->id, $prefix) === 0) {
                $current_page_id = substr($current_screen->id, strlen($prefix));
            }
            
            // 方法2: 如果方法1失败，尝试从末尾提取 _page_ 后面的部分
            if (empty($current_page_id)) {
                $parts = explode('_page_', $current_screen->id);
                if (count($parts) > 1) {
                    $current_page_id = end($parts);
                }
            }
            
            // 方法3: 如果还是失败，尝试匹配所有已知的页面 ID
            if (empty($current_page_id)) {
                foreach ($this->config['pages'] as $page) {
                    $page_slug = sanitize_title($page['id']);
                    if (strpos($current_screen->id, $page_slug) !== false) {
                        $current_page_id = $page['id'];
                        break;
                    }
                }
            }
        }
        
        // 查找当前页面对应的配置
        $current_page = null;
        foreach ($this->config['pages'] as $page) {
            if ($page['id'] === $current_page_id || sanitize_title($page['id']) === $current_page_id) {
                $current_page = $page;
                break;
            }
        }
        
        if (!$current_page) {
            $current_page = reset($this->config['pages']);
        }
        
        // 使用全局选项名称
        $option_name = $this->get_global_option_name();
        ?>
        <div class="wrap starfish-wrapper">
            <h1><?php echo esc_html($current_page['title']); ?></h1>
            
            <?php settings_errors($option_name); ?>
            
            <form method="post" action="options.php" class="starfish-form">
                <?php settings_fields($option_name); ?>
                
                <?php if (!empty($current_page['fields'])): ?>
                    <table class="form-table starfish-form-table" role="presentation">
                        <tbody>
                        <?php foreach ($current_page['fields'] as $field): ?>
                            <?php $this->render_field($field, $option_name, $current_page['id']); ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <?php submit_button(__('保存设置', 'starfish')); ?>
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
        ?>
        <tr class="<?php echo esc_attr($field_class); ?>" 
             data-field-id="<?php echo esc_attr($field['id']); ?>"
             <?php if ($dependency): ?>
             data-dependency-field="<?php echo esc_attr($dependency['field']); ?>"
             data-dependency-value="<?php echo esc_attr($dependency['value']); ?>"
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
     * 文本域字段
     */
    private function render_textarea_field($field, $name, $id, $value) {
        $rows = isset($field['rows']) ? intval($field['rows']) : 5;
        $attributes = $this->get_field_attributes($field);
        ?>
        <textarea name="<?php echo esc_attr($name); ?>" 
                  id="<?php echo esc_attr($id); ?>" 
                  rows="<?php echo esc_attr($rows); ?>"
                  class="large-text"
                  <?php echo $attributes; ?>><?php echo esc_textarea($value); ?></textarea>
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
        $multiple = isset($field['multiple']) && $field['multiple'] ? ' multiple' : '';
        $attributes = $this->get_field_attributes($field);
        ?>
        <select name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>" 
                id="<?php echo esc_attr($id); ?>"
                class="regular-text"
                <?php echo $multiple . ' ' . $attributes; ?>>
            <?php foreach ($options as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" 
                        <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
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
        $button_text = isset($field['button_text']) ? $field['button_text'] : __('选择文件', 'starfish');
        $attributes = $this->get_field_attributes($field);
        ?>
        <div class="starfish-upload-wrapper">
            <input type="text" 
                   name="<?php echo esc_attr($name); ?>" 
                   id="<?php echo esc_attr($id); ?>" 
                   value="<?php echo esc_attr($value); ?>"
                   class="regular-text starfish-upload-url"
                   readonly
                   <?php echo $attributes; ?>>
            <button type="button" class="button starfish-upload-button" data-field-id="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($button_text); ?>
            </button>
            <?php if (!empty($value)): ?>
                <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($id); ?>">
                    <?php _e('移除', 'starfish'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 图片选择字段
     */
    private function render_image_field($field, $name, $id, $value) {
        $button_text = isset($field['button_text']) ? $field['button_text'] : __('选择图片', 'starfish');
        $preview_size = isset($field['preview_size']) ? $field['preview_size'] : 'thumbnail';
        $attributes = $this->get_field_attributes($field);
        ?>
        <div class="starfish-image-wrapper">
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
            <input type="hidden" 
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
                    <?php _e('移除', 'starfish'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 画廊字段
     */
    private function render_gallery_field($field, $name, $id, $value) {
        $button_text = isset($field['button_text']) ? $field['button_text'] : __('管理画廊', 'starfish');
        
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
        $button_title = isset($field['button_title']) ? $field['button_title'] : __('添加新项', 'starfish');
        $value = is_array($value) ? $value : array();
        $attributes = $this->get_field_attributes($field);
        ?>
        <div class="starfish-group-wrapper" data-field-id="<?php echo esc_attr($id); ?>">
            <div class="starfish-group-items">
                <?php if (!empty($value)): ?>
                    <?php foreach ($value as $index => $group_data): ?>
                        <div class="starfish-group-item" data-index="<?php echo esc_attr($index); ?>">
                            <div class="starfish-group-header">
                                <span class="starfish-group-title"><?php printf(__('项目 #%d', 'starfish'), $index + 1); ?></span>
                                <button type="button" class="button starfish-group-remove" title="<?php _e('删除', 'starfish'); ?>">
                                    <span class="dashicons dashicons-no"></span>
                                </button>
                            </div>
                            <div class="starfish-group-content">
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
                                            ?>
                                            <div class="starfish-image-wrapper starfish-sub-field">
                                                <?php if (!empty($sub_field['title'])): ?>
                                                    <label><?php echo esc_html($sub_field['title']); ?></label>
                                                <?php endif; ?>
                                                <div class="starfish-image-preview">
                                                    <?php if (!empty($sub_value)): ?>
                                                        <?php 
                                                        $attachment_id = attachment_url_to_postid($sub_value);
                                                        if ($attachment_id) {
                                                            echo wp_get_attachment_image($attachment_id, 'thumbnail');
                                                        } else {
                                                            echo '<img src="' . esc_url($sub_value) . '" style="max-width: 150px; height: auto;">';
                                                        }
                                                        ?>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" 
                                                       name="<?php echo esc_attr($sub_field_name); ?>" 
                                                       id="<?php echo esc_attr($sub_field_id); ?>" 
                                                       value="<?php echo esc_attr($sub_value); ?>"
                                                       class="starfish-image-url">
                                                <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($id . '_' . $index); ?>">
                                                    <?php echo isset($sub_field['button_text']) ? esc_html($sub_field['button_text']) : __('选择', 'starfish'); ?>
                                                </button>
                                                <?php if (!empty($sub_value)): ?>
                                                    <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($id . '_' . $index); ?>">
                                                        <?php _e('移除', 'starfish'); ?>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (!empty($sub_field['desc'])): ?>
                                                    <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                <?php endif; ?>
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
                        <span class="starfish-group-title"><?php _e('新项目', 'starfish'); ?></span>
                        <button type="button" class="button starfish-group-remove" title="<?php _e('删除', 'starfish'); ?>">
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
                                    ?>
                                    <div class="starfish-image-wrapper starfish-sub-field">
                                        <?php if (!empty($sub_field['title'])): ?>
                                            <label><?php echo esc_html($sub_field['title']); ?></label>
                                        <?php endif; ?>
                                        <div class="starfish-image-preview">
                                        </div>
                                        <input type="hidden" 
                                               name="<?php echo esc_attr($sub_field_name); ?>" 
                                               id="<?php echo esc_attr($sub_field_id); ?>" 
                                               value=""
                                               class="starfish-image-url">
                                        <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($id); ?>_INDEX_<?php echo esc_attr($sub_field['id']); ?>">
                                            <?php echo isset($sub_field['button_text']) ? esc_html($sub_field['button_text']) : __('选择', 'starfish'); ?>
                                        </button>
                                        <?php if (!empty($sub_field['desc'])): ?>
                                            <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                        <?php endif; ?>
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
        $enabled_title = isset($field['enabled_title']) ? $field['enabled_title'] : __('已启用模块', 'starfish');
        $disabled_title = isset($field['disabled_title']) ? $field['disabled_title'] : __('已禁用模块', 'starfish');
        
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
        $title = isset($field['title']) ? $field['title'] : __('数据备份与还原', 'starfish');
        $desc = isset($field['desc']) ? $field['desc'] : __('您可以导出当前设置为JSON文件进行备份，或从备份文件还原设置', 'starfish');
        
        // 获取全局选项名称并读取所有数据（扁平结构）
        $global_option_name = $this->get_global_option_name();
        $all_options = get_option($global_option_name, array());
        
        $backup_data = json_encode($all_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        ?>
        <div class="starfish-backup-wrapper">
            <!-- 导出部分 -->
            <div class="starfish-backup-section starfish-backup-export">
                <h4><?php _e('导出数据', 'starfish'); ?></h4>
                <p><?php _e('将当前所有设置导出为JSON文件，方便备份和迁移。', 'starfish'); ?></p>
                <button type="button" class="button button-primary starfish-export-button" id="starfish-export-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('导出设置', 'starfish'); ?>
                </button>
                
                <!-- 隐藏的数据域 -->
                <textarea id="starfish-backup-data" style="display:none;"><?php echo esc_textarea($backup_data); ?></textarea>
            </div>
            
            <!-- 导入部分 -->
            <div class="starfish-backup-section starfish-backup-import">
                <h4><?php _e('导入数据', 'starfish'); ?></h4>
                <p><?php _e('从之前导出的JSON文件还原设置。注意：这将覆盖当前所有设置！', 'starfish'); ?></p>
                
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
                        <?php _e('导入设置', 'starfish'); ?>
                    </button>
                    
                    <button type="button" 
                            class="button button-secondary starfish-reset-button" 
                            id="starfish-reset-btn"
                            style="margin-left: 10px;">
                        <span class="dashicons dashicons-undo"></span>
                        <?php _e('重置设置', 'starfish'); ?>
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
                
                // 只处理当前提交的字段
                if (isset($options[$field_id])) {
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
            case 'group':
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
                $page_slug = sanitize_title($page['id']);
                $valid_pages[] = $menu_slug . '_page_' . $page_slug;
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
                        $page_slug = sanitize_title($page['id']);
                        if (strpos($screen->id, $page_slug) !== false) {
                            $is_valid_page = true;
                            break;
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
            defined('VCONFIG_VERSION') ? VCONFIG_VERSION : '1.0.0'
        );
        
        // 加载 JS
        wp_enqueue_script(
            'starfish-script',
            plugin_dir_url(__FILE__) . 'index.js',
            array(),
            defined('VCONFIG_VERSION') ? VCONFIG_VERSION : '1.0.0',
            true
        );
        
        // 本地化脚本数据
        wp_localize_script('starfish-script', 'starfishData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('starfish_nonce'),
            'strings' => array(
                'remove' => __('移除', 'starfish'),
                'add' => __('添加', 'starfish'),
                'confirmDelete' => __('确定要删除吗？', 'starfish'),
                'confirmImport' => __('确定要导入设置吗？这将覆盖当前的配置。', 'starfish'),
                'confirmReset' => __('确定要重置为默认设置吗？这将清除所有自定义配置！', 'starfish'),
                'selectFileFirst' => __('请先选择一个JSON文件', 'starfish'),
                'selectFile' => __('选择文件', 'starfish'),
                'useThisFile' => __('使用此文件', 'starfish'),
                'selectImage' => __('选择图片', 'starfish'),
                'useThisImage' => __('使用此图片', 'starfish'),
                'manageGallery' => __('管理画廊', 'starfish'),
                'addToGallery' => __('添加到画廊', 'starfish'),
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
                'message' => __('安全验证失败', 'starfish')
            ));
            return;
        }
        
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('没有权限执行此操作', 'starfish')
            ));
            return;
        }
        
        // 检查文件是否上传
        if (!isset($_FILES['starfish_import_file']) || $_FILES['starfish_import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array(
                'message' => __('上传文件失败，请重试。', 'starfish')
            ));
            return;
        }
        
        $file = $_FILES['starfish_import_file'];
        
        // 验证文件类型
        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            wp_send_json_error(array(
                'message' => __('只支持JSON格式的备份文件。', 'starfish')
            ));
            return;
        }
        
        // 读取文件内容
        $json_content = file_get_contents($file['tmp_name']);
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            wp_send_json_error(array(
                'message' => __('JSON文件格式错误，无法解析。', 'starfish')
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
            'message' => __('成功导入设置！', 'starfish')
        ));
    }
    
    /**
     * AJAX 重置设置处理
     */
    public function ajax_reset_settings() {
        // 验证 nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'starfish_nonce')) {
            wp_send_json_error(array(
                'message' => __('安全验证失败', 'starfish')
            ));
            return;
        }
        
        // 检查权限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('没有权限执行此操作', 'starfish')
            ));
            return;
        }
        
        // 重置为默认值
        $this->reset_to_defaults();
        
        wp_send_json_success(array(
            'message' => __('已成功重置为默认设置！', 'starfish')
        ));
    }

}
