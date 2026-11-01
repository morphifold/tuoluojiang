# app

## 目录说明
- **路径**: `./apps/oa-backend/app`
- **用途**: Laravel 应用核心目录，包含所有业务逻辑代码
- **归属模块**: 应用核心层
- **创建时间**: 2026-03-18
- **最后更新时间**: 2026-03-18

## 目录介绍

Laravel 的 App 目录是应用程序的核心目录，包含了应用程序的主要功能。它被视为 Laravel 框架的"心脏"，因为它包含了应用程序的逻辑代码，以及控制应用程序如何运行的关键组件。

## 文件结构
```
├── Console/
├── Constants/
├── Exceptions/
├── Helpers/
├── Http/
├── Jobs/
├── Listeners/
├── Mail/
├── Notifications/
├── Observers/
├── Providers/
├── Task/
├── README.md
└── tree.md
```

## 子目录说明

### Console - 命令行接口
这个子目录包含了应用程序的命令行接口（CLI）命令。开发者可以创建自定义命令，通过命令行界面执行特定的任务。

### Constants - 系统枚举目录
系统级常量、配置常量、业务枚举等统一管理，避免魔法值，提高代码可维护性。

### Exceptions - 异常处理模块
自定义业务异常类，统一异常处理流程，提供友好的错误信息。所有业务异常应继承 `Crmeb\Exceptions\AdminException`。

### Helpers - 全局函数存放目录
可复用的辅助函数集合，如格式化、验证、转换等，避免重复代码。

### Http - HTTP 层核心
此子目录包含了应用程序的控制器、中间件和请求类。控制器负责处理用户请求并返回响应，中间件则用于过滤 HTTP 请求，例如进行身份验证或记录日志。

#### Http 子目录结构：
- `Controller/` - 控制器层，处理 HTTP 请求
  - `AdminApi/` - 管理后台 API 接口
  - `Client/` - 客户端 API 接口（如有）
  - `Mcp/` - MCP（Model Context Protocol）接口
- `Middleware/` - 中间件，过滤和验证请求
- `Model/` - Eloquent 模型，数据表映射
- `Requests/` - 表单请求验证类
- `Service/` - 业务服务层，核心业务逻辑
- `Dao/` - 数据访问对象层，数据库查询封装
- `Kernel.php` - HTTP 内核，管理全局中间件

### Jobs - 队列任务
用于存放异步任务，通过队列驱动执行。适合耗时操作如邮件发送、数据导入等。

### Listeners - 事件监听器
监听器用于监听应用程序中触发的事件，并执行相应的操作。例如用户注册后发送欢迎邮件、订单创建后通知等。

### Mail - 邮件服务
邮件模板类存放目录，定义邮件的 HTML 模板和发送逻辑。

### Notifications - 消息通知
通知类存放目录，定义各种通知方式（数据库、邮件、短信、站内信等）。

### Observers - 模型观察者
用于捕捉 Eloquent 模型的事件（创建、更新、删除），并在事件触发前或后执行相关操作。

### Providers - 服务提供者
服务提供者类存放在这里。Laravel 的 IoC（控制反转）容器的一部分，负责绑定服务到容器中。

### Task - 定时任务
存放 Swoole 定时任务类。定时任务在 `config/laravels.php` 中配置，按指定时间间隔执行。

## 重要文件说明

### 核心文件
| 文件名 | 用途 | 关键类/函数 | 依赖关系 |
|--------|------|-------------|----------|
| README.md | 应用目录说明文档 | - | - |
| tree.md | App 目录结构文档 | - | - |

## 使用示例

### 代码示例
```php
// Console 命令示例
namespace App\Console;

use Illuminate\Console\Command;
use App\Http\Service\UserService;

class SyncUsers extends Command
{
    protected $signature = 'users:sync';
    protected $description = '同步用户数据';

    public function handle()
    {
        $this->info('开始同步用户数据...');
        // 执行同步逻辑
        $this->info('同步完成');
    }
}

// Helpers 使用示例
use App\Helpers\Common;

// 使用全局辅助函数
$name = Common::formatName('john');
$phone = Common::formatPhone('13800138000');
```

### 调用方式
```bash
# 创建命令
php artisan make:command SyncUsers

# 执行命令
php artisan users:sync

# 查看可用命令
php artisan list

# 调用辅助函数
php -r "echo App\Helpers\Common::formatName('test');"
```

## 注意事项

1. **命名规范**: 所有类必须使用正确的命名空间（如 `App\Console`）
2. **类型声明**: 所有 PHP 文件必须声明 `declare(strict_types=1);`
3. **异常处理**: 业务异常使用 `AdminException` 抛出，统一错误响应
4. **依赖注入**: 使用构造函数注入依赖，避免直接使用 `new` 关键字
5. **服务层职责**: Service 层只负责业务逻辑，不直接处理 HTTP 请求
6. **DAO 层职责**: DAO 层只负责数据库查询，避免在控制器中写 SQL

## 更新历史
| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-18 | 1.1 | 完善目录说明、使用示例和注意事项 | AI助手 |
| 2026-03-18 | 1.0 | 自动生成 | 系统 |
