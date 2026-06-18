<?php
/**
 * StarFish Backup Field Renderer
 * 
 * @package StarFish
 */

defined('ABSPATH') or exit;

if (!class_exists('StarFish_BackupField')) {
    class StarFish_BackupField {
        
        /**
         * 渲染备份字段
         */
        public static function render($field, $option_name, $starfish_instance) {
            $title = isset($field['title']) ? $field['title'] : __('Data Backup & Restore', 'starfish');
            $desc = isset($field['desc']) ? $field['desc'] : __('You can export the current settings as a JSON file for backup, or restore settings from a backup file.', 'starfish');
            
            // 获取全局选项名称并读取所有数据（扁平结构）
            $global_option_name = $starfish_instance->get_option_name_public();
            $all_options = get_option($global_option_name, array());
            
            $backup_data = json_encode($all_options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            ?>

            <div class="starfish-backup-wrapper">
                <!-- 导出部分 -->
                <div class="starfish-backup-section starfish-backup-export">
                    <h4><?php _e('Export Data', 'starfish'); ?></h4>
                    <p><?php _e('Export all current settings as a JSON file for backup and migration.', 'starfish'); ?></p>
                    <button type="button" class="button button-primary starfish-export-button" id="starfish-export-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Settings', 'starfish'); ?>
                    </button>
                    
                    <!-- 隐藏的数据域 -->
                    <textarea id="starfish-backup-data" style="display:none;"><?php echo esc_textarea($backup_data); ?></textarea>
                </div>
                
                <!-- 导入部分 -->
                <div class="starfish-backup-section starfish-backup-import">
                    <h4><?php _e('Import Data', 'starfish'); ?></h4>
                    <p><?php _e('Restore settings from a previously exported JSON file. Note: This will overwrite all current settings!', 'starfish'); ?></p>
                    
                    <div class="starfish-import-form">
                        <input type="file" 
                                name="starfish_import_file" 
                                id="starfish-import-file" 
                                accept=".json" 
                                class="starfish-import-input">
                        
                        <button type="button" 
                                class="button button-secondary starfish-import-button" 
                                id="starfish-import-btn">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Import Settings', 'starfish'); ?>
                        </button>
                        
                        <button type="button" 
                                class="button button-secondary starfish-reset-button" 
                                id="starfish-reset-btn"
                                style="margin-left: 10px;">
                            <span class="dashicons dashicons-undo"></span>
                            <?php _e('Reset Settings', 'starfish'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
