<?php
/**
 * Color Schemes - 颜色方案管理类
 *
 * 负责管理WordPress后台的颜色方案，包括：
 * - 提供预设颜色方案列表（Modern、Fresh、Ocean等）
 * - 支持用户自定义颜色方案（主色调、菜单背景、高亮色、链接色、按钮色）
 * - 通过WordPress钩子强制应用选定的颜色方案
 * - 动态生成颜色方案的CSS样式输出
 * - 颜色亮度调整工具方法（用于生成hover等状态的颜色变体）
 *
 * @package Admin_Plus
 */

// 防止直接访问文件
defined('ABSPATH') || exit;

/**
 * Color_Schemes 类
 *
 * 管理后台颜色方案的定义、选择和应用，采用单例模式。
 * 通过 get_user_option_admin_color 过滤器强制覆盖用户的颜色方案设置。
 */
class Color_Schemes {

	/** @var self|null 单例实例 */
	private static $instance = null;

	/** @var Admin_Settings 设置管理实例，用于读取当前选中的颜色方案 */
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
	 * - init:                       检查是否需要强制应用颜色方案
	 * - get_user_option_admin_color: 覆盖用户的后台颜色方案偏好
	 * - admin_head / login_head:    输出自定义颜色或预设方案的CSS样式
	 */
	private function __construct() {
		$this->settings = Admin_Settings::get_instance();
		add_action('init', array($this, 'maybe_force_scheme'));
		add_filter('get_user_option_admin_color', array($this, 'force_scheme'));
		add_action('admin_head', array($this, 'apply_custom_colors'));
		add_action('login_head', array($this, 'apply_custom_colors'));
	}

	/**
	 * 获取所有可用的颜色方案
	 *
	 * 返回包含所有预设颜色方案的关联数组。
	 * 每个方案包含：
	 * - name: 方案的显示名称
	 * - colors: 方案的颜色定义（主色调、菜单背景、高亮色、链接色、按钮色）
	 *
	 * 预设方案列表：
	 * - default: 默认（无变化），使用WordPress原生颜色
	 * - custom:  自定义，用户通过颜色选择器自行配置
	 * - modern:  Modern (WordPress 7.0) 风格
	 * - fresh:   Fresh (经典) 风格
	 * - ocean:   Ocean 海洋风格
	 * - sunrise: Sunrise 日出风格
	 * - midnight: Midnight 午夜风格
	 * - coffee:  Coffee 咖啡风格
	 * - light:   Light 浅色风格
	 * - ectoplasm: Ectoplasm 风格
	 * - blue:    Blue 蓝色风格
	 *
	 * @return array 颜色方案关联数组，键为方案标识符
	 */
	public static function get_schemes() {
		return array(
			'default' => array(
				'name' => __('默认（无变化）', 'admin-plus'),
				'colors' => array(),
			),
			'custom' => array(
				'name' => __('自定义', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#3858e9',
					__('菜单背景', 'admin-plus') => '#1e1e1e',
					__('高亮色', 'admin-plus') => '#7b90ff',
					__('链接色', 'admin-plus') => '#3858e9',
					__('按钮色', 'admin-plus') => '#3858e9',
				),
			),
			'modern' => array(
				'name' => __('Modern (WordPress 7.0)', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#3858e9',
					__('菜单背景', 'admin-plus') => '#1e1e1e',
					__('高亮色', 'admin-plus') => '#7b90ff',
					__('链接色', 'admin-plus') => '#3858e9',
					__('按钮色', 'admin-plus') => '#3858e9',
				),
			),
			'fresh' => array(
				'name' => __('Fresh (经典)', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#2271b1',
					__('菜单背景', 'admin-plus') => '#1d2327',
					__('高亮色', 'admin-plus') => '#72aee6',
					__('链接色', 'admin-plus') => '#2271b1',
					__('按钮色', 'admin-plus') => '#3858e9',
				),
			),
			'ocean' => array(
				'name' => __('Ocean', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#9ebaa0',
					__('菜单背景', 'admin-plus') => '#627c83',
					__('高亮色', 'admin-plus') => '#aa9d88',
					__('链接色', 'admin-plus') => '#9ebaa0',
					__('按钮色', 'admin-plus') => '#9ebaa0',
				),
			),
			'sunrise' => array(
				'name' => __('Sunrise', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#dd823b',
					__('菜单背景', 'admin-plus') => '#b43c38',
					__('高亮色', 'admin-plus') => '#ccaf0b',
					__('链接色', 'admin-plus') => '#dd823b',
					__('按钮色', 'admin-plus') => '#dd823b',
				),
			),
			'midnight' => array(
				'name' => __('Midnight', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#69a8bb',
					__('菜单背景', 'admin-plus') => '#25282b',
					__('高亮色', 'admin-plus') => '#e14d43',
					__('链接色', 'admin-plus') => '#69a8bb',
					__('按钮色', 'admin-plus') => '#69a8bb',
				),
			),
			'coffee' => array(
				'name' => __('Coffee', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#c7a589',
					__('菜单背景', 'admin-plus') => '#46403c',
					__('高亮色', 'admin-plus') => '#9ea476',
					__('链接色', 'admin-plus') => '#c7a589',
					__('按钮色', 'admin-plus') => '#c7a589',
				),
			),
			'light' => array(
				'name' => __('Light', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#04a4cc',
					__('菜单背景', 'admin-plus') => '#e5e5e5',
					__('高亮色', 'admin-plus') => '#d64e07',
					__('链接色', 'admin-plus') => '#04a4cc',
					__('按钮色', 'admin-plus') => '#04a4cc',
				),
			),
			'ectoplasm' => array(
				'name' => __('Ectoplasm', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#a3b745',
					__('菜单背景', 'admin-plus') => '#413256',
					__('高亮色', 'admin-plus') => '#d46f15',
					__('链接色', 'admin-plus') => '#a3b745',
					__('按钮色', 'admin-plus') => '#a3b745',
				),
			),
			'blue' => array(
				'name' => __('Blue', 'admin-plus'),
				'colors' => array(
					__('主色调', 'admin-plus') => '#52accc',
					__('菜单背景', 'admin-plus') => '#096484',
					__('高亮色', 'admin-plus') => '#74b6ce',
					__('链接色', 'admin-plus') => '#52accc',
					__('按钮色', 'admin-plus') => '#52accc',
				),
			),
		);
	}

	/**
	 * 在WordPress初始化时检查是否需要强制应用颜色方案
	 *
	 * 当用户选择了 default 或 custom 方案时无需额外操作
	 * （default使用原生颜色，custom通过CSS覆盖）。
	 * 对于预设方案，实际的强制应用由 force_scheme() 方法处理。
	 */
	public function maybe_force_scheme() {
		$scheme = $this->settings->get_setting('color_scheme', 'default');
		if ($scheme === 'default' || $scheme === 'custom') {
			return;
		}
	}

	/**
	 * 强制覆盖用户的后台颜色方案偏好
	 *
	 * 通过 get_user_option_admin_color 过滤器，将用户在个人设置中
	 * 选择的颜色方案替换为插件设置中指定的方案。
	 * 仅对预设方案生效，default 和 custom 方案保持用户原有设置。
	 *
	 * @param string $color_scheme 用户当前选择的颜色方案标识符
	 * @return string 覆盖后的颜色方案标识符
	 */
	public function force_scheme($color_scheme) {
		$scheme = $this->settings->get_setting('color_scheme', 'default');
		if ($scheme === 'default' || $scheme === 'custom') {
			return $color_scheme;
		}

		// WordPress 7.0 内置的可用颜色方案白名单
		$allowed_schemes = array(
			'modern' => 'modern',
			'fresh' => 'fresh',
			'ocean' => 'ocean',
			'sunrise' => 'sunrise',
			'midnight' => 'midnight',
			'coffee' => 'coffee',
			'light' => 'light',
			'ectoplasm' => 'ectoplasm',
			'blue' => 'blue',
		);

		if (isset($allowed_schemes[$scheme])) {
			return $allowed_schemes[$scheme];
		}

		return $color_scheme;
	}

	/**
	 * 在页面<head>中应用自定义颜色CSS
	 *
	 * 根据当前选中的颜色方案，输出对应的CSS样式：
	 * - custom: 输出用户自定义的五个颜色值的完整CSS覆盖规则
	 * - 预设方案: 输出该方案的颜色值到Discuz布局的顶部菜单中
	 *
	 * 自定义颜色覆盖范围：
	 * - CSS变量（--wp-admin-theme-color 等）
	 * - 管理菜单背景和悬停/激活状态
	 * - 管理工具栏背景
	 * - 全局链接和按钮颜色
	 * - 登录页面按钮和输入框焦点样式
	 * - Discuz布局的顶部菜单和侧边栏颜色
	 *
	 * 所有颜色值均通过 esc_attr() 安全输出。
	 */
	public function apply_custom_colors() {
		$scheme = $this->settings->get_setting('color_scheme', 'default');
		$schemes = self::get_schemes();

		// 默认方案不输出任何自定义CSS
		if ($scheme === 'default') {
			return;
		}

		// 根据方案类型获取颜色值
		if ($scheme === 'custom') {
			// 自定义方案：从用户设置中读取五个颜色值
			$primary = $this->settings->get_setting('custom_primary', '#3858e9');
			$menu_bg = $this->settings->get_setting('custom_menu_bg', '#1e1e1e');
			$highlight = $this->settings->get_setting('custom_highlight', '#7b90ff');
			$link = $this->settings->get_setting('custom_link', '#3858e9');
			$button = $this->settings->get_setting('custom_button', '#3858e9');
		} else if (isset($schemes[$scheme])) {
			// 预设方案：从方案定义中按顺序读取颜色值
			$colors = $schemes[$scheme]['colors'];
			$color_keys = array_keys($colors);
			$primary = $colors[$color_keys[0]];
			$menu_bg = $colors[$color_keys[1]];
			$highlight = $colors[$color_keys[2]];
			$link = $colors[$color_keys[3]];
			$button = $colors[$color_keys[4]];
		} else {
			return;
		}

		// 生成主色调的暗色变体（用于hover等交互状态）
		$primary_dark_10 = $this->darken($primary, 10);
		$primary_dark_20 = $this->darken($primary, 20);
		?>
		<style type="text/css">
			<?php if ($scheme === 'custom'): ?>
			/* CSS自定义属性：覆盖WordPress核心颜色变量 */
			:root {
				--wp-admin-theme-color: <?php echo esc_attr($primary); ?>;
				--wp-admin-theme-color-darker-10: <?php echo esc_attr($primary_dark_10); ?>;
				--wp-admin-theme-color-darker-20: <?php echo esc_attr($primary_dark_20); ?>;
			}

			/* 管理菜单背景 */
			#adminmenuback, #adminmenuwrap, #adminmenu {
				background-color: <?php echo esc_attr($menu_bg); ?> !important;
			}

			/* 管理菜单项悬停/激活/焦点状态 */
			#adminmenu li.menu-top:hover, 
			#adminmenu li.opensub > a.menu-top, 
			#adminmenu li > a.menu-top:focus,
			#adminmenu li.current a.menu-top, 
			#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu {
				background-color: <?php echo esc_attr($primary); ?> !important;
			}

			/* 子菜单背景 */
			#adminmenu .wp-submenu, 
			#adminmenu .wp-has-current-submenu .wp-submenu {
				background-color: <?php echo esc_attr($this->darken($menu_bg, 10)); ?> !important;
			}

			/* 子菜单链接颜色 */
			#adminmenu .wp-submenu a {
				color: rgba(255, 255, 255, 0.8) !important;
			}

			/* 子菜单链接悬停/焦点状态 */
			#adminmenu .wp-submenu a:focus, 
			#adminmenu .wp-submenu a:hover {
				color: #fff !important;
				background-color: rgba(255, 255, 255, 0.1) !important;
			}

			/* 当前活跃的子菜单项 */
			#adminmenu .wp-submenu li.current a {
				color: #fff !important;
				background-color: rgba(255, 255, 255, 0.2) !important;
			}

			/* 管理工具栏背景 */
			#wpadminbar {
				background-color: <?php echo esc_attr($menu_bg); ?> !important;
			}

			/* 全局链接颜色 */
			a {
				color: <?php echo esc_attr($link); ?>;
			}

			a:hover, a:focus {
				color: <?php echo esc_attr($highlight); ?>;
			}

			/* 主要按钮样式 */
			.wp-core-ui .button-primary {
				background: <?php echo esc_attr($button); ?> !important;
				border-color: <?php echo esc_attr($button); ?> !important;
			}

			.wp-core-ui .button-primary:hover, 
			.wp-core-ui .button-primary:focus {
				background: <?php echo esc_attr($primary_dark_10); ?> !important;
				border-color: <?php echo esc_attr($primary_dark_20); ?> !important;
			}

			/* 登录页面按钮样式 */
			body.login .button-primary {
				background: <?php echo esc_attr($button); ?> !important;
				border-color: <?php echo esc_attr($button); ?> !important;
			}

			body.login .button-primary:hover {
				background: <?php echo esc_attr($primary_dark_10); ?> !important;
				border-color: <?php echo esc_attr($primary_dark_20); ?> !important;
			}

			/* 登录表单输入框焦点样式 */
			body.login input[type="text"]:focus, 
			body.login input[type="password"]:focus {
				border-color: <?php echo esc_attr($link); ?>;
				box-shadow: 0 0 0 1px <?php echo esc_attr($link); ?>;
			}
			<?php endif; ?>

			/* Discuz布局顶部菜单颜色覆盖 */
			.ap-top-menu {
				background-color: <?php echo esc_attr($menu_bg); ?> !important;
			}

			.ap-top-menu-item {
				color: rgba(255, 255, 255, 0.9) !important;
				border-right-color: rgba(255, 255, 255, 0.1) !important;
			}

			.ap-top-menu-item:hover,
			.ap-top-menu-item.current {
				background-color: <?php echo esc_attr($primary); ?> !important;
				color: #fff !important;
			}

			.ap-top-menu-scroll-left,
			.ap-top-menu-scroll-right {
				background-color: <?php echo esc_attr($this->darken($menu_bg, 10)); ?> !important;
			}

			.ap-top-menu-scroll-left {
				border-right-color: rgba(255, 255, 255, 0.1) !important;
			}

			.ap-top-menu-scroll-right {
				border-left-color: rgba(255, 255, 255, 0.1) !important;
			}
		</style>
		<?php
	}

	/**
	 * 将十六进制颜色值变暗指定百分比
	 *
	 * darken('#3858e9', 10) 会返回一个比原色暗10%的颜色值。
	 * 用于生成按钮hover等交互状态的颜色变体。
	 *
	 * @param string $hex      原始十六进制颜色值（如 #3858e9）
	 * @param int    $percent  变暗的百分比（1-100）
	 * @return string 变暗后的十六进制颜色值
	 */
	private function darken($hex, $percent) {
		return $this->adjust_brightness($hex, -$percent);
	}

	/**
	 * 调整十六进制颜色值的亮度
	 *
	 * 将颜色值转换为RGB分量，按百分比调整每个分量的亮度，
	 * 确保结果值在 0-255 范围内，然后转换回十六进制格式。
	 * 支持三字符简写格式（如 #fff）和标准六字符格式（如 #ffffff）。
	 *
	 * @param string $hex     十六进制颜色值（带或不带 # 前缀）
	 * @param int    $percent 亮度调整百分比，负值变暗，正值变亮
	 * @return string 调整后的十六进制颜色值（带 # 前缀）
	 */
	private function adjust_brightness($hex, $percent) {
		// 移除 # 前缀
		$hex = ltrim($hex, '#');
		// 将三字符简写展开为六字符（如 fff → ffffff）
		if (strlen($hex) === 3) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		// 将十六进制转换为十进制RGB分量
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));

		// 按百分比调整亮度，并限制在 0-255 范围内
		$r = max(0, min(255, $r + ($r * $percent / 100)));
		$g = max(0, min(255, $g + ($g * $percent / 100)));
		$b = max(0, min(255, $b + ($b * $percent / 100)));

		// 转换回十六进制格式，确保两位数输出
		return '#' . sprintf('%02x', round($r)) . sprintf('%02x', round($g)) . sprintf('%02x', round($b));
	}
}
