<?php
/**
 * StarFish Select Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_SelectField')) {
    class StarFish_SelectField {
        
        /**
         * 渲染下拉选择字段
         */
        public static function render($field, $name, $id, $value, $starfish_instance) {
            $options = isset($field['options']) ? $field['options'] : array();
            
            // 如果设置了 query_args，动态获取数据
            if (isset($field['query_args'])) {
                $options = self::get_query_options($field['query_args']);
            }
            
            $multiple = isset($field['multiple']) && $field['multiple'] ? ' multiple' : '';
            $attributes = self::get_field_attributes($field);
            
            // 确保 value 是正确的类型
            if ($multiple) {
                // 多选模式：确保 value 是数组
                if (!is_array($value)) {
                    $value = !empty($value) ? array($value) : array();
                }
            } else {
                // 单选模式：确保 value 是字符串
                if (is_array($value)) {
                    $value = !empty($value) ? reset($value) : '';
                }
            }
            ?>
            <select name="<?php echo esc_attr($name); ?><?php echo $multiple ? '[]' : ''; ?>" 
                    id="<?php echo esc_attr($id); ?>"
                    class="regular-text"
                    <?php echo $multiple . ' ' . $attributes; ?>>
                <?php foreach ($options as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" 
                            <?php 
                            if ($multiple) {
                                // 多选模式：检查值是否在数组中
                                selected(in_array(strval($key), array_map('strval', $value)), true);
                            } else {
                                // 单选模式：直接比较（转换为字符串）
                                selected(strval($value), strval($key));
                            }
                            ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }
        
        /**
         * 根据 query_args 获取选项
         */
        private static function get_query_options($query_args) {
            $options = array();
            
            if ($query_args === 'categories') {
                $categories = get_categories(array(
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC'
                ));
                
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $options[$category->term_id] = $category->name;
                    }
                }
            } elseif ($query_args === 'pages') {
                $pages = get_pages(array(
                    'sort_column' => 'post_title',
                    'sort_order' => 'asc'
                ));
                
                if (!empty($pages)) {
                    foreach ($pages as $page) {
                        $options[$page->ID] = $page->post_title;
                    }
                }
            } elseif ($query_args === 'posts') {
                $posts = get_posts(array(
                    'numberposts' => -1,
                    'post_type' => 'post',
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                if (!empty($posts)) {
                    foreach ($posts as $post) {
                        $options[$post->ID] = $post->post_title;
                    }
                }
            } elseif (is_array($query_args)) {
                $items = get_terms($query_args);
                
                if (!empty($items) && !is_wp_error($items)) {
                    foreach ($items as $item) {
                        $options[$item->term_id] = $item->name;
                    }
                }
            }
            
            return $options;
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
