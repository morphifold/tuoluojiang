# Http

## 目录说明
- **路径**: `./apps/oa-backend/app/Http`
- **用途**: HTTP 层目录，包含控制器、中间件、模型、服务、数据访问对象等
- **归属模块**: HTTP 请求处理层
- **创建时间**: 2026-03-18
- **最后更新时间**: 2026-03-18

## 文件结构
```
├── Contract/              # 接口契约定义（63 个 PHP 文件）
├── Controller/            # 控制器层（131 个 PHP 文件，3 个文档）
│   ├── AdminApi/         # 后台 API 控制器
│   ├── Base/             # 基础控制器
│   └── Mcp/              # MCP 接口控制器
├── Dao/                   # 数据访问对象层（201 个 PHP 文件）
├── Middleware/            # 中间件（23 个 PHP 文件，1 个文档）
├── Model/                 # Eloquent 模型（205 个 PHP 文件，1 个文档）
├── Requests/              # 表单请求验证（85 个 PHP 文件，1 个文档）
├── Service/               # 业务逻辑服务层（209 个 PHP 文件，1 个文档）
└── Kernel.php             # HTTP 内核配置
```

## 重要文件说明

### 核心文件
| 文件名 | 用途 | 关键类/函数 | 依赖关系 |
|--------|------|-------------|----------|
| Kernel.php | HTTP 内核，管理全局中间件和路由 | `Illuminate\Foundation\Http\Kernel` | Laravel |

### 子目录说明

#### Controller/
控制器层目录，负责接收 HTTP 请求并返回响应。

**子目录结构**：
- `AdminApi/` - 后台 API 控制器，所有业务 API 接口都在此目录
- `Base/` - 基础控制器，包含 `AuthController` 等通用基类
- `Mcp/` - MCP（Model Context Protocol）接口控制器

**开发规范**：
- 所有 AdminApi 控制器必须继承 `AuthController`
- 使用属性注解定义路由（`spatie/laravel-route-attributes`）
- 控制器方法只负责调用服务层和返回响应，不包含业务逻辑

#### Service/
业务逻辑服务层，封装核心业务逻辑。

**特点**：
- 每个业务模块对应一个服务类
- 通过依赖注入使用 Dao 层进行数据访问
- 可复用的业务逻辑放在此层

#### Dao/
数据访问对象层，封装数据库查询逻辑。

**特点**：
- 避免在 Service 或 Controller 中直接写 SQL 或复杂查询
- 使用 Eloquent ORM 进行数据库操作
- 提供 `search()` 等通用查询方法

#### Model/
Eloquent 模型层，定义数据表映射和模型关系。

**特点**：
- 每个模型对应一张数据库表
- 定义表名、主键、可填充字段等属性
- 定义模型之间的关联关系（hasOne、hasMany 等）

#### Middleware/
中间件目录，处理请求和响应的过滤逻辑。

**常用中间件**：
- `AuthAdmin` - 后台用户 JWT 认证
- `AuthEnterprise` - 企业权限验证
- `LogEnterprise` - 操作日志记录
- `CheckCrudInfo` - 动态 CRUD 实体验证
- `CheckVersion` - 版本检查

#### Requests/
表单请求验证类，定义请求参数验证规则。

**特点**：
- 每个 API 接口对应一个 Request 类
- 使用场景（scene）区分不同操作的验证规则
- 自动验证失败时返回统一错误响应

#### Contract/
接口契约定义，定义服务层、Dao 层等的接口规范。

**作用**：
- 提高代码可测试性
- 实现依赖倒置原则
- 便于接口抽象和多重实现


## 使用示例

### 代码示例
```php
// 控制器层 (Controller)
namespace App\Http\Controller\AdminApi;
use App\Http\Service\ExampleService;

class ExampleController extends AuthController
{
    protected $service;

    public function __construct(ExampleService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getList($this->entId);
        return $this->success($data);
    }
}

// 服务层 (Service)
namespace App\Http\Service;

class ExampleService
{
    protected $dao;

    public function __construct(ExampleDao $dao)
    {
        $this->dao = $dao;
    }

    public function getList(int $entId)
    {
        return $this->dao->search(['entid' => $entId]);
    }
}

// 数据访问层 (Dao)
namespace App\Http\Dao;

class ExampleDao
{
    public function search(array $where)
    {
        return Example::where($where)->get();
    }
}
```

### 调用方式
```bash
# 创建控制器
php artisan make:controller AdminApi/ExampleController

# 创建验证请求
php artisan make:request ExampleRequest

# 创建中间件
php artisan make:middleware ExampleMiddleware

# 创建模型
php artisan make:model Example
```

### 中间件配置

#### 全局中间件（Kernel.php）
在 `Kernel.php` 的 `$middleware` 数组中配置的中间件会在每个请求中运行：
- `TrustProxies` - 信任代理
- `HandleCors` - 处理跨域请求
- `PreventRequestsDuringMaintenance` - 维护模式拦截
- `ValidatePostSize` - 验证 POST 请求大小
- `FilterStrings` - 过滤字符串
- `ConvertEmptyStringsToNull` - 空字符串转 null
- `StartSession` - 启动 Session
- `LangUage` - 语言处理

#### 路由中间件组（Kernel.php）
在 `$middlewareGroups` 中定义的中间件组：
- `web` - Web 路由组中间件
- `api` - API 路由组中间件
- `uni` - Uni-app 路由组中间件

#### 路由中间件（Kernel.php）
在 `$routeMiddleware` 中注册的中间件可通过中间件别名使用：
- `auth.admin` - 后台认证
- `ent.auth` - 企业认证
- `ent.log` - 企业日志
- `ent.crud` - CRUD 验证

### 控制器示例（使用属性注解）
```php
<?php

declare(strict_types=1);

namespace App\Http\Controller\AdminApi;

use App\Http\Controller\Base\AuthController;
use App\Http\Requests\UserRequest;
use App\Http\Service\UserService;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;

#[Prefix('admin/user')]
#[Middleware('auth.admin')]
class UserController extends AuthController
{
    protected $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    #[Get('list')]
    public function list(UserRequest $request)
    {
        $data = $this->service->getList($request->postMore([
            ['page', 1],
            ['limit', 20],
            'keyword'
        ]), $this->entId);

        return $this->success($data);
    }

    #[Post('create')]
    public function create(UserRequest $request)
    {
        $data = $request->postMore([
            'username',
            'nickname',
            'password',
            'status'
        ]);

        $this->service->create($data, $this->entId, $this->userInfo->id);

        return $this->success('创建成功');
    }
}
```

### 请求验证示例
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Crmeb\Interfaces\RequestInterface;

class UserRequest extends RequestInterface
{
    // 定义验证规则
    protected array $rules = [
        'username' => 'required|string|max:50',
        'nickname' => 'required|string|max:50',
        'password' => 'required|string|min:6',
        'status' => 'required|integer|in:0,1',
    ];

    // 定义验证消息
    protected array $message = [
        'username.required' => '用户名不能为空',
        'password.min' => '密码至少6位',
    ];

    // 定义场景
    protected array $scene = [
        'create' => ['username', 'nickname', 'password', 'status'],
        'update' => ['nickname', 'status'],
    ];

    // 获取验证规则
    public function rules(): array
    {
        // 根据场景返回不同规则
        if (isset($this->scene[$this->scene])) {
            return array_intersect_key($this->rules, array_flip($this->scene[$this->scene]));
        }
        return $this->rules;
    }
}
```

## 注意事项
1. **分层架构**: 严格遵循 Controller → Service → Dao → Model 的分层架构
2. **依赖注入**: 使用构造函数注入依赖，便于测试和维护
3. **数据验证**: 所有请求必须通过 Request 验证类进行验证
4. **权限控制**: API 控制器必须继承 `AuthController` 并配置中间件
5. **数据过滤**: 所有查询必须使用 `$this->entId` 进行企业数据隔离
6. **严格类型**: 所有 PHP 文件必须声明 `declare(strict_types=1);`
7. **命名规范**: 类名使用大驼峰，方法名和变量名使用小驼峰
8. **异常处理**: 业务异常使用 `Crmeb\Exceptions\AdminException` 抛出
9. **响应格式**: 统一使用 `$this->success()` 和 `$this->fail()` 返回响应
10. **参数获取**: 使用 `$request->postMore()` 获取参数，避免使用 `$request->all()`

## 相关文档
- [API 开发指南](../../.codebuddy/skills/tuoluojiang-api-dev/SKILL.md) - 完整的 API 开发流程
- [路由系统说明](../../routes/README.md) - 路由定义和使用说明
- [项目规则](../../.trae/rules/tuoluojiang.md) - 项目整体规范和约定

## 更新历史
| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-19 | 2.0 | 完善目录结构、子目录说明、中间件配置、完整示例 | AI助手 |
| 2026-03-18 | 1.1 | 完善目录说明和使用示例 | AI助手 |
| 2026-03-18 | 1.0 | 自动生成 | 系统 |