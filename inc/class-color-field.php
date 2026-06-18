<?php
/**
 * StarFish Color Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_ColorField')) {
    class StarFish_ColorField {
        
        /**
         * 渲染颜色选择器字段
         */
        public static function render($field, $name, $id, $value) {
            $default = isset($field['default']) ? $field['default'] : '#000000';
            $attributes = self::get_field_attributes($field);
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
