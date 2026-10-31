# Task 定时任务目录

## 目录说明

- **路径**: `./code/app/Task`
- **用途**: Swoole 定时任务目录，包含所有基于 LaravelS 的定时任务（T timer）
- **归属模块**: 定时任务层
- **配置位置**: `config/laravels.php` 中的 `timer` 数组
- **创建时间**: 2026-03-18
- **最后更新时间**: 2026-03-19

## 文件结构

```
Task/
├── approve/              # 审批流程相关定时任务
├── assess/               # 绩效考核相关定时任务
├── crud/                 # 低代码表单相关定时任务
├── customer/             # 客户管理相关定时任务
├── daily/                # 日报相关定时任务
├── financial/           # 财务管理相关定时任务
├── folder/               # 云盘相关定时任务
├── frame/                # 组织架构相关定时任务
├── message/              # 消息通知相关定时任务
├── report/               # 报表相关定时任务
├── system/               # 系统相关定时任务
├── user/                 # 用户相关定时任务
└── CompanyInitTask.php   # 企业初始化异步任务
```

## 子目录详细说明

### 1. approve/ - 审批流程定时任务

| 文件名 | 用途说明 |
|--------|----------|
| ApplySavedTask.php | 审批申请保存超时处理 |
| ApprovedTask.php | 审批通过后相关业务处理 |
| ApproveRejectTask.php | 审批拒绝后相关业务处理 |
| ApproveRevokeTask.php | 审批撤回后相关业务处理 |

### 2. assess/ - 绩效考核定时任务

| 文件名 | 用途说明 |
|--------|----------|
| AssessAutoEndTask.php | 绩效考核自动结束任务 |
| AssessTask.php | 绩效考核定时处理任务 |

### 3. crud/ - 低代码表单定时任务

包含 11 个 PHP 文件，用于处理低代码表单的定时业务逻辑。

### 4. customer/ - 客户管理定时任务

包含 9 个 PHP 文件，用于客户相关业务的定时处理。

### 5. daily/ - 日报定时任务

| 文件名 | 用途说明 |
|--------|----------|
| DailyTask.php | 日报相关定时任务（发送提醒等） |

### 6. financial/ - 财务定时任务

| 文件名 | 用途说明 |
|--------|----------|
| FinancialRecordTask.php | 财务记录相关定时任务 |

### 7. folder/ - 云盘定时任务

| 文件名 | 用途说明 |
|--------|----------|
| SpaceDestroyTask.php | 云盘空间销毁定时任务 |

### 8. frame/ - 组织架构定时任务

| 文件名 | 用途说明 |
|--------|----------|
| FrameCensusTask.php | 组织架构统计数据定时任务 |

### 9. message/ - 消息通知定时任务

| 文件名 | 用途说明 |
|--------|----------|
| BusinessAdoptApplyRemind.php | 业务通过申请提醒 |
| BusinessAdoptCcRemind.php | 业务抄送通过提醒 |
| BusinessApprovalRemind.php | 业务审批提醒 |
| BusinessFailRemind.php | 业务失败提醒 |
| BusinessRecallRemind.php | 业务撤回提醒 |
| CloudFileCreateRemind.php | 云文件创建提醒 |
| CloudFileDeleteRemind.php | 云文件删除提醒 |
| CloudFileReadRemind.php | 云文件阅读提醒 |
| DailyReportRemind.php | 日报提醒 |
| MessageSendTask.php | 消息发送任务 |
| NewsRemind.php | 新闻提醒 |
| NoticeMessageTask.php | 通知消息任务 |
| PerfectInfoRemind.php | 完善信息提醒 |
| SmsMessageTask.php | 短信消息任务 |
| StatusChangeTask.php | 状态变更任务 |
| UserAssessAppealRemind.php | 绩效申诉提醒 |
| UserAssessAppealResRemind.php | 绩效申诉结果提醒 |
| UserAssessEndRemind.php | 绩效结束提醒 |
| UserAssessEvaluateRemind.php | 绩效评价提醒 |
| UserAssessReleaseRemind.php | 绩效发布提醒 |

### 10. report/ - 报表定时任务

| 文件名 | 用途说明 |
|--------|----------|
| DailyCreateTask.php | 日报生成定时任务 |

### 11. system/ - 系统定时任务

| 文件名 | 用途说明 |
|--------|----------|
| BatchUpdateDictTask.php | 字典数据批量更新任务 |

### 12. user/ - 用户定时任务

| 文件名 | 用途说明 |
|--------|----------|
| CardDeleteSuccess.php | 名片删除成功通知任务 |
| SaveHistoryTask.php | 历史记录保存任务 |

## 核心文件说明

### CompanyInitTask.php

| 属性 | 说明 |
|------|------|
| 文件名 | CompanyInitTask.php |
| 类名 | `App\Task\CompanyInitTask` |
| 父类 | `Hhxsv5\LaravelS\Swoole\Task\Task` |
| 用途 | 企业初始化异步任务，创建新企业时自动触发，初始化企业基础数据 |
| 调用方式 | 异步任务，通过 `Task::deliver()` 投递 |

**主要初始化内容**：
- 企业基本信息初始化
- 快捷配置初始化
- 绩效评分初始化
- 审批流程初始化
- 假期类型初始化
- 职位体系初始化
- 菜单权限初始化
- 组织架构初始化
- 协议配置初始化
- 字典数据初始化
- 支付方式初始化
- 日程类型初始化
- 账单分类初始化
- 表单分类初始化
- 低代码CRUD初始化
- 客户管理初始化
- 合同管理初始化
- 联系人初始化
- API文档初始化
- 考勤配置初始化
- 系统消息同步

## 定时任务配置

### 在 config/laravels.php 中注册

```php
'timer' => [
    // 审批相关
    App\Task\approve\ApplySavedTask::class,
    App\Task\approve\ApprovedTask::class,
    App\Task\approve\ApproveRejectTask::class,
    App\Task\approve\ApproveRevokeTask::class,
    
    // 绩效考核
    App\Task\assess\AssessAutoEndTask::class,
    App\Task\assess\AssessTask::class,
    
    // 日报
    App\Task\daily\DailyTask::class,
    
    // 财务
    App\Task\financial\FinancialRecordTask::class,
    
    // 云盘
    App\Task\folder\SpaceDestroyTask::class,
    
    // 组织架构
    App\Task\frame\FrameCensusTask::class,
    
    // 消息通知
    App\Task\message\MessageSendTask::class,
    App\Task\message\DailyReportRemind::class,
    // ... 其他消息任务
    
    // 报表
    App\Task\report\DailyCreateTask::class,
    
    // 系统
    App\system\BatchUpdateDictTask::class,
    
    // 用户
    App\Task\user\SaveHistoryTask::class,
],
```

### 定时任务基类要求

定时任务类需要实现以下方法：

```php
class XxxTask
{
    // 定时执行的方法
    public function task()
    {
        // 任务逻辑
    }
    
    // 返回 cron 表达式
    public function crontabString(): string
    {
        return '0 9 * * *'; // 每天9点执行
    }
}
```

## 注意事项

1. **任务注册**: 定时任务必须在 `config/laravels.php` 的 `timer` 数组中注册
2. **时间格式**: 使用 cron 表达式定义执行时间
3. **长任务处理**: 定时任务不应阻塞 Swoole Worker，避免使用 sleep
4. **异常处理**: 任务内部应该捕获异常并记录日志
5. **时间精度**: Swoole 定时器精度为毫秒级，比传统 cron 更精确
6. **日志记录**: 任务的开始、结束和结果都应记录日志
7. **异步任务**: `CompanyInitTask` 继承自 `Task` 类，用于异步执行企业初始化

## 更新历史

| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-19 | 1.2 | 添加所有子目录详细文件说明 | AI助手 |
| 2026-03-18 | 1.1 | 完善目录说明和使用示例 | AI助手 |
| 2026-03-18 | 1.0 | 自动生成 | 系统 |