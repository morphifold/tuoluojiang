# Config 目录说明

## 目录说明
- **路径**: `code/config/`
- **用途**: Laravel 应用配置文件目录，存储所有应用配置和第三方服务配置
- **归属模块**: Laravel 核心框架 + 陀螺匠业务配置
- **创建时间**: 2016-01-01
- **最后更新时间**: 2026-03-19

## 文件结构
```
config/
├── ajcaptcha.php                # AJCaptcha 行为验证码配置
├── app.php                     # Laravel 应用核心配置
├── auth.php                    # 认证相关配置
├── broadcasting.php             # 广播服务配置
├── cache.php                   # 缓存服务配置
├── captcha.php                 # 验证码配置
├── company.php                 # 企业信息配置
├── cors.php                    # 跨域资源共享配置
├── database.php                # 数据库连接配置
├── filesystems.php             # 文件系统配置（OSS/COS等）
├── hashing.php                 # 哈希算法配置
├── ide-helper.php              # IDE 辅助工具配置
├── jwt.php                     # JWT 认证配置
├── laravels.php                # LaravelS (Swoole) 服务器配置
├── lauthz-rbac-model.conf     # Lauthz RBAC 权限模型配置
├── lauthz.php                 # Lauthz 权限管理配置
├── logging.php                 # 日志系统配置
├── mail.php                    # 邮件服务配置
├── queue.php                   # 队列服务配置
├── route-attributes.php        # 路由属性注解配置
├── services.php                # 第三方服务配置
├── session.php                 # 会话管理配置
├── silder_captcha.php          # Slider Captcha 滑块验证码配置
├── sms.php                     # 短信服务配置
├── upload.php                 # 文件上传配置
└── view.php                   # 视图引擎配置
```

## 重要文件说明

### 核心配置文件

| 文件名 | 用途 | 关键配置项 | 依赖关系 |
|--------|------|-----------|----------|
| `app.php` | Laravel 应用核心配置 | 服务提供者、中间件、时区、语言 | 所有服务 |
| `database.php` | 数据库连接配置 | MySQL/PostgreSQL 连接参数、表前缀 | PDO 扩展 |
| `laravels.php` | Swoole 服务器配置 | 监听端口、Worker 数量、定时任务、WebSocket | Swoole 扩展 |

### 认证与授权

| 文件名 | 用途 | 关键配置项 | 依赖关系 |
|--------|------|-----------|----------|
| `auth.php` | 认证配置 | 认证守卫、提供者、密码重置 | Laravel Auth |
| `jwt.php` | JWT Token 配置 | Token 过期时间、刷新策略、密钥 | tymon/jwt-auth |
| `lauthz.php` | 权限管理配置 | RBAC 模型、策略、适配器 | casbin/lauthz |

### 业务服务配置

| 文件名 | 用途 | 关键配置项 | 依赖关系 |
|--------|------|-----------|----------|
| `company.php` | 企业信息配置 | 企业名称、Logo、地址 | 无 |
| `sms.php` | 短信服务配置 | 阿里云/腾讯云短信接口 | 云服务 SDK |
| `upload.php` | 文件上传配置 | 上传路径、大小限制、存储驱动 | 文件系统 |
| `cache.php` | 缓存服务配置 | Redis 连接、缓存驱动 | Redis 扩展 |

### 安全与验证

| 文件名 | 用途 | 关键配置项 | 依赖关系 |
|--------|------|-----------|----------|
| `captcha.php` | 图形验证码配置 | 验证码长度、有效期、图片样式 | 无 |
| `ajcaptcha.php` | 行为验证码配置 | 滑块验证参数 | AJ-Captcha SDK |
| `silder_captcha.php` | 滑块验证码配置 | 滑动距离、容错率 | 无 |

### 其他服务配置

| 文件名 | 用途 | 关键配置项 | 依赖关系 |
|--------|------|-----------|----------|
| `mail.php` | 邮件服务配置 | SMTP 配置、发件人信息 | PHP Mailer |
| `queue.php` | 队列服务配置 | 队列驱动、重试次数、超时设置 | Redis/Database |
| `logging.php` | 日志系统配置 | 日志通道、级别、格式 | Monolog |
| `filesystems.php` | 文件系统配置 | OSS/COS/本地上传配置 | 云存储 SDK |

## 配置使用示例

### 读取配置值
```php
// 读取基础配置
$appName = config('app.name');
$timezone = config('app.timezone');

// 读取数据库配置
$dbHost = config('database.connections.mysql.host');
$dbPrefix = config('database.connections.mysql.prefix');

// 读取自定义配置
$enterpriseName = config('company.enterprise_name');
```

### 环境变量配置
```env
# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuoluojiang
DB_USERNAME=root
DB_PASSWORD=123456

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# LaravelS 配置
LARAVELS_LISTEN_IP=0.0.0.0
LARAVELS_LISTEN_PORT=20200

# JWT 配置
JWT_SECRET=your-secret-key-here
JWT_TTL=1440
```

### 运行时配置
```php
// 临时修改配置值
config(['app.timezone' => 'Asia/Shanghai']);

// 获取所有配置值
$allConfig = config()->all();
```

## 注意事项

1. **环境变量优先级**
   - `.env` 文件中的环境变量优先级高于配置文件默认值
   - 生产环境必须设置 `APP_ENV=production`
   - 敏感信息（密码、密钥）必须通过环境变量配置，不得硬编码

2. **配置缓存**
   - 生产环境使用 `php artisan config:cache` 缓存配置
   - 修改配置后必须运行 `php artisan config:clear` 清除缓存
   - `.env` 文件的修改不会自动清除缓存

3. **Swoole 配置**
   - `laravels.php` 中的配置修改后需重启 Swoole 服务
   - 使用热重载功能时，修改配置文件会自动重启
   - Worker 进程数建议设置为 CPU 核心数的 1-4 倍

4. **数据库连接**
   - MySQL 连接使用持久连接（`PDO::ATTR_PERSISTENT`）提升性能
   - 支持多个数据库连接（主库、从库、Wiki 库等）
   - 注意表前缀配置，避免与现有表冲突

5. **安全性**
   - JWT 密钥必须足够复杂，使用 `php artisan jwt:secret` 生成
   - 邮件、短信等第三方服务的 API 密钥通过环境变量配置
   - 生产环境关闭调试模式 `APP_DEBUG=false`

## 常用命令

```bash
# 清除配置缓存
php artisan config:clear

# 缓存所有配置（生产环境）
php artisan config:cache

# 查看配置值
php artisan config:get app.name

# 生成 JWT 密钥
php artisan jwt:secret

# 启动 Swoole 服务
php bin/laravels start -d

# 重启 Swoole 服务
php bin/laravels restart

# 查看 Swoole 服务状态
php bin/laravels info
```

## 扩展配置

### 添加新配置文件
1. 在 `config/` 目录下创建新的 PHP 文件
2. 返回配置数组
3. 使用 `config('filename.key')` 读取配置值

示例：
```php
// config/custom.php
return [
    'feature_enabled' => env('CUSTOM_FEATURE_ENABLED', false),
    'api_endpoint' => env('CUSTOM_API_ENDPOINT', 'https://api.example.com'),
];
```

### 发布第三方包配置
```bash
# 发布第三方包的配置文件到 config 目录
php artisan vendor:publish --provider="SomePackage\ServiceProvider"
```

## 相关文件

- `.env` - 环境变量配置文件
- `app/Providers/` - 服务提供者（注册配置）
- `config/laravels.php` - Swoole 服务器详细配置
- `config/lauthz-rbac-model.conf` - 权限模型定义

## 更新历史

| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-19 | 1.0 | 初始创建 | AI Assistant |
