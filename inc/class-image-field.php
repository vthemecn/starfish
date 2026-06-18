<?php
/**
 * StarFish Image Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_ImageField')) {
    class StarFish_ImageField {
        
        /**
         * 渲染图片选择字段
         */
        public static function render($field, $name, $id, $value) {
            $button_text = isset($field['button_text']) ? $field['button_text'] : __('Select Image', 'starfish');
            $preview_size = isset($field['preview_size']) ? $field['preview_size'] : 'thumbnail';
            $show_preview = isset($field['preview']) && $field['preview'] === true; 
            $attributes = self::get_field_attributes($field);
            ?>
            <div class="starfish-image-wrapper">
                <?php if ($show_preview): ?>
                    <div class="starfish-image-preview">
                        <?php if (!empty($value)): ?>
                            <?php 
                            $attachment_id = attachment_url_to_postid($value);
                            if ($attachment_id) {
                                echo wp_get_attachment_image($attachment_id, $preview_size);
                            } else {
                                echo '<img src="' . esc_url($value) . '" style="max-width: 150px; height: auto;">';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="starfish-image-action">
                    <input type="text" 
                        name="<?php echo esc_attr($name); ?>" 
                        id="<?php echo esc_attr($id); ?>" 
                        value="<?php echo esc_attr($value); ?>"
                        class="starfish-image-url"
                        <?php echo $attributes; ?>>
                    <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($id); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                    <?php if (!empty($value)): ?>
                        <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($id); ?>">
                            <?php _e('Remove', 'starfish'); ?>
                        </button>
                    <?php endif; ?>
                </div>
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
