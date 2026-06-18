<?php
/**
 * StarFish Upload Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_UploadField')) {
    class StarFish_UploadField {
        
        /**
         * 渲染上传字段
         */
        public static function render($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : __('Select File', 'starfish');
            $attributes = self::get_field_attributes($field);
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
