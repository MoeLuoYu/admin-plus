# Admin Plus

一款强大的 WordPress 后台美化插件，支持自定义登录页面、管理后台样式、颜色方案、Discuz 风格布局等多种美化功能。

## 功能特性

### 🎨 登录页面自定义

- **自定义 Logo** — 上传品牌 Logo 替换默认 WordPress 标识，支持调整宽度（100-600px）
- **背景设置** — 自定义背景颜色和背景图片（支持 WordPress 媒体库选择）
- **表单样式** — 三种内置风格可选：
  - 现代简洁 — 圆角卡片 + 柔和阴影
  - 经典风格 — 传统 WordPress 直角设计
  - 毛玻璃效果（AI风格） — 半透明模糊背景 + 渐变按钮

### 🖥️ 后台样式管理

- **布局模式** — 支持 WordPress 原生布局和 Discuz 风格横向菜单布局
- **字体选择** — 内置 6 种字体方案（系统默认、系统UI、苹果字体、Segoe UI、中文字体、Inter）
- **页脚自定义** — 自定义后台页脚文字，支持 HTML 标签
- **工具栏控制** — 可移除顶部工具栏中的 WordPress Logo

### 🎭 颜色方案

提供 10 种内置颜色方案，一键切换后台配色：

| 方案 | 说明 |
|------|------|
| 默认 | WordPress 原生颜色 |
| 自定义 | 用户通过颜色选择器自行配置五个颜色值 |
| Modern | WordPress 7.0 新风格 |
| Fresh | 经典蓝色调 |
| Ocean | 海洋绿色调 |
| Sunrise | 暖橙色日出风格 |
| Midnight | 深色午夜风格 |
| Coffee | 咖啡棕色调 |
| Light | 浅色明亮风格 |
| Ectoplasm | 紫绿撞色风格 |
| Blue | 深蓝海洋风 |

自定义方案支持配置：主色调、菜单背景、高亮色、链接色、按钮色。

### 📝 自定义 CSS

- 登录页面和后台管理分别独立的 CSS 编辑区域
- 内置常用 CSS 示例参考
- 安全过滤机制，自动移除危险的 HTML 标签

### 📐 Discuz 风格布局

- 顶部固定水平主菜单栏
- 左侧固定子菜单侧边栏
- 支持鼠标悬停延迟切换子菜单
- 菜单项超出宽度时自动显示滚动按钮，支持鼠标滚轮横向滚动
- 完整的移动端响应式适配

### 🌐 Gravatar 镜像源

- **默认** — WordPress 原生 Gravatar 镜像源
- **cravatar.cn** — 中国 Gravatar 镜像源，支持中文用户
- **gravatar.loli.net** — 中国 Gravatar 镜像源，支持中文用户
- **cdn.v2ex.com/gravatar** — 中国 Gravatar 镜像源，支持中文用户
- **gravatar.com** — 国际 Gravatar 镜像源，支持英文用户
- **自定义** — 用户自定义 Gravatar 镜像域名，支持自定义域名

## 系统要求

| 项目 | 最低版本 |
|------|---------|
| WordPress | 6.8+ |
| PHP | 7.4+ |

## 项目结构

```
admin-plus/
├── admin-plus.php                  # 插件主入口文件
├── includes/
│   ├── class-admin-settings.php       # 设置页面管理（注册、清洗、渲染）
│   ├── class-login-customizer.php     # 登录页面自定义（Logo、背景、表单样式）
│   ├── class-admin-styles.php         # 后台样式输出与 Discuz 布局控制
│   └── class-color-schemes.php        # 颜色方案定义与应用
├── assets/
│   ├── css/
│   │   ├── admin.css                  # 后台管理界面样式
│   │   └── login.css                  # 登录页面样式
│   └── js/
│       ├── admin.js                   # 设置页面交互（颜色选择器、图片上传）
│       └── discuz-layout.js           # Discuz 布局交互逻辑（菜单切换、滚动）
├─── README.md
├─── LICENSE
└─── readme.txt
```

## 许可证

GPL v2 or later — 详见 [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)

## 联系方式

- **MoeLuoYu** — [GitHub](https://github.com/MoeLuoYu)
- 如果在使用过程中遇到任何问题或有任何建议，欢迎联系插件开发者：
- **QQ**：1498640871

## 贡献代码

欢迎开发者为该插件贡献代码。如果你有好的想法或改进方案，请提交 Pull Request，我们会认真审核并合并优秀的贡献。

## 问题反馈与支持

如果你在使用过程中遇到任何问题或有改进建议，请提交 Issues，我们将尽快处理。

## 支持与捐赠

- 如果您觉得本程序做的不错，您可以捐赠支持我！
- **捐赠地址：https://afdian.net/@MoeLuoYu**
- 感谢您对开源项目的支持！
