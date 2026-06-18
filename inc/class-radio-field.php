<?php
/**
 * StarFish Radio Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_RadioField')) {
    class StarFish_RadioField {
        
        /**
         * 渲染单选字段
         */
        public static function render($field, $name, $id, $value) {
            $options = isset($field['options']) ? $field['options'] : array();
            $inline = isset($field['inline']) && $field['inline'];
            $attributes = self::get_field_attributes($field);
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
