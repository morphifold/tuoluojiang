# 陀螺匠 API 项目技能文档

## 1. 技能概述

**技能名称**: tuoluojiang_api
**技能版本**: 1.0.0
**技能描述**: 陀螺匠 API 项目开发技能，提供完整的 Laravel 后端开发文档和工具。
**适用场景**: 企业管理系统、OA 系统、API 开发等后端开发。
**触发条件**:
- 当用户询问关于 Laravel 后端开发的问题时
- 当用户需要创建 API 接口时
- 当用户询问关于项目结构、开发规范、数据库等问题时
- 当用户需要使用开发工具或脚本时
- 当用户询问关于构建部署的问题时
- 当用户打开或编辑 `code` 目录下的文件时
- 当用户打开或编辑 PHP 文件（.php）时
- 当用户打开或编辑 SQL 文件（.sql）时

**触发关键词**:
- 陀螺匠
- API 开发
- 后端开发
- Laravel
- 数据库
- 开发规范
- 项目结构
- 常用命令
- 代码生成
- 接口测试

**技能调用示例**:

1. **创建 API 接口**:
   ```bash
   # 使用脚本创建 API 接口
   php .trae/skills/tuoluojiang_api/scripts/create_api.php ModuleName FunctionName ent/route
   ```

2. **检查代码规范**:
   ```bash
   # 运行代码规范检查
   php .trae/skills/tuoluojiang_api/scripts/check_api.php ModuleName
   ```

3. **查询项目信息**:
   - "陀螺匠项目的技术栈是什么？"
   - "如何创建一个新的 API 接口？"
   - "项目的数据库结构是怎样的？"

4. **文件操作触发**:
   - 打开 `code/app/Http/Controller/AdminApi/UserController.php` 文件
   - 编辑 `code/database/migrations/2024_01_01_000000_create_users_table.php` 文件
   - 打开 `code/database/seeders/UserSeeder.sql` 文件

## 2. 文档导航

### 2.1 技能目录结构

```
.trae/skills/tuoluojiang_api/
├── assets/            # 代码模板
├── references/        # 参考文档
├── scripts/           # 脚本工具
└── SKILL.md           # 技能主文档
```

### 2.2 参考文档快速导航

| 文档名称 | 描述 | 路径 |
|---------|------|------|
| 项目概述 | 项目简介、技术栈、核心模块 | [01-项目概述.md](references/01-项目概述.md) |
| 开发规范 | 技术规范、代码风格、安全规范 | [02-开发规范.md](references/02-开发规范.md) |
| 数据库说明 | 数据库表结构、字段说明、表关系 | [03-数据库说明.md](references/03-数据库说明.md) |
| API 接口说明 | API 接口文档、认证授权、响应格式 | [04-API接口说明.md](references/04-API接口说明.md) |
| 目录结构 | 完整目录结构说明、新增模块流程 | [05-目录结构.md](references/05-目录结构.md) |
| 常用命令 | 开发环境、数据库、队列、缓存等常用命令 | [06-常用命令.md](references/06-常用命令.md) |
| 路由说明 | 路由配置、中间件、权限控制 | [07-路由说明.md](references/07-路由说明.md) |
| 事件系统 | 事件触发、监听器、事件处理 | [08-事件系统.md](references/08-事件系统.md) |
| 定时任务 | 任务调度、执行机制、配置方法 | [09-定时任务.md](references/09-定时任务.md) |
| 中间件说明 | 中间件类型、使用方法、自定义中间件 | [10-中间件说明.md](references/10-中间件说明.md) |

## 3. 核心功能

详细的核心功能说明请参考：[01-项目概述.md](references/01-项目概述.md)

## 4. 技术栈

详细的技术栈说明请参考：[01-项目概述.md](references/01-项目概述.md)

## 5. 开发工具

### 5.1 代码生成器

位于 `.trae/skills/tuoluojiang_api/scripts/create_api.php`，可快速生成 API 接口所需的控制器、验证类、模型、Dao、服务类等文件。

### 5.2 规范检查工具

位于 `.trae/skills/tuoluojiang_api/scripts/check_api.php`，检查 API 接口控制器是否符合陀螺匠开发规范。

### 5.3 代码模板

位于 `.trae/skills/tuoluojiang_api/assets/`，包含控制器、Dao、模型、请求验证类、资源控制器、服务类等代码模板。

## 6. 详细文档

| 文档名称 | 描述 | 路径 |
|---------|------|------|
| 项目概述 | 项目简介、技术栈、核心模块 | [01-项目概述.md](references/01-项目概述.md) |
| 开发规范 | 技术规范、代码风格、安全规范 | [02-开发规范.md](references/02-开发规范.md) |
| 数据库说明 | 数据库表结构、字段说明、表关系 | [03-数据库说明.md](references/03-数据库说明.md) |
| API 接口说明 | API 接口文档、认证授权、响应格式 | [04-API接口说明.md](references/04-API接口说明.md) |
| 目录结构 | 完整目录结构说明、新增模块流程 | [05-目录结构.md](references/05-目录结构.md) |
| 常用命令 | 开发环境、数据库、队列、缓存等常用命令 | [06-常用命令.md](references/06-常用命令.md) |
| 路由说明 | 路由配置、中间件、权限控制 | [07-路由说明.md](references/07-路由说明.md) |
| 事件系统 | 事件触发、监听器、事件处理 | [08-事件系统.md](references/08-事件系统.md) |
| 定时任务 | 任务调度、执行机制、配置方法 | [09-定时任务.md](references/09-定时任务.md) |
| 中间件说明 | 中间件类型、使用方法、自定义中间件 | [10-中间件说明.md](references/10-中间件说明.md) |

---

**最后更新时间**: 2026-03-25
**适用版本**: 陀螺匠 API v1.0.0
