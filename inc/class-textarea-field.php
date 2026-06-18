<?php
/**
 * StarFish Textarea Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_TextareaField')) {
    class StarFish_TextareaField {
        
        /**
         * 渲染文本域字段
         */
        public static function render($field, $name, $id, $value) {
            $rows = isset($field['rows']) ? intval($field['rows']) : 5;
            $attributes = self::get_field_attributes($field);
            
            // 判断是否开启了 sanitize
            $output_value = (isset($field['sanitize']) && $field['sanitize'] === false) ? $value : esc_textarea($value);
            ?>
            <textarea name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" rows="<?php echo esc_attr($rows); ?>" class="large-text" <?php echo $attributes; ?>><?php echo $output_value; ?></textarea>
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
