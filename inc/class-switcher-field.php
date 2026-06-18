<?php
/**
 * StarFish Switcher Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_SwitcherField')) {
    class StarFish_SwitcherField {
        
        /**
         * 渲染开关切换器字段
         */
        public static function render($field, $name, $id, $value) {
            $attributes = self::get_field_attributes($field);
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
