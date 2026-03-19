# Constants 目录说明

## 目录概述
`Constants` 目录用于定义系统中所有的常量和枚举值，包括业务状态码、功能模块标识、通知类型等系统级常量定义。

## 目录结构

### 根目录枚举文件
根目录下存放各业务模块的常量定义文件：

| 文件名 | 说明 |
|--------|------|
| `ApproveEnum.php` | 审批流程相关常量（审批状态、审批类型、请假类型等） |
| `AssessEnum.php` | 绩效评估相关常量 |
| `AttachEnum.php` | 附件管理相关常量 |
| `AttendanceClockEnum.php` | 考勤打卡相关常量 |
| `AttendanceGroupEnum.php` | 考勤组相关常量 |
| `CacheEnum.php` | 缓存键定义常量 |
| `ClientEnum.php` | 客户管理相关常量（合同状态、发票状态、资金状态等） |
| `CloudEnum.php` | 云服务相关常量 |
| `CodeEnum.php` | 代码相关常量 |
| `CommonEnum.php` | 通用常量（状态、排序、功能模块标识等） |
| `CompanyEnum.php` | 企业相关常量 |
| `DailyEnum.php` | 日报/汇报相关常量 |
| `MenuEnum.php` | 菜单权限相关常量 |
| `NoticeEnum.php` | 通知类型相关常量 |
| `RuleEnum.php` | 规则相关常量 |
| `ScheduleEnum.php` | 日程/计划相关常量 |
| `StorageEnum.php` | 存储相关常量 |
| `UserAgentEnum.php` | 用户代理相关常量 |
| `UserEnum.php` | 用户相关常量（用户状态、性别等） |

### 子目录
各子目录按功能模块进一步组织常量定义：

#### `Crud/`
CRUD 低代码平台相关常量定义：

| 文件名 | 说明 |
|--------|------|
| `CrudAggregateEnum.php` | 聚合运算相关常量 |
| `CrudDashboardEnum.php` | 仪表盘相关常量 |
| `CrudEventEnum.php` | 事件类型相关常量 |
| `CrudFormEnum.php` | 表单相关常量 |
| `CrudLogTypeEnum.php` | 日志类型相关常量 |
| `CrudOperatorEnum.php` | 操作符相关常量 |
| `CrudTriggerEnum.php` | 触发器相关常量 |
| `CrudUpdateEnum.php` | 更新操作相关常量 |

#### `CustomEnum/`
自定义枚举目录（目前为空）

#### `ProgramEnum/`
程序枚举目录（目前为空）

#### `System/`
系统级常量定义：

| 文件名 | 说明 |
|--------|------|
| `CategoryEnum.php` | 分类相关常量 |
| `ConfigEnum.php` | 配置相关常量 |

## 常量定义规范

### 类定义
- 所有常量类必须声明为 `final class`
- 必须添加 `declare(strict_types=1);` 严格类型声明
- 命名空间统一为 `namespace App\Constants;`

### 命名规范
- **类名**: 采用大驼峰命名，以 `Enum` 结尾，如 `ApproveEnum`、`ClientEnum`
- **常量名**: 全大写字母 + 下划线分隔，如 `STATUS_DELETE`、`BILL_PASSED`
- **分组常量**: 按功能或类型分组定义，便于维护和查找

### 常量分类

#### 1. 状态类常量
```php
public const STATUS_DELETE = -1;    // 删除状态
public const STATUS_NOMAL = 1;      // 正常状态
public const BILL_PENDING = 0;      // 待审核
public const BILL_PASSED = 1;       // 已通过
```

#### 2. 标识类常量
```php
public const MODULE_ASSESS = 'assess';      // 绩效模块标识
public const MODULE_APPROVE = 'approve';    // 审批模块标识
```

#### 3. 配置类常量
```php
public const APPROVE_URGE_INTERVAL = 300;   // 催办间隔（秒）
public const SORT_ASC = 'asc';              // 正序排序
public const SORT_DESC = 'desc';            // 倒序排序
```

#### 4. 数组配置常量
```php
public const LINK_NOTICE = [
    NoticeEnum::BUSINESS_APPROVAL_TYPE,
    NoticeEnum::BUSINESS_RECALL_TYPE,
    // ...
];
```

## 常量使用示例

### 基础使用
```php
use App\Constants\CommonEnum;

// 获取状态值
$status = CommonEnum::STATUS_NOMAL;

// 获取排序方式
$sort = CommonEnum::SORT_DESC;

// 获取模块标识
$module = CommonEnum::MODULE_APPROVE;
```

### 枚举类型使用（继承自 `MyCLabs\Enum\Enum`）
```php
use App\Constants\UserEnum;

// 获取枚举值
$status = UserEnum::USER_NORMAL;

// 判断状态
if ($userStatus == UserEnum::USER_NOT_ACTIVE) {
    // 处理未激活用户
}
```

### 数组配置常量使用
```php
use App\Constants\ClientEnum;

// 检查通知类型
if (in_array($noticeType, ClientEnum::INVOICE_NOTICE)) {
    // 处理发票通知
}
```

## 注意事项

1. **常量唯一性**: 确保不同枚举类中不存在命名冲突
2. **注释完整**: 每个常量必须有清晰的注释说明其用途
3. **常量引用**: 常量之间可以相互引用（如 `NoticeEnum::BUSINESS_APPROVAL_TYPE`）
4. **扩展性**: 预留常量空间，便于后续扩展
5. **类型安全**: 尽量使用 `const` 而非 `define()`，便于 IDE 提示和类型检查

## 相关文件
- `app/Http/Service/` - 服务层使用常量进行业务逻辑判断
- `app/Http/Controller/` - 控制器层使用常量进行参数验证
- `app/Http/Model/` - 模型层使用常量定义状态映射
