# Jobs 目录说明

## 目录概述

`app/Jobs` 目录包含陀螺匠项目的异步任务和定时任务，基于 **LaravelS (Swoole)** 实现。这些任务用于处理耗时操作、定期执行的业务逻辑以及系统通知等场景。

---

## 任务类型

### 1. CronJob（定时任务）

基于 Swoole 毫秒级定时器的定时任务，替代 Linux Crontab。所有 CronJob 类继承自 `Hhxsv5\LaravelS\Swoole\Timer\CronJob`。

| 任务类 | 功能描述 | 执行频率 | 对应 Task |
|--------|---------|---------|------------|
| `DailyCronJob` | 日报提醒 | 每30秒 | `DailyTask` |
| `AssessCronJob` | 绩效提醒 | 每30秒 | - |
| `AssessEvaluateCronJob` | 自我评价提醒、开启考核任务提醒 | 每30秒 | - |
| `AssessAutoEndCronJob` | 自动结束考核 | 每30秒 | - |
| `AssessAbnormalCronJob` | 考核结束期未创建绩效人员提醒 | 每30秒 | `AssessAbnormalTask` |
| `ContractRemindCronJob` | 合同到期提醒（今日到期、即将到期） | 每30秒 | `ContractRemindTask` |
| `ContractRenewRemindCronJob` | 合同金额续费提醒 | 每30秒 | - |
| `ContractStatusCronJob` | 合同状态变更检查 | 每30秒 | - |
| `CustomerFollowCronJob` | 客户跟进提醒 | 每30秒 | - |
| `CustomerReturnCronJob` | 客户自动退回公海 | 每30秒 | `CustomerReturnTask` |
| `CustomerReturnRemindCronJob` | 客户退回提醒 | 每30秒 | - |
| `ScheduleCronJob` | 日程提醒 | 每30秒 | - |
| `AttendStatisticsCronJob` | 考勤数据创建 | 每30秒 | `AttendStatisticsTask` |
| `CrudEventCronJob` | 低代码实体触发器定时执行 | 每30秒 | `CrudEventTask` |

#### CronJob 标准结构

```php
class XxxCronJob extends CronJob
{
    /**
     * 定义执行频率（毫秒）
     */
    public function interval(): int
    {
        return 30000; // 30秒
    }

    /**
     * 任务执行逻辑
     */
    public function run(): void
    {
        Task::deliver(new XxxTask());
    }
}
```

#### 配置方式

定时任务在 `config/laravels.php` 的 `timer.crons` 配置项中注册：

```php
'timer' => [
    'enable' => env('LARAVELS_TIMER', false),
    'crons' => [
        AssessCronJob::class,
        DailyCronJob::class,
        // ... 更多 CronJob
    ],
],
```

---

### 2. Queue Job（队列任务）

异步队列任务，用于处理耗时操作或需要解耦的业务逻辑。所有队列任务实现 `Illuminate\Contracts\Queue\ShouldQueue` 接口。

#### 2.1 审批流程相关

| 任务类 | 功能描述 |
|--------|---------|
| `ApproveAutoJob` | 审批自动流转处理，处理审批节点自动通过逻辑 |

#### 2.2 考勤管理相关

| 任务类 | 功能描述 |
|--------|---------|
| `AttendStatisticsJob` | 考勤异步执行任务，包括考勤数据生成、缺卡提醒、日报/周报/月报推送等 |
| `AttendanceImportJob` | 考勤数据导入任务 |

#### 2.3 绩效评估相关

| 任务类 | 功能描述 |
|--------|---------|
| `AssessAbnormalJob` | 绩效异常检测任务 |
| `AssessEvaluateJob` | 绩效评估任务 |

#### 2.4 低代码平台相关

| 任务类 | 功能描述 |
|--------|---------|
| `CrudEventJob` | 低代码触发器队列任务 |
| `CrudEventRunGetDataJob` | 低代码触发器数据获取任务 |
| `CrudLogJob` | 低代码实体操作日志任务 |
| `CrudCronActionJob` | 低代码定时动作任务 |
| `FormUpdateJob` | 自定义表单修改后重载规则参数和表单字段 |

#### 2.5 系统管理相关

| 任务类 | 功能描述 |
|--------|---------|
| `SystemRoleJob` | 权限同步任务，更新 Casbin 权限规则 |
| `InitRoleJob` | 初始化角色权限任务 |

#### 2.6 日程管理相关

| 任务类 | 功能描述 |
|--------|---------|
| `ScheduleJob` | 日程定时提醒任务，支持分批处理 |

#### 2.7 通知推送相关

| 任务类 | 功能描述 |
|--------|---------|
| `WebhookJob` | 企业微信/钉钉 Webhook 消息推送任务 |

#### Queue Job 标准结构

```php
class XxxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * 构造函数 - 接收任务参数
     */
    public function __construct(
        private string $param1,
        private int $param2,
    ) {}

    /**
     * 任务执行逻辑
     */
    public function handle(): void
    {
        // 业务逻辑处理
    }
}
```

---

## 核心依赖

### LaravelS 集成

- **包名**: `hhxsv5/laravel-s`
- **功能**: 提供 Swoole 服务器、定时器、异步任务等能力
- **配置文件**: `config/laravels.php`

### 异步任务

CronJob 中通过 `Task::deliver(new XxxTask())` 投递异步任务，实际执行逻辑在 `app/Task/` 目录的 Task 类中实现。

---

## 命名规范

- **CronJob**: 以 `CronJob` 结尾，如 `DailyCronJob`
- **Queue Job**: 以 `Job` 结尾（非 CronJob），如 `ApproveAutoJob`
- **文件名**: 与类名一致，使用大驼峰命名法

---

## 开发指南

### 新增 CronJob

1. 在 `app/Jobs/` 创建新类，继承 `CronJob`
2. 实现 `interval()` 方法，定义执行频率（毫秒）
3. 实现 `run()` 方法，调用相应的 Task 类
4. 在 `config/laravels.php` 的 `timer.crons` 数组中注册

### 新增 Queue Job

1. 在 `app/Jobs/` 创建新类，实现 `ShouldQueue` 接口
2. 使用 Laravel Queue Trait（Dispatchable、InteractsWithQueue、Queueable、SerializesModels）
3. 在构造函数中定义任务参数
4. 实现 `handle()` 方法，编写业务逻辑
5. 使用 `dispatch()` 或 `dispatchNow()` 调度任务

### 错误处理

所有任务应在 `handle()` 方法中添加 `try-catch` 块，记录异常日志：

```php
public function handle(): void
{
    try {
        // 业务逻辑
    } catch (\Throwable $e) {
        Log::error(__CLASS__ . ':' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
```

---

## 调试方法

### 查看 CronJob 执行日志

```bash
# 查看 Swoole 日志
docker logs -f tuoluojiang

# 查看应用日志
docker exec -it tuoluojiang tail -f storage/logs/laravel.log
```

### 查看队列任务

```bash
# 查看队列状态
docker exec -it tuoluojiang php artisan queue:work --queue=default

# 重试失败任务
docker exec -it tuoluojiang php artisan queue:retry all
```

---

## 注意事项

1. **定时任务频率**: 所有 CronJob 默认每 30 秒执行一次，内部应自行判断是否需要执行具体逻辑
2. **任务幂等性**: 队列任务应设计为幂等操作，避免重复执行导致数据不一致
3. **性能优化**: 耗时任务应使用分批处理，避免一次性处理过多数据
4. **日志记录**: 所有任务执行情况应记录详细日志，便于问题排查
5. **异常捕获**: 务必捕获异常并记录，避免任务中断影响系统稳定性

---

## 相关目录

- `app/Task/` - Task 类实现，CronJob 的实际执行逻辑
- `config/laravels.php` - LaravelS 和定时任务配置
- `storage/logs/` - 日志文件目录

---

## 参考资料

- [LaravelS 文档](https://www.laravel-s.com/)
- [Laravel 队列文档](https://laravel.com/docs/9.x/queues)
- [Swoole 定时器](https://wiki.swoole.com/#/timer)
