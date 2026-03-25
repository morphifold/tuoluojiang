# 陀螺匠 API 接口说明

> 版本：1.0.0
> 作者：陀螺匠团队
> 更新日期：2026-03-24

## 4.1 API概述

### 4.1.1 接口规范

陀螺匠 API 基于 RESTful 架构设计，采用 JSON 格式进行数据交换。所有 API 接口均通过 HTTPS 协议访问，确保数据传输的安全性。

#### 基本原则

- 使用 HTTPS 协议进行通信
- 采用 JSON 格式作为请求和响应的数据格式
- 使用 HTTP 状态码表示请求结果
- 支持 CORS 跨域访问
- 使用 JWT Token 进行身份认证

### 4.1.2 基础URL

```
生产环境: https://api.tuoluojiang.com
开发环境: http://localhost:8000
```

### 4.1.3 接口版本

当前 API 版本为 v1，所有接口路径以 `/api/v1` 开头。

```
https://api.tuoluojiang.com/api/v1/users
```

## 4.2 认证授权

### 4.2.1 获取Token

接口地址：`POST /api/v1/auth/login`

请求参数：

```json
{
    "account": "admin",
    "password": "password123"
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 7200
    }
}
```

### 4.2.2 使用Token

在请求头中携带 Token 进行身份认证：

```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### 4.2.3 刷新Token

接口地址：`POST /api/v1/auth/refresh`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 7200
    }
}
```

### 4.2.4 退出登录

接口地址：`POST /api/v1/auth/logout`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": null
}
```

## 4.3 用户管理接口

### 4.3.1 用户列表

接口地址：`GET /api/v1/ent/users`

请求参数：

| 参数名 | 类型 | 必填 | 描述 |
|--------|------|------|------|
| page | int | 否 | 页码，默认1 |
| limit | int | 否 | 每页数量，默认20 |
| keyword | string | 否 | 搜索关键词 |
| status | int | 否 | 用户状态 |

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "list": [
            {
                "id": 1,
                "account": "admin",
                "name": "管理员",
                "phone": "13800138000",
                "status": 1,
                "created_at": "2024-01-01 00:00:00"
            }
        ],
        "pagination": {
            "total": 100,
            "page": 1,
            "limit": 20,
            "total_pages": 5
        }
    }
}
```

### 4.3.2 用户详情

接口地址：`GET /api/v1/ent/users/{id}`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "id": 1,
        "account": "admin",
        "name": "管理员",
        "phone": "13800138000",
        "email": "admin@tuoluojiang.com",
        "avatar": "https://example.com/avatar.jpg",
        "status": 1,
        "roles": ["admin"],
        "created_at": "2024-01-01 00:00:00",
        "updated_at": "2024-01-01 00:00:00"
    }
}
```

### 4.3.3 创建用户

接口地址：`POST /api/v1/ent/users`

请求参数：

```json
{
    "account": "newuser",
    "name": "新用户",
    "phone": "13800138001",
    "email": "newuser@tuoluojiang.com",
    "password": "password123",
    "role_ids": [1, 2]
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "用户创建成功",
    "data": {
        "id": 2
    }
}
```

### 4.3.4 更新用户

接口地址：`PUT /api/v1/ent/users/{id}`

请求参数：

```json
{
    "name": "更新后的用户名",
    "phone": "13800138002",
    "email": "updated@tuoluojiang.com"
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "用户更新成功",
    "data": null
}
```

### 4.3.5 删除用户

接口地址：`DELETE /api/v1/ent/users/{id}`

响应示例：

```json
{
    "code": 200,
    "msg": "用户删除成功",
    "data": null
}
```

## 4.4 聊天接口

### 4.4.1 聊天应用列表

接口地址：`GET /api/v1/ent/chat/applications`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "list": [
            {
                "id": 1,
                "name": "智能助手",
                "content": "你是一个智能助手",
                "models_id": 1,
                "status": 1,
                "created_at": "2024-01-01 00:00:00"
            }
        ]
    }
}
```

### 4.4.2 创建聊天应用

接口地址：`POST /api/v1/ent/chat/applications`

请求参数：

```json
{
    "name": "新聊天应用",
    "content": "你是一个有用的助手",
    "tooltip_text": "有什么可以帮助你的吗？",
    "models_id": 1
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "聊天应用创建成功",
    "data": {
        "id": 2
    }
}
```

### 4.4.3 发送消息

接口地址：`POST /api/v1/ent/chat/send`

请求参数：

```json
{
    "application_id": 1,
    "message": "你好，请介绍一下自己"
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "history_id": 1,
        "message_id": 1,
        "answer": "你好！我是陀螺匠智能助手..."
    }
}
```

### 4.4.4 获取聊天历史

接口地址：`GET /api/v1/ent/chat/history/{id}`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "id": 1,
        "title": "新对话",
        "application": {
            "id": 1,
            "name": "智能助手"
        },
        "records": [
            {
                "id": 1,
                "problem_text": "你好",
                "answer_text": "你好！有什么可以帮助你的吗？",
                "created_at": "2024-01-01 00:00:00"
            }
        ],
        "created_at": "2024-01-01 00:00:00"
    }
}
```

## 4.5 审批接口

### 4.5.1 审批列表

接口地址：`GET /api/v1/ent/approves`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "list": [
            {
                "id": 1,
                "name": "请假申请",
                "icon": "icon-leave",
                "color": "#1890ff",
                "info": "请假申请说明",
                "types": 1,
                "status": 1,
                "sort": 1,
                "created_at": "2024-01-01 00:00:00"
            }
        ]
    }
}
```

### 4.5.2 创建审批申请

接口地址：`POST /api/v1/ent/approves/apply`

请求参数：

```json
{
    "approve_id": 1,
    "info": "请假原因",
    "form_data": {
        "leave_type": "年假",
        "start_time": "2024-01-15 09:00:00",
        "end_time": "2024-01-16 18:00:00",
        "reason": "个人原因"
    }
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "审批申请提交成功",
    "data": {
        "apply_id": 1,
        "number": "AP2024010001"
    }
}
```

### 4.5.3 审批操作

接口地址：`POST /api/v1/ent/approves/{id}/action`

请求参数：

```json
{
    "action": "approve",
    "comment": "同意"
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "审批操作成功",
    "data": null
}
```

### 4.5.4 获取审批详情

接口地址：`GET /api/v1/ent/approves/{id}`

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "id": 1,
        "approve": {
            "id": 1,
            "name": "请假申请"
        },
        "apply": {
            "id": 1,
            "number": "AP2024010001",
            "status": 1,
            "info": "请假原因"
        },
        "forms": [
            {
                "title": "请假类型",
                "value": "年假",
                "required": true
            }
        ],
        "created_at": "2024-01-01 00:00:00"
    }
}
```

## 4.6 考勤接口

### 4.6.1 打卡接口

接口地址：`POST /api/v1/ent/attendance/clock`

请求参数：

```json
{
    "group_id": 1,
    "latitude": 39.908823,
    "longitude": 116.397470,
    "address": "北京市朝阳区xxx",
    "type": 0,
    "remark": "备注信息"
}
```

响应示例：

```json
{
    "code": 200,
    "msg": "打卡成功",
    "data": {
        "clock_id": 1,
        "clock_time": "2024-01-15 09:00:00"
    }
}
```

### 4.6.2 获取考勤记录

接口地址：`GET /api/v1/ent/attendance/records`

请求参数：

| 参数名 | 类型 | 必填 | 描述 |
|--------|------|------|------|
| start_date | string | 是 | 开始日期 |
| end_date | string | 是 | 结束日期 |
| uid | int | 否 | 用户ID |

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "list": [
            {
                "id": 1,
                "group_id": 1,
                "group_name": "总部考勤组",
                "address": "北京市朝阳区xxx",
                "clock_time": "2024-01-15 09:00:00",
                "is_external": 0
            }
        ]
    }
}
```

### 4.6.3 获取考勤统计

接口地址：`GET /api/v1/ent/attendance/statistics`

请求参数：

| 参数名 | 类型 | 必填 | 描述 |
|--------|------|------|------|
| month | string | 是 | 月份，格式：2024-01 |

响应示例：

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "month": "2024-01",
        "total_days": 22,
        "work_days": 20,
        "actual_days": 19,
        "late_count": 1,
        "leave_early_count": 0,
        "absent_count": 1,
        "detail": [
            {
                "date": "2024-01-15",
                "status": "normal",
                "clock_in_time": "09:00:00",
                "clock_out_time": "18:00:00"
            }
        ]
    }
}
```

## 4.7 响应格式

### 4.7.1 成功响应

```json
{
    "code": 200,
    "msg": "success",
    "data": {}
}
```

### 4.7.2 错误响应

```json
{
    "code": 400,
    "msg": "参数错误",
    "errors": {
        "field_name": "错误信息"
    }
}
```

### 4.7.3 状态码说明

| 状态码 | 说明 | 使用场景 |
|--------|------|----------|
| 200 | 成功 | 操作成功 |
| 201 | 已创建 | 资源创建成功 |
| 400 | 请求错误 | 参数错误、验证失败 |
| 401 | 未认证 | 未登录或token无效 |
| 403 | 无权限 | 无权限访问资源 |
| 404 | 未找到 | 资源不存在 |
| 422 | 验证错误 | 请求参数验证失败 |
| 500 | 服务器错误 | 服务器内部错误 |

## 4.8 错误码说明

### 4.8.1 通用错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| 10001 | 参数错误 | 检查请求参数是否正确 |
| 10002 | 认证失败 | 检查token是否有效 |
| 10003 | 无权限 | 检查用户权限 |
| 10004 | 资源不存在 | 检查资源ID是否正确 |
| 10005 | 服务器错误 | 联系管理员处理 |

### 4.8.2 业务错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| 20001 | 用户已存在 | 更换用户名 |
| 20002 | 用户不存在 | 检查用户ID |
| 20003 | 密码错误 | 重新输入密码 |
| 30001 | 审批不存在 | 检查审批ID |
| 30002 | 审批状态错误 | 检查审批当前状态 |
| 40001 | 考勤组不存在 | 检查考勤组ID |
| 40002 | 不在考勤范围内 | 到达考勤地点后重试 |

## 4.9 相关文档

- [项目概述](./01-项目概述.md) - 项目整体介绍
- [开发规范](./02-开发规范.md) - 开发规范说明
- [数据库说明](./03-数据库说明.md) - 数据库表结构
- [目录结构](./05-目录结构.md) - 目录结构说明
- [常用命令](./06-常用命令.md) - 常用命令参考
