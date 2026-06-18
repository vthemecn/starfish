<?php
/**
 * StarFish Number Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_NumberField')) {
    class StarFish_NumberField {
        
        /**
         * 渲染数字字段
         */
        public static function render($field, $name, $id, $value) {
            $min = isset($field['min']) ? intval($field['min']) : '';
            $max = isset($field['max']) ? intval($field['max']) : '';
            $step = isset($field['step']) ? intval($field['step']) : 1;
            $attributes = self::get_field_attributes($field);
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
