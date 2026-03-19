# CODEBUDDY.md
This file provides guidance to CodeBuddy when working with code in this repository.

## 常用命令

### Docker 服务管理
- **`./run.sh start`** – 启动所有 Docker 容器（MySQL、Redis、PHP‑Swoole、Nginx），端口映射为：应用 20300→20200，MySQL 3376→3306，Redis 6389→6379，HTTP 80→80。启动后访问 http://localhost:20300。
- **`./run.sh stop``** – 停止所有容器并清理网络，保留数据卷。
- **`./run.sh restart`** – 重启所有容器，适用于配置更新后。
- **`./run.sh logs [service]`** – 查看指定服务日志；`service` 可为 `mysql`、`redis`、`tuoluojiang`（PHP 应用）、`nginx` 或留空（所有日志）。
- **`./run.sh install`** – 清理数据库、Redis、上传文件等所有数据，然后重新启动容器（相当于重置安装）。
- **`./run.sh build`** – 重新构建 PHP 镜像（基于 `./help/docker-compose/php/Dockerfile`），用于更新 PHP 扩展或项目依赖。
- **`./run.sh delete`** – 删除容器、数据卷及临时文件，仅保留代码和配置文件。
- **`docker-compose up -d`** – 使用 `docker-compose.yml` 启动全部服务（与 `./run.sh start` 等效）。
- **`docker-compose down`** – 停止并删除容器、网络、数据卷。

### 后端开发
- **`php bin/laravels start -d`** – 在容器内启动 LaravelS Swoole 服务器（默认端口 20200）。开发时可启用热重载（`config/laravels.php` 中 `hot_reload` 设为 `true`）。
- **`php artisan cache:clear`** – 清除应用缓存。
- **`php artisan config:clear`** – 清除配置缓存。
- **`php artisan route:clear`** – 清除路由缓存。
- **`php artisan route:list`** – 列出所有已注册路由，便于调试。
- **`php artisan migrate`** – 运行数据库迁移（需先确保数据库服务运行）。
- **`composer install`** – 安装 PHP 依赖；生产环境可加 `--no-dev --optimize-autoloader`。
- **`php -c php-cs-fixer.php`** – 使用项目中预置的 PHP‑CS‑Fixer 配置格式化代码。

### 前端开发
- **`cd template && npm run dev`** – 启动 Vue 开发服务器（默认端口 8080），支持热更新。
- **`cd template && npm run build:prod`** – 构建生产环境的前端资源，输出到 `dist/` 目录。
- **`cd template && npm run lint`** – 运行 ESLint 检查代码规范。
- **`cd template && npm run prettier`** – 使用 Prettier 自动格式化所有前端文件。
- **`cd template && npm run svgo`** – 优化 SVG 图标文件。

### 调试与日志
- **`./run.sh status`** – 查看各容器运行状态及资源使用情况。
- **`docker logs -f tuoluojiang`** – 实时查看 PHP 应用容器的输出日志。
- **`docker exec -it tuoluojiang bash`** – 进入 PHP 容器内部执行命令（如运行 Artisan 命令）。
- 在代码中使用 `\Log::info('message', $data)` 记录调试信息，日志文件位于 `code/storage/logs/`。

---

## 高级架构

### 项目概览
陀螺匠（Tuoluojiang）是一套基于 **Laravel 9.x + Swoole 4** 的高性能企业管理系统，采用前后端分离架构。后端使用 LaravelS（Swoole 驱动）提供 API 服务，前端采用 **Vue 2 + Element UI** 构建管理界面。系统融合 AI 智能体与低代码平台，提供 OA、CRM、项目管理、审批流等全套企业数字化解决方案。

### 目录结构
```
根目录/
├── code/                     # Laravel 后端项目
│   ├── app/
│   │   ├── Console/         # 命令行脚本
│   │   ├── Constants/       # 常量定义
│   │   ├── Exceptions/      # 自定义异常
│   │   ├── Helpers/         # 辅助函数（common.php）
│   │   ├── Http/            # HTTP 相关核心目录
│   │   │   ├── Controller/  # 控制器（AdminApi/ 子目录存放 API 控制器）
│   │   │   ├── Dao/         # 数据访问对象（数据层）
│   │   │   ├── Middleware/  # 中间件（auth.admin、ent.auth、ent.log 等）
│   │   │   ├── Model/       # Eloquent 模型
│   │   │   ├── Requests/    # 表单请求验证类
│   │   │   └── Service/     # 业务逻辑服务层
│   │   ├── Jobs/            # 队列任务
│   │   ├── Listeners/       # 事件监听器
│   │   └── Task/            # 定时任务（与 LaravelS 的 timer 配置对应）
│   ├── crmeb/               # 项目自定义的核心包
│   │   ├── exceptions/      # 业务异常类
│   │   ├── interfaces/      # 接口定义
│   │   ├── services/        # 通用服务（如 ApiResponseService）
│   │   ├── swoole/          # Swoole 相关工具
│   │   ├── traits/          # 复用 Trait（如 ResourceControllerTrait）
│   │   └── utils/           # 工具类
│   ├── config/              # Laravel 配置文件
│   │   └── laravels.php     # Swoole 服务器配置（热重载、定时器、WebSocket 等）
│   ├── routes/              # 路由定义
│   │   ├── web.php          # Web 路由（目前仅加载 MCP 路由）
│   │   ├── mcp.php          # MCP（Model Context Protocol）接口路由
│   │   └── install.php      # 安装向导路由
│   ├── storage/             # 存储目录（日志、缓存、上传文件等）
│   └── public/              # 公共入口（前端资源及安装锁文件）
├── template/                 # Vue 前端项目
│   ├── src/
│   │   ├── api/             # 前端 API 请求封装
│   │   ├── components/      # Vue 组件
│   │   ├── views/           # 页面视图
│   │   └── utils/           # 前端工具函数
│   └── vue.config.js        # Vue CLI 配置（代理、打包优化等）
└── help/docker-compose/     # Docker 相关配置（PHP、Nginx、MySQL、Redis 的配置文件）
```

### 核心架构要点

#### 1. 分层设计
后端采用清晰的分层架构，职责分离：
- **控制器（Controller）**：位于 `app/Http/Controller/AdminApi/`，负责接收请求、调用服务、返回响应。所有 API 控制器继承 `AuthController`，自动集成 JWT 认证与权限检查。
- **服务层（Service）**：位于 `app/Http/Service/` 与 `crmeb/services/`，封装核心业务逻辑，保持控制器轻薄。
- **数据访问对象（Dao）**：位于 `app/Http/Dao/`，封装对数据库的复杂查询，避免在控制器或服务中直接编写冗长的 SQL。
- **模型（Model）**：位于 `app/Http/Model/`，使用 Eloquent ORM 定义数据表映射及关系。
- **请求验证（Request）**：位于 `app/Http/Requests/`，每个 API 接口有对应的验证类，通过场景（scene）区分创建、更新等不同验证规则。

#### 2. 路由与中间件
- **路由定义**：采用 **属性注解**（`spatie/laravel-route-attributes`），在控制器类与方法上通过 `#[Prefix]`、`#[Get]`、`#[Post]` 等注解声明路由，取代传统的 `routes/web.php` 集中定义。现有路由文件仅负责加载 MCP 等特殊路由。
- **中间件栈**：
  - `auth.admin`：JWT 后台用户认证。
  - `ent.auth`：企业权限验证，确保用户属于当前企业。
  - `ent.log`：记录操作日志，用于审计。
  - `ent.crud`：动态 CRUD 实体验证。
  中间件在控制器类上通过 `#[Middleware]` 属性统一配置，也可在单个方法上覆盖。

#### 3. 认证与权限
- **JWT 认证**：使用 `tymon/jwt-auth` 实现 Token 认证，Token 通过 `Authorization: Bearer {token}` 头传递。
- **Casbin 权限**：集成 Casbin 进行细粒度的 RBAC 权限控制，通过 `$this->enforcer` 在控制器中检查具体操作权限。
- **数据权限**：自动注入当前企业 ID（`$this->entId`）与用户 ID（`$this->userInfo->id`），所有查询默认增加 `where('entid', $this->entId)` 条件，实现数据隔离。

#### 4. Swoole 高性能特性
- **LaravelS**：通过 `hhxsv5/laravel-s` 将 Laravel 运行在 Swoole 服务器上，大幅提升并发性能。
- **定时任务**：`config/laravels.php` 中的 `timer` 配置了绩效提醒、日报提醒、合同续费、客户退回等十余个定时任务（详见 `app/Jobs/`）。
- **WebSocket**：配置支持 WebSocket 服务器（`websocket.enable`），处理器为 `crmeb\socket\WebsocketHandler`。
- **热重载**：开发时开启 `hot_reload`，修改代码后自动重启 Worker 进程，无需手动重启服务。

#### 5. 前后端协作
- **API 规范**：所有接口返回统一 JSON 格式：`{"status":200,"message":"ok","data":{},"tips":1}`。成功使用 `$this->success($data)`，失败使用 `$this->fail($message)`。
- **前端代理**：Vue 开发模式下通过 `vue.config.js` 的 `devServer.proxy` 将 `/api` 请求代理到后端容器（`http://tuoluojiang:20200`），解决跨域问题。
- **语言包**：后端语言文件位于 `resources/lang/zh-CN/`，前端语言文件在 `template/src/lang/`，保持前后端提示一致。

#### 6. 数据库与缓存
- **数据库**：MySQL 8.0，表名采用小写加下划线（如 `enterprise_user`），字段名同样规范。
- **缓存**：Redis 作为缓存驱动，同时用于 Session、队列等。配置在 `.env` 中指定连接信息。
- **队列**：使用 Redis 队列，定时任务与耗时操作通过 `app/Jobs/` 中的 Job 类异步处理。

#### 7. 扩展与定制
- **低代码平台**：系统内置表单设计器、流程设计器，可通过可视化配置快速生成业务模块。
- **AI 智能体**：集成 NL2SQL（自然语言转 SQL）技术，实现智能数据查询与报表生成。
- **多端支持**：除 Web 管理后台外，还提供 H5 工作台（移动端）及 PC 客户端的适配。

### 开发注意事项
1. **严格类型**：所有 PHP 文件必须声明 `declare(strict_types=1);`。
2. **权限控制**：任何新增 API 接口必须配置合适的中间件（至少包含 `auth.admin`）。
3. **数据过滤**：使用 `$request->postMore([...])` 获取请求参数，避免直接使用 `$request->all()`。
4. **异常处理**：业务异常使用 `Crmeb\Exceptions\AdminException` 抛出，系统会自动转换为统一错误响应。
5. **代码风格**：遵循项目中预置的 PHP‑CS‑Fixer 与 ESLint（前端）配置，保持代码一致。

### 相关文件参考
- **项目规则**：`.trae/rules/tuoluojiang.md`（项目概述、目录规范、代码风格等）
- **API 开发指南**：`.codebuddy/skills/tuoluojiang-api-dev/SKILL.md`（控制器、验证、路由、权限等完整流程）
- **Docker 构建规范**：`.trae/rules/dockerbuild.md`（镜像构建、分层优化、多架构支持等）
- **README**：`README.md`（产品介绍、功能列表、演示地址等）

### 快速开始
1. 运行 `./run.sh start` 启动所有服务。
2. 访问 http://localhost:20300 进入安装向导。
3. 安装完成后，默认管理员账号：`18888888888`，密码：`888888`（演示数据）。
4. 前端开发：`cd template && npm run dev`，后端代码修改后会自动热重载（需配置 `hot_reload`）。

---
*本文件旨在帮助 AI 助手快速理解项目结构、常用命令与核心架构，以便高效地进行代码开发与维护。*