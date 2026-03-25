# 陀螺匠 Skills - AI 开发技能包

🎯 **让 AI 助手更懂陀螺匠！**

## 📖 简介

这是一个专为[陀螺匠](https://tuoluojiang.com)项目开发的 AI 技能包，为 AI 助手提供：

- 📚 完整的开发文档和参考指南
- 💻 可复用的代码模板
- 🚀 代码生成工具
- ✅ 规范检查工具

## ✨ 功能特点

### 📖 10 个详细参考文档
- 项目概述和技术栈
- 开发规范和代码风格
- 数据库表结构说明
- API 接口文档
- 目录结构详解
- 常用命令汇总
- 路由配置说明
- 事件系统
- 定时任务配置
- 中间件使用

### 💻 6 个代码模板
- 控制器模板
- 请求验证类模板
- 模型模板
- Dao 模板
- 服务类模板
- 资源控制器模板

### 🚀 开发工具
- `create_api.php` - 快速生成 API 接口代码
- `check_api.php` - 检查代码规范

## 🔧 安装

### 使用 skills CLI（推荐）

```bash
npx skills add tuoluojiang-tuoluojiang/tuoluojiang-skills
```

### 手动安装

1. 克隆仓库到本地
2. 将内容复制到项目的 `.trae/skills/` 目录

## 🎯 使用场景

- 🤔 不知道陀螺匠的代码规范？
- 📝 需要快速创建 API 接口？
- ✅ 想检查代码是否符合规范？
- 📚 需要查阅开发文档？
- 🔧 开发时需要参考代码模板？

**只需向 AI 助手询问相关问题，技能包会自动提供专业的陀螺匠开发知识！**

## 🛠️ 快速开始

### 生成 API 接口

```bash
cd /path/to/project
php .trae/skills/scripts/create_api.php User user ent/user
```

这将自动生成：
- `app/Http/Controller/AdminApi/User/UserController.php`
- `app/Http/Requests/User/UserRequest.php`
- `app/Http/Model/User.php`
- `app/Http/Dao/UserDao.php`
- `app/Http/Service/User/UserService.php`

### 检查代码规范

```bash
cd /path/to/project
php .trae/skills/scripts/check_api.php User
```

## 📚 技术栈

- **框架**: Laravel 9.x
- **PHP**: 8.0+
- **数据库**: MySQL 5.7+ / 8.0+
- **认证**: JWT
- **授权**: Casbin RBAC

## 📖 文档导航

| 文档 | 说明 |
|------|------|
| [SKILL.md](SKILL.md) | 技能主文档 |
| [references/01-项目概述.md](references/01-项目概述.md) | 项目简介 |
| [references/02-开发规范.md](references/02-开发规范.md) | 开发规范 |
| [references/03-数据库说明.md](references/03-数据库说明.md) | 数据库说明 |
| [references/04-API接口说明.md](references/04-API接口说明.md) | API 文档 |
| [references/05-目录结构.md](references/05-目录结构.md) | 目录结构 |
| [references/06-常用命令.md](references/06-常用命令.md) | 常用命令 |
| [references/07-路由说明.md](references/07-路由说明.md) | 路由配置 |
| [references/08-事件系统.md](references/08-事件系统.md) | 事件系统 |
| [references/09-定时任务.md](references/09-定时任务.md) | 定时任务 |
| [references/10-中间件说明.md](references/10-中间件说明.md) | 中间件 |

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

专有许可证

## 🙏 致谢

[陀螺匠](https://tuoluojiang.com) - AI+低代码双引擎智能办公系统

---

**用心做开源，我们也很需要你的鼓励！右上角 Star🌟，等你点亮！**
