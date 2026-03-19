# Listeners 目录说明

## 目录概述

`Listeners` 目录存放所有 Laravel 事件监听器（Event Listeners），用于响应系统内发生的各种事件。监听器是事件驱动架构的核心组件，负责在特定事件触发时执行相应的业务逻辑。

## 目录结构

```
Listeners/
├── socket/              # WebSocket 相关监听器
│   ├── WebSocketAdmin.php     # 管理员 WebSocket 连接监听
│   ├── WebSocketChatBase.php  # 聊天基础 WebSocket 监听
│   ├── WebSocketEnt.php       # 企业 WebSocket 连接监听
│   ├── WebSocketError.php      # WebSocket 错误监听
│   ├── WebSocketStart.php      # WebSocket 服务启动监听
│   ├── WebSocketStop.php       # WebSocket 服务停止监听
│   └── WebSocketUser.php       # 用户 WebSocket 连接监听
├── swoole/              # Swoole 服务生命周期监听器
│   ├── SwooleShutDown.php      # Swoole 服务关闭监听
│   ├── SwooleStart.php         # Swoole 服务启动监听
│   └── SwooleTask.php          # Swoole 任务执行监听
├── AutoCopy.php         # 审批自动抄送监听器
├── RoleCleaner.php      # Casbin 权限清理器（Swoole 环境专用）
├── SystemCrud.php       # 系统动态 CRUD 缓存清理监听器
└── README.md            # 本文档
```

## 文件说明

### 核心监听器

#### AutoCopy.php
**审批自动抄送监听器**

- **功能**: 处理审批流程中的自动抄送逻辑
- **职责**:
  - 遍历审批流程节点，识别抄送人节点（types=2）
  - 更新抄送人状态并触发抄送提醒任务
  - 推进审批流程到下一节点
  - 触发审批通过任务（`ApprovedTask`）
- **关联服务**:
  - `App\Http\Service\Approve\ApproveApplyService` - 审批申请服务
  - `App\Task\approve\ApprovedTask` - 审批通过异步任务
  - `App\Task\message\BusinessAdoptCcRemind` - 业务抄送提醒任务

#### RoleCleaner.php
**Casbin 权限清理器**

- **功能**: 清理 Swoole 常驻进程中的 Casbin enforcer 实例，避免权限缓存问题
- **职责**:
  - 实现 `CleanerInterface` 接口
  - 在每次请求后清理 `enforcer` 实例
  - 确保权限规则变更即时生效
- **使用场景**: Swoole 常驻进程中，防止旧权限规则缓存导致的安全问题

#### SystemCrud.php
**系统动态 CRUD 缓存清理监听器**

- **功能**: 监听 CRUD 配置变更事件，自动清理相关缓存
- **职责**:
  - 调用 `SystemCrudService::clearCache()` 清理缓存
  - 确保动态 CRUD 规则变更后立即生效
- **关联服务**: `App\Http\Service\Crud\SystemCrudService`

### WebSocket 监听器（socket/）

#### WebSocketStart.php
WebSocket 服务启动监听器，处理连接建立初始化逻辑。

#### WebSocketStop.php
WebSocket 服务停止监听器，处理连接关闭清理逻辑。

#### WebSocketError.php
WebSocket 错误监听器，处理连接过程中的异常错误。

#### WebSocketAdmin.php
管理员 WebSocket 连接监听器，处理后台管理员的消息推送。

#### WebSocketUser.php
用户 WebSocket 连接监听器，处理普通用户的消息推送。

#### WebSocketEnt.php
企业 WebSocket 连接监听器，处理企业级别的消息推送。

#### WebSocketChatBase.php
聊天基础 WebSocket 监听器，处理聊天相关的 WebSocket 消息。

### Swoole 监听器（swoole/）

#### SwooleStart.php
Swoole 服务启动监听器，执行服务启动时的初始化逻辑（如定时器注册、连接池初始化等）。

#### SwooleShutDown.php
Swoole 服务关闭监听器，执行服务关闭时的清理逻辑（如资源释放、数据保存等）。

#### SwooleTask.php
Swoole 异步任务监听器，处理异步任务的执行（如邮件发送、日志记录等耗时操作）。

## 监听器使用规范

### 1. 命名规范
- 类名使用大驼峰命名，如 `AutoCopy`、`RoleCleaner`
- 监听器方法名为 `handle()`，接收事件对象作为参数
- 文件名与类名保持一致

### 2. 注册监听器
监听器通过 `EventServiceProvider` 注册或使用 Laravel 的事件发现功能。

### 3. 异步任务
对于耗时操作，使用 `Hhxsv5\LaravelS\Swoole\Task\Task::deliver()` 交付异步任务：
```php
Task::deliver(new ApprovedTask($applyId));
```

### 4. Swoole 环境特殊处理
- 使用 `CleanerInterface` 清理静态缓存，避免数据脏读
- WebSocket 监听器需处理长连接的生命周期管理
- 任务监听器保持无状态，避免内存泄漏

### 5. 依赖注入
通过 `app()->get()` 或构造函数注入服务类：
```php
$applyService = app()->get(ApproveApplyService::class);
```

## 注意事项

1. **性能优化**: 监听器中的逻辑应保持轻量，耗时操作必须通过异步任务处理
2. **异常处理**: 监听器中的异常应妥善处理，避免影响主流程
3. **测试覆盖**: 关键监听器需编写单元测试，确保业务逻辑正确
4. **日志记录**: 重要操作应记录日志，便于问题排查
5. **版本控制**: 修改监听器时需考虑向后兼容性

## 相关文档

- [事件系统官方文档](https://laravel.com/docs/events)
- [Swoole 监听器文档](https://github.com/hhxsv5/laravel-s)
- [WebSocket 开发指南](../crmeb/socket/)
- [任务队列说明](../Jobs/README.md)
