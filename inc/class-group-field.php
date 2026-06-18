<?php
/**
 * StarFish Group Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_GroupField')) {
    class StarFish_GroupField {
        
        /**
         * 渲染群组字段
         */
        public static function render($field, $name, $id, $value, $page_id) {
            $fields = isset($field['fields']) ? $field['fields'] : array();
            $button_title = isset($field['button_title']) ? $field['button_title'] : __('Add New Item', 'starfish');
            $value = is_array($value) ? $value : array();
            $attributes = self::get_field_attributes($field);
            ?>
            <div class="starfish-group-wrapper" data-field-id="<?php echo esc_attr($id); ?>">
                <!-- 隐藏字段存储 Group 的 JSON 值 -->
                <input type="hidden" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id . '_hidden_value'); ?>" 
                    value="<?php echo esc_attr(json_encode($value)); ?>"
                    class="starfish-group-hidden-value" />
                
                <div class="starfish-group-items">
                    <?php if (!empty($value)): ?>
                        <?php foreach ($value as $index => $group_data): ?>
                            <div class="starfish-group-item" data-index="<?php echo esc_attr($index); ?>">
                                <div class="starfish-group-header">
                                    <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                    <span class="starfish-group-title"><?php printf(__('Item #%d', 'starfish'), $index + 1); ?></span>
                                    <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                        <span class="dashicons dashicons-menu"></span>
                                    </span>
                                    <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                    </button>
                                </div>
                                <div class="starfish-group-content">
                                    <?php foreach ($fields as $sub_field): ?>
                                        <?php 
                                        $sub_field_name = $name . '[' . $index . '][' . $sub_field['id'] . ']';
                                        $sub_field_id = $id . '_' . $index . '_' . $sub_field['id'];
                                        $sub_value = isset($group_data[$sub_field['id']]) ? $group_data[$sub_field['id']] : '';
                                        
                                        // 根据类型直接渲染
                                        switch ($sub_field['type']) {
                                            case 'text':
                                            case 'email':
                                            case 'url':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text"
                                                        <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'textarea':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                            id="<?php echo esc_attr($sub_field_id); ?>" 
                                                            rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                            class="large-text"><?php echo esc_textarea($sub_value); ?></textarea>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'image':
                                            case 'upload':
                                                $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                                $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                                $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                                ?>
                                                <div class="starfish-image-wrapper starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <div style="display: inline-block;">
                                                        <?php if ($sub_show_preview): ?>
                                                            <div class="starfish-image-preview">
                                                                <?php if (!empty($sub_value)): ?>
                                                                    <?php 
                                                                    $sub_attachment_id = attachment_url_to_postid($sub_value);
                                                                    if ($sub_attachment_id) {
                                                                        echo wp_get_attachment_image($sub_attachment_id, $sub_preview_size);
                                                                    } else {
                                                                        echo '<img src="' . esc_url($sub_value) . '" style="max-width: 150px; height: auto;">';
                                                                    }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="starfish-image-action">
                                                            <input type="text" 
                                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                                value="<?php echo esc_attr($sub_value); ?>"
                                                                class="starfish-image-url"
                                                                placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                            <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                <?php echo esc_html($sub_button_text); ?>
                                                            </button>
                                                            <?php if (!empty($sub_value)): ?>
                                                                <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                    <?php _e('Remove', 'starfish'); ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($sub_field['desc'])): ?>
                                                            <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php
                                                break;
                                            case 'group':
                                                // 嵌套 Group 字段（仅支持一层嵌套）
                                                self::render_nested_group($sub_field, $sub_field_name, $sub_field_id, $sub_value, $page_id);
                                                break;
                                            default:
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text">
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="starfish-group-add-widget">
                    <button type="button" class="button starfish-group-add" data-field-id="<?php echo esc_attr($id); ?>" <?php echo $attributes; ?>>
                        <?php echo esc_html($button_title); ?>
                    </button>
                </div>
                
                <!-- 模板 -->
                <script type="text/template" class="starfish-group-template" data-field-id="<?php echo esc_attr($id); ?>">
                    <div class="starfish-group-item" data-index="__INDEX__">
                        <div class="starfish-group-header">
                            <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <span class="starfish-group-title"><?php _e('New Item', 'starfish'); ?></span>
                            <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="starfish-group-content">
                            <?php foreach ($fields as $sub_field): ?>
                                <?php 
                                $sub_field_name = $name . '[__INDEX__][' . $sub_field['id'] . ']';
                                $sub_field_id = $id . '_INDEX_' . $sub_field['id'];
                                
                                // 根据类型直接渲染
                                switch ($sub_field['type']) {
                                    case 'text':
                                    case 'email':
                                    case 'url':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text"
                                                <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'textarea':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                    id="<?php echo esc_attr($sub_field_id); ?>" 
                                                    rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                    class="large-text"><?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : ''; ?></textarea>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'image':
                                    case 'upload':
                                        $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                        $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                        $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                        ?>
                                        <div class="starfish-image-wrapper starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <div style="display: inline-block;">
                                                <?php if ($sub_show_preview): ?>
                                                    <div class="starfish-image-preview">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="starfish-image-action">
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value=""
                                                        class="starfish-image-url"
                                                        placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                    <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                        <?php echo esc_html($sub_button_text); ?>
                                                    </button>
                                                    <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>" style="display: none;">
                                                        <?php _e('Remove', 'starfish'); ?>
                                                    </button>
                                                </div>
                                                <?php if (!empty($sub_field['desc'])): ?>
                                                    <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php
                                        break;
                                    default:
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text">
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </script>
            </div>
            <?php
        }
        
        /**
         * 渲染嵌套 Group 字段
         */
        private static function render_nested_group($field, $name, $id, $value, $page_id) {
            $nested_fields = isset($field['fields']) ? $field['fields'] : array();
            $button_title = isset($field['button_title']) ? $field['button_title'] : __('Add New Item', 'starfish');
            $value = is_array($value) ? $value : array();
            $attributes = self::get_field_attributes($field);
            ?>
            <div class="starfish-group-wrapper starfish-nested-group" data-field-id="<?php echo esc_attr($id); ?>">
                <input type="hidden" 
                    name="<?php echo esc_attr($name); ?>" 
                    id="<?php echo esc_attr($id . '_hidden_value'); ?>" 
                    value="<?php echo esc_attr(json_encode($value)); ?>"
                    class="starfish-group-hidden-value" />
                
                <div class="starfish-group-items">
                    <?php if (!empty($value)): ?>
                        <?php foreach ($value as $index => $group_data): ?>
                            <div class="starfish-group-item" data-index="<?php echo esc_attr($index); ?>">
                                <div class="starfish-group-header">
                                    <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                    <span class="starfish-group-title"><?php printf(__('Item #%d', 'starfish'), $index + 1); ?></span>
                                    <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                        <span class="dashicons dashicons-menu"></span>
                                    </span>
                                    <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                    </button>
                                </div>
                                <div class="starfish-group-content">
                                    <?php foreach ($nested_fields as $sub_field): ?>
                                        <?php 
                                        $sub_field_name = $name . '[' . $index . '][' . $sub_field['id'] . ']';
                                        $sub_field_id = $id . '_' . $index . '_' . $sub_field['id'];
                                        $sub_value = isset($group_data[$sub_field['id']]) ? $group_data[$sub_field['id']] : '';
                                        
                                        switch ($sub_field['type']) {
                                            case 'text':
                                            case 'email':
                                            case 'url':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text"
                                                        <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'textarea':
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                            id="<?php echo esc_attr($sub_field_id); ?>" 
                                                            rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                            class="large-text"><?php echo esc_textarea($sub_value); ?></textarea>
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                break;
                                            case 'image':
                                            case 'upload':
                                                $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                                $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                                $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                                ?>
                                                <div class="starfish-image-wrapper starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <div style="display: inline-block;">
                                                        <?php if ($sub_show_preview): ?>
                                                            <div class="starfish-image-preview">
                                                                <?php if (!empty($sub_value)): ?>
                                                                    <?php 
                                                                    $sub_attachment_id = attachment_url_to_postid($sub_value);
                                                                    if ($sub_attachment_id) {
                                                                        echo wp_get_attachment_image($sub_attachment_id, $sub_preview_size);
                                                                    } else {
                                                                        echo '<img src="' . esc_url($sub_value) . '" style="max-width: 150px; height: auto;">';
                                                                    }
                                                                    ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="starfish-image-action">
                                                            <input type="text" 
                                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                                value="<?php echo esc_attr($sub_value); ?>"
                                                                class="starfish-image-url"
                                                                placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                            <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                <?php echo esc_html($sub_button_text); ?>
                                                            </button>
                                                            <?php if (!empty($sub_value)): ?>
                                                                <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                                    <?php _e('Remove', 'starfish'); ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!empty($sub_field['desc'])): ?>
                                                            <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php
                                                break;
                                            default:
                                                ?>
                                                <div class="starfish-sub-field">
                                                    <?php if (!empty($sub_field['title'])): ?>
                                                        <label><?php echo esc_html($sub_field['title']); ?></label>
                                                    <?php endif; ?>
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value="<?php echo esc_attr($sub_value); ?>"
                                                        class="regular-text">
                                                    <?php if (!empty($sub_field['desc'])): ?>
                                                        <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                        }
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="starfish-group-add-widget">
                    <button type="button" class="button starfish-group-add" data-field-id="<?php echo esc_attr($id); ?>" <?php echo $attributes; ?>>
                        <?php echo esc_html($button_title); ?>
                    </button>
                </div>
                
                <script type="text/template" class="starfish-group-template" data-field-id="<?php echo esc_attr($id); ?>">
                    <div class="starfish-group-item" data-index="__INDEX__">
                        <div class="starfish-group-header">
                            <button type="button" class="button starfish-group-toggle" title="<?php _e('Toggle', 'starfish'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                            <span class="starfish-group-title"><?php _e('New Item', 'starfish'); ?></span>
                            <span class="starfish-group-drag-handle" title="<?php _e('Drag to reorder', 'starfish'); ?>">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <button type="button" class="button starfish-group-remove" title="<?php _e('Delete', 'starfish'); ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="starfish-group-content">
                            <?php foreach ($nested_fields as $sub_field): ?>
                                <?php 
                                $sub_field_name = $name . '[__INDEX__][' . $sub_field['id'] . ']';
                                $sub_field_id = $id . '_INDEX_' . $sub_field['id'];
                                
                                switch ($sub_field['type']) {
                                    case 'text':
                                    case 'email':
                                    case 'url':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text"
                                                <?php echo isset($sub_field['placeholder']) ? 'placeholder="' . esc_attr($sub_field['placeholder']) . '"' : ''; ?>>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'textarea':
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <textarea name="<?php echo esc_attr($sub_field_name); ?>" 
                                                    id="<?php echo esc_attr($sub_field_id); ?>" 
                                                    rows="<?php echo isset($sub_field['rows']) ? intval($sub_field['rows']) : 3; ?>"
                                                    class="large-text"></textarea>
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        break;
                                    case 'image':
                                    case 'upload':
                                        $sub_preview_size = isset($sub_field['preview_size']) ? $sub_field['preview_size'] : 'thumbnail';
                                        $sub_button_text = isset($sub_field['button_text']) ? $sub_field['button_text'] : __('Select Image', 'starfish');
                                        $sub_show_preview = isset($sub_field['preview']) && $sub_field['preview'] === true;
                                        ?>
                                        <div class="starfish-image-wrapper starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <div style="display: inline-block;">
                                                <?php if ($sub_show_preview): ?>
                                                    <div class="starfish-image-preview">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="starfish-image-action">
                                                    <input type="text" 
                                                        name="<?php echo esc_attr($sub_field_name); ?>" 
                                                        id="<?php echo esc_attr($sub_field_id); ?>" 
                                                        value=""
                                                        class="starfish-image-url"
                                                        placeholder="<?php echo isset($sub_field['placeholder']) ? esc_attr($sub_field['placeholder']) : __('Image URL', 'starfish'); ?>">
                                                    <button type="button" class="button starfish-image-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>">
                                                        <?php echo esc_html($sub_button_text); ?>
                                                    </button>
                                                    <button type="button" class="button starfish-remove-button" data-field-id="<?php echo esc_attr($sub_field_id); ?>" style="display: none;">
                                                        <?php _e('Remove', 'starfish'); ?>
                                                    </button>
                                                </div>
                                                <?php if (!empty($sub_field['desc'])): ?>
                                                    <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php
                                        break;
                                    default:
                                        ?>
                                        <div class="starfish-sub-field">
                                            <?php if (!empty($sub_field['title'])): ?>
                                                <label><?php echo esc_html($sub_field['title']); ?></label>
                                            <?php endif; ?>
                                            <input type="text" 
                                                name="<?php echo esc_attr($sub_field_name); ?>" 
                                                id="<?php echo esc_attr($sub_field_id); ?>" 
                                                value=""
                                                class="regular-text">
                                            <?php if (!empty($sub_field['desc'])): ?>
                                                <p class="description"><?php echo esc_html($sub_field['desc']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                }
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </script>
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
