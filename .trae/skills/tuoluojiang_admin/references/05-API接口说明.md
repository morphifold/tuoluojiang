# API 接口说明

## 1. API 概述

陀螺匠管理后台的 API 接口采用 RESTful 风格，使用 Axios 进行封装。API 接口按功能模块分类，存放在 `src/api/` 目录下。

## 2. API 目录结构

```
src/api/
├── README.md            # 说明文件
├── administration.js    # 行政管理
├── business.js          # 业务管理
├── chart.js             # 图表相关
├── chatAi.js            # AI 聊天
├── client.js            # 客户端
├── cloud.js             # 云服务
├── config.js            # 配置管理
├── develop.js           # 开发管理
├── enterprise.js        # 企业管理
├── form.js              # 表单管理
├── program.js           # 程序管理
├── public.js            # 公共接口
├── request.js           # 请求封装
├── setting.js           # 设置管理
├── system.js            # 系统管理
├── systemForm.js        # 系统表单
├── tree.md              # 目录结构说明
└── user.js              # 用户管理
```

## 3. 请求封装

### 3.1 request.js

`request.js` 是 API 请求的核心封装文件，基于 Axios 实现，提供了请求拦截、响应拦截、错误处理等功能。

**主要功能**：
- 统一的基础 URL 配置
- 请求超时设置
- 请求头设置
- 请求参数处理
- 响应数据处理
- 错误处理

**代码示例**：

```javascript
import axios from 'axios'

const request = axios.create({
  baseURL: process.env.VUE_APP_API_BASE_URL,
  timeout: 10000
})

// 请求拦截器
request.interceptors.request.use(
  config => {
    // 添加 token 等认证信息
    const token = localStorage.getItem('token')
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code === 200) {
      return res
    } else {
      // 错误处理
      return Promise.reject(new Error(res.message || 'Error'))
    }
  },
  error => {
    // 错误处理
    return Promise.reject(error)
  }
)

export default request
```

## 4. API 模块说明

### 4.1 user.js - 用户管理

**主要接口**：
- `login` - 用户登录
- `logout` - 用户登出
- `getUserInfo` - 获取用户信息
- `updateUserInfo` - 更新用户信息
- `changePassword` - 修改密码

**代码示例**：

```javascript
import request from './request'

const userApi = {
  // 用户登录
  login(data) {
    return request({
      url: '/api/user/login',
      method: 'post',
      data
    })
  },

  // 用户登出
  logout() {
    return request({
      url: '/api/user/logout',
      method: 'post'
    })
  },

  // 获取用户信息
  getUserInfo() {
    return request({
      url: '/api/user/info',
      method: 'get'
    })
  },

  // 更新用户信息
  updateUserInfo(data) {
    return request({
      url: '/api/user/info',
      method: 'put',
      data
    })
  },

  // 修改密码
  changePassword(data) {
    return request({
      url: '/api/user/password',
      method: 'put',
      data
    })
  }
}

export default userApi
```

### 4.2 system.js - 系统管理

**主要接口**：
- `getSystemConfig` - 获取系统配置
- `updateSystemConfig` - 更新系统配置
- `getMenuList` - 获取菜单列表
- `updateMenu` - 更新菜单
- `getRoleList` - 获取角色列表
- `updateRole` - 更新角色

**代码示例**：

```javascript
import request from './request'

const systemApi = {
  // 获取系统配置
  getSystemConfig() {
    return request({
      url: '/api/system/config',
      method: 'get'
    })
  },

  // 更新系统配置
  updateSystemConfig(data) {
    return request({
      url: '/api/system/config',
      method: 'put',
      data
    })
  },

  // 获取菜单列表
  getMenuList() {
    return request({
      url: '/api/system/menu',
      method: 'get'
    })
  },

  // 更新菜单
  updateMenu(data) {
    return request({
      url: '/api/system/menu',
      method: 'put',
      data
    })
  },

  // 获取角色列表
  getRoleList() {
    return request({
      url: '/api/system/role',
      method: 'get'
    })
  },

  // 更新角色
  updateRole(data) {
    return request({
      url: '/api/system/role',
      method: 'put',
      data
    })
  }
}

export default systemApi
```

### 4.3 form.js - 表单管理

**主要接口**：
- `getFormList` - 获取表单列表
- `getFormDetail` - 获取表单详情
- `createForm` - 创建表单
- `updateForm` - 更新表单
- `deleteForm` - 删除表单
- `getFormData` - 获取表单数据

**代码示例**：

```javascript
import request from './request'

const formApi = {
  // 获取表单列表
  getFormList(params) {
    return request({
      url: '/api/form/list',
      method: 'get',
      params
    })
  },

  // 获取表单详情
  getFormDetail(id) {
    return request({
      url: `/api/form/${id}`,
      method: 'get'
    })
  },

  // 创建表单
  createForm(data) {
    return request({
      url: '/api/form',
      method: 'post',
      data
    })
  },

  // 更新表单
  updateForm(id, data) {
    return request({
      url: `/api/form/${id}`,
      method: 'put',
      data
    })
  },

  // 删除表单
  deleteForm(id) {
    return request({
      url: `/api/form/${id}`,
      method: 'delete'
    })
  },

  // 获取表单数据
  getFormData(formId, params) {
    return request({
      url: `/api/form/${formId}/data`,
      method: 'get',
      params
    })
  }
}

export default formApi
```

### 4.4 enterprise.js - 企业管理

**主要接口**：
- `getEnterpriseList` - 获取企业列表
- `getEnterpriseDetail` - 获取企业详情
- `createEnterprise` - 创建企业
- `updateEnterprise` - 更新企业
- `deleteEnterprise` - 删除企业

**代码示例**：

```javascript
import request from './request'

const enterpriseApi = {
  // 获取企业列表
  getEnterpriseList(params) {
    return request({
      url: '/api/enterprise/list',
      method: 'get',
      params
    })
  },

  // 获取企业详情
  getEnterpriseDetail(id) {
    return request({
      url: `/api/enterprise/${id}`,
      method: 'get'
    })
  },

  // 创建企业
  createEnterprise(data) {
    return request({
      url: '/api/enterprise',
      method: 'post',
      data
    })
  },

  // 更新企业
  updateEnterprise(id, data) {
    return request({
      url: `/api/enterprise/${id}`,
      method: 'put',
      data
    })
  },

  // 删除企业
  deleteEnterprise(id) {
    return request({
      url: `/api/enterprise/${id}`,
      method: 'delete'
    })
  }
}

export default enterpriseApi
```

### 4.5 chart.js - 图表相关

**主要接口**：
- `getChartData` - 获取图表数据
- `getDashboardData` - 获取仪表盘数据

**代码示例**：

```javascript
import request from './request'

const chartApi = {
  // 获取图表数据
  getChartData(type, params) {
    return request({
      url: `/api/chart/${type}`,
      method: 'get',
      params
    })
  },

  // 获取仪表盘数据
  getDashboardData() {
    return request({
      url: '/api/chart/dashboard',
      method: 'get'
    })
  }
}

export default chartApi
```

## 5. API 调用示例

### 5.1 基本调用

```javascript
import userApi from '@/api/user'

export default {
  methods: {
    async login() {
      try {
        const response = await userApi.login({
          username: 'admin',
          password: '123456'
        })
        console.log('登录成功:', response)
      } catch (error) {
        console.error('登录失败:', error)
      }
    }
  }
}
```

### 5.2 带参数调用

```javascript
import formApi from '@/api/form'

export default {
  data() {
    return {
      formList: [],
      pagination: {
        currentPage: 1,
        pageSize: 10,
        total: 0
      }
    }
  },
  methods: {
    async getFormList() {
      try {
        const response = await formApi.getFormList({
          page: this.pagination.currentPage,
          limit: this.pagination.pageSize
        })
        this.formList = response.list
        this.pagination.total = response.total
      } catch (error) {
        console.error('获取表单列表失败:', error)
      }
    }
  },
  mounted() {
    this.getFormList()
  }
}
```

### 5.3 错误处理

```javascript
import systemApi from '@/api/system'

export default {
  methods: {
    async updateSystemConfig() {
      try {
        await systemApi.updateSystemConfig({
          siteName: '陀螺匠管理系统',
          siteLogo: 'https://example.com/logo.png'
        })
        this.$message.success('配置更新成功')
      } catch (error) {
        this.$message.error('配置更新失败: ' + (error.message || '未知错误'))
      }
    }
  }
}
```

## 6. API 规范

### 6.1 命名规范

- API 文件：kebab-case（如 `user-management.js`）
- API 方法：camelCase（如 `getUserList`）
- API 路径：kebab-case（如 `/api/user/list`）

### 6.2 HTTP 方法

- GET：获取资源
- POST：创建资源
- PUT：更新资源
- DELETE：删除资源

### 6.3 响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {
    // 响应数据
  }
}
```

### 6.4 错误处理

- 统一的错误处理机制
- 友好的错误提示
- 日志记录

## 7. 常见问题

### 7.1 跨域问题

**解决方案**：
- 后端设置 CORS 头
- 使用代理服务器
- 前端配置 axios 跨域选项

### 7.2 认证问题

**解决方案**：
- 使用 JWT 令牌
- 在请求头中添加认证信息
- 处理令牌过期

### 7.3 超时问题

**解决方案**：
- 设置合理的超时时间
- 实现请求重试机制
- 优化 API 响应速度

### 7.4 性能问题

**解决方案**：
- 使用缓存
- 优化查询参数
- 减少不必要的请求

## 8. 最佳实践

### 8.1 API 设计

- 遵循 RESTful 规范
- 合理设计 API 路径
- 统一响应格式
- 提供完整的错误处理

### 8.2 前端调用

- 使用 async/await 语法
- 合理处理错误
- 实现 loading 状态
- 优化请求参数

### 8.3 安全性

- 避免暴露敏感信息
- 使用 HTTPS
- 实现 CSRF 保护
- 合理设置权限

---

**最后更新时间**：2026-03-25
**适用版本**：陀螺匠管理后台 v1.1.0