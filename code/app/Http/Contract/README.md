# Contract 接口层说明文档

## 目录概述

`Contract` 目录是陀螺匠项目的**接口层**，定义了各业务模块的**契约接口**（Interfaces）。遵循**依赖倒置原则**（Dependency Inversion Principle），通过接口抽象业务逻辑，实现高内聚、低耦合的架构设计。

---

## 目录结构

```
Contract/
├── Approve/              # 审批流程接口
├── Assess/               # 考核评估接口
├── Attendance/           # 考勤管理接口
├── Client/               # 客户管理接口
├── Cloud/                # 云服务接口
├── Common/               # 公共通用接口
├── Company/              # 公司管理接口
├── Config/               # 配置管理接口
├── Finance/              # 财务管理接口
├── Frame/                # 框架基础接口
├── Gather/               # 聚合统计接口
├── News/                 # 新闻资讯接口
├── Notepad/              # 笔记管理接口
├── Notice/               # 通知公告接口
├── Package/              # 扩展包接口
├── Report/               # 报表统计接口
├── Schedule/             # 日程计划接口
├── Storage/              # 存储管理接口
├── System/               # 系统管理接口
└── User/                 # 用户管理接口
```

---

## 子目录说明

### Approve（审批流程）
- `ApproveApplyInterface.php` - 审批申请接口
- `ApproveConfigInterface.php` - 审批配置接口

### Assess（考核评估）
考核评估相关业务接口定义。

### Attendance（考勤管理）
考勤打卡、请假、加班等考勤业务接口。

### Client（客户管理）
客户关系管理（CRM）核心接口：
- `ClientInterface.php` - 客户基本信息接口
- `ClientBillInterface.php` - 客户账单接口
- `ClientContractInterface.php` - 客户合同接口
- `ClientContractSubscribeInterface.php` - 合同订阅接口
- `ClientFollowInterface.php` - 客户跟进接口
- `ClientInvoiceInterface.php` - 客户发票接口
- `ClientLiaisonInterface.php` - 客户联络人接口
- `ClientRemindInterface.php` - 客户提醒接口
- `ClientSubscribeInterface.php` - 客户订阅接口
- `ContractResourceInterface.php` - 合同资源接口

### Cloud（云服务）
云服务集成相关接口。

### Common（公共通用）
系统通用功能接口：
- `CommonInterface.php` - 公共接口
  - 图像验证码（captcha）
  - 短信验证码（smsVerifyKey、smsVerifyCode）
  - 文件上传（uploadFromFile、uploadFromResource、uploadFromUrl）
  - 城市数据树（getCityTree）
  - 文件下载（downloadFileUrl）

### Company（公司管理）
企业公司管理接口：
- `CompanyInterface.php` - 公司信息接口
- `CompanyUserInterface.php` - 企业用户接口
- `EmployeeTrainInterface.php` - 员工培训接口
- `HayGroupInterface.php` - 海氏评估接口
- `PromotionDataInterface.php` - 晋升数据接口
- `PromotionInterface.php` - 晋升管理接口

### Config（配置管理）
系统配置项管理接口。

### Finance（财务管理）
财务收支管理接口：
- `FinanceBillInterface.php` - 财务账单接口

### Frame（框架基础）
框架核心基础设施接口。

### Gather（聚合统计）
数据聚合与统计分析接口。

### News（新闻资讯）
企业新闻、资讯管理接口。

### Notepad（笔记管理）
个人/企业笔记管理接口。

### Notice（通知公告）
系统通知、公告发布接口。

### Package（扩展包）
插件包/扩展模块接口。

### Report（报表统计）
业务报表、数据统计接口。

### Schedule（日程计划）
日程安排、计划任务接口。

### Storage（存储管理）
文件存储、云存储管理接口：
- `StorageInterface.php` - 存储管理接口
- `StorageClassifyInterface.php` - 存储分类接口
- `StorageLogInterface.php` - 存储日志接口

### System（系统管理）
系统基础功能接口：
- `MenusInterface.php` - 菜单管理接口
- `RolesInterface.php` - 角色管理接口
- `PermissionInterface.php` - 权限管理接口
- `AttachInterface.php` - 附件管理接口
- `LogInterface.php` - 日志管理接口

### User（用户管理）
用户账号管理接口：
- `UserInterface.php` - 用户基本信息接口
- `UserResumeInterface.php` - 用户简历接口
- `UserSalaryInterface.php` - 用户薪资接口

---

## 设计原则

### 1. 依赖倒置原则（DIP）
- 高层模块不应依赖低层模块，二者都应依赖其抽象（接口）
- Service 层依赖 Contract 接口，而非直接依赖具体实现
- 便于单元测试和模块替换

### 2. 接口隔离原则（ISP）
- 接口应当职责单一，避免"胖接口"
- 每个接口专注于特定的业务领域

### 3. 里氏替换原则（LSP）
- 任何实现接口的类都可以替换接口类型使用
- 保证系统的可扩展性和稳定性

---

## 使用规范

### 接口命名规范
- **接口文件名**：`{ModuleName}Interface.php`
- **接口类名**：`{ModuleName}Interface`
- **命名空间**：`App\Http\Contract\{SubNamespace}`

**示例**：
```php
namespace App\Http\Contract\Client;

interface ClientInterface {}
```

### 实现类命名规范
- 实现类文件位于 `app/Http/Service/{Module}/` 目录
- 实现类名通常为 `{ModuleName}Service` 或类似命名

**示例**：
```php
namespace App\Http\Service\Client;

use App\Http\Contract\Client\ClientInterface;

class ClientService implements ClientInterface {
    // 实现接口方法
}
```

### 方法定义规范
- **命名**：使用驼峰法（首字母小写），清晰表达方法功能
- **参数**：明确参数类型，使用 PHP 类型声明
- **返回值**：明确返回类型，使用 PHP 类型声明

**示例**：
```php
public function getClientById(int $id): array;

public function createClient(array $data): bool;

public function getCaptcha(): array;
```

---

## 与其他层的关系

```
Controller（控制器层）
    ↓ 调用
Service（服务层） ────→ 实现 ────→ Contract（接口层）
    ↓ 依赖
Dao（数据访问层）
    ↓ 依赖
Model（模型层）
```

**数据流向**：
1. Controller 接收 HTTP 请求
2. Controller 调用 Service（依赖接口类型）
3. Service 实现接口定义的业务方法
4. Service 调用 Dao 访问数据
5. Dao 操作 Model 与数据库交互
6. 数据按相反路径返回

---

## 依赖注入示例

### 在 Service 中实现接口
```php
namespace App\Http\Service\Client;

use App\Http\Contract\Client\ClientInterface;

class ClientService implements ClientInterface
{
    public function getClientById(int $id): array
    {
        // 业务逻辑实现
    }
}
```

### 在 Controller 中注入接口
```php
namespace App\Http\Controller\AdminApi\Client;

use App\Http\Contract\Client\ClientInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ClientController
{
    protected ClientInterface $clientService;

    public function __construct(ContainerInterface $container)
    {
        $this->clientService = $container->get(ClientInterface::class);
    }

    public function getClient(int $id): JsonResponse
    {
        $data = $this->clientService->getClientById($id);
        return $this->success($data);
    }
}
```

---

## 开发注意事项

1. **新增接口**：在对应的子目录下创建 `{ModuleName}Interface.php` 文件
2. **接口方法**：使用 PHP 类型声明严格约束参数和返回值
3. **文档注释**：为每个接口方法添加 PHPDoc 注释，说明功能、参数、返回值
4. **实现对应**：确保每个接口都有对应的 Service 实现类
5. **单元测试**：基于接口编写单元测试，便于 Mock 和隔离测试
6. **版本控制**：接口变更时需考虑向后兼容性

---

## 代码模板

### 接口模板
```php
<?php

declare(strict_types=1);

namespace App\Http\Contract\{SubNamespace};

/**
 * {功能描述}接口
 */
interface {ModuleName}Interface
{
    /**
     * {方法功能描述}
     * @param {参数类型} ${参数名} {参数说明}
     * @return {返回类型} {返回值说明}
     */
    public function {methodName}({参数类型} ${参数名}): {返回类型};
}
```

---

## 相关文档

- [Service 服务层说明](../Service/README.md)
- [Controller 控制器说明](../Controller/README.md)
- [Dao 数据访问层说明](../Dao/README.md)
- [项目目录说明规则](../../../../.trae/rules/directory-documentation.mdc)

---

**维护说明**：本文档由 CodeBuddy 自动维护，当目录结构发生变化时会自动更新。最后更新时间：2026-03-19
