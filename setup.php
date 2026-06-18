<?php
/**
 * StarFish 初始化配置文件
 * 在此文件中定义你的配置选项
 *
 * @author vthemecn <mail@vtheme.cn>
 * @link https://vtheme.cn
 */

if (!defined('ABSPATH')) {
    exit;
}

// 引入核心类
require_once plugin_dir_path(__FILE__) . 'starfish.php';


/**
 * 配置数组定义
 */
$config = array(
    'menu_title' => 'StarFish 演示',
    'menu_icon' => 'dashicons-star-filled',
    'menu_position' => 20,
    'option_name' => 'starfish_config',
    'pages' => array(
        array(
            'id' => 'general_page',
            'title' => '常规设置',
            'fields' => array(
                array(
                    'id' => 'site_title',
                    'type' => 'text',
                    'title' => '网站标题',
                    'desc' => '请输入您的网站标题',
                    'default' => '我的网站',
                    'placeholder' => '输入网站标题',
                ),
                array(
                    'id' => 'site_description',
                    'type' => 'textarea',
                    'title' => '网站描述',
                    'desc' => '网站的简短描述',
                    'rows' => 3,
                    'default' => '渐酒空金榼健康',
                ),
                array(
                    'id' => 'items_per_page',
                    'type' => 'number',
                    'title' => '每页显示数量',
                    'desc' => '设置每页显示的项目数量',
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
                    'default' => 10,
                ),
                array(
                    'id' => 'layout_mode',
                    'type' => 'select',
                    'title' => '布局模式',
                    'desc' => '选择网站的整体布局',
                    'options' => array(
                        'default' => '默认布局',
                        'boxed' => '盒装布局',
                        'wide' => '宽屏布局',
                        'fluid' => '流式布局',
                    ),
                    'default' => 'default',
                ),
                array(
                    'id' => 'my_category',
                    'type' => 'select',
                    'title' => '分类选择',
                    'desc' => '可以多选',
                    'options' => array(),
                    'default' => 'default',
                    'multiple' => true,
                    'query_args' => 'categories'
                ),
                array(
                    'id' => 'show_sidebar',
                    'type' => 'radio',
                    'title' => '侧边栏显示',
                    'desc' => '选择是否显示侧边栏',
                    'inline' => true,
                    'options' => array(
                        'yes' => '显示',
                        'no' => '隐藏',
                    ),
                    'default' => 'yes',
                ),
                array(
                    'id' => 'enable_comments',
                    'type' => 'checkbox',
                    'title' => '评论功能',
                    'desc' => '启用或禁用评论功能',
                    'options' => array(
                        'posts' => '文章评论',
                        'pages' => '页面评论',
                        'media' => '媒体评论',
                    ),
                    'inline' => true,
                    'default' => array('posts'),
                ),
                array(
                    'id' => 'maintenance_mode',
                    'type' => 'switcher',
                    'title' => '维护模式',
                    'desc' => '开启后网站将显示维护页面',
                    'default' => '',
                ),
                array(
                    'id' => 'logo_opacity',
                    'type' => 'slider',
                    'title' => 'Logo 透明度',
                    'desc' => '调整 Logo 的透明度',
                    'min' => 0,
                    'max' => 100,
                    'step' => 1,
                    'unit' => '%',
                    'default' => 100,
                ),
                array(
                    'id' => 'primary_color',
                    'type' => 'color',
                    'title' => '主题色',
                    'desc' => '选择网站的主要颜色',
                    'default' => '#0073aa',
                ),
                array(
                    'id' => 'background_image',
                    'type' => 'upload',
                    'title' => '背景图片',
                    'desc' => '上传网站背景图片',
                    'button_text' => '选择背景图',
                    'default' => ''
                ),
                array(
                    'id' => 'logo_image',
                    'type' => 'image',
                    'title' => '网站 Logo11',
                    'desc' => '上传网站 Logo 图片',
                    'button_text' => '选择 Logo',
                    'preview' => true,
                    'default' => ''
                ),
                array(
                    'id' => 'gallery_images',
                    'type' => 'gallery',
                    'title' => '图片画廊',
                    'desc' => '选择多张图片创建画廊',
                    'button_text' => '管理画廊',
                    'default' => array(),
                ),
                array(
                    'id' => 'team_members',
                    'type' => 'group',
                    'title' => '团队成员',
                    'desc' => '添加团队成员信息',
                    'button_title' => '添加成员',
                    'fields' => array(
                        array(
                            'id' => 'image',
                            'type' => 'image',
                            'title' => '照片',
                            'desc' => '上传用户照片',
                            'button_text' => '上传',
                            'preview' => true,
                            'default' => ''
                        ),
                        array(
                            'id' => 'name',
                            'type' => 'text',
                            'title' => '姓名',
                            'placeholder' => '输入姓名',
                        ),
                        array(
                            'id' => 'position',
                            'type' => 'text',
                            'title' => '职位',
                            'placeholder' => '输入职位',
                        ),
                        array(
                            'id' => 'members-text',
                            'type' => 'group',
                            'title' => '子group测试',
                            'button_title' => '添加子项',
                            'fields' => array(
                                array(
                                    'id' => 'name1',
                                    'type' => 'text',
                                    'title' => '姓名1',
                                ),
                                array(
                                    'id' => 'name2',
                                    'type' => 'text',
                                    'title' => '姓名2'
                                ),
                            )
                        )
                    ),
                    'default' => array(),
                ),
                array(
                    'id' => 'module_order',
                    'type' => 'sorter',
                    'title' => '模块排序',
                    'desc' => '拖拽调整模块显示顺序，可将模块在已启用和已禁用之间移动',
                    'enabled_title' => '已启用模块',
                    'disabled_title' => '已禁用模块',
                    'options' => array(
                        'header' => '页头',
                        'hero' => '首屏大图',
                        'content' => '主要内容',
                        'sidebar' => '侧边栏',
                        'footer' => '页脚',
                    ),
                    'default' => array('header', 'content', 'footer'),
                ),
                // SEO 设置字段
                array(
                    'id' => 'seo_enabled',
                    'type' => 'switcher',
                    'title' => '启用 SEO',
                    'desc' => '开启 SEO 优化功能',
                    'default' => '1',
                ),
                array(
                    'id' => 'meta_keywords',
                    'type' => 'textarea',
                    'title' => '关键词',
                    'desc' => '网站的 meta 关键词，用逗号分隔',
                    'rows' => 2,
                    'default' => '',
                    'dependency' => array('seo_enabled', '==', '1'),
                ),
                array(
                    'id' => 'meta_description',
                    'type' => 'textarea',
                    'title' => '描述',
                    'desc' => '网站的 meta 描述',
                    'rows' => 3,
                    'default' => '',
                    'dependency' => array('seo_enabled', '==', '1'),
                ),
                array(
                    'id' => 'google_analytics',
                    'type' => 'textarea',
                    'title' => 'Google Analytics 代码',
                    'desc' => '粘贴 Google Analytics 跟踪代码',
                    'rows' => 5,
                    'default' => '',
                    'sanitize' => false
                ),
            ),
        ),
        array(
            'id' => 'advanced_page',
            'title' => '高级设置',
            'fields' => array(
                // 性能设置字段
                array(
                    'id' => 'enable_cache',
                    'type' => 'switcher',
                    'title' => '启用缓存',
                    'desc' => '开启页面缓存以提高性能',
                    'default' => '',
                ),
                array(
                    'id' => 'cache_expiration',
                    'type' => 'number',
                    'title' => '缓存过期时间',
                    'desc' => '设置缓存过期时间（小时）',
                    'min' => 1,
                    'max' => 72,
                    'step' => 1,
                    'default' => 24,
                    'dependency' => array('enable_cache', '==', '1'),
                ),
                array(
                    'id' => 'minify_css',
                    'type' => 'checkbox',
                    'title' => '代码优化',
                    'desc' => '选择需要优化的资源',
                    'options' => array(
                        'css' => '压缩 CSS',
                        'js' => '压缩 JavaScript',
                        'html' => '压缩 HTML',
                    ),
                    'inline' => true,
                    'default' => array('css'),
                ),
                // 安全设置字段
                array(
                    'id' => 'enable_firewall',
                    'type' => 'switcher',
                    'title' => '防火墙',
                    'desc' => '启用网站防火墙',
                    'default' => '',
                ),
                // 自定义字段配置
                array(
                    'id' => 'location_time',
                    'type' => 'custom_time_input',
                    'title' => '自定义时间与位置',
                    'desc' => '你可以手动修改时间，系统会自动验证格式并保存。',
                    'sanitize_callback' => 'save_location_time_data'
                ),

            ),
        ),
        array(
            'id' => 'page_test',
            'title' => '子页面设置',
            'fields' => array(
                array(
                    'id' => 'site_title1',
                    'type' => 'text',
                    'title' => '标题1',
                ),
                array(
                    'id' => 'site_title2',
                    'type' => 'text',
                    'title' => '标题1',
                ),
            )
        ),
        array(
            'parent' => 'page_test',
            'title' => '子页面设置1',
            'fields' => array(
                array(
                    'id' => 'advanced_page_11',
                    'type' => 'text',
                    'title' => '测试a1',
                ),
                array(
                    'id' => 'advanced_page_12',
                    'type' => 'text',
                    'title' => '测试a2',
                ),
            )
        ),
        array(
            'parent' => 'page_test',
            'title' => '子页面设置2',
            'fields' => array(
                array(
                    'id' => 'advanced_page_21',
                    'type' => 'text',
                    'title' => '测试b1',
                ),
                array(
                    'id' => 'advanced_page_22',
                    'type' => 'text',
                    'title' => '测试b2',
                ),
            )
        ),
        array(
            'id' => 'backup_page',
            'title' => '备份与还原',
            'fields' => array(
                array(
                    'id' => 'backup_section',
                    'type' => 'backup',
                    'title' => '数据备份与还原',
                    'desc' => '您可以导出当前设置为JSON文件进行备份，或从备份文件还原设置',
                ),
            ),
        ),
    ),
);


/**
 * 初始化 StarFish
 */
add_action('init', function() use ($config) {
    /**
     * 1. 添加设置面板
     */
    $starFish = new StarFish();
    $starFish->init($config);


    /**
     * 2. 添加另外的设置面板
     */
    $another_config = array(
        'menu_title' => 'StarFish 演示2',
        'menu_icon' => 'dashicons-star-half',
        'menu_position' => 20,
        'option_name' => 'starfish_config_1',
        'pages' => array(
            array(
                'id' => 'general_page1',
                'title' => '常规设置1',
                'fields' => array(
                    array(
                        'id' => 'site_title_01',
                        'type' => 'text',
                        'title' => '标题1',
                        'desc' => '请输入您的标题',
                        'default' => '我的标题1',
                        'placeholder' => '输入标题',
                    ),
                )
            ),
            array(
                'id' => 'general_page2',
                'title' => '常规设置2',
                'fields' => array(
                    array(
                        'id' => 'site_title_02',
                        'type' => 'text',
                        'title' => '标题2',
                        'desc' => '请输入您的标题',
                        'default' => '我的标题2',
                        'placeholder' => '输入标题',
                    ),
                )
            ),
        )
    );

    // 每个设置面板都需要单独实例化一个类
    $anotherStarFish = new StarFish();
    $anotherStarFish->init($another_config);
});


/**
 * 自定义字段 - 渲染逻辑
 */
function handle_starfish_custom_field($field, $field_name, $field_id, $value) {
    // 根据字段ID
    // if ($field['id'] !== 'location_time') {
    //     return;
    // }

    // 根据字段类型
    if ($field['type'] !== 'custom_time_input') {
        return;
    }

    // 如果数据库没值，给个默认值方便演示
    if (empty($value)) {
        $value = current_time('Y-m-d H:i:s');
    }

    // 生成一个唯一的 ID 用于 JS 操作
    $input_id = esc_attr($field_id) . '_input';
    ?>
    <div class="starfish-custom-wrapper" style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;">
        
        <!-- 文本输入框 -->
        <label for="<?php echo $input_id; ?>" style="display:block; margin-bottom:5px; font-weight:bold;">
            ⏱️ 设定时间 (格式: YYYY-MM-DD HH:MM:SS)
        </label>
        <input 
            type="text" 
            id="<?php echo $input_id; ?>" 
            name="<?php echo esc_attr($field_name); ?>" 
            value="<?php echo esc_attr($value); ?>" 
            class="regular-text"
            autocomplete="off"
        >
        
        <!-- 实时状态提示 -->
        <p id="<?php echo $input_id; ?>_status" style="margin-top:5px; font-size:12px;"></p>

        <!-- 简单的 JS 实时校验 -->
        <script>
        (function() {
            const input = document.getElementById('<?php echo $input_id; ?>');
            const status = document.getElementById('<?php echo $input_id; ?>_status');
            
            function validateTime() {
                const val = input.value.trim();
                // 简单的正则校验 (YYYY-MM-DD HH:MM:SS)
                const regex = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/;
                
                if (regex.test(val)) {
                    status.innerHTML = '<span style="color:green;">✅ 格式正确，将被保存</span>';
                    input.style.borderColor = '#008a20';
                } else {
                    status.innerHTML = '<span style="color:#d63638;">❌ 格式错误，请使用 YYYY-MM-DD HH:MM:SS</span>';
                    input.style.borderColor = '#d63638';
                }
            }

            // 页面加载时检查一次
            validateTime();
            // 监听输入事件
            input.addEventListener('input', validateTime);
        })();
        </script>
    </div>
    <?php
}
add_action('starfish_render_custom_field', 'handle_starfish_custom_field', 10, 5);


/**
 * 自定义字段 - 渲染逻辑
 */
function save_location_time_data($raw_input) {
    // 去除首尾空格
    $raw_input = trim($raw_input);
    
    // 尝试按 Y-m-d H:i:s 格式解析
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $raw_input);
    
    if ($datetime && $datetime->format('Y-m-d H:i:s') === $raw_input) {
        // 格式完全正确，返回标准格式存入数据库
        return $raw_input; 
    } else {
        // 格式错误，可以在这里记录日志或返回空字符串
        // add_settings_error('location_time', 'invalid_format', '时间格式不正确');
        return ''; 
    }
}