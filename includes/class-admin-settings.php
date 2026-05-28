<?php
/**
 * Admin Settings - 后台设置管理类
 *
 * 负责插件设置页面的注册、选项的存储与读取、用户输入的清洗与验证，
 * 以及设置页面的HTML渲染（包含四个选项卡：登录页面、后台样式、颜色方案、自定义代码）。
 *
 * @package Admin_Plus
 */

// 防止直接访问文件
defined('ABSPATH') || exit;

/**
 * Admin_Settings 类
 *
 * 管理插件的全部后台设置项，采用单例模式。
 * 设置数据存储在 WordPress 的 ap_settings 选项中。
 */
class Admin_Settings {

    /** @var self|null 单例实例 */
    private static $instance = null;

    /** @var string WordPress选项名称，所有设置项存储在此选项的关联数组中 */
    private $option_name = 'ap_settings';

    /** @var array 当前已保存的设置项缓存 */
    private $settings;

    /**
     * 获取单例实例
     *
     * @return self
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 私有构造函数
     *
     * 从数据库加载已保存的设置，并注册以下WordPress钩子：
     * - admin_menu:              添加"工具"菜单下的设置子页面
     * - admin_init:              注册设置项以便WordPress的Settings API处理
     * - admin_enqueue_scripts:   在设置页面加载颜色选择器和媒体上传器
     * - plugin_action_links:     在插件列表页添加"设置"快捷链接
     */
    private function __construct() {
        $this->settings = get_option($this->option_name, array());
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . AP_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * 获取单个设置项的值
     *
     * 从已缓存的设置数组中读取指定键的值，不存在时返回默认值。
     *
     * @param string $key    设置项键名
     * @param mixed  $default 默认值（默认为空字符串）
     * @return mixed 设置项的值或默认值
     */
    public function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * 在WordPress"工具"菜单下添加插件设置页面
     *
     * 使用 add_management_page() 将设置页挂载到"工具"菜单下，
     * 需要 manage_options 权限才能访问。
     */
    public function add_admin_menu() {
        add_management_page(
            __('Admin Plus 设置', 'admin-plus'),
            __('Admin Plus', 'admin-plus'),
            'manage_options',
            'admin-plus',
            array($this, 'render_settings_page')
        );
    }

    /**
     * 在插件列表页添加"设置"快捷链接
     *
     * 使用户可以直接从插件列表跳转到设置页面。
     *
     * @param array $links 已有的插件操作链接数组
     * @return array 在数组开头插入了"设置"链接后的数组
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('tools.php?page=admin-plus') . '">' . __('设置', 'admin-plus') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * 注册设置项到WordPress Settings API
     *
     * 注册 ap_settings 选项组，并指定 sanitize_settings() 作为数据清洗回调。
     */
    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'sanitize_settings'));
    }

    /**
     * 清洗并验证用户提交的设置数据
     *
     * 对每个设置项进行类型验证和安全过滤：
     * - URL类型: 使用 esc_url_raw() 过滤
     * - 颜色值: 使用 sanitize_hex_color_safe() 验证十六进制颜色格式
     * - 枚举类型: 使用白名单验证（如表单样式、布局模式、颜色方案）
     * - HTML文本: 使用 wp_kses_post() 过滤危险标签
     * - CSS代码: 使用 sanitize_custom_css() 移除可能的XSS攻击向量
     * - 字体族: 使用预定义白名单验证
     *
     * @param array $input 用户提交的原始输入数据
     * @return array 清洗后的安全数据
     */
    public function sanitize_settings($input) {
        // 获取当前已保存的设置，作为清洗结果的基础
        $existing = get_option($this->option_name, array());
        $sanitized = $existing;

        // 登录页Logo URL
        if (isset($input['login_logo'])) {
            $sanitized['login_logo'] = esc_url_raw($input['login_logo']);
        }
        // Logo宽度，取绝对整数值
        if (isset($input['login_logo_width'])) {
            $sanitized['login_logo_width'] = absint($input['login_logo_width']);
        }
        // 登录页背景颜色
        if (isset($input['login_bg_color'])) {
            $sanitized['login_bg_color'] = $this->sanitize_hex_color_safe($input['login_bg_color']);
        }
        // 登录页背景图片URL
        if (isset($input['login_bg_image'])) {
            $sanitized['login_bg_image'] = esc_url_raw($input['login_bg_image']);
        }
        // 登录表单样式，白名单验证
        if (isset($input['login_form_style'])) {
            $sanitized['login_form_style'] = in_array($input['login_form_style'], array('modern', 'classic', 'glass')) ? $input['login_form_style'] : 'modern';
        }
        // 后台布局模式，白名单验证
        if (isset($input['admin_layout'])) {
            $sanitized['admin_layout'] = in_array($input['admin_layout'], array('default', 'discuz')) ? $input['admin_layout'] : 'default';
        }
        // 后台页脚文字，允许安全的HTML标签
        if (isset($input['admin_footer_text'])) {
            $sanitized['admin_footer_text'] = wp_kses_post($input['admin_footer_text']);
        }
        // 颜色方案，白名单验证（从Color_Schemes获取所有可用方案）
        if (isset($input['color_scheme'])) {
            $sanitized['color_scheme'] = in_array($input['color_scheme'], array_keys(Color_Schemes::get_schemes())) ? $input['color_scheme'] : 'default';
        }
        // 登录页自定义CSS
        if (isset($input['login_custom_css'])) {
            $sanitized['login_custom_css'] = $this->sanitize_custom_css($input['login_custom_css']);
        }
        // 后台自定义CSS
        if (isset($input['admin_custom_css'])) {
            $sanitized['admin_custom_css'] = $this->sanitize_custom_css($input['admin_custom_css']);
        }
        // 后台字体族
        if (isset($input['admin_font_family'])) {
            $sanitized['admin_font_family'] = $this->sanitize_font_family($input['admin_font_family']);
        }

        // 复选框处理：移除工具栏Logo（未勾选时不会出现在$input中）
        if (isset($input['admin_layout'])) {
            $sanitized['remove_admin_bar_logo'] = isset($input['remove_admin_bar_logo']) ? 1 : 0;
        }

        // 自定义颜色值的清洗
        if (isset($input['custom_primary'])) {
            $sanitized['custom_primary'] = $this->sanitize_hex_color_safe($input['custom_primary']);
        }
        if (isset($input['custom_menu_bg'])) {
            $sanitized['custom_menu_bg'] = $this->sanitize_hex_color_safe($input['custom_menu_bg']);
        }
        if (isset($input['custom_highlight'])) {
            $sanitized['custom_highlight'] = $this->sanitize_hex_color_safe($input['custom_highlight']);
        }
        if (isset($input['custom_link'])) {
            $sanitized['custom_link'] = $this->sanitize_hex_color_safe($input['custom_link']);
        }
        if (isset($input['custom_button'])) {
            $sanitized['custom_button'] = $this->sanitize_hex_color_safe($input['custom_button']);
        }

        if (isset($input['gravatar_mirror'])) {
            $sanitized['gravatar_mirror'] = $this->sanitize_gravatar_mirror($input['gravatar_mirror']);
        }
        if (isset($input['gravatar_mirror_custom'])) {
            $sanitized['gravatar_mirror_custom'] = $this->sanitize_gravatar_mirror_custom($input['gravatar_mirror_custom']);
        }

        return $sanitized;
    }

    /**
     * 安全清洗十六进制颜色值
     *
     * 验证颜色值是否为合法的十六进制格式（如 #3858e9），
     * 不合法时返回默认灰色 #f0f0f1 以防止CSS注入。
     *
     * @param string $color 用户输入的颜色值
     * @return string 合法的十六进制颜色值
     */
    private function sanitize_hex_color_safe($color) {
        if (empty($color)) {
            return '#f0f0f1';
        }
        $sanitized = sanitize_hex_color($color);
        if (null === $sanitized) {
            return '#f0f0f1';
        }
        return $sanitized;
    }

    /**
     * 清洗自定义CSS代码
     *
     * 移除所有HTML标签和可能的 </style> 闭合标签，
     * 防止用户通过自定义CSS注入恶意HTML/JavaScript。
     *
     * @param string $css 用户输入的CSS代码
     * @return string 清洗后的纯CSS代码
     */
    private function sanitize_custom_css($css) {
        $css = wp_strip_all_tags($css);
        $css = str_replace('</style>', '', $css);
        $css = str_replace('</STYLE>', '', $css);
        return $css;
    }

    /**
     * 清洗字体族名称
     *
     * 使用预定义白名单验证字体族值，防止注入非法CSS值。
     * 白名单包含常见的系统字体和Web字体组合。
     *
     * @param string $font_family 用户输入的字体族名称
     * @return string 白名单内的字体族名称，或空字符串
     */
    private function sanitize_font_family($font_family) {
        $allowed = array(
            '',                                              // 系统默认
            'system-ui',                                     // 系统UI字体
            '-apple-system,BlinkMacSystemFont',              // 苹果字体栈
            "'Segoe UI',Roboto,Oxygen-Sans",                 // Windows/Android字体栈
            "'Noto Sans SC','Microsoft YaHei'",               // 中文字体栈
            "'Inter',sans-serif",                            // Inter字体
        );
        if (in_array($font_family, $allowed, true)) {
            return $font_family;
        }
        return '';
    }

    private function sanitize_gravatar_mirror($mirror) {
        $allowed = array(
            '',
            'cravatar.cn',
            'gravatar.loli.net',
            'cdn.v2ex.com/gravatar',
            'gravatar.com',
            'custom',
        );
        return in_array($mirror, $allowed, true) ? $mirror : '';
    }

    private function sanitize_gravatar_mirror_custom($host) {
        if (empty($host)) {
            return '';
        }
        $host = sanitize_text_field($host);
        $host = preg_replace('#^https?://#i', '', $host);
        $host = preg_replace('#/+$#', '', $host);
        if (preg_match('#^[a-zA-Z0-9]([a-zA-Z0-9\-\.]*[a-zA-Z0-9])?(/[^/]+)*$#', $host)) {
            return $host;
        }
        return '';
    }

    /**
     * 在设置页面加载所需的资源文件
     *
     * 仅在插件设置页面（tools_page_admin-plus）加载：
     * - wp-color-picker: WordPress内置颜色选择器
     * - wp-media-upload: WordPress媒体上传器
     * - admin.js: 设置页面的交互逻辑（颜色选择器初始化、图片上传等）
     * - admin.css: 设置页面的样式
     *
     * @param string $hook 当前页面的钩子标识符
     */
    public function enqueue_admin_assets($hook) {
        // 仅在插件设置页面加载资源，避免影响其他后台页面
        if ('tools_page_admin-plus' !== $hook) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'ap-admin-settings',
            AP_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker', 'wp-i18n'),
            AP_VERSION,
            true
        );
        wp_set_script_translations('ap-admin-settings', 'admin-plus', AP_PLUGIN_DIR . 'languages');
        wp_enqueue_style(
            'ap-admin-settings',
            AP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AP_VERSION
        );
    }

    /**
     * 渲染插件设置页面的主框架
     *
     * 输出设置页面的HTML结构，包括：
     * - 页面标题
     * - 四个选项卡的导航栏（登录页面、后台样式、颜色方案、自定义代码）
     * - 表单容器和WordPress Settings API所需的隐藏字段
     * - 根据 URL 参数 tab 的值切换不同的选项卡内容
     * - 保存按钮
     */
    public function render_settings_page() {
        // 从URL参数获取当前活跃的选项卡，默认为 login
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'login';
        ?>
        <div class="wrap ap-settings-wrap">
            <h1><?php echo esc_html__('Admin Plus 设置', 'admin-plus'); ?></h1>

            <!-- 选项卡导航 -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=admin-plus&tab=login" class="nav-tab <?php echo $active_tab === 'login' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('登录页面', 'admin-plus'); ?>
                </a>
                <a href="?page=admin-plus&tab=admin" class="nav-tab <?php echo $active_tab === 'admin' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('后台样式', 'admin-plus'); ?>
                </a>
                <a href="?page=admin-plus&tab=colors" class="nav-tab <?php echo $active_tab === 'colors' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('颜色方案', 'admin-plus'); ?>
                </a>
                <a href="?page=admin-plus&tab=custom" class="nav-tab <?php echo $active_tab === 'custom' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('自定义代码', 'admin-plus'); ?>
                </a>
            </h2>

            <form method="post" action="options.php" class="ap-settings-form">
                <?php settings_fields($this->option_name); ?>

                <div class="ap-tab-content">
                    <?php
                    // 根据当前选项卡渲染对应的设置表单
                    switch ($active_tab) {
                        case 'login':
                            $this->render_login_tab();
                            break;
                        case 'admin':
                            $this->render_admin_tab();
                            break;
                        case 'colors':
                            $this->render_colors_tab();
                            break;
                        case 'custom':
                            $this->render_custom_tab();
                            break;
                    }
                    ?>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * 渲染"登录页面"选项卡
     *
     * 包含以下设置项：
     * - 登录页Logo（支持媒体库上传选择）
     * - Logo显示宽度
     * - 登录页背景颜色（颜色选择器）
     * - 登录页背景图片（支持媒体库上传选择）
     * - 登录表单样式（下拉选择：现代简洁/经典风格/毛玻璃效果（AI风格））
     */
    private function render_login_tab() {
        $logo = $this->get_setting('login_logo');
        $logo_width = $this->get_setting('login_logo_width', 280);
        $bg_color = $this->get_setting('login_bg_color', '#f0f0f1');
        $bg_image = $this->get_setting('login_bg_image');
        $form_style = $this->get_setting('login_form_style', 'modern');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="login_logo"><?php _e('登录页面Logo', 'admin-plus'); ?></label>
                </th>
                <td>
                    <div class="ap-image-uploader">
                        <input type="text" id="login_logo" name="ap_settings[login_logo]"
                               value="<?php echo esc_url($logo); ?>" class="regular-text ap-image-input" />
                        <button type="button" class="button ap-upload-btn">
                            <?php _e('选择图片', 'admin-plus'); ?>
                        </button>
                        <button type="button" class="button ap-remove-btn <?php echo empty($logo) ? 'hidden' : ''; ?>">
                            <?php _e('移除', 'admin-plus'); ?>
                        </button>
                    </div>
                    <p class="description"><?php _e('建议尺寸：宽度不超过300px的正方形或横向图片。留空则显示默认WordPress Logo。', 'admin-plus'); ?></p>
                    <?php if (!empty($logo)) : ?>
                        <div class="ap-preview">
                            <img src="<?php echo esc_url($logo); ?>" alt="Logo Preview" style="max-width:300px;max-height:100px;margin-top:10px;" />
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="login_logo_width"><?php _e('Logo宽度(px)', 'admin-plus'); ?></label>
                </th>
                <td>
                    <input type="number" id="login_logo_width" name="ap_settings[login_logo_width]"
                           value="<?php echo esc_attr($logo_width); ?>" class="small-text" min="100" max="600" />
                    <p class="description"><?php _e('设置Logo在登录页面的显示宽度（100-600px）', 'admin-plus'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="login_bg_color"><?php _e('登录页背景颜色', 'admin-plus'); ?></label>
                </th>
                <td>
                    <input type="text" id="login_bg_color" name="ap_settings[login_bg_color]"
                           value="<?php echo esc_attr($bg_color); ?>" class="ap-color-picker" data-default-color="#f0f0f1" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="login_bg_image"><?php _e('登录页背景图片', 'admin-plus'); ?></label>
                </th>
                <td>
                    <div class="ap-image-uploader">
                        <input type="text" id="login_bg_image" name="ap_settings[login_bg_image]"
                               value="<?php echo esc_url($bg_image); ?>" class="regular-text ap-image-input" />
                        <button type="button" class="button ap-upload-btn">
                            <?php _e('选择图片', 'admin-plus'); ?>
                        </button>
                        <button type="button" class="button ap-remove-btn <?php echo empty($bg_image) ? 'hidden' : ''; ?>">
                            <?php _e('移除', 'admin-plus'); ?>
                        </button>
                    </div>
                    <p class="description"><?php _e('建议使用1920x1080或更大尺寸的图片作为背景。背景颜色将在图片未加载时显示。', 'admin-plus'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="login_form_style"><?php _e('登录表单样式', 'admin-plus'); ?></label>
                </th>
                <td>
                    <select id="login_form_style" name="ap_settings[login_form_style]">
                        <option value="modern" <?php selected($form_style, 'modern'); ?>><?php _e('现代简洁', 'admin-plus'); ?></option>
                        <option value="classic" <?php selected($form_style, 'classic'); ?>><?php _e('经典风格', 'admin-plus'); ?></option>
                        <option value="glass" <?php selected($form_style, 'glass'); ?>><?php _e('毛玻璃效果（AI风格）', 'admin-plus'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * 渲染"后台样式"选项卡
     *
     * 包含以下设置项：
     * - 后台布局模式（默认WordPress布局 / Discuz风格横向菜单布局）
     * - 后台字体选择
     * - 后台页脚自定义文字（支持HTML）
     * - 是否移除顶部工具栏中的WordPress Logo
     */
    private function render_admin_tab() {
        $layout = $this->get_setting('admin_layout', 'default');
        $footer_text = $this->get_setting('admin_footer_text');
        $remove_logo = $this->get_setting('remove_admin_bar_logo', 0);
        $font_family = $this->get_setting('admin_font_family');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="admin_layout"><?php _e('后台布局', 'admin-plus'); ?></label>
                </th>
                <td>
                    <select id="admin_layout" name="ap_settings[admin_layout]">
                        <option value="default" <?php selected($layout, 'default'); ?>><?php _e('默认布局（WordPress原生）', 'admin-plus'); ?></option>
                        <option value="discuz" <?php selected($layout, 'discuz'); ?>><?php _e('Discuz风格布局', 'admin-plus'); ?></option>
                    </select>
                    <p class="description"><?php _e('Discuz风格：主菜单在顶部，子菜单在左侧，支持横向滚动和悬停延迟切换。', 'admin-plus'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="admin_font_family"><?php _e('后台字体', 'admin-plus'); ?></label>
                </th>
                <td>
                    <select id="admin_font_family" name="ap_settings[admin_font_family]">
                        <option value="" <?php selected($font_family, ''); ?>><?php _e('系统默认', 'admin-plus'); ?></option>
                        <option value="system-ui" <?php selected($font_family, 'system-ui'); ?>><?php _e('系统UI字体', 'admin-plus'); ?></option>
                        <option value="-apple-system,BlinkMacSystemFont" <?php selected($font_family, '-apple-system,BlinkMacSystemFont'); ?>><?php _e('苹果字体', 'admin-plus'); ?></option>
                        <option value="'Segoe UI',Roboto,Oxygen-Sans" <?php selected($font_family, "'Segoe UI',Roboto,Oxygen-Sans"); ?>><?php _e('Segoe UI', 'admin-plus'); ?></option>
                        <option value="'Noto Sans SC','Microsoft YaHei'" <?php selected($font_family, "'Noto Sans SC','Microsoft YaHei'"); ?>><?php _e('中文字体', 'admin-plus'); ?></option>
                        <option value="'Inter',sans-serif" <?php selected($font_family, "'Inter',sans-serif"); ?>><?php _e('Inter', 'admin-plus'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="admin_footer_text"><?php _e('后台页脚文字', 'admin-plus'); ?></label>
                </th>
                <td>
                    <textarea id="admin_footer_text" name="ap_settings[admin_footer_text]" rows="3" class="large-text"><?php echo esc_textarea($footer_text); ?></textarea>
                    <p class="description"><?php _e('自定义后台页面底部显示的文字，支持HTML标签。留空则显示默认信息。', 'admin-plus'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('顶部工具栏Logo', 'admin-plus'); ?>
                </th>
                <td>
                    <label for="remove_admin_bar_logo">
                        <input type="checkbox" id="remove_admin_bar_logo" name="ap_settings[remove_admin_bar_logo]"
                               value="1" <?php checked($remove_logo, 1); ?> />
                        <?php _e('移除顶部工具栏中的WordPress Logo', 'admin-plus'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="gravatar_mirror"><?php _e('Gravatar镜像源', 'admin-plus'); ?></label>
                </th>
                <td>
                    <select id="gravatar_mirror" name="ap_settings[gravatar_mirror]" class="ap-gravatar-mirror-select">
                        <option value="" <?php selected($this->get_setting('gravatar_mirror'), ''); ?>><?php _e('默认（Gravatar官方）', 'admin-plus'); ?></option>
                        <option value="cravatar.cn" <?php selected($this->get_setting('gravatar_mirror'), 'cravatar.cn'); ?>><?php _e('Cravatar（推荐国内使用）', 'admin-plus'); ?></option>
                        <option value="gravatar.loli.net" <?php selected($this->get_setting('gravatar_mirror'), 'gravatar.loli.net'); ?>>LOLI.net</option>
                        <option value="cdn.v2ex.com/gravatar" <?php selected($this->get_setting('gravatar_mirror'), 'cdn.v2ex.com/gravatar'); ?>>V2EX</option>
                        <option value="gravatar.com" <?php selected($this->get_setting('gravatar_mirror'), 'gravatar.com'); ?>><?php _e('Gravatar官方（gravatar.com）', 'admin-plus'); ?></option>
                        <option value="custom" <?php selected($this->get_setting('gravatar_mirror'), 'custom'); ?>><?php _e('自定义', 'admin-plus'); ?></option>
                    </select>
                    <div class="ap-gravatar-custom-wrap" <?php if ($this->get_setting('gravatar_mirror') !== 'custom') echo 'style="display:none;"'; ?>>
                        <p></p>
                        <input type="text" id="gravatar_mirror_custom" name="ap_settings[gravatar_mirror_custom]"
                               value="<?php echo esc_attr($this->get_setting('gravatar_mirror_custom')); ?>"
                               class="regular-text" placeholder="example.com/avatar" />
                        <p class="description"><?php _e('输入自定义镜像域名（含路径），如 example.com/avatar。将替换 Gravatar URL 中的域名部分。', 'admin-plus'); ?></p>
                    </div>
                    <p class="description"><?php _e('在中国大陆访问 Gravatar 可能较慢或无法加载，选择镜像源可加速头像显示。', 'admin-plus'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * 渲染"颜色方案"选项卡
     *
     * 包含以下内容：
     * - 颜色方案下拉选择器（从 Color_Schemes::get_schemes() 获取可用方案）
     * - 当选择"自定义"方案时，显示五个颜色选择器（主色调、菜单背景、高亮色、链接色、按钮色）
     * - 当选择预设方案时，显示该方案的色板预览
     */
    private function render_colors_tab() {
        $current = $this->get_setting('color_scheme', 'default');
        $schemes = Color_Schemes::get_schemes();

        // 自定义颜色的默认值
        $custom_primary = $this->get_setting('custom_primary', '#3858e9');
        $custom_menu_bg = $this->get_setting('custom_menu_bg', '#1e1e1e');
        $custom_highlight = $this->get_setting('custom_highlight', '#7b90ff');
        $custom_link = $this->get_setting('custom_link', '#3858e9');
        $custom_button = $this->get_setting('custom_button', '#3858e9');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="color_scheme"><?php _e('后台颜色方案', 'admin-plus'); ?></label>
                </th>
                <td>
                    <select id="color_scheme" name="ap_settings[color_scheme]">
                        <?php foreach ($schemes as $key => $scheme) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                                <?php echo esc_html($scheme['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('选择后台颜色方案，覆盖默认的WordPress后台颜色。选择"默认"即使用WordPress原生颜色。', 'admin-plus'); ?></p>
                </td>
            </tr>
        </table>

        <?php if ($current === 'custom') : ?>
            <!-- 自定义颜色编辑区域：五个颜色选择器 -->
            <div class="ap-color-customizer">
                <h3><?php _e('自定义颜色设置', 'admin-plus'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="custom_primary"><?php _e('主色调', 'admin-plus'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_primary" name="ap_settings[custom_primary]" 
                                   value="<?php echo esc_attr($custom_primary); ?>" class="ap-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_menu_bg"><?php _e('菜单背景', 'admin-plus'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_menu_bg" name="ap_settings[custom_menu_bg]" 
                                   value="<?php echo esc_attr($custom_menu_bg); ?>" class="ap-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_highlight"><?php _e('高亮色', 'admin-plus'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_highlight" name="ap_settings[custom_highlight]" 
                                   value="<?php echo esc_attr($custom_highlight); ?>" class="ap-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_link"><?php _e('链接色', 'admin-plus'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_link" name="ap_settings[custom_link]" 
                                   value="<?php echo esc_attr($custom_link); ?>" class="ap-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_button"><?php _e('按钮色', 'admin-plus'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_button" name="ap_settings[custom_button]" 
                                   value="<?php echo esc_attr($custom_button); ?>" class="ap-color-picker" />
                        </td>
                    </tr>
                </table>
            </div>
        <?php elseif ($current !== 'default' && isset($schemes[$current])) : ?>
            <!-- 预设方案的色板预览 -->
            <div class="ap-color-preview">
                <h3><?php _e('颜色预览', 'admin-plus'); ?></h3>
                <div class="ap-color-swatch-list">
                    <?php foreach ($schemes[$current]['colors'] as $label => $color) : ?>
                        <div class="ap-color-swatch">
                            <span class="ap-swatch-dot" style="background-color: <?php echo esc_attr($color); ?>;"></span>
                            <span class="ap-swatch-label"><?php echo esc_html($label); ?></span>
                            <span class="ap-swatch-value"><?php echo esc_html($color); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * 渲染"自定义代码"选项卡
     *
     * 提供两个CSS代码编辑区域：
     * - 登录页面自定义CSS：仅作用于 wp-login.php 页面
     * - 后台管理自定义CSS：仅作用于 WordPress 管理后台页面
     * 同时展示常用CSS示例供用户参考。
     */
    private function render_custom_tab() {
        $login_custom_css = $this->get_setting('login_custom_css');
        $admin_custom_css = $this->get_setting('admin_custom_css');
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="login_custom_css"><?php _e('登录页面自定义CSS', 'admin-plus'); ?></label>
                </th>
                <td>
                    <textarea id="login_custom_css" name="ap_settings[login_custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($login_custom_css); ?></textarea>
                    <p class="description">
                        <?php _e('仅作用于登录页面（wp-login.php）的自定义CSS。<strong>不需要</strong>添加 &lt;style&gt; 标签。', 'admin-plus'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="admin_custom_css"><?php _e('后台管理自定义CSS', 'admin-plus'); ?></label>
                </th>
                <td>
                    <textarea id="admin_custom_css" name="ap_settings[admin_custom_css]" rows="10" class="large-text code"><?php echo esc_textarea($admin_custom_css); ?></textarea>
                    <p class="description">
                        <?php _e('仅作用于WordPress管理后台的自定义CSS。<strong>不需要</strong>添加 &lt;style&gt; 标签。', 'admin-plus'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- 常用CSS示例，帮助用户快速上手 -->
        <div class="ap-custom-css-examples">
            <h3><?php _e('常用CSS示例', 'admin-plus'); ?></h3>
            <p><strong><?php _e('登录页面示例：', 'admin-plus'); ?></strong></p>
            <pre class="ap-code-example">
/* 修改登录页背景 */
body.login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

/* 自定义登录表单圆角 */
body.login #loginform { border-radius: 16px; }

/* 修改欢迎文字颜色 */
body.login .message { color: #333; }</pre>
            <p><strong><?php _e('后台管理示例：', 'admin-plus'); ?></strong></p>
            <pre class="ap-code-example">
/* 修改后台字体大小 */
body { font-size: 14px; }

/* 自定义仪表盘小工具样式 */
.postbox { border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }

/* 自定义表格样式 */
.wp-list-table { border-radius: 8px; overflow: hidden; }

/* 隐藏特定的后台元素 */
#wp-admin-bar-wp-logo { display: none; }</pre>
        </div>
        <?php
    }
}
