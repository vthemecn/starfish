# StarFish - WordPress 配置框架

一个轻量级的 WordPress 选项框架插件，通过配置化的方式，快速为 WordPress 主题或插件生成后台设置页面。

## ✨ 特性

- 🎯 **配置驱动 UI**：只需定义数组配置，自动生成完整的表单界面
- 📱 **多页面架构**：支持多个独立的设置页面
- 🎨 **丰富的字段类型**：包含 15+ 种常用字段类型
- 🔗 **字段依赖系统**：实现字段间的联动效果
- ✅ **数据验证与清理**：自动进行安全清理，防止 XSS 攻击
- 🚀 **零依赖**：使用原生 JavaScript，无需 jQuery、Vue 或 React
- 💪 **WordPress 标准**：遵循 WordPress 编码规范，使用 Settings API

## 📦 安装

### 方法 1: 直接上传（推荐）

1. 将整个 `starfish` 文件夹上传到 WordPress 的插件目录：
   ```
   /wp-content/plugins/starfish/
   ```

2. 登录 WordPress 后台，进入"插件"页面

3. 找到"StarFish"，点击"激活"

4. 在左侧菜单中会出现"我的插件设置"，点击即可开始配置

### 方法 2: 在主题中使用

如果您想在主题中使用此框架（而不是作为独立插件）：

1. 将以下文件复制到您的主题目录：
   - `starfish.php`
   - `style.css`
   - `index.js`

2. 在主题的 `functions.php` 中添加：
    ```php
    require_once get_template_directory() . '/starfish.php';

    // 然后定义您的配置数组
    $config = array(
        'menu_title' => '主题设置',
        'pages' => array(
            // ... 您的配置
        )
    );

    $starFish = new StarFish();
    $starFish->init($config);
    ```

## 🚀 快速开始

### 基本配置

在 `config.php` 中定义您的配置：

```php
$config = array(
    'menu_title' => '我的插件设置',
    'menu_icon' => 'dashicons-admin-generic',
    'pages' => array(
        array(
            'id' => 'general_page',
            'title' => '常规设置',
            'fields' => array(
                // 在这里定义字段
            )
        ),
        array(
            'id' => 'advanced_page',
            'title' => '高级设置',
            'fields' => array(
                // 在这里定义字段
            )
        )
    )
);
```
菜单图标所使用的类，在 https://developer.wordpress.org/resource/dashicons 选择。

### 获取选项值

```php
// 获取单个选项，参数可以通过 $config['option_name'] 设置
$value = get_option('starfish_config');
```

## 📝 字段类型

### 基础字段

#### 文本输入 (text)
```php
array(
    'id' => 'site_title',
    'type' => 'text',
    'title' => '网站标题',
    'desc' => '请输入网站标题',
    'default' => '我的网站',
    'placeholder' => '输入标题',
)
```

#### 文本域 (textarea)
```php
array(
    'id' => 'description',
    'type' => 'textarea',
    'title' => '描述',
    'rows' => 5,
    'default' => '',
    'sanitize' => false // 默认是 true，关闭以后不进行过滤
)
```

#### 数字输入 (number)
```php
array(
    'id' => 'items_per_page',
    'type' => 'number',
    'title' => '每页数量',
    'min' => 1,
    'max' => 100,
    'step' => 1,
    'default' => 10,
)
```

#### 下拉选择 (select)
```php
array(
    'id' => 'layout_mode',
    'type' => 'select',
    'title' => '布局模式',
    'options' => array(
        'default' => '默认',
        'boxed' => '盒装',
        'wide' => '宽屏',
    ),
    'default' => 'default',
    'multiple' => false, // 是否多选
)
```
额外属性 query_args:
```php
'query_args' => 'pages', // 获取所有页面
'query_args' => 'posts', // 获取所有文章
'query_args' => 'categories', // 获取所有分类
'query_args' => array('taxonomy' => 'post_tag'), // 获取标签
'query_args' => array('taxonomy' => 'custom_taxonomy') // 获取自定义分类法 
```

#### 单选框 (radio)
```php
array(
    'id' => 'show_sidebar',
    'type' => 'radio',
    'title' => '显示侧边栏',
    'inline' => true, // 是否横向排列
    'options' => array(
        'yes' => '是',
        'no' => '否',
    ),
    'default' => 'yes',
)
```

#### 复选框 (checkbox)
```php
// 单个复选框
array(
    'id' => 'enable_feature',
    'type' => 'checkbox',
    'title' => '启用功能',
    'checkbox_label' => '开启此功能',
    'default' => '',
)

// 多个复选框
array(
    'id' => 'features',
    'type' => 'checkbox',
    'title' => '功能选择',
    'inline' => true,
    'options' => array(
        'feature1' => '功能1',
        'feature2' => '功能2',
        'feature3' => '功能3',
    ),
    'default' => array('feature1'),
)
```

#### 开关切换器 (switcher)
```php
array(
    'id' => 'maintenance_mode',
    'type' => 'switcher',
    'title' => '维护模式',
    'desc' => '开启后网站将显示维护页面',
    'default' => '',
)
```

#### 滑块 (slider)
```php
array(
    'id' => 'opacity',
    'type' => 'slider',
    'title' => '透明度',
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'unit' => '%',
    'default' => 100,
)
```

### 高级字段

#### 颜色选择器 (color)
```php
array(
    'id' => 'primary_color',
    'type' => 'color',
    'title' => '主题色',
    'default' => '#0073aa',
)
```

#### 文件上传 (upload)
```php
array(
    'id' => 'background_file',
    'type' => 'upload',
    'title' => '背景文件',
    'button_text' => '选择文件',
    'default' => '',
)
```

#### 图片选择 (image)
```php
array(
    'id' => 'logo_image',
    'type' => 'image',
    'title' => 'Logo',
    'button_text' => '选择图片',
    'preview_size' => 'medium', // thumbnail, medium, large
    'default' => '',
)
```

#### 画廊 (gallery)
```php
array(
    'id' => 'gallery_images',
    'type' => 'gallery',
    'title' => '图片画廊',
    'button_text' => '管理画廊',
    'default' => array(),
)
```

### 复杂字段

#### 群组 (group)
```php
array(
    'id' => 'team_members',
    'type' => 'group',
    'title' => '团队成员',
    'button_title' => '添加成员',
    'fields' => array(
        array(
            'id' => 'name',
            'type' => 'text',
            'title' => '姓名',
        ),
        array(
            'id' => 'position',
            'type' => 'text',
            'title' => '职位',
        ),
    ),
    'default' => array(),
)
```

#### 排序器 (sorter)
```php
array(
    'id' => 'module_order',
    'type' => 'sorter',
    'title' => '模块排序',
    'desc' => '拖拽调整顺序',
    'options' => array(
        'header' => '页头',
        'content' => '内容',
        'sidebar' => '侧边栏',
        'footer' => '页脚',
    ),
    'default' => array('header', 'content', 'sidebar', 'footer'),
)
```

## 🔗 字段依赖

实现字段间的联动效果，支持多种运算符：

### 基本用法（等于判断）

```php
array(
    'id' => 'logo_image',
    'type' => 'image',
    'title' => 'Logo',
    'dependency' => array('maintenance_mode', '==', ''),
)
```

### 支持的运算符

- `==` ：等于
- `!=` ：不等于
- `>` ：大于
- `<` ：小于
- `>=` ：大于等于
- `<=` ：小于等于

### 使用示例

```php
// 当 seo_enabled 等于 1 时显示
array(
    'id' => 'meta_keywords',
    'type' => 'textarea',
    'title' => '关键词',
    'dependency' => array('seo_enabled', '==', '1'),
)

// 当 items_per_page 大于 10 时显示
array(
    'id' => 'advanced_option',
    'type' => 'text',
    'title' => '高级选项',
    'dependency' => array('items_per_page', '>', '10'),
)

// 当 layout_mode 不等于 'default' 时显示
array(
    'id' => 'custom_layout',
    'type' => 'text',
    'title' => '自定义布局',
    'dependency' => array('layout_mode', '!=', 'default'),
)

// 当 cache_expiration 小于等于 12 时显示
array(
    'id' => 'quick_cache',
    'type' => 'checkbox',
    'title' => '快速缓存',
    'dependency' => array('cache_expiration', '<=', '12'),
)
```

**注意**：比较时不进行类型转换，使用 JavaScript 的默认比较行为。


## 自定义类型

可以在配置中添加自定义子类型的配置：

```php
array(
    'id' => 'location_time',
    'type' => 'custom_time_input',
    'title' => '自定义时间与位置',
    'desc' => '你可以手动修改时间，系统会自动验证格式并保存。',
    'sanitize_callback' => 'save_location_time_data' // 绑定保存函数
)
```

渲染逻辑：

```php
function handle_starfish_custom_field($field, $field_name, $field_id, $value) {
    // 根据字段ID
    if ($field['id'] !== 'location_time') {
        return;
    }

    // 根据字段类型
    // if ($field['type'] !== 'custom_time_input') {
    //     return;
    // }
    $value = current_time('Y-m-d H:i:s');
    $input_id = esc_attr($field_id) . '_input';
    ?>
    <div class="starfish-custom-wrapper" style="">
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

        <p id="<?php echo $input_id; ?>_status" style="margin-top:5px; font-size:12px;"></p>

        <script>
        (function() {
            ...
        })();
        </script>
    </div>
    <?php
}
add_action('starfish_render_custom_field', 'handle_starfish_custom_field', 10, 5);
```

保存逻辑：

```php
function save_location_time_data($raw_input) {
    $raw_input = trim($raw_input);
    $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $raw_input);
    
    if ($datetime && $datetime->format('Y-m-d H:i:s') === $raw_input) {
        return $raw_input + 'hello'; 
    } else {
        return ''; 
    }
}
```

## 🛡️ 数据验证与清理

### 自定义清理回调

```php
array(
    'id' => 'custom_field',
    'type' => 'text',
    'title' => '自定义字段',
    'sanitize_callback' => function($value) {
        // 自定义清理逻辑
        return sanitize_text_field($value);
    },
)
```

### 内置清理规则

框架会根据字段类型自动应用相应的清理函数：

- `text` / `textarea`: `sanitize_text_field()`
- `number`: `intval()`
- `email`: `sanitize_email()`
- `url`: `esc_url_raw()`
- `color`: `sanitize_hex_color()`
- `checkbox` / `switcher`: 转换为 '1' 或 ''

## 📂 文件结构

```
starfish/
├── config.php       # 配置文件
├── index.js         # JavaScript 交互逻辑
├── index.php        # 插件定义
├── README.md        # 说明文档
├── starfish.php     # 主插件文件
└── style.css        # 样式文件
```

## ⚠️ 注意事项

1. **CSS 规范**：严禁使用 `gap` 属性，所有间距通过 `margin` 和 `padding` 实现
2. **JavaScript**：使用原生 JS，不依赖 jQuery、Vue 或 React
3. **安全性**：所有输出都经过转义处理，输入都经过清理
4. **兼容性**：需要 WordPress 4.0 或更高版本

## 📖 使用示例

查看 `setup.php` 文件获取更多使用示例。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

GPL v2 or later

## 👨‍💻 作者

vthemecn
