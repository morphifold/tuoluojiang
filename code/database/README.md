# 数据库管理目录

> 包含数据库迁移文件（migrations）、数据填充文件（seeders）、数据库结构定义（schema），用于管理数据库结构变更和初始化数据。

## 目录结构

```
database/
├── migrations/           # 数据库迁移文件
│   └── v2.0/            # 2.0版本迁移文件
├── schema/              # 数据库结构文件
├── seeders/             # 数据填充文件
└── README.md
```

## 各子目录说明

### 1. migrations/ - 数据库迁移文件

使用 Laravel 的迁移机制管理数据库结构变更，支持版本控制和团队协作。

#### v2.0/ - 2.0版本迁移

| 文件名 | 说明 | 日期 |
|--------|------|------|
| `2024_11_26_114028_update_dict_data_table.php` | 更新字典数据表 | 2024-11-26 |
| `2024_11_29_143650_update_system_crud.php` | 更新系统 CRUD | 2024-11-29 |
| `2024_11_29_153220_create_system_crud_log_table.php` | 创建系统 CRUD 日志表 | 2024-11-29 |
| `2024_11_29_153227_create_system_crud_comment_table.php` | 创建系统 CRUD 评论表 | 2024-11-29 |
| `2024_12_06_151137_create_system_crud_data_share_table.php` | 创建系统 CRUD 数据共享表 | 2024-12-06 |
| `2024_12_06_151242_create_system_crud_share_table.php` | 创建系统 CRUD 共享表 | 2024-12-06 |
| `2024_12_07_093358_create_system_crud_questionnaire_table.php` | 创建系统 CRUD 问卷表 | 2024-12-07 |
| `2024_12_10_091837_update_system_crud_role_table.php` | 更新系统 CRUD 角色表 | 2024-12-10 |
| `2024_12_10_100101_update_bill_list_table.php` | 更新账单列表表 | 2024-12-10 |
| `2024_12_11_100607_update_system_crud_event_table.php` | 更新系统 CRUD 事件表 | 2024-12-11 |
| `2025_01_09_112044_update_message_template_table.php` | 更新消息模板表 | 2025-01-09 |
| `2025_01_09_112084_update_message_table.php` | 更新消息表 | 2025-01-09 |
| `2025_01_13_182033_create_system_crud_approve_record_table.php` | 创建系统 CRUD 审批记录表 | 2025-01-13 |
| `2025_02_27_172846_create_chat_models_table.php` | 创建聊天模型表 | 2025-02-27 |
| `2025_02_27_173418_create_chat_applications_table.php` | 创建聊天应用表 | 2025-02-27 |
| `2025_02_28_161758_create_chat_record_table.php` | 创建聊天记录表 | 2025-02-28 |
| `2025_02_28_162334_create_chat_history_table.php` | 创建聊天历史表 | 2025-02-28 |
| `2025_03_04_115818_create_chat_app_auth_table.php` | 创建聊天应用授权表 | 2025-03-04 |

### 2. schema/ - 数据库结构文件

| 文件名 | 说明 |
|--------|------|
| `mysql-schema.sql` | MySQL 数据库完整结构 SQL 文件 |

用于导出和导入完整的数据库结构，通常用于：
- 新环境初始化
- 数据库备份和恢复
- 快速搭建测试环境

### 3. seeders/ - 数据填充文件

用于填充初始数据、测试数据或示例数据。

| 文件名 | 说明 |
|--------|------|
| `DatabaseSeeder.php` | 主 Seeder，调用其他 Seeder |
| `SystemCitySeeder.php` | 系统城市数据填充 |
| `SystemCrudSeeder.php` | 系统 CRUD 数据填充 |
| `SystemDictSeeder.php` | 系统字典数据填充 |
| `v2.0.sql` | 2.0版本数据 SQL 文件 |

## 使用规范

### 迁移文件管理

1. **创建迁移文件**
   ```bash
   php artisan make:migration create_table_name --path=database/migrations/v2.0
   ```

2. **执行迁移**
   ```bash
   php artisan migrate
   # 或指定路径
   php artisan migrate --path=database/migrations/v2.0
   ```

3. **回滚迁移**
   ```bash
   php artisan migrate:rollback
   # 回滚指定步数
   php artisan migrate:rollback --step=3
   ```

4. **重置数据库**
   ```bash
   php artisan migrate:refresh
   # 或
   php artisan migrate:fresh
   ```

### Seeder 使用

1. **执行所有 Seeder**
   ```bash
   php artisan db:seed
   ```

2. **执行指定 Seeder**
   ```bash
   php artisan db:seed --class=SystemCitySeeder
   ```

3. **结合迁移和 Seeder**
   ```bash
   php artisan migrate:fresh --seed
   ```

### 命名规范

1. **迁移文件命名**
   - 格式：`YYYY_MM_DD_HHMMSS_description.php`
   - 表创建：`create_表名_table.php`
   - 表更新：`update_表名.php`
   - 表删除：`delete_表名.php`

2. **Seeder 类命名**
   - 格式：`{表名}Seeder`
   - 示例：`SystemCitySeeder`、`UserSeeder`

3. **表名命名**
   - 小写字母 + 下划线
   - 使用复数形式
   - 示例：`system_city`、`user_profiles`

## 最佳实践

1. **版本管理**
   - 按版本号组织迁移文件（如 `v2.0/`、`v2.1/`）
   - 避免修改已发布的迁移文件
   - 新变更创建新的迁移文件

2. **数据安全**
   - 生产环境执行迁移前先备份数据库
   - 测试环境先验证迁移文件
   - 重要数据变更提供回滚方案

3. **团队协作**
   - 迁移文件通过 Git 管理
   - 及时同步数据库结构变更
   - 避免多人同时修改同一表结构

4. **性能优化**
   - 批量操作使用事务
   - 大数据量迁移分批处理
   - 合理使用索引

## 注意事项

1. 执行迁移前确保数据库连接正常
2. 涉及数据删除的迁移务必谨慎
3. 生产环境迁移需经过充分测试
4. Seeder 数据不应包含敏感信息
5. 定期清理过期的迁移文件

## 相关文档

- [Laravel 数据库迁移官方文档](https://laravel.com/docs/migrations)
- [Laravel Seeder 官方文档](https://laravel.com/docs/seeding)
- [数据库规范](../../.trae/rules/database.md)
