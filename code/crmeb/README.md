# 系统核心类目录

> 封装了系统通用的类、接口、服务、工具函数等，为整个系统提供基础功能支撑，方便各模块调用。

## 目录结构

```
crmeb/
├── exceptions/           # 自定义异常类
├── interfaces/           # 接口定义
├── options/               # 配置选项
├── services/              # 业务服务层
├── socket/                # WebSocket 相关
├── swoole/                # Swoole 扩展功能
├── traits/                # 通用 Trait
└── utils/                 # 工具类库
```

## 各子目录说明

### 1. exceptions/ - 异常类目录

定义了系统中各类业务异常，统一异常处理机制，便于错误追踪和友好提示。

| 文件名 | 说明 |
|--------|------|
| `AdminException.php` | 后台管理异常 |
| `ApiException.php` | API 接口异常 |
| `ApiRequestException.php` | API 请求参数异常 |
| `AuthException.php` | 认证授权异常 |
| `EntException.php` | 企业相关异常 |
| `HttpServiceExceptions.php` | HTTP 服务异常 |
| `PayException.php` | 支付相关异常 |
| `ServicesException.php` | 服务层异常 |
| `UploadException.php` | 文件上传异常 |
| `WebOfficeException.php` | 在线办公异常 |

### 2. interfaces/ - 接口定义

定义了系统中各层的接口规范，确保代码的可扩展性和可维护性。

| 文件名 | 说明 |
|--------|------|
| `ApiMiddlewareInterface.php` | API 中间件接口 |
| `ApiRequestInterface.php` | API 请求接口 |
| `ConfigInterface.php` | 配置接口 |
| `EntUserInterface.php` | 企业用户接口 |
| `ListenerInterface.php` | 事件监听器接口 |
| `OptionsInterface.php` | 配置选项接口 |
| `ProviderInterface.php` | 服务提供者接口 |
| `ResourceControllerInterface.php` | 资源控制器接口 |
| `ResourceDaoInterface.php` | 资源 DAO 接口 |
| `ResourceServicesInterface.php` | 资源服务接口 |
| `TimeDataInterface.php` | 时间数据接口 |

### 3. services/ - 业务服务层

封装了核心业务逻辑，包含第三方服务集成和通用服务。

#### 主要服务文件
- `AccessTokenServeService.php` - 访问令牌服务
- `AliPayService.php` - 支付宝支付服务
- `ApiResponseService.php` - API 统一响应服务
- `BaiduTranslateService.php` - 百度翻译服务
- `CaptchaService.php` - 验证码服务
- `ConfigService.php` - 配置管理服务
- `EntUserService.php` - 企业用户服务
- `FileService.php` - 文件处理服务
- `FormService.php` - 表单服务
- `GroupDataService.php` - 分组数据服务
- `HttpService.php` - HTTP 请求服务
- `Manager.php` - 服务管理器
- `SendShortLetter.php` - 短信发送服务
- `SmsService.php` - 短信服务
- `SwooleTaskService.php` - Swoole 任务服务
- `UploadService.php` - 文件上传服务

#### 子目录服务
- `ai/` - AI 相关服务
- `expressionLanguage/` - 表达式语言服务
- `phpoffice/` - PHP Office 文档处理服务
- `serve/` - 基础服务
- `synchro/` - 数据同步服务
- `uniPush/` - 统一推送服务
- `upload/` - 上传服务（支持 OSS、COS、七牛云等）
- `wps/` - WPS 在线文档协作服务

### 4. socket/ - WebSocket 相关

实现 WebSocket 服务功能，支持实时通信。

| 文件名 | 说明 |
|--------|------|
| `Parser.php` | 消息解析器 |
| `Ping.php` | 心跳检测 |
| `Room.php` | 房间管理 |
| `WebsocketHandler.php` | WebSocket 处理器 |

### 5. swoole/ - Swoole 扩展功能

基于 Swoole 的高性能扩展功能。

| 目录 | 说明 |
|------|------|
| `command/` | Swoole 命令 |
| `laravels/` | LaravelS 相关 |
| `queue/` | 队列处理 |
| `server/` | 服务器管理 |

### 6. traits/ - 通用 Trait

提供可复用的方法，减少代码重复。

#### 根目录 Trait
- `CateControllerTrait.php` - 分类控制器 Trait
- `ErrorTrait.php` - 错误处理 Trait
- `MiddlewareTrait.php` - 中间件 Trait
- `OptionsTrait.php` - 配置选项 Trait
- `RequestHelpTrait.php` - 请求辅助 Trait
- `ResourceControllerTrait.php` - 资源控制器 Trait
- `SearchTrait.php` - 搜索 Trait
- `TokenTrait.php` - Token 处理 Trait

#### traits/dao/ - 数据层 Trait
- `BatchSearchTrait.php` - 批量搜索
- `GroupDateSearchTrait.php` - 日期分组搜索
- `JoinSearchTrait.php` - 关联搜索
- `ListSearchTrait.php` - 列表搜索
- `PathUpdateTrait.php` - 路径更新
- `TogetherSearchTrait.php` - 联合搜索

#### traits/model/ - 模型 Trait
- `PathAttrTrait.php` - 路径属性
- `TimeDataTrait.php` - 时间数据

#### traits/service/ - 服务层 Trait
- `CategoryTrait.php` - 分类服务
- `MenusRouteTrait.php` - 菜单路由
- `PathServiceTrait.php` - 路径服务
- `RelationAssistTrait.php` - 关联辅助
- `ResourceServiceTrait.php` - 资源服务
- `RolesTrait.php` - 角色管理
- `ServicesTrait.php` - 通用服务

### 7. utils/ - 工具类库

提供常用的工具函数和辅助方法。

| 文件名 | 说明 |
|--------|------|
| `Arr.php` | 数组工具类 |
| `Date.php` | 日期时间工具类 |
| `Disk.php` | 磁盘操作工具类 |
| `DownloadImage.php` | 图片下载工具 |
| `fileVerification.php` | 文件验证工具 |
| `ForMac.php` | Mac 地址工具 |
| `JWTIlluminate.php` | JWT 工具类 |
| `MessageType.php` | 消息类型工具 |
| `Migration.php` - 迁移工具 |
| `PagePath.php` | 分页路径工具 |
| `Regex.php` | 正则表达式工具 |
| `SplitTable.php` | 分表工具 |
| `Statistics.php` | 统计工具 |
| `Str.php` | 字符串工具类 |
| `Task.php` | 任务工具 |
| `Timer.php` | 定时器工具 |

## 使用规范

1. **异常使用**：抛出业务异常时，使用 `crmeb\exceptions\` 下的异常类，如 `throw new AdminException('错误信息')`
2. **接口实现**：实现接口时，确保遵守接口定义的方法签名
3. **服务调用**：通过依赖注入或服务容器获取服务实例，避免直接 new
4. **Trait 使用**：在需要的类中引入 Trait，注意方法命名冲突
5. **工具类**：静态调用工具类方法，如 `Arr::get()`, `Str::snake()`

## 扩展指南

- 添加新异常类：在 `exceptions/` 目录下创建对应的异常类
- 添加新服务：在 `services/` 目录下创建服务类，并实现必要接口
- 添加新 Trait：根据功能选择对应的子目录存放
- 添加工具类：在 `utils/` 目录下创建工具类，保持方法的静态特性
