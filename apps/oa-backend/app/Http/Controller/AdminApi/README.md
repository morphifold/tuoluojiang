# AdminApi - 管理后台API控制器目录

## 目录说明
- **路径**: `./apps/oa-backend/app/Http/Controller/AdminApi`
- **用途**: 管理后台 API 控制器，包含所有业务功能的 API 接口
- **归属模块**: 管理后台 API 层
- **创建时间**: 2026-03-18
- **最后更新时间**: 2026-03-19

## 架构概述
AdminApi目录是陀螺匠企业助手系统的核心API控制器层，采用**前后端分离架构**，基于**Laravel 9.x + Swoole 4**框架开发。所有控制器遵循**RESTful API设计原则**，使用**属性注解路由**，支持**JWT认证**和**Casbin权限控制**。

### 核心特性
1. **高性能**: 基于Swoole 4异步非阻塞架构，支持高并发
2. **安全认证**: JWT Token认证 + Casbin RBAC权限控制
3. **数据隔离**: 自动企业数据隔离，多租户支持
4. **统一响应**: 标准化JSON响应格式
5. **注解路由**: 使用Spatie Route Attributes定义路由
6. **依赖注入**: 自动依赖注入，代码解耦

## 文件结构
```
├── Approve/          # 审批管理接口
├── Assess/           # 评估考核接口
├── Attach/           # 附件管理接口
├── Attendance/       # 考勤管理接口
├── Chat/             # 聊天通信接口
├── Client/           # 客户管理接口 (17个控制器)
├── Cloud/            # 云服务接口
├── Company/          # 企业管理接口 (12个控制器)
├── Config/           # 系统配置接口 (10个控制器)
├── Finance/          # 财务管理接口
├── Forum/            # 论坛社区接口
├── Frame/            # 框架管理接口
├── Message/          # 消息通知接口
├── Module/           # 模块管理接口
├── News/             # 新闻资讯接口
├── Notepad/          # 记事本接口
├── Notice/           # 公告通知接口
├── Open/             # 开放接口 (10个控制器)
├── Package/          # 套餐管理接口
├── Position/         # 职位管理接口
├── Program/          # 项目管理接口
├── Report/           # 报表统计接口
├── Schedule/         # 日程管理接口
├── Storage/          # 存储管理接口
├── System/          # 系统管理接口
├── User/             # 用户管理接口 (9个控制器)
├── AuthController.php    # 认证控制器基类
├── CommonController.php  # 通用控制器
├── LoginController.php   # 登录控制器
├── TestController.php    # 测试控制器
├── README.md            # 目录说明文档
└── tree.md              # 控制器目录结构文档
```

## 核心文件说明

### 1. 基础控制器
| 文件名 | 用途 | 关键特性 | 依赖关系 |
|--------|------|----------|----------|
| **AuthController.php** | API控制器基类 | JWT认证、权限检查、统一响应、请求参数处理 | `tymon/jwt-auth`, `lauthz/enforcer` |
| **CommonController.php** | 通用控制器 | 公共方法、版本信息、问卷调查 | Laravel框架 |
| **LoginController.php** | 登录控制器 | 账号密码登录、短信登录、扫码登录、注册 | `AdminService` |
| **TestController.php** | 测试控制器 | 开发调试、接口测试 | - |

### 2. AuthController 核心功能
```php
// 自动注入的属性
$this->uuid      // 登录用户UUID
$this->isEnt     // 是否登录企业
$this->userInfo  // 登录用户信息
$this->entId     // 当前企业ID
$this->entInfo   // 当前企业信息
$this->enforcer  // Casbin权限管理器

// 请求参数处理
$this->request->postMore($params)    // POST参数安全获取
$this->request->getMore($params)     // GET参数安全获取

// 统一响应
$this->success($data, $message)      // 成功响应 (200)
$this->fail($message, $data)         // 失败响应 (400/500)
```

### 3. 业务模块目录
| 目录 | 控制器数量 | 主要功能 | 典型接口 |
|------|-----------|----------|----------|
| **Client/** | 17 | 客户管理、合同管理、账单管理 | 客户列表、合同创建、发票管理 |
| **Company/** | 12 | 企业管理、部门管理、角色管理 | 企业信息、部门列表、角色权限 |
| **Config/** | 10 | 系统配置、表单配置、字段配置 | 配置获取、表单设计、字段管理 |
| **Open/** | 10 | 开放API、第三方集成、Webhook | 接口调用、数据同步、回调处理 |
| **User/** | 9 | 用户管理、权限管理、个人信息 | 用户列表、权限分配、个人设置 |

## 开发规范

### 1. 控制器创建规范
```php
<?php

declare(strict_types=1);

namespace App\Http\Controller\AdminApi\模块名;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\模块名\服务名Service;
use App\Http\Requests\模块名\请求验证类;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 模块功能说明
 * Class 控制器名.
 */
#[Prefix('ent/模块路径')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])] // 必须的中间件
class 控制器名 extends AuthController
{
    public function __construct(服务名Service $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    #[Get('list', '接口描述')]
    public function index(): mixed
    {
        $params = $this->request->postMore(['字段1', '字段2']);
        $data = $this->service->getList($this->entId, $params);
        return $this->success($data);
    }
}
```

### 2. 路由注解规范
```php
// 基本路由注解
#[Get('path', '接口描述')]
#[Post('path', '接口描述')]
#[Put('path', '接口描述')]
#[Delete('path', '接口描述')]

// 资源路由
#[Resource('/', false, except: ['show', 'index'], names: [
    'create'  => '创建表单',
    'store'   => '创建数据',
    'edit'    => '编辑表单',
    'update'  => '更新数据',
    'destroy' => '删除数据',
], parameters: ['' => 'id'])]

// 中间件配置
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]  // 类级别中间件
#[Middleware(['auth.admin', 'ent.crud'])]             // 方法级别中间件
```

### 3. 请求参数处理
```php
// 推荐：使用postMore/getMore安全获取参数
$params = $this->request->postMore([
    ['name', ''],        // 参数名，默认值
    ['page', 1],
    ['limit', 20],
    ['status', [1, 2]],  // 数组类型
], true);               // true表示参数名带后缀防止SQL注入

// 不推荐：直接获取所有参数（不安全）
$data = $this->request->all();
```

### 4. 响应格式规范
```php
// 成功响应
return $this->success($data);                     // 默认消息
return $this->success($data, '操作成功');         // 自定义消息
return $this->success('操作成功', ['id' => 123]); // 消息+数据

// 失败响应
return $this->fail('参数错误');                    // 默认400错误
return $this->fail('权限不足', [], [], 0);        // tips=0不显示提示
```

## 完整开发示例

### 客户管理控制器示例
```php
<?php

declare(strict_types=1);

namespace App\Http\Controller\AdminApi\Client;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Client\CustomerService;
use App\Http\Service\Config\FormService;
use App\Constants\CustomEnum\CustomEnum;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Resource;

/**
 * 客户管理
 * Class CustomerController.
 */
#[Prefix('ent/client/customer')]
#[Resource('/', false, except: ['show', 'index'], names: [
    'create'  => '客户新增表单',
    'store'   => '新增客户',
    'edit'    => '客户修改表单',
    'update'  => '修改客户',
    'destroy' => '删除客户',
], parameters: ['' => 'id'])]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class CustomerController extends AuthController
{
    public function __construct(CustomerService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 客户列表
     */
    #[Post('list', '客户列表')]
    public function index(): mixed
    {
        $where = $this->request->postMore($this->service->searchField(1));
        return $this->success($this->service->getListByType($where, false));
    }

    /**
     * 客户详情
     */
    #[Get('info/{id}', '客户详情')]
    public function info($id): mixed
    {
        if (!$id) {
            return $this->fail('ID不能为空');
        }
        return $this->success($this->service->getInfo((int)$id, $this->uuid));
    }

    /**
     * 创建客户
     */
    public function store(FormService $service): mixed
    {
        $data = $this->request->postMore($service->getRequestFields(CustomEnum::CUSTOMER));
        $res = $this->service->saveCustomer($data, 2, 0);
        return $this->success('创建成功', ['id' => $res->id]);
    }
}
```

## 调试与测试

### 1. 查看路由列表
```bash
# 查看所有API路由
php artisan route:list --path=admin

# 查看客户相关路由
php artisan route:list --path=customer

# 查看路由详情（带中间件）
php artisan route:list -v
```

### 2. API接口测试
```bash
# 获取Token
curl -X POST http://localhost:20200/ent/user/login \
  -H "Content-Type: application/json" \
  -d '{"account":"admin","password":"password"}'

# 调用客户列表接口
curl -X POST http://localhost:20200/ent/client/customer/list \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"page":1,"limit":20}'

# 调用客户详情接口
curl -X GET "http://localhost:20200/ent/client/customer/info/123" \
  -H "Authorization: Bearer {token}"
```

### 3. 开发调试
```php
// 在控制器中添加调试日志
\Log::info('客户列表请求参数', ['params' => $params, 'entId' => $this->entId]);

// 使用TestController进行接口测试
// 访问: http://localhost:20200/api/test

// 查看日志文件
tail -f apps/oa-backend/storage/logs/laravel-YYYY-MM-DD.log
```

## 错误处理

### 1. 常见错误类型
| 错误码 | 错误类型 | 处理方式 |
|--------|----------|----------|
| 400 | 参数验证失败 | 检查Request验证规则 |
| 401 | 未授权/Token过期 | 重新登录获取Token |
| 403 | 权限不足 | 检查Casbin权限配置 |
| 404 | 接口不存在 | 检查路由注解是否正确 |
| 500 | 服务器内部错误 | 查看日志文件排查问题 |

### 2. 异常处理
```php
try {
    // 业务逻辑
    $result = $this->service->complexOperation($data);
    return $this->success($result);
} catch (\Crmeb\Exceptions\AdminException $e) {
    // 业务异常
    return $this->fail($e->getMessage());
} catch (\Exception $e) {
    // 系统异常
    \Log::error('操作失败', ['error' => $e->getMessage(), 'trace' => $e->getTrace()]);
    return $this->fail('系统错误，请稍后重试');
}
```

## 性能优化建议

### 1. 数据库查询优化
```php
// 推荐：使用Service层进行复杂查询
$data = $this->service->getListWithRelations($params);

// 不推荐：在控制器中直接写复杂SQL
$users = DB::table('users')
    ->join('departments', 'users.department_id', '=', 'departments.id')
    ->where('users.status', 1)
    ->get();
```

### 2. 缓存使用
```php
// 使用Redis缓存热点数据
$data = Cache::remember("customer:{$id}", 3600, function () use ($id) {
    return $this->service->getCustomerDetail($id);
});
```

### 3. 批量操作
```php
// 批量更新代替循环更新
$this->service->batchUpdate($ids, $data);

// 使用队列处理耗时操作
$this->dispatch(new ProcessImportJob($fileData));
```

## 安全规范

### 1. 必须遵循的安全规则
1. **所有API必须继承AuthController** - 确保认证和权限检查
2. **必须配置适当的中间件** - 至少包含`auth.admin`
3. **必须使用Request验证类** - 防止SQL注入和XSS攻击
4. **必须使用postMore/getMore** - 安全的参数获取方式
5. **必须进行数据权限检查** - 使用`$this->entId`进行数据隔离

### 2. 敏感数据处理
```php
// 敏感信息脱敏
$user->phone = substr_replace($user->phone, '****', 3, 4);
$user->id_card = substr_replace($user->id_card, '********', 6, 8);

// 密码加密存储
$user->password = password_hash($password, PASSWORD_DEFAULT);
```

## 目录维护

### 1. 新增控制器步骤
1. 在对应模块目录创建控制器文件
2. 继承AuthController基类
3. 添加路由注解和中间件
4. 创建对应的Request验证类
5. 创建对应的Service服务类
6. 更新README.md文档

### 2. 目录结构更新
当新增或删除控制器时，需要更新：
1. 本README.md文件中的文件结构
2. `tree.md`文件中的目录树
3. 对应的模块说明文档

## 相关文件

### 1. 依赖文件
- `app/Http/Requests/` - 请求验证类
- `app/Http/Service/` - 业务服务层
- `app/Http/Dao/` - 数据访问层
- `app/Http/Model/` - 数据模型
- `crmeb/services/` - 通用服务

### 2. 配置文件
- `config/jwt.php` - JWT认证配置
- `config/casbin.php` - 权限配置
- `config/laravels.php` - Swoole服务器配置

### 3. 路由文件
- `routes/web.php` - Web路由入口
- `app/Providers/RouteServiceProvider.php` - 路由服务提供者

## 更新历史
| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-19 | 2.0 | 完善架构说明、开发规范、示例代码 | AI助手 |
| 2026-03-18 | 1.1 | 完善目录说明和使用示例 | AI助手 |
| 2026-03-18 | 1.0 | 自动生成 | 系统 |

---

**文档维护**: 本目录文档需随代码变更同步更新，确保文档与实际代码一致。
**最后检查**: 2026-03-19
**下次检查**: 2026-04-19