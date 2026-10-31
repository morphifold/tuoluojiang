# Public 目录说明

## 目录概述

`public/` 目录是 Laravel 应用的 Web 访问入口目录，包含所有前端资源、静态文件和应用入口文件。这是 Web 服务器（如 Nginx、Apache）的根目录，对外提供访问服务。

## 目录结构

```
public/
├── admin/              # 后台管理系统前端资源
│   ├── image/         # 后台图片资源
│   ├── luckysheet/    # LuckySheet 表格编辑器插件
│   ├── system/        # 系统核心前端资源（JS、CSS、字体等）
│   ├── favicon.ico    # 后台图标
│   ├── index.html     # 后台入口页面
│   └── manifest.json  # PWA 清单文件
│
├── chat/              # 聊天功能前端资源
│   ├── assets/        # 聊天功能静态资源（JS、CSS、图片）
│   ├── entry/         # 聊天入口文件
│   ├── favicon.ico    # 聊天图标
│   └── index.html     # 聊天入口页面
│
├── install/           # 系统安装向导资源
│   ├── css/           # 安装向导样式文件
│   ├── images/        # 安装向导图片资源
│   ├── js/            # 安装向导脚本文件
│   ├── *.sql          # 安装所需的 SQL 数据库文件
│   └── install.lock   # 安装锁文件（防止重复安装）
│
├── static/            # 静态资源文件
│   ├── img/           # 静态图片资源（Logo、图标等）
│   └── temp/          # 临时文件目录
│
├── template/          # 模板文件
│   ├── apply.xlsx     # 申请模板
│   ├── demo.docx      # 示例文档模板
│   ├── demo.ppt       # 示例演示文稿模板
│   └── demo.xlsx      # 示例电子表格模板
│
├── uploads/           # 用户上传文件存储目录
│                      # 包含用户上传的图片、文档等文件
│
├── favicon.ico        # 网站默认图标
├── index.php          # Laravel 应用入口文件
├── nginx.htaccess     # Nginx URL 重写配置
└── web.config         # IIS 配置文件
```

## 核心文件说明

### `index.php`
- **作用**: Laravel 应用的唯一入口文件
- **功能**:
  - 加载 Composer 自动加载器
  - 初始化 Laravel 应用
  - 处理 HTTP 请求并返回响应
  - 支持维护模式检测

### `nginx.htaccess`
- **作用**: Nginx URL 重写规则配置
- **功能**: 实现优雅的 URL 结构，将所有请求转发到 `index.php`

### `web.config`
- **作用**: IIS 服务器配置文件
- **功能**: IIS 服务器的 URL 重写和安全配置

### `favicon.ico`
- **作用**: 网站默认图标（浏览器标签页显示）

## 子目录详细说明

### `admin/` - 后台管理系统
后台管理系统的前端资源，采用 Vue 2 + Element UI 构建。

- **`index.html`**: 后台管理入口页面
- **`system/`**: 系统核心资源
  - JavaScript 文件（框架、组件库、业务逻辑）
  - CSS 样式文件
  - 字体文件（`.ttf`, `.woff`）
  - 图标资源
- **`luckysheet/`**: LuckySheet 在线表格编辑器
  - 表格编辑核心库
  - 相关样式和图片资源
- **`manifest.json`**: PWA（渐进式 Web 应用）配置

### `chat/` - 聊天功能
聊天功能的前端资源，支持实时通讯。

- **`index.html`**: 聊天入口页面
- **`assets/`**: 聊天功能静态资源
  - JavaScript 文件（聊天逻辑、WebSocket 连接）
  - CSS 样式文件
  - 聊天图标和表情
- **`entry/`**: 聊天入口相关文件

### `install/` - 系统安装向导
系统首次安装时的引导界面和数据库初始化脚本。

- **`css/`**: 安装向导样式
- **`images/`**: 安装向导图片和动画
- **`js/`**: 安装向导交互脚本
- **`*.sql`**: 数据库初始化文件
  - `city.sql`: 城市数据
  - `config.sql`: 系统配置数据
  - `crud.sql`: CRUD 动态表单数据
  - `defaultData.sql`: 默认演示数据
  - `develop.sql`: 开发环境数据
  - `dict.sql`: 数据字典
- **`install.lock`**: 安装锁文件
  - 存在则表示系统已安装
  - 防止重复执行安装程序

### `static/` - 静态资源
系统全局静态资源文件。

- **`img/`**: 图片资源
  - Logo 图片
  - 功能图标
  - 系统截图
- **`temp/`**: 临时文件存储

### `template/` - 模板文件
系统提供的文档模板，用于快速创建标准化文档。

- **`apply.xlsx`**: 申请表格模板
- **`demo.docx`**: Word 文档示例模板
- **`demo.ppt`**: PowerPoint 演示文稿模板
- **`demo.xlsx`**: Excel 电子表格模板

### `uploads/` - 用户上传文件
用户上传的文件存储目录。

- **存储内容**:
  - 用户头像
  - 附件文件
  - 文档预览文件
  - 图片文件
- **访问权限**: 通过应用接口控制，不支持直接访问

## 配置要点

### Web 服务器配置

#### Nginx 配置示例
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/tuoluojiang/code/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### Apache 配置
启用 `mod_rewrite` 模块，使用 `.htaccess` 文件进行 URL 重写。

### 文件权限

```bash
# 设置目录权限
chmod -R 755 public/

# 设置文件权限
chmod 644 public/index.php
chmod 644 public/favicon.ico

# uploads 目录需要可写
chmod -R 777 public/uploads/
```

### 安全建议

1. **禁止直接访问敏感文件**:
   - `.env` 文件不应位于 `public/` 目录
   - `.sql` 文件应限制访问权限

2. **启用 HTTPS**:
   - 生产环境必须使用 HTTPS
   - 配置 SSL 证书

3. **限制访问**:
   - `uploads/` 目录通过应用程序接口访问
   - 禁止直接执行上传的文件

4. **安装锁文件**:
   - 安装完成后保留 `install/install.lock`
   - 防止恶意重新安装

## 开发注意事项

### 前端开发
- 前端源代码位于 `template/` 目录
- 使用 `npm run build:prod` 构建后，资源会输出到 `public/` 目录
- 开发时使用 `npm run dev` 启动开发服务器（端口 8080）

### 静态资源更新
- 更新静态资源后需要清除缓存
- 可以通过添加版本号或时间戳解决缓存问题
  ```html
  <link rel="stylesheet" href="/css/style.css?v=1.0.0">
  ```

### 文件上传
- 上传文件通过应用接口处理
- 文件存储在 `uploads/` 目录
- 需要配置文件类型、大小限制

### 安装流程
1. 删除 `install/install.lock` 可重新进入安装向导
2. 安装过程中会执行 `*.sql` 文件初始化数据库
3. 安装完成后自动生成 `install.lock`

## 相关文件

- **应用入口**: `code/public/index.php`
- **后端代码**: `code/app/`
- **前端源码**: `template/`
- **配置文件**: `code/config/`
- **日志文件**: `code/storage/logs/`

## 常见问题

### Q: 为什么静态资源无法访问？
A: 检查 Web 服务器配置，确保 `public/` 目录设置为根目录，且文件权限正确。

### Q: 如何更新前端资源？
A: 在 `template/` 目录运行 `npm run build:prod`，构建后的资源会自动输出到 `public/` 目录。

### Q: 安装向导无法访问？
A: 检查 `install/install.lock` 文件是否存在，如需重新安装请删除此文件。

### Q: 上传文件无法访问？
A: 确保通过应用程序接口访问，而非直接 URL 访问。检查 `uploads/` 目录权限。

---

**更新时间**: 2026-03-19
**维护者**: 陀螺匠开发团队
