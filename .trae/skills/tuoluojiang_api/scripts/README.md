# API 开发工具脚本

本目录包含用于陀螺匠 API 接口开发的辅助工具脚本。

## 脚本列表

### 1. create_api.php - API 接口代码生成器

快速生成 API 接口所需的控制器、验证类、模型、Dao、服务类等文件。

**用法:**
```bash
php create_api.php <模块名> <功能名> [路由前缀]
```

**参数说明:**
- `模块名` - 控制器所属的模块名（首字母大写），如 `User`、`Order`
- `功能名` - 功能名称（首字母大写），如 `User`、`Order`
- `路由前缀` - （可选）API 路由前缀，默认为 `ent/{功能名}`

**示例:**

```bash
# 生成用户管理接口
php create_api.php User User

# 生成订单管理接口（自定义路由前缀）
php create_api.php Order Order ent/order

# 生成产品管理接口（指定模块和功能）
php create_api.php Product Product ent/product
```

**生成的文件:**

1. **控制器** - `app/Http/Controller/AdminApi/{模块}/{功能}Controller.php`
   - 继承 `AuthController`
   - 配置路由属性 `#[Prefix]` 和 `#[Middleware]`
   - 包含 CRUD 标准方法

2. **验证类** - `app/Http/Requests/{模块}/{功能}Request.php`
   - 继承 `ApiValidate`
   - 定义验证规则和错误消息
   - 配置验证场景

3. **模型** - `app/Http/Model/{功能}.php`
   - 继承 `Model`
   - 定义 `$table`、`$fillable` 等属性
   - 包含关联关系

4. **Dao** - `app/Http/Dao/{功能}Dao.php`
   - 封装数据访问逻辑
   - 提供搜索、查询等方法

5. **服务类** - `app/Http/Service/{模块}/{功能}Service.php`
   - 封装业务逻辑
   - 调用 Dao 层进行数据操作

**生成后需要完成的工作:**

1. 编辑控制器，实现具体业务逻辑
2. 配置验证类的规则和错误消息
3. 定义模型的数据表关联
4. 实现服务类的业务逻辑
5. 测试接口功能

---

### 2. check_api.php - API 接口规范检查工具

检查 API 接口控制器是否符合陀螺匠开发规范。

**用法:**
```bash
php check_api.php [控制器路径]
```

**参数说明:**
- 不带参数 - 检查所有 AdminApi 控制器
- `模块名` - 检查指定模块的所有控制器
- `控制器路径` - 检查指定控制器文件

**示例:**

```bash
# 检查所有控制器
php check_api.php

# 检查 User 模块的所有控制器
php check_api.php User

# 检查指定控制器
php check_api.php User/UserController.php
```

**检查项目:**

1. ✓ 严格类型声明 (`declare(strict_types=1);`)
2. ✓ 陀螺匠版权注释
3. ✓ 命名空间规范 (`App\Http\Controller\AdminApi`)
4. ✓ 继承 `AuthController`
5. ✓ 路由属性 `#[Prefix]`
6. ✓ 中间件属性 `#[Middleware]`
7. ✓ 包含 `auth.admin` 中间件
8. ✓ 包含 `ent.auth` 中间件
9. ✓ 包含 `ent.log` 中间件（建议）
10. ✓ 注入服务类（建议）
11. ✓ 使用统一响应方法 (`$this->success()` / `$this->fail()`)

**输出示例:**

```
===========================================
API 接口规范检查
===========================================
检查范围: 5 个控制器

检查: app/Http/Controller/AdminApi/User/UserController.php
  ✓ 符合规范

检查: app/Http/Controller/AdminApi/Order/OrderController.php
  ✗ 发现 2 个问题:
    - 缺少 #[Prefix] 路由属性
    - 中间件缺少 ent.auth

===========================================
✗ 共发现 2 个问题
===========================================
```

---

## 最佳实践

### 1. 开发新接口的完整流程

```bash
# 1. 使用代码生成器创建基础代码
php create_api.php ModuleName FunctionName ent/route

# 2. 编辑生成的文件，实现业务逻辑
# - 编辑控制器
# - 配置验证规则
# - 定义模型字段
# - 实现服务逻辑

# 3. 使用规范检查工具验证代码
php check_api.php ModuleName

# 4. 清除缓存并启动服务
php artisan config:clear
php artisan route:clear
php bin/laravels start -d

# 5. 测试接口
php artisan route:list | grep 'ent/route'
```

### 2. 定期规范检查

```bash
# 定期检查所有控制器是否符合规范
php check_api.php

# 或针对特定模块进行检查
php check_api.php User
php check_api.php Order
```

### 3. 持续集成

可以将 `check_api.php` 集成到 Git Hooks 或 CI/CD 流程中：

```bash
# .git/hooks/pre-commit
php .codebuddy/skills/tuoluojiang-api-dev/scripts/check_api.php

if [ $? -ne 0 ]; then
    echo "代码规范检查失败，请修正后再提交"
    exit 1
fi
```

---

## 常见问题

### Q: 生成的代码需要手动修改什么？

A: 生成器创建的是基础代码框架，需要根据实际需求修改：

- **控制器**: 实现具体的业务逻辑
- **验证类**: 根据表单字段配置验证规则
- **模型**: 定义数据表字段、关联关系
- **Dao**: 根据业务需求定制查询逻辑
- **服务类**: 实现具体的业务规则

### Q: 检查工具报错怎么办？

A: 根据提示的问题逐项修正：

- 缺少属性 → 在控制器类上添加对应属性
- 中间件配置 → 在 `#[Middleware]` 中添加缺失的中间件
- 继承问题 → 确保继承 `AuthController`
- 响应方法 → 使用 `$this->success()` 和 `$this->fail()`

### Q: 如何自定义代码模板？

A: 编辑 `create_api.php` 中的生成函数：

- `generateController()` - 控制器模板
- `generateRequest()` - 验证类模板
- `generateModel()` - 模型模板
- `generateDao()` - Dao 模板
- `generateService()` - 服务类模板

---

## 相关文档

- [API 开发指南](../SKILL.md) - 完整的 API 开发规范
- [路由系统说明](../../../../code/routes/README.md) - 路由定义和使用
- [项目规则](../../../../.trae/rules/tuoluojiang.md) - 项目整体规范

---

## 贡献

如有建议或问题，请联系开发团队。
