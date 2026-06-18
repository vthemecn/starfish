<?php
/**
 * StarFish Gallery Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_GalleryField')) {
    class StarFish_GalleryField {
        
        /**
         * 渲染画廊字段
         */
        public static function render($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : 'Manage Gallery';
            
            // 处理 value：确保是数组格式
            if (is_string($value) && !empty($value)) {
                $images = array_filter(explode(',', $value));
            } else if (is_array($value)) {
                $images = $value;
            } else {
                $images = array();
            }
            
            $attributes = self::get_field_attributes($field);
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
