=== Admin Plus ===
Contributors: MoeLuoYu
Tags: admin, login, color-scheme, styles, discuz
Donate link: https://afdian.com/a/MoeLuoYu
Requires at least: 6.8
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

一款强大的 WordPress 后台美化插件，支持自定义登录页面、管理后台样式、颜色方案、Discuz 风格布局等多种美化功能。

== Description ==

Admin Plus 是一款功能丰富的 WordPress 后台美化插件，让您无需编写代码即可全面定制 WordPress 后台的视觉外观。

**登录页面自定义**

* 自定义 Logo — 上传品牌 Logo 替换默认 WordPress 标识，支持调整宽度（100-600px）
* 背景设置 — 自定义背景颜色和背景图片
* 三种表单风格 — 现代简洁（圆角卡片）、经典风格（传统直角）、毛玻璃效果（半透明模糊背景 + 渐变按钮）

**后台样式管理**

* Discuz 风格布局 — 顶部固定水平主菜单 + 左侧子菜单侧边栏，支持鼠标悬停切换、横向滚动、完整的移动端响应式适配
* 字体选择 — 内置 6 种字体方案（系统默认、系统UI、苹果字体、Segoe UI、中文字体、Inter）
* 页脚自定义 — 自定义后台页脚文字，支持 HTML 标签
* 工具栏控制 — 可移除顶部工具栏中的 WordPress Logo
* Gravatar 镜像 — 内置多个 Gravatar 镜像源（Cravatar、LOLI.net、V2EX 等），支持自定义镜像，解决中国大陆地区头像加载问题

**颜色方案**

* 10 种内置颜色方案一键切换：默认、自定义、Modern、Fresh、Ocean、Sunrise、Midnight、Coffee、Light、Ectoplasm、Blue
* 自定义方案支持配置五个颜色值：主色调、菜单背景、高亮色、链接色、按钮色
* 自动生成颜色变体

**自定义 CSS**

* 登录页面和后台管理分别独立的 CSS 编辑区域
* 内置常用 CSS 示例参考，帮助快速上手

== Frequently Asked Questions ==

= 插件会影响网站前端吗？ =

不会。Admin Plus 仅作用于 WordPress 管理后台和登录页面，不会对网站前端产生任何影响。

= 选择 Discuz 布局后部分页面显示异常怎么办？ =

插件已自动在古腾堡编辑器、自定义器、媒体上传弹窗、字体库等页面跳过 Discuz 布局。如果遇到其他兼容性问题，可以随时切换回默认布局。

= 自定义 CSS 需要添加 `<style>` 标签吗？ =

不需要。直接编写纯 CSS 代码即可，插件会自动将其包装在 `<style>` 标签中。

= 卸载插件后设置数据会丢失吗？ =

插件停用时不会删除设置数据。如果需要彻底清除数据，请在停用后手动删除 WordPress 数据库中的 `ap_settings` 选项。

= 颜色方案和 WordPress 自带的颜色方案有什么区别？ =

插件的预设方案会强制覆盖所有用户的颜色方案设置，确保全站后台配色统一。自定义方案则通过 CSS 变量覆盖实现，提供更精细的颜色控制。

== Changelog ==

= 1.0.0 =
* 首次发布
* 支持自定义登录页 Logo、背景颜色、背景图片
* 支持登录表单样式切换（现代简洁、经典风格、毛玻璃）
* 支持 10 种内置颜色方案
* 支持自定义 CSS 代码
* 支持后台页脚文字自定义
* 支持移除顶部工具栏 WordPress Logo
* 支持 Discuz 风格横向菜单布局
* 支持后台字体更改
* 支持 Gravatar 镜像源设置

== Upgrade Notice ==
= 1.0.0 =
Admin Plus 首次发布，支持登录页面自定义、颜色方案切换和后台样式美化。
