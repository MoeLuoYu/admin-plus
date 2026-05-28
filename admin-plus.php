<?php
/**
 * Plugin Name: Admin Plus
 * Plugin URI: https://github.com/MoeLuoYu/admin-plus
 * Description: 一款强大的WordPress后台美化插件，支持自定义登录页面、管理后台样式、颜色方案等多种美化功能。
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: MoeLuoYu
 * Author URI: https://github.com/MoeLuoYu
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: admin-plus
 */

// 防止直接访问文件，确保只能在WordPress环境中运行
defined('ABSPATH') || exit;

// 插件版本号，用于资源文件的缓存管理
define('AP_VERSION', '1.0.0');
// 插件目录的绝对路径（末尾带斜杠）
define('AP_PLUGIN_DIR', plugin_dir_path(__FILE__));
// 插件目录的URL地址（末尾带斜杠），用于加载前端资源
define('AP_PLUGIN_URL', plugin_dir_url(__FILE__));
// 插件在WordPress中的标识符（如 admin-plus/admin-plus.php），用于插件操作链接
define('AP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 加载插件的各个功能模块类文件
require_once AP_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once AP_PLUGIN_DIR . 'includes/class-login-customizer.php';
require_once AP_PLUGIN_DIR . 'includes/class-admin-styles.php';
require_once AP_PLUGIN_DIR . 'includes/class-color-schemes.php';
require_once AP_PLUGIN_DIR . 'includes/class-gravatar-mirror.php';

/**
 * Admin Plus 主类
 *
 * 采用单例模式管理插件的整体生命周期，包括：
 * - 插件激活时的默认设置初始化
 * - 插件停用时的清理工作
 * - 国际化（i18n）文本域加载
 * - 各功能模块的初始化调度
 */
class Admin_Plus {

    /** @var self|null 单例实例 */
    private static $instance = null;

    /**
     * 获取插件单例实例
     *
     * 如果实例不存在则创建一个新实例，确保全局只有一个插件对象。
     *
     * @return self 插件实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 私有构造函数，防止外部直接实例化
     *
     * 仅调用 init_hooks() 注册WordPress钩子。
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * 注册WordPress动作钩子
     *
     * - register_activation_hook:   插件激活时执行 activate()
     * - register_deactivation_hook: 插件停用时执行 deactivate()
     * - plugins_loaded:             所有插件加载完毕后加载文本域
     * - init:                       WordPress初始化时启动各功能模块
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_modules'));
    }

    /**
     * 插件激活回调
     *
     * 初始化插件的默认设置项。如果数据库中尚无设置，则写入默认值；
     * 如果已有设置，则将新增的默认项合并进去（保留用户已有配置）。
     *
     * @return void
     */
    public function activate() {
        // 插件所有可配置项及其默认值
        $defaults = array(
            'login_logo' => '',              // 登录页Logo图片URL
            'login_logo_width' => 280,       // 登录页Logo显示宽度（px）
            'login_bg_color' => '#f0f0f1',   // 登录页背景颜色
            'login_bg_image' => '',           // 登录页背景图片URL
            'login_form_style' => 'modern',  // 登录表单样式：modern / classic / glass
            'admin_layout' => 'default',     // 后台布局模式：default / discuz
            'admin_footer_text' => '',       // 自定义后台页脚文字
            'color_scheme' => 'default',     // 颜色方案标识
            'remove_admin_bar_logo' => 0,    // 是否移除顶部工具栏WordPress Logo
            'login_custom_css' => '',        // 登录页自定义CSS代码
            'admin_custom_css' => '',        // 后台自定义CSS代码
            'admin_font_family' => '',       // 后台自定义字体
            'custom_primary' => '#3858e9',   // 自定义颜色 - 主色调
            'custom_menu_bg' => '#1e1e1e',   // 自定义颜色 - 菜单背景
            'custom_highlight' => '#7b90ff', // 自定义颜色 - 高亮色
            'custom_link' => '#3858e9',      // 自定义颜色 - 链接色
            'custom_button' => '#3858e9',    // 自定义颜色 - 按钮色
            'gravatar_mirror' => '',          // Gravatar镜像源，留空使用默认
            'gravatar_mirror_custom' => '',   // Gravatar自定义镜像域名
        );

        $existing = get_option('ap_settings');
        if (!$existing) {
            // 首次安装：直接写入默认设置
            add_option('ap_settings', $defaults);
        } else {
            // 升级安装：将新增的默认项合并到已有设置中，不覆盖用户已有配置
            $merged = wp_parse_args($existing, $defaults);
            update_option('ap_settings', $merged);
        }
    }

    /**
     * 插件停用回调
     *
     * 当前无额外清理操作，保留所有设置数据供重新激活时使用。
     *
     * @return void
     */
    public function deactivate() {
        // Clean up if needed
    }

    /**
     * 加载插件国际化文本域
     *
     * 从 /languages 目录加载翻译文件，使插件支持多语言。
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain('admin-plus', false, dirname(AP_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * 初始化各功能模块
     *
     * 通过单例模式实例化以下四个核心模块：
     * - Admin_Settings:    后台设置页面与选项管理
     * - Login_Customizer:  登录页面自定义（Logo、背景、表单样式）
     * - Admin_Styles:      后台管理界面的样式输出与布局控制
     * - Color_Schemes:     颜色方案管理与应用
     *
     * @return void
     */
    public function init_modules() {
        Admin_Settings::get_instance();
        Login_Customizer::get_instance();
        Admin_Styles::get_instance();
        Color_Schemes::get_instance();
        Gravatar_Mirror::get_instance();
    }
}

/**
 * 获取 Admin Plus 插件实例的全局辅助函数
 *
 * 在插件外部可通过 Admin_Plus() 获取主插件实例。
 *
 * @return Admin_Plus 插件主实例
 */
function Admin_Plus() {
    return Admin_Plus::get_instance();
}

// 启动插件
Admin_Plus();
