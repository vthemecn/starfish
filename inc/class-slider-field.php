<?php
/**
 * StarFish Slider Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_SliderField')) {
    class StarFish_SliderField {
        
        /**
         * 渲染滑块字段
         */
        public static function render($field, $name, $id, $value) {
            $min = isset($field['min']) ? intval($field['min']) : 0;
            $max = isset($field['max']) ? intval($field['max']) : 100;
            $step = isset($field['step']) ? intval($field['step']) : 1;
            $unit = isset($field['unit']) ? esc_html($field['unit']) : '';
            $attributes = self::get_field_attributes($field);
            ?>
            <div class="starfish-slider-wrapper">
                <input type="range" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id); ?>" 
                    value="<?php echo esc_attr($value); ?>"
                    min="<?php echo esc_attr($min); ?>"
                    max="<?php echo esc_attr($max); ?>"
                    step="<?php echo esc_attr($step); ?>"
                    class="starfish-slider"
                    <?php echo $attributes; ?>>
                <span class="starfish-slider-value"><?php echo esc_html($value); ?><?php echo $unit; ?></span>
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
