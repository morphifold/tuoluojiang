# Console

## 目录说明
- **路径**: `./apps/oa-backend/app/Console`
- **用途**: 命令行目录，包含所有 Artisan 命令
- **归属模块**: 命令行层
- **创建时间**: 2026-03-18
- **最后更新时间**: 2026-03-18

## 文件结构
```
├── Commands/
└── Kernel.php
```

## 重要文件说明

### 核心文件
| 文件名 | 用途 | 关键类/函数 | 依赖关系 |
|--------|------|-------------|----------|
| Kernel.php | PHP类文件 | 请填写 | 请填写 |


## 使用示例

### 代码示例
```php
namespace App\Console;

use Illuminate\Console\Command;
use App\Http\Service\UserService;

class ExampleCommand extends Command
{
    protected $signature = 'user:sync {id}';
    protected $description = '同步用户数据';

    protected $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    public function handle()
    {
        $userId = $this->argument('id');
        $result = $this->userService->syncUser($userId);
        
        $this->info("同步成功: {$result}");
        return 0;
    }
}
```

### 调用方式
```bash
# 列出所有命令
php artisan list

# 运行命令
php artisan user:sync 123

# 调用命令并显示输出
php artisan user:sync 123 --verbose

# 查看命令帮助
php artisan user:sync --help
```

## 注意事项
1. **命名规范**: 命令名应使用动词:名词格式，如 `user:sync`
2. **签名定义**: 使用 `$signature` 定义命令参数和选项
3. **依赖注入**: 通过构造函数注入服务，而非直接实例化
4. **输出方法**: 使用 `$this->info()`, `$this->error()`, `$this->warn()` 输出日志
5. **返回值**: 返回 0 表示成功，非 0 表示失败
6. **定时任务**: 定时任务在 `config/laravels.php` 的 `timer` 中配置

## 更新历史
| 日期 | 版本 | 变更说明 | 修改人 |
|------|------|----------|--------|
| 2026-03-18 | 1.1 | 完善目录说明和使用示例 | AI助手 |
| 2026-03-18 | 1.0 | 自动生成 | 系统 |