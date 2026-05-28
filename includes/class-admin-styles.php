<?php
/**
 * Admin Styles - 后台样式与布局管理类
 *
 * 负责管理WordPress后台管理界面的视觉样式，包括：
 * - 后台和登录页的CSS资源加载
 * - 自定义CSS和字体的内联输出
 * - Discuz风格的横向菜单布局（顶部主菜单 + 左侧子菜单）
 * - 后台页脚文字自定义
 * - 顶部工具栏WordPress Logo的移除
 *
 * @package Admin_Plus
 */

defined('ABSPATH') || exit;

/**
 * Admin_Styles 类
 *
 * 管理后台管理界面的所有样式输出和布局控制，采用单例模式。
 * 支持"默认"和"Discuz"两种布局模式。
 */
class Admin_Styles {
    /** @var self|null 单例实例 */
    private static $instance = null;

    /** @var Admin_Settings 设置管理实例，用于读取插件配置 */
    private $settings = null;

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
     * - admin_enqueue_scripts / login_enqueue_scripts: 加载CSS资源文件
     * - admin_head / login_head: 输出自定义CSS（字体、布局、用户自定义代码）
     * - admin_footer_text: 自定义后台页脚左侧文字
     * - update_footer: 自定义后台页脚右侧版本号
     * - wp_before_admin_bar_render: 修改管理工具栏（移除Logo等）
     * - admin_enqueue_scripts: 加载Discuz布局所需的JS脚本
     * - admin_head: 输出Discuz风格的菜单HTML结构
     */
    private function __construct() {
        $this->settings = Admin_Settings::get_instance();

        // 加载后台和登录页的CSS资源文件
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        // 在页面<head>中输出内联自定义CSS
        add_action('admin_head', array($this, 'output_custom_css'));
        add_action('login_head', array($this, 'output_custom_css'));

        // 自定义后台页脚文字
        add_filter('admin_footer_text', array($this, 'custom_footer_text'));
        add_filter('update_footer', array($this, 'custom_footer_version'), 99);

        // 修改管理工具栏
        add_action('wp_before_admin_bar_render', array($this, 'modify_admin_bar'));

        // Discuz布局：加载JS脚本和输出菜单HTML
        add_action('admin_enqueue_scripts', array($this, 'enqueue_discuz_scripts'));
        add_action('admin_head', array($this, 'output_discuz_menu_html'), 1);
    }

    /**
     * 加载后台和登录页的CSS资源文件
     *
     * 注册并加载插件自带的 admin.css 和 login.css 样式表。
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style(
            'ap-admin-styles',
            AP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AP_VERSION
        );

        wp_enqueue_style(
            'ap-login-styles',
            AP_PLUGIN_URL . 'assets/css/login.css',
            array(),
            AP_VERSION
        );
    }

    /**
     * 在页面<head>中输出内联自定义CSS
     *
     * 根据设置项动态输出以下CSS：
     * 1. 自定义字体：覆盖后台所有元素的字体族
     * 2. Discuz布局CSS：隐藏默认左侧菜单，添加顶部菜单和左侧子菜单样式
     * 3. 用户自定义CSS：用户在设置页面编写的额外CSS代码
     */
    public function output_custom_css() {
        $admin_custom_css = $this->settings->get_setting('admin_custom_css');
        $font_family = $this->settings->get_setting('admin_font_family');
        $layout = $this->settings->get_setting('admin_layout', 'default');

        $css_parts = array();

        $web_fonts = array(
            'OPPOSans' => array(
                'font-family' => "'OPPO Sans', sans-serif",
                'src' => 'https://www.oppo.com/content/dam/oppo/common/fonts/font2/new-font/OPPOSansOS2-5000-Regular.woff2',
            ),
            'MiSans' => array(
                'font-family' => "'MiSans', sans-serif",
                'src' => 'https://cdn.jsdelivr.net/npm/misans@4.1.0/lib/Latin/MiSansLatin-Medium.woff2',
            ),
        );

        if (!empty($font_family)) {
            if (isset($web_fonts[$font_family])) {
                $wf = $web_fonts[$font_family];
                $css_parts[] = '@font-face {
                    font-family: ' . $wf['font-family'] . ';
                    src: url(' . esc_url($wf['src']) . ') format("woff2");
                    font-weight: normal;
                    font-style: normal;
                    font-display: swap;
                }';
                $css_parts[] = 'body, #wpadminbar, #adminmenu, .wp-submenu, .wrap, #wpbody-content {
                    font-family: ' . $wf['font-family'] . ' !important;
                }';
            } else {
                $css_parts[] = 'body, #wpadminbar, #adminmenu, .wp-submenu, .wrap, #wpbody-content {
                    font-family: ' . $font_family . ', sans-serif !important;
                }';
            }
        }

        // Discuz风格布局CSS
        if ($layout === 'discuz') {
            if (!$this->should_skip_discuz()) {
                $css_parts[] = $this->get_discuz_layout_css();
            }
        }

        // 用户自定义CSS代码
        if (!empty($admin_custom_css)) {
            $css_parts[] = $admin_custom_css;
        }

        // 将所有CSS部分合并输出到一个<style>标签中
        if (!empty($css_parts)) {
            echo '<style id="ap-custom-styles" type="text/css">' . "\n";
            echo implode("\n", $css_parts) . "\n";
            echo '</style>' . "\n";
        }
    }

    /**
     * 获取Discuz风格布局的完整CSS代码
     *
     * Discuz布局的核心样式：
     * - 顶部固定菜单栏（紧跟在WordPress工具栏下方）
     * - 左侧固定子菜单侧边栏
     * - 隐藏WordPress默认的左侧菜单
     * - 内容区域自适应调整
     * - 响应式适配（移动端侧边栏变为滑出式）
     * - 横向滚动指示器（当菜单项超出视口宽度时显示）
     *
     * @return string Discuz布局的CSS代码
     */
    private function get_discuz_layout_css() {
        return '
            /* Discuz Style Layout - Complete Overhaul */
            html, body {
                margin: 0;
                padding: 0;
            }
            
            /* Make sure admin bar stays fixed at top, don\'t touch it */
            #wpadminbar {
                position: fixed !important;
                top: 0;
                left: 0;
                right: 0;
                z-index: 9999;
            }
            
            /* Hide original left menu */
            #adminmenuback,
            #adminmenuwrap {
                display: none !important;
            }
            
            /* Adjust content position: just start from top, we handle our own layout */
            #wpcontent {
                margin-left: 0 !important;
                padding-left: 0 !important;
                margin-top: 0 !important;
                padding-top: 40px !important;
                box-sizing: border-box;
            }
            
            /* Setup wpbody as normal container */
            #wpbody {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Prevent wrap from causing issues */
            #wpwrap {
                overflow: visible !important;
            }
            
            /* Add new top menu area - make it fixed */
            .ap-top-menu {
                background: #2c3338;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden;
                position: fixed;
                top: 32px;
                left: 0;
                right: 0;
                z-index: 9998;
                border-bottom: 1px solid rgba(0,0,0,0.1);
            }
            
            .ap-top-menu-inner {
                display: flex;
                align-items: center;
                min-height: 48px;
                padding: 0 40px;
                overflow-x: auto;
                overflow-y: hidden;
                white-space: nowrap;
            }
            
            .ap-top-menu-item {
                display: inline-flex;
                align-items: center;
                color: #fff;
                padding: 12px 16px;
                text-decoration: none;
                font-size: 14px;
                cursor: pointer;
                border-right: 1px solid rgba(255,255,255,0.1);
                transition: background 0.3s;
                flex-shrink: 0;
            }
            
            .ap-top-menu-item:hover {
                background: #3858e9;
                color: #fff;
            }
            
            .ap-top-menu-item.current {
                background: #3858e9;
            }
            
            .ap-top-menu-item .dashicons {
                margin-right: 6px;
                font-size: 18px;
            }
            
            .ap-top-menu-item .ap-menu-icon-img {
                width: 20px;
                height: 20px;
                margin-right: 6px;
                vertical-align: middle;
                object-fit: contain;
            }
            
            .ap-top-menu-item .ap-menu-icon-svg {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-right: 6px;
                vertical-align: middle;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
            }
            
            /* Scroll indicators */
            .ap-top-menu-scroll-left,
            .ap-top-menu-scroll-right {
                position: absolute;
                top: 0;
                bottom: 0;
                width: 40px;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(44,51,56,0.95);
                color: #fff;
                cursor: pointer;
                border: none;
                z-index: 10;
                font-size: 18px;
            }
            
            .ap-top-menu-scroll-left {
                left: 0;
                border-right: 1px solid rgba(255,255,255,0.1);
            }
            
            .ap-top-menu-scroll-right {
                right: 0;
                border-left: 1px solid rgba(255,255,255,0.1);
            }
            
            /* Layout container - we don\'t need this anymore */
            .ap-layout-container {
                display: block;
            }
            
            /* Left sidebar for submenus - fixed positioning */
            .ap-sidebar {
                position: fixed;
                top: 80px;
                left: 0;
                width: 220px;
                height: calc(100vh - 80px);
                background: #e4e4e7;
                border-right: 1px solid #c3c4c7;
                overflow-y: auto;
                z-index: 9997;
            }
            
            .ap-sidebar-menu {
                padding: 10px 0;
            }
            
            .ap-sidebar-item {
                display: block;
                padding: 10px 15px;
                color: #1d2327;
                text-decoration: none;
            }
            
            .ap-sidebar-item:hover {
                background: #d4d4d8;
            }
            
            .ap-sidebar-item.current {
                background: #d4d4d8;
                font-weight: 600;
            }
            
            .ap-sidebar-item .dashicons {
                margin-right: 6px;
            }
            
            .ap-sidebar-item .ap-menu-icon-img {
                width: 20px;
                height: 20px;
                margin-right: 6px;
                vertical-align: middle;
                object-fit: contain;
            }
            
            .ap-sidebar-item .ap-menu-icon-svg {
                display: inline-block;
                width: 20px;
                height: 20px;
                margin-right: 6px;
                vertical-align: middle;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
            }
            
            /* Menu count bubble - WordPress style */
            .ap-menu-count {
                display: inline-block;
                vertical-align: top;
                box-sizing: border-box;
                margin: 1px 0 -1px 5px;
                padding: 0 5px;
                min-width: 18px;
                height: 18px;
                border-radius: 9px;
                background-color: #d63638;
                color: #fff;
                font-size: 11px;
                line-height: 1.6;
                text-align: center;
                z-index: 26;
            }
            
            /* Content area - make room for fixed sidebar */
            #wpbody-content {
                margin-left: 220px !important;
                padding: 20px;
                box-sizing: border-box;
                width: calc(100% - 220px);
                max-width: 100%;
            }

            /* Only remove WordPress default max-width, don\'t force width - this preserves absolute positioning */
            #wpbody-content .wrap {
                margin: 0 !important;
                max-width: none !important;
            }

            /* Make sure footer is in content area */
            #wpfooter {
                position: relative !important;
                margin-left: 220px !important;
            }
            
            /* Ensure screen options and help are hidden */
            #screen-meta-links {
                display: none;
            }

            /* Prevent tables and other elements from overflowing */
            #wpbody-content table,
            #wpbody-content .form-table,
            #wpbody-content .widefat {
                table-layout: fixed;
                max-width: 100%;
                width: 100%;
            }

            #wpbody-content table td,
            #wpbody-content table th {
                word-wrap: break-word;
                word-break: break-word;
            }

            /* Make images responsive */
            #wpbody-content img {
                max-width: 100%;
                height: auto;
            }
            
            /* Responsive adjustments */
            @media (max-width: 782px) {
                .ap-top-menu-item {
                    padding: 12px 12px;
                }
                
                .ap-top-menu {
                    top: 46px !important;
                }
                
                .ap-sidebar {
                    top: 94px !important;
                    width: 180px;
                    height: calc(100vh - 94px);
                    left: -180px;
                    transition: left 0.3s ease;
                    z-index: 99999;
                    background: #e4e4e7;
                }
                
                .ap-sidebar.ap-sidebar-open {
                    left: 0;
                }
                
                .ap-sidebar-overlay {
                    display: none;
                    position: fixed;
                    top: 94px;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    z-index: 99998;
                }
                
                .ap-sidebar-overlay.ap-sidebar-overlay-show {
                    display: block;
                }
                
                #wpcontent {
                    padding-top: 94px !important;
                }
                
                #wpbody-content {
                    margin-left: 0 !important;
                    width: 100%;
                    max-width: 100%;
                }
                
                #wpfooter {
                    margin-left: 0 !important;
                }
                
                /* 覆盖WordPress自带的主内容区域平移效果 */
                .wp-responsive-open #wpbody {
                    right: 0 !important;
                }
            }
        ';
    }

    /**
     * 加载Discuz布局所需的JavaScript脚本
     *
     * 仅在布局模式为 discuz 且不在排除页面时加载 discuz-layout.js，
     * 该脚本负责菜单的交互逻辑（悬停切换子菜单、横向滚动等）。
     */
    public function enqueue_discuz_scripts() {
        $layout = $this->settings->get_setting('admin_layout', 'default');
        if ($layout !== 'discuz') {
            return;
        }
        if ($this->should_skip_discuz()) {
            return;
        }
        wp_enqueue_script(
                'ap-discuz-layout',
                AP_PLUGIN_URL . 'assets/js/discuz-layout.js',
                array('jquery'),
                AP_VERSION,
                true
            );
    }

    /**
     * 自定义后台页脚左侧文字
     *
     * 如果用户设置了自定义页脚文字则使用用户设置（经过 wp_kses_post 安全过滤），
     * 否则显示默认的感谢信息 + 插件推广链接。
     *
     * @return string 页脚左侧HTML内容
     */
    public function custom_footer_text() {
        $footer_text = $this->settings->get_setting('admin_footer_text');
        if (!empty($footer_text)) {
            return wp_kses_post($footer_text);
        }
        return '<span id="footer-thankyou">' . sprintf(
            __('感谢使用 <a href="%s">WordPress</a>', 'admin-plus'),
            'https://wordpress.org/'
        ) . '</span> | ' . sprintf(
            __('由 <a href="%s">Admin Plus</a> 美化加速', 'admin-plus'),
            admin_url('tools.php?page=admin-plus')
        );
    }

    /**
     * 自定义后台页脚右侧版本号
     *
     * 如果用户勾选了"移除工具栏Logo"，则同时隐藏版本号；
     * 否则显示 WordPress 版本号。
     *
     * @return string 页脚右侧版本号文字，或空字符串
     */
    public function custom_footer_version() {
        $remove = $this->settings->get_setting('remove_admin_bar_logo', 0);
        if ($remove) {
            return '';
        }
        return __('版本', 'admin-plus') . ' ' . get_bloginfo('version');
    }

    /**
     * 修改管理工具栏
     *
     * 如果用户勾选了"移除工具栏Logo"，则从顶部工具栏中移除WordPress Logo节点。
     */
    public function modify_admin_bar() {
        $remove_logo = $this->settings->get_setting('remove_admin_bar_logo', 0);
        if ($remove_logo) {
            global $wp_admin_bar;
            $wp_admin_bar->remove_node('wp-logo');
        }
    }
    
    /**
     * 输出Discuz风格的菜单HTML结构
     *
     * 从WordPress全局变量 $menu 和 $submenu 中读取菜单数据，
     * 构建顶部主菜单和左侧子菜单的HTML结构，同时将子菜单数据
     * 以JSON格式传递给前端JavaScript进行交互处理。
     *
     * 生成的HTML结构：
     * 1. .ap-top-menu - 固定在顶部的水平主菜单栏
     *    - .ap-top-menu-inner - 可横向滚动的菜单项容器
     *    - 滚动指示按钮（左右箭头）
     * 2. .ap-sidebar - 固定在左侧的子菜单侧边栏
     * 3. <script> - 将子菜单数据以JS变量形式输出供前端使用
     */
    public function output_discuz_menu_html() {
        $layout = $this->settings->get_setting('admin_layout', 'default');
        if ($layout !== 'discuz') {
            return;
        }
        
        if ($this->should_skip_discuz()) {
            return;
        }
        
        // WordPress全局菜单变量
        global $menu, $submenu, $parent_file, $submenu_file;
        
        $current_menu_id = '';
        $menu_items = array();
        
        // 遍历WordPress主菜单，构建菜单项数据
        foreach ($menu as $key => $item) {
            if (empty($item[2])) {
                continue;
            }
            
            $menu_slug = $item[2];
            
            // 跳过分隔符
            if (strpos($menu_slug, 'separator') === 0) {
                continue;
            }
            
            if (empty($item[0]) || !empty($item[4]) && $item[4] === 'wp-menu-separator') {
                continue;
            }

            // 清理菜单标题中的计数气泡HTML
            $menu_title = $this->strip_menu_count($item[0]);
            // 构建菜单项的URL
            $menu_url = $this->build_menu_url($menu_slug);
            // 判断是否为当前活跃菜单
            $is_current = ($parent_file === $menu_slug);
            
            // 提取菜单项的数字计数（如未读评论数、更新数等）
            $count = $this->extract_menu_count($item[0]);
            
            $css_class = 'menu-top';
            if ($is_current) {
                $css_class .= ' wp-has-current-submenu current';
                $current_menu_id = $menu_slug;
            }
            
            // 解析菜单图标类型（图片URL / Data URI / Dashicon / 无图标）
            $icon_type = 'none';
            $icon_value = '';
            if (!empty($item[6])) {
                if (strpos($item[6], 'data:image/') === 0) {
                    $icon_type = 'data-uri';
                    $icon_value = $item[6];
                } elseif (preg_match('/^(https?:|\/\/)/i', $item[6]) || preg_match('/\.(png|jpg|jpeg|gif|svg|ico|webp)(\?|$)/i', $item[6])) {
                    $icon_type = 'image';
                    $icon_value = $item[6];
                } elseif ($item[6] !== 'none' && $item[6] !== 'div') {
                    $icon_type = 'dashicon';
                    $icon_value = $item[6];
                }
            }
            
            // 构建子菜单项数据
            $submenu_items = array();
            if (isset($submenu[$menu_slug]) && is_array($submenu[$menu_slug])) {
                foreach ($submenu[$menu_slug] as $sub_key => $sub_item) {
                    if (empty($sub_item[2])) {
                        continue;
                    }
                    
                    $sub_title = $this->strip_menu_count($sub_item[0]);
                    $sub_url = $this->build_menu_url($sub_item[2]);
                    $sub_is_current = ($submenu_file === $sub_item[2]);
                    $sub_count = $this->extract_menu_count($sub_item[0]);
                    
                    $submenu_items[] = array(
                        'title' => $sub_title,
                        'url' => $sub_url,
                        'is_current' => $sub_is_current,
                        'count' => $sub_count
                    );
                }
            }
            
            // 如果有子菜单，主菜单链接指向第一个子菜单项
            if (!empty($submenu_items)) {
                $menu_url = $submenu_items[0]['url'];
            }
            
            $menu_items[] = array(
                'id' => $menu_slug,
                'title' => $menu_title,
                'url' => $menu_url,
                'icon_type' => $icon_type,
                'icon_value' => $icon_value,
                'is_current' => $is_current,
                'count' => $count,
                'submenu' => $submenu_items
            );
        }
        
        // ===== 输出顶部主菜单HTML =====
        echo '<div class="ap-top-menu" id="ap-top-menu">' . "\n";
        echo '<button class="ap-top-menu-scroll-left" title="' . esc_attr__('向左滚动', 'admin-plus') . '">&lt;</button>' . "\n";
        echo '<div class="ap-top-menu-inner">' . "\n";
        
        foreach ($menu_items as $item) {
            $current_class = $item['is_current'] ? ' current' : '';
            // 数字计数气泡（如未读数）
            $count_bubble = $item['count'] > 0 ? '<span class="ap-menu-count">' . (int)$item['count'] . '</span>' : '';
            $icon_html = $this->render_icon_html($item['icon_type'], $item['icon_value']);
            
            echo '<div class="ap-top-menu-item' . $current_class . '" data-menu-id="' . esc_attr($item['id']) . '">' . "\n";
            if ($item['url'] && $item['url'] !== '#') {
                echo '<a href="' . esc_url($item['url']) . '" style="color:inherit;text-decoration:none;display:flex;align-items:center;">' . "\n";
            }
            echo $icon_html . esc_html($item['title']) . $count_bubble . "\n";
            if ($item['url'] && $item['url'] !== '#') {
                echo '</a>' . "\n";
            }
            echo '</div>' . "\n";
        }
        
        echo '</div>' . "\n";
        echo '<button class="ap-top-menu-scroll-right" title="' . esc_attr__('向右滚动', 'admin-plus') . '">&gt;</button>' . "\n";
        echo '</div>' . "\n";
        
        // ===== 输出左侧子菜单侧边栏HTML =====
        echo '<div class="ap-sidebar" id="ap-sidebar">' . "\n";
        echo '<div class="ap-sidebar-menu" id="ap-sidebar-menu">' . "\n";
        
        // 找到当前活跃的菜单项
        $current_menu = null;
        foreach ($menu_items as $item) {
            if ($item['is_current']) {
                $current_menu = $item;
                break;
            }
        }
        
        // 如果没有找到当前菜单项，默认使用第一个
        if (!$current_menu && !empty($menu_items)) {
            $current_menu = $menu_items[0];
        }
        
        // 渲染子菜单项或主菜单项
        if ($current_menu) {
            if (!empty($current_menu['submenu'])) {
                // 有子菜单时，列出所有子菜单项
                foreach ($current_menu['submenu'] as $sub_item) {
                    $sub_current_class = $sub_item['is_current'] ? ' current' : '';
                    $sub_count_bubble = $sub_item['count'] > 0 ? '<span class="ap-menu-count">' . (int)$sub_item['count'] . '</span>' : '';
                    
                    echo '<a href="' . esc_url($sub_item['url']) . '" class="ap-sidebar-item' . $sub_current_class . '">' . "\n";
                    echo esc_html($sub_item['title']) . $sub_count_bubble . "\n";
                    echo '</a>' . "\n";
                }
            } else {
                // 无子菜单时，显示主菜单项本身
                $main_count_bubble = $current_menu['count'] > 0 ? '<span class="ap-menu-count">' . (int)$current_menu['count'] . '</span>' : '';
                $main_icon_html = $this->render_icon_html($current_menu['icon_type'], $current_menu['icon_value']);
                
                echo '<a href="' . esc_url($current_menu['url']) . '" class="ap-sidebar-item current">' . "\n";
                echo $main_icon_html . esc_html($current_menu['title']) . $main_count_bubble . "\n";
                echo '</a>' . "\n";
            }
        }
        
        echo '</div>' . "\n";
        echo '</div>' . "\n";
        
        // ===== 输出子菜单数据供前端JavaScript使用 =====
        echo '<script type="text/javascript">' . "\n";
        // 将所有菜单项的子菜单数据以JSON格式传递给JS，用于悬停切换
        echo 'var apSubmenuData = ' . json_encode($this->get_submenu_data($menu_items)) . ';' . "\n";
        // 初始化：检测菜单是否需要滚动按钮
        echo '(function(){var m=document.querySelector(".ap-top-menu-inner");if(!m)return;var bl=document.querySelector(".ap-top-menu-scroll-left"),br=document.querySelector(".ap-top-menu-scroll-right");if(m.scrollWidth>m.clientWidth){bl.style.display="flex";br.style.display="flex";}else{m.style.padding="0";}})();' . "\n";
        echo '</script>' . "\n";
    }
    
    /**
     * 从菜单项数据中提取所有子菜单数据
     *
     * 将子菜单数据按菜单ID分组，供前端JavaScript在悬停时动态切换侧边栏内容。
     *
     * @param array $menu_items 完整的菜单项数据数组
     * @return array 以菜单ID为键、子菜单数组为值的关联数组
     */
    private function get_submenu_data($menu_items) {
        $submenu_data = array();
        
        foreach ($menu_items as $item) {
            if (!empty($item['submenu'])) {
                $submenu_data[$item['id']] = $item['submenu'];
            }
        }
        
        return $submenu_data;
    }
    
    /**
     * 从菜单标题HTML中移除计数气泡
     *
     * WordPress菜单项的标题中可能包含数字计数气泡（如评论数、更新数），
     * 此方法移除这些HTML标签，只保留纯文本标题。
     *
     * @param string $title 包含HTML的菜单标题
     * @return string 清理后的纯文本标题
     */
    private function strip_menu_count($title) {
        // 移除嵌套的计数span标签（如 awaiting-mod、update-plugins 等）
        $title = preg_replace('#<span[^>]*class="[^"]*(?:awaiting-mod|update-plugins|menu-counter)[^"]*"[^>]*>.*?</span>.*?</span>#si', '', $title);
        $title = preg_replace('#<span[^>]*class="[^"]*(?:awaiting-mod|update-plugins|menu-counter)[^"]*"[^>]*>.*?</span>#si', '', $title);
        // 移除简单的计数span标签
        $title = preg_replace('#<span[^>]*class="[^"]*(?:pending-count|update-count|plugin-count)[^"]*"[^>]*>.*?</span>#si', '', $title);
        $title = strip_tags($title);
        return trim($title);
    }
    
    /**
     * 从菜单标题HTML中提取数字计数值
     *
     * 用于在Discuz布局中显示数字气泡（如未读评论数、可用更新数）。
     *
     * @param string $title 包含HTML的菜单标题
     * @return int 提取到的计数值，无匹配时返回0
     */
    private function extract_menu_count($title) {
        // 匹配 update-count、pending-count 等class的span中的数字
        if (preg_match('/<span[^>]*class="[^"]*(?:update-count|pending-count|plugin-count)[^"]*"[^>]*>(\d+)<\/span>/i', $title, $matches)) {
            return (int)$matches[1];
        }
        // 匹配 count-N 格式的class
        if (preg_match('/class="[^"]*count-(\d+)[^"]*"/i', $title, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
    
    /**
     * 判断当前页面是否应跳过Discuz布局
     *
     * 某些WordPress页面（如古腾堡编辑器、自定义器、媒体上传弹窗等）
     * 不适合使用Discuz布局，此方法返回true时将跳过Discuz布局的渲染。
     *
     * @return bool 是否跳过Discuz布局
     */
    private function should_skip_discuz() {
        // 古腾堡块编辑器页面
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && !empty($screen->is_block_editor)) {
            return true;
        }
        // 自定义器、媒体上传、字体库等特殊页面
        global $pagenow;
        if (in_array($pagenow, array('customize.php', 'media-upload.php', 'font-library.php'), true)) {
            return true;
        }
        return false;
    }
    
    /**
     * 渲染菜单图标的HTML
     *
     * 根据图标类型生成对应的HTML标签：
     * - image: 渲染为 <img> 标签（用于自定义图标URL）
     * - dashicon: 渲染为 <span class="dashicons"> 标签（WordPress内置图标字体）
     *
     * @param string $icon_type  图标类型：'image' / 'dashicon' / 'none'
     * @param string $icon_value 图标值：URL或Dashicon类名
     * @return string 图标的HTML代码，无图标时返回空字符串
     */
    private function render_icon_html($icon_type, $icon_value) {
        if ($icon_type === 'data-uri' && !empty($icon_value)) {
            $safe_uri = $this->sanitize_data_uri($icon_value);
            if (!empty($safe_uri)) {
                return '<span class="ap-menu-icon ap-menu-icon-svg" style="background-image: url(' . esc_attr($safe_uri) . ');"></span> ';
            }
            return '';
        }
        if ($icon_type === 'image' && !empty($icon_value)) {
            $url = (strpos($icon_value, '//') === 0) ? set_url_scheme($icon_value) : $icon_value;
            return '<img src="' . esc_url($url) . '" alt="" class="ap-menu-icon-img"> ';
        }
        if ($icon_type === 'dashicon' && !empty($icon_value)) {
            return '<span class="dashicons ' . esc_attr($icon_value) . '"></span> ';
        }
        return '';
    }

    private function sanitize_data_uri($uri) {
        if (preg_match('/^data:image\/[a-z0-9+.-]+;base64,[a-zA-Z0-9+\/=]+$/', $uri)) {
            return $uri;
        }
        return '';
    }

    /**
     * 根据菜单slug构建完整的后台URL
     *
     * WordPress菜单slug有三种格式：
     * - 包含 .php 的路径（如 upload.php）→ 直接拼接 admin_url()
     * - 以 http 开头的完整URL → 原样返回
     * - 其他（如插件页面slug）→ 使用 admin.php?page= 格式
     *
     * @param string $slug 菜单slug标识符
     * @return string 完整的后台URL
     */
    private function build_menu_url($slug) {
        if (preg_match('/\.php/', $slug)) {
            return admin_url($slug);
        }
        if (strpos($slug, 'http') === 0) {
            return $slug;
        }
        return admin_url('admin.php?page=' . $slug);
    }
}
