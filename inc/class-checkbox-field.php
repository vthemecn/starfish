<?php
/**
 * StarFish Checkbox Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_CheckboxField')) {
    class StarFish_CheckboxField {
        
        /**
         * 渲染复选框字段
         */
        public static function render($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            $inline = isset($field['inline']) && $field['inline'];
            $attributes = self::get_field_attributes($field);
            
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
