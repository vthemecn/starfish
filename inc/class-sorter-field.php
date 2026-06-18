<?php
/**
 * StarFish Sorter Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_SorterField')) {
    class StarFish_SorterField {
        
        /**
         * 渲染排序器字段（双列表拖拽）
         */
        public static function render($field, $name, $id, $value) {
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
            
            $attributes = self::get_field_attributes($field);
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
         * 获取字段属性字符串
         */
        private static function get_field_attributes($field) {
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
    }
}
