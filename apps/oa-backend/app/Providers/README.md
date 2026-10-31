# 服务提供者目录 (Service Providers)

本目录包含陀螺匠项目的主要服务提供者文件，负责在应用程序启动时注册和引导各种服务组件。

## 文件列表

| 文件 | 功能描述 | 核心作用 |
|------|----------|----------|
| **AppServiceProvider.php** | 应用核心服务提供者 | 注册应用级别的单例服务和依赖绑定 |
| **AuthServiceProvider.php** | 认证授权服务提供者 | 定义策略映射和注册认证授权服务 |
| **BroadcastServiceProvider.php** | 广播服务提供者 | 配置广播路由和频道 |
| **CrmebOaServiceProvider.php** | OA系统自定义验证提供者 | 注册OA系统专用的表单验证规则 |
| **EventServiceProvider.php** | 事件服务提供者 | 注册事件监听器和订阅者 |
| **ModuleServiceProvider.php** | 模块服务绑定提供者 | 绑定接口到具体服务实现 |
| **RouteServiceProvider.php** | 路由服务提供者 | 配置路由、中间件和速率限制 |
| **ValidatorServiceProvider.php** | 自定义验证提供者 | 注册全局自定义验证规则 |

## 详细说明

### AppServiceProvider.php
**核心服务注册**
- 绑定 `json` 服务：API响应格式化服务 (`ApiResponseService`)
- 绑定 `group_config` 服务：组合数据服务 (`GroupDataService`)
- 绑定 `config_crmeb` 服务：系统配置服务 (`ConfigService`)
- 绑定 `ent_config_crmeb` 服务：企业配置服务
- 绑定 `enterprise_user` 服务：企业用户服务 (`EntUserService`)

### AuthServiceProvider.php
**认证与授权**
- 定义策略映射：`ApproveApply::class => ApproveApply::class`
- 注册授权策略服务

### BroadcastServiceProvider.php
**实时通信**
- 配置广播路由：`Broadcast::routes()`
- 加载广播频道定义：`routes/channels.php`

### CrmebOaServiceProvider.php
**OA系统验证规则**
- 验证码验证：`captcha_api`
- 手机验证码验证：`verification_api`
- 时间对比验证：`time_contrast_api`
- 密码确认验证：`password_confirm_api`

### EventServiceProvider.php
**事件系统管理**
**Swoole事件监听：**
- `swoole.start` → `SwooleStart` (Swoole启动)
- `swoole.task` → `SwooleTask` (Swoole任务处理)
- `swoole.shutDown` → `SwooleShutDown` (Swoole停止)
- `swoole.workerError` → `WebSocketError` (WebSocket错误)

**WebSocket事件监听：**
- `swoole.websocket.user` → `WebSocketUser` (用户端事件)
- `swoole.websocket.admin` → `WebSocketAdmin` (管理端事件)
- `swoole.websocket.ent` → `WebSocketEnt` (企业端事件)

**业务事件监听：**
- `approve.autoCopy` → `AutoCopy` (审批自动抄送)
- `system.crud` → `SystemCrud` (系统CRUD操作)

### ModuleServiceProvider.php
**依赖注入绑定**
**接口-服务绑定关系：**
- `UserInterface` → `AdminService` (用户管理)
- `ClientInterface` → `CustomerService` (客户管理)
- `LogInterface` → `LogService` (日志服务)
- `CommonInterface` → `CommonService` (公共服务)
- `AssessConfigInterface` → `AssessConfigService` (考核配置)
- `FrameInterface` → `FrameService` (框架服务)
- `CompanyInterface` → `CompanyService` (企业管理)
- `NoticeInterface` → `NoticeService` (通知服务)
- `ScheduleInterface` → `ScheduleService` (日程服务)
- `MenusInterface` → `MenusService` (菜单服务)
- `RolesInterface` → `RolesService` (角色服务)
- `ApproveApplyInterface` → `ApproveApplyService` (审批申请)
- `ContractResourceInterface` → `ContractResourceService` (合同资源)
- `ClientSubscribeInterface` → `ClientSubscribeService` (客户订阅)
- `ClientContractSubscribeInterface` → `ClientContractSubscribeService` (客户合同订阅)
- `ClientBillInterface` → `ClientBillService` (客户账单)
- `ClientFollowInterface` → `ClientFollowService` (客户跟进)
- `ClientContractInterface` → `ContractService` (合同服务)
- `EmployeeTrainInterface` → `EmployeeTrainService` (员工培训)
- `PromotionInterface` → `PromotionService` (晋升管理)
- `PromotionDataInterface` → `PromotionDataService` (晋升数据)
- `HayGroupInterface` → `HayGroupService` (职级组管理)
- `AttendanceGroupInterface` → `AttendanceGroupService` (考勤组管理)
- `AttendanceShiftInterface` → `AttendanceShiftService` (班次管理)
- `ClockRecordInterface` → `ClockRecordService` (打卡记录)
- `RosterCycleInterface` → `RosterCycleService` (排班周期)
- `CalendarConfigInterface` → `CalendarConfigService` (日历配置)
- `AttendanceArrangeInterface` → `AttendanceArrangeService` (考勤安排)

### RouteServiceProvider.php
**路由配置**
**核心路由：**
- `GET q/{unique}` - 问卷调查中转页面
- `GET api/version` - 获取版本信息

**路由组加载：**
- `routes/install.php` - 安装程序路由
- `routes/mcp.php` - MCP接口路由
- `routes/web.php` - Web路由（通过`web`中间件）

**回退路由：**
1. 检查系统是否已安装 (`install.lock`)
2. 根据路径判断访问类型：
   - API路径 → 返回404错误
   - Chat路径 → 返回聊天页面
   - 其他路径 → 返回管理后台页面

**速率限制：**
- API接口限流：180次/分钟/用户或IP

### ValidatorServiceProvider.php
**自定义验证规则**
- 验证码验证：`captcha_api`
- 手机验证码验证：`verification_api`
- 时间对比验证：`time_contrast_api`
- 密码确认验证：`password_confirm_api`
- 文本长度确认：`text_strlen_confirm_api`

## 服务提供者生命周期

### 注册阶段 (`register()`)
- 执行依赖绑定和容器注册
- 适用于：`AppServiceProvider`, `ModuleServiceProvider`

### 引导阶段 (`boot()`)
- 执行路由、事件、验证等配置
- 适用于所有服务提供者

### 启动时机
服务提供者按照`config/app.php`中`providers`数组的顺序加载。

## 新增服务提供者

如需添加新的服务提供者：

1. 在`Providers`目录下创建新文件，例如`CustomServiceProvider.php`
2. 在`config/app.php`的`providers`数组中注册：
   ```php
   'providers' => [
       // ... 现有提供者
       App\Providers\CustomServiceProvider::class,
   ],
   ```
3. 实现`register()`和/或`boot()`方法

## 调试技巧

### 查看已注册的服务提供者
```bash
php artisan route:list --sort=name
```

### 检查服务绑定
```bash
php artisan tinker
>>> app()->bound('json')
=> true
>>> app('json')
=> crmeb\services\ApiResponseService {#4289 ...}
```

## 相关配置

- `config/app.php` - 服务提供者注册配置
- `config/laravels.php` - Swoole服务器配置
- `config/broadcasting.php` - 广播服务配置
- `config/auth.php` - 认证服务配置