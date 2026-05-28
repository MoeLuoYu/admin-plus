<?php
/**
 * Login Customizer - 登录页面自定义类
 *
 * 负责WordPress登录页面（wp-login.php）的视觉自定义，包括：
 * - 自定义Logo图片和链接
 * - 登录页背景颜色和背景图片
 * - 登录表单样式（现代简洁 / 经典风格 / 毛玻璃效果）
 * - 登录页自定义CSS代码输出
 * - 登录页body元素的CSS类名注入
 *
 * @package Admin_Plus
 */

// 防止直接访问文件
defined('ABSPATH') || exit;

/**
 * Login_Customizer 类
 *
 * 管理登录页面的所有视觉自定义功能，采用单例模式。
 * 通过WordPress的登录页面相关钩子（login_head、login_enqueue_scripts等）
 * 注入自定义的CSS样式和资源文件。
 */
class Login_Customizer {

    /** @var self|null 单例实例 */
    private static $instance = null;

    /** @var Admin_Settings 设置管理实例，用于读取登录页相关配置 */
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
     * 获取 Admin_Settings 实例并注册以下WordPress钩子：
     * - login_headerurl:    修改Logo的链接URL为站点首页
     * - login_headertext:   修改Logo的title属性为站点名称
     * - login_head:         输出自定义登录页样式（Logo、背景、表单样式）
     * - login_head:         输出用户自定义的CSS代码
     * - login_enqueue_scripts: 加载登录页专用的CSS资源文件
     * - login_body_class:   为登录页body元素添加样式类名
     */
    private function __construct() {
        $settings_instance = Admin_Settings::get_instance();
        $this->settings = $settings_instance;

        // 修改Logo链接和文字
        add_filter('login_headerurl', array($this, 'custom_login_logo_url'));
        add_filter('login_headertext', array($this, 'custom_login_logo_title'));

        // 输出自定义登录页样式和CSS
        add_action('login_head', array($this, 'custom_login_styles'));
        add_action('login_head', array($this, 'output_login_custom_css'));

        // 加载登录页CSS资源
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_assets'));

        // 为登录页body添加样式类名
        add_filter('login_body_class', array($this, 'add_login_body_classes'));
    }

    /**
     * 修改登录页Logo的链接URL
     *
     * 将默认的 wordpress.org 链接替换为站点首页URL。
     *
     * @return string 站点首页URL
     */
    public function custom_login_logo_url() {
        return home_url();
    }

    /**
     * 修改登录页Logo的鼠标悬停文字
     *
     * 将默认的 "Powered by WordPress" 替换为站点名称。
     *
     * @return string 站点名称
     */
    public function custom_login_logo_title() {
        return get_bloginfo('name');
    }

    /**
     * 加载登录页专用的CSS资源文件
     *
     * 注册并加载 login.css 样式表，包含三种表单样式的基础样式。
     */
    public function enqueue_login_assets() {
        wp_enqueue_style(
            'ap-login',
            AP_PLUGIN_URL . 'assets/css/login.css',
            array(),
            AP_VERSION
        );
    }

    /**
     * 为登录页body元素添加CSS类名
     *
     * 根据用户选择的表单样式添加对应的类名（如 ap-login-style-modern），
     * 用于 login.css 中的样式选择器。
     *
     * @param array $classes 已有的body类名数组
     * @return array 添加了自定义样式类名后的数组
     */
    public function add_login_body_classes($classes) {
        $style = $this->settings->get_setting('login_form_style', 'modern');
        $classes[] = 'ap-login-style-' . $style;
        return $classes;
    }

    /**
     * 输出自定义登录页样式
     *
     * 根据设置项动态输出登录页的内联CSS，包括：
     * 1. Logo图片：如果设置了自定义Logo，覆盖默认的WordPress Logo
     * 2. 背景样式：背景颜色和可选的背景图片（封面填充模式）
     * 3. 表单样式：根据选择的样式类型输出不同的CSS规则
     *    - modern: 圆角卡片式表单，柔和阴影
     *    - classic: 经典直角表单，传统WordPress风格
     *    - glass: 毛玻璃效果，半透明背景 + 渐变按钮
     *
     * 所有输出值均通过 esc_url() 和 esc_attr() 进行安全转义。
     */
    public function custom_login_styles() {
        $logo = $this->get_setting('login_logo');
        $logo_width = $this->get_setting('login_logo_width', 280);
        $bg_color = $this->get_setting('login_bg_color', '#f0f0f1');
        $bg_image = $this->get_setting('login_bg_image');
        $style = $this->get_setting('login_form_style', 'modern');
        ?>
        <style type="text/css">
            <?php if (!empty($logo)) : ?>
            /* 自定义Logo：替换WordPress默认Logo */
            #login h1 a, .login h1 a {
                background-image: url(<?php echo esc_url($logo); ?>);
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                width: <?php echo esc_attr($logo_width); ?>px;
                height: <?php echo esc_attr(min(120, $logo_width * 0.4)); ?>px;
            }
            <?php endif; ?>

            /* 登录页背景 */
            body.login {
                background-color: <?php echo esc_attr($bg_color); ?>;
                <?php if (!empty($bg_image)) : ?>
                background-image: url(<?php echo esc_url($bg_image); ?>);
                background-size: cover;
                background-position: center center;
                background-repeat: no-repeat;
                <?php endif; ?>
            }

            <?php
            // 根据表单样式类型输出对应的CSS
            switch ($style) {
                case 'classic':
                    // 经典风格：直角、传统阴影
                    ?>
                    body.login #loginform {
                        background: #fff;
                        border-radius: 0;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.13);
                        padding: 26px 24px;
                    }
                    body.login .button-primary {
                        background: #2271b1;
                        border-color: #2271b1;
                        border-radius: 0;
                        box-shadow: none;
                        text-shadow: none;
                    }
                    <?php
                    break;

                case 'glass':
                    // 毛玻璃效果：半透明背景 + 模糊 + 渐变按钮
                    ?>
                    body.login #loginform {
                        background: rgba(255, 255, 255, 0.85);
                        backdrop-filter: blur(20px);
                        -webkit-backdrop-filter: blur(20px);
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        border-radius: 16px;
                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                        padding: 32px;
                    }
                    body.login .button-primary {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border: none;
                        border-radius: 8px;
                        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                        text-shadow: none;
                        transition: all 0.3s ease;
                    }
                    body.login .button-primary:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                    }
                    body.login input[type="text"],
                    body.login input[type="password"] {
                        border-radius: 8px;
                        border: 1px solid rgba(0, 0, 0, 0.1);
                        background: rgba(255, 255, 255, 0.9);
                        padding: 8px 16px;
                    }
                    body.login input[type="text"]:focus,
                    body.login input[type="password"]:focus {
                        border-color: #667eea;
                        box-shadow: 0 0 0 1px #667eea;
                    }
                    body.login .forgetmenot label {
                        color: #666;
                    }
                    body.login .message,
                    body.login #login_error {
                        border-radius: 8px;
                        border: none;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
                    }
                    <?php
                    break;

                default: // modern
                    // 现代简洁风格：圆角卡片、柔和阴影、微交互
                    ?>
                    body.login #loginform {
                        background: #fff;
                        border-radius: 12px;
                        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                        padding: 32px;
                    }
                    body.login .button-primary {
                        background: #2271b1;
                        border-color: #2271b1;
                        border-radius: 6px;
                        box-shadow: none;
                        text-shadow: none;
                        padding: 6px 16px;
                        transition: all 0.2s ease;
                    }
                    body.login .button-primary:hover {
                        background: #135e96;
                        border-color: #135e96;
                        transform: translateY(-1px);
                    }
                    body.login input[type="text"],
                    body.login input[type="password"] {
                        border-radius: 6px;
                        border: 1px solid #dcdcde;
                        padding: 8px 12px;
                    }
                    body.login input[type="text"]:focus,
                    body.login input[type="password"]:focus {
                        border-color: #2271b1;
                        box-shadow: 0 0 0 1px #2271b1;
                    }
                    body.login .message,
                    body.login #login_error {
                        border-radius: 8px;
                        border: none;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                    }
                    <?php
                    break;
            }
            ?>
        </style>
        <?php
    }

    /**
     * 输出用户自定义的登录页CSS代码
     *
     * 将用户在设置页面"自定义代码"选项卡中编写的CSS代码
     * 包装在 <style> 标签中输出到登录页的 <head> 区域。
     * CSS代码已在保存时经过安全清洗（移除HTML标签和 </style> 注入）。
     */
    public function output_login_custom_css() {
        $login_custom_css = $this->settings->get_setting('login_custom_css');
        if (!empty($login_custom_css)) {
            echo '<style id="ap-login-custom-css" type="text/css">' . "\n";
            echo $login_custom_css . "\n";
            echo '</style>' . "\n";
        }
    }

    /**
     * 获取单个设置项的值
     *
     * 代理方法，直接调用 Admin_Settings::get_setting()。
     *
     * @param string $key     设置项键名
     * @param mixed  $default 默认值
     * @return mixed 设置项的值或默认值
     */
    private function get_setting($key, $default = '') {
        return $this->settings->get_setting($key, $default);
    }
}
