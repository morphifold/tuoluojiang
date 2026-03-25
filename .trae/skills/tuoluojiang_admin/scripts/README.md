# 脚本工具说明

## 1. 脚本概述

本目录包含陀螺匠管理后台的开发脚本工具，用于辅助开发过程，提高开发效率。

## 2. 脚本列表

| 脚本名称 | 功能描述 | 使用方法 |
|---------|---------|--------|
| create-component.js | 创建组件 | `node create-component.js --name ComponentName` |
| create-page.js | 创建页面 | `node create-page.js --name PageName` |
| lint-check.js | 代码检查 | `node lint-check.js` |

## 3. 脚本使用说明

### 3.1 create-component.js

**功能**：创建新的 Vue 组件

**参数**：
- `--name`：组件名称（必填）
- `--dir`：组件目录（可选，默认：src/components/common）
- `--template`：模板类型（可选，默认：default）

**使用示例**：

```bash
# 创建一个名为 UserList 的组件
node create-component.js --name UserList

# 创建一个名为 UserForm 的组件，放在 src/components/form-common 目录
node create-component.js --name UserForm --dir src/components/form-common
```

### 3.2 create-page.js

**功能**：创建新的页面组件

**参数**：
- `--name`：页面名称（必填）
- `--dir`：页面目录（可选，默认：src/views）
- `--template`：模板类型（可选，默认：default）

**使用示例**：

```bash
# 创建一个名为 UserManagement 的页面
node create-page.js --name UserManagement

# 创建一个名为 OrderDetail 的页面，放在 src/views/order 目录
node create-page.js --name OrderDetail --dir src/views/order
```

### 3.3 lint-check.js

**功能**：检查代码质量，运行 ESLint

**参数**：
- `--fix`：自动修复代码问题（可选）
- `--path`：检查路径（可选，默认：src）

**使用示例**：

```bash
# 检查 src 目录下的代码
node lint-check.js

# 检查 src/components 目录下的代码并自动修复
node lint-check.js --fix --path src/components
```

## 4. 脚本实现原理

### 4.1 模板机制

脚本使用模板文件来生成代码，模板文件位于 `../assets/` 目录：
- `ComponentTemplate.vue`：组件模板
- `PageTemplate.vue`：页面模板

### 4.2 命名转换

脚本会自动处理命名转换：
- PascalCase 转 kebab-case（用于文件名）
- PascalCase 转 camelCase（用于变量名）
- camelCase 转 SNAKE_CASE（用于常量名）

### 4.3 目录创建

如果指定的目录不存在，脚本会自动创建该目录。

## 5. 最佳实践

### 5.1 组件命名

- 组件名使用 PascalCase（如 `UserList`）
- 文件名使用 kebab-case（如 `user-list.vue`）
- 保持组件名称语义化

### 5.2 页面命名

- 页面名使用 PascalCase（如 `UserManagement`）
- 目录结构按照业务模块组织
- 保持页面名称与路由配置一致

### 5.3 代码质量

- 定期运行 lint 检查
- 遵循项目的代码规范
- 保持代码风格一致

## 6. 注意事项

- 脚本会覆盖同名文件，请谨慎使用
- 确保 Node.js 版本 >= 8.9
- 运行脚本时请在项目根目录执行

---

**最后更新时间**：2026-03-25
**适用版本**：陀螺匠管理后台 v1.1.0