<?php
/**
 * StarFish Text Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_TextField')) {
    class StarFish_TextField {
        
        /**
         * 渲染文本字段
         */
        public static function render($field, $name, $id, $value) {
            $attributes = self::get_field_attributes($field);
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
