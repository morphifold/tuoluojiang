#!/usr/bin/env php
<?php
/**
 * API 接口代码生成器
 * 用途: 快速生成 API 接口所需的控制器、验证类、模型、服务类等文件
 * 用法: php create_api.php 模块名 功能名 [路由前缀]
 *
 * 示例:
 *   php create_api.php User user - 用户管理
 *   php create_api.php Order order - 订单管理
 *   php create_api.php Product product ent/product - 产品管理(自定义路由)
 */

declare(strict_types=1);

if ($argc < 3) {
    echo "用法: php create_api.php <模块名> <功能名> [路由前缀]\n";
    echo "示例: php create_api.php User user ent/user\n";
    echo "      php create_api.php Order order ent/order\n";
    exit(1);
}

$moduleName = ucfirst($argv[1]);  // 模块名 (User)
$functionName = ucfirst($argv[2]); // 功能名 (user)
$prefix = isset($argv[3]) ? $argv[3] : 'ent/' . strtolower($functionName); // 路由前缀
$description = $functionName; // 功能描述

$baseDir = __DIR__ . '/../../..';

// 定义文件路径
$paths = [
    'controller' => "{$baseDir}/code/app/Http/Controller/AdminApi/{$moduleName}/{$functionName}Controller.php",
    'request' => "{$baseDir}/code/app/Http/Requests/{$moduleName}/{$functionName}Request.php",
    'model' => "{$baseDir}/code/app/Http/Model/{$functionName}.php",
    'dao' => "{$baseDir}/code/app/Http/Dao/{$functionName}Dao.php",
    'service' => "{$baseDir}/code/app/Http/Service/{$moduleName}/{$functionName}Service.php",
];

// 确保目录存在
foreach ($paths as $path) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✓ 创建目录: {$dir}\n";
    }
}

// 生成控制器
echo "正在生成控制器...\n";
$controllerContent = generateController($moduleName, $functionName, $prefix, $description);
file_put_contents($paths['controller'], $controllerContent);
echo "✓ 控制器已创建: {$paths['controller']}\n";

// 生成验证类
echo "正在生成验证类...\n";
$requestContent = generateRequest($moduleName, $functionName);
file_put_contents($paths['request'], $requestContent);
echo "✓ 验证类已创建: {$paths['request']}\n";

// 生成模型
echo "正在生成模型...\n";
$modelContent = generateModel($functionName);
file_put_contents($paths['model'], $modelContent);
echo "✓ 模型已创建: {$paths['model']}\n";

// 生成 Dao
echo "正在生成 Dao...\n";
$daoContent = generateDao($functionName);
file_put_contents($paths['dao'], $daoContent);
echo "✓ Dao 已创建: {$paths['dao']}\n";

// 生成服务类
echo "正在生成服务类...\n";
$serviceContent = generateService($moduleName, $functionName);
file_put_contents($paths['service'], $serviceContent);
echo "✓ 服务类已创建: {$paths['service']}\n";

echo "\n===========================================\n";
echo "✓ API 接口生成完成！\n";
echo "===========================================\n";
echo "\n下一步操作:\n";
echo "1. 编辑控制器: {$paths['controller']}\n";
echo "2. 配置验证规则: {$paths['request']}\n";
echo "3. 定义模型字段: {$paths['model']}\n";
echo "4. 实现业务逻辑: {$paths['service']}\n";
echo "5. 测试接口: php artisan route:list | grep '{$prefix}'\n";
echo "\n";

/**
 * 生成控制器
 */
function generateController(string $moduleName, string $functionName, string $prefix, string $description): string
{
    return <<<PHP
<?php
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Controller\AdminApi\\{$moduleName};

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\\{$moduleName}\\{$functionName}Request;
use App\Http\Service\\{$moduleName}\\{$functionName}Service;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Put;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;

/**
 * {$description}控制器
 */
#[Prefix('{$prefix}')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class {$functionName}Controller extends AuthController
{
    protected {$functionName}Service \$service;

    public function __construct({$functionName}Service \$service)
    {
        \$this->service = \$service;
    }

    /**
     * 列表
     */
    #[Get('list', '{$description}列表')]
    public function index({$functionName}Request \$request)
    {
        \$data = \$this->service->getList(\$request->postMore([
            ['page', 1],
            ['limit', 20],
            'keyword',
            'status',
        ]), \$this->entId);

        return \$this->success(\$data);
    }

    /**
     * 添加
     */
    #[Post('save', '添加{$description}')]
    public function store({$functionName}Request \$request)
    {
        \$data = \$request->postMore([
            ['name', ''],
            ['description', ''],
            ['status', 1],
        ]);

        \$this->service->create(\$data, \$this->entId, \$this->userInfo->id);

        return \$this->success('common.create.succ');
    }

    /**
     * 修改
     */
    #[Put('update/{id}', '修改{$description}')]
    public function update(\$id, {$functionName}Request \$request)
    {
        \$data = \$request->postMore([
            ['name', ''],
            ['description', ''],
            ['status', 1],
        ]);

        \$this->service->update(\$id, \$data, \$this->entId);

        return \$this->success('common.update.succ');
    }

    /**
     * 删除
     */
    #[Delete('delete/{id}', '删除{$description}')]
    public function destroy(\$id)
    {
        \$this->service->delete(\$id, \$this->entId);

        return \$this->success('common.delete.succ');
    }

    /**
     * 详情
     */
    #[Get('detail/{id}', '{$description}详情')]
    public function show(\$id)
    {
        \$data = \$this->service->detail(\$id, \$this->entId);

        return \$this->success(\$data);
    }

    /**
     * 修改状态
     */
    #[Put('status/{id}', '修改状态')]
    public function status(\$id, {$functionName}Request \$request)
    {
        \$data = \$request->postMore([['status', 0]]);
        \$this->service->update(\$id, \$data, \$this->entId);

        return \$this->success('common.update.succ');
    }
}
PHP;
}

/**
 * 生成验证类
 */
function generateRequest(string $moduleName, string $functionName): string
{
    return <<<PHP
<?php
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Requests\\{$moduleName};

use App\Http\Requests\ApiValidate;

class {$functionName}Request extends ApiValidate
{
    /**
     * 错误提示信息
     */
    protected \$message = [
        'name.required' => '请填写名称',
        'name.max'      => '名称不能超过50个字符',
        'status.in'     => '状态值不正确',
    ];

    /**
     * 验证场景
     */
    protected \$scene = [
        'create' => ['name', 'description', 'status'],
        'update' => ['name', 'description', 'status'],
        'status' => ['status'],
    ];

    /**
     * 验证规则
     */
    protected function rules(): array
    {
        return [
            'name'        => 'required|max:50',
            'description' => 'max:500',
            'status'      => 'in:0,1',
        ];
    }
}
PHP;
}

/**
 * 生成模型
 */
function generateModel(string $functionName): string
{
    $tableName = strtolower(preg_replace('/([A-Z])/', '_$1', $functionName));
    $tableName = ltrim($tableName, '_');

    return <<<PHP
<?php
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * {$functionName} 模型
 */
class {$functionName} extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 数据表名
     */
    protected \$table = '{$tableName}';

    /**
     * 主键
     */
    protected \$primaryKey = 'id';

    /**
     * 可批量赋值的字段
     */
    protected \$fillable = [
        'entid',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * 应该被转换为日期的属性
     */
    protected \$dates = [
        'deleted_at',
    ];

    /**
     * 类型转换
     */
    protected \$casts = [
        'status' => 'integer',
    ];

    /**
     * 创建人关联
     */
    public function creator()
    {
        return \$this->belongsTo(User::class, 'created_by');
    }

    /**
     * 更新人关联
     */
    public function updater()
    {
        return \$this->belongsTo(User::class, 'updated_by');
    }
}
PHP;
}

/**
 * 生成 Dao
 */
function generateDao(string $functionName): string
{
    return <<<PHP
<?php
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Dao;

use App\Http\Model\\{$functionName};

/**
 * {$functionName} 数据访问对象
 */
class {$functionName}Dao
{
    /**
     * 搜索列表
     */
    public function search(array \$where = [])
    {
        \$query = {$functionName}::query();

        // 企业数据隔离
        if (isset(\$where['entid'])) {
            \$query->where('entid', \$where['entid']);
        }

        // 关键词搜索
        if (isset(\$where['keyword']) && \$where['keyword']) {
            \$query->where('name', 'like', '%' . \$where['keyword'] . '%');
        }

        // 状态筛选
        if (isset(\$where['status']) && \$where['status'] !== '') {
            \$query->where('status', \$where['status']);
        }

        // 分页
        if (isset(\$where['page']) && isset(\$where['limit'])) {
            return \$query->orderBy('id', 'desc')
                ->paginate((int)\$where['limit'], ['*'], 'page', (int)\$where['page']);
        }

        return \$query->orderBy('id', 'desc')->get();
    }

    /**
     * 获取详情
     */
    public function get(int \$id, int \$entId)
    {
        return {$functionName}::where('id', \$id)
            ->where('entid', \$entId)
            ->first();
    }

    /**
     * 检查是否存在
     */
    public function exists(int \$id, int \$entId): bool
    {
        return {$functionName}::where('id', \$id)
            ->where('entid', \$entId)
            ->exists();
    }
}
PHP;
}

/**
 * 生成服务类
 */
function generateService(string $moduleName, string $functionName): string
{
    return <<<PHP
<?php
/**
 *  +----------------------------------------------------------------------
 *  | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2025 https://www.tuoluojiang.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 *  +----------------------------------------------------------------------
 *  | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 *  +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Http\Service\\{$moduleName};

use App\Http\Dao\\{$functionName}Dao;
use App\Http\Model\\{$functionName};
use Crmeb\Exceptions\AdminException;

/**
 * {$functionName} 服务类
 */
class {$functionName}Service
{
    protected {$functionName}Dao \$dao;

    public function __construct({$functionName}Dao \$dao)
    {
        \$this->dao = \$dao;
    }

    /**
     * 获取列表
     */
    public function getList(array \$where, int \$entId): array
    {
        \$where['entid'] = \$entId;
        \$list = \$this->dao->search(\$where);

        return [
            'list'  => \$list->items(),
            'count' => \$list->total(),
            'page'  => \$list->currentPage(),
            'limit' => \$list->perPage(),
        ];
    }

    /**
     * 创建
     */
    public function create(array \$data, int \$entId, int \$userId): {$functionName}
    {
        \$data['entid'] = \$entId;
        \$data['created_by'] = \$userId;
        \$data['updated_by'] = \$userId;

        return {$functionName}::create(\$data);
    }

    /**
     * 更新
     */
    public function update(int \$id, array \$data, int \$entId): bool
    {
        \$model = \$this->dao->get(\$id, \$entId);
        if (!\$model) {
            throw new AdminException('数据不存在');
        }

        \$data['updated_by'] = \$this->userInfo->id ?? 0;
        return \$model->update(\$data);
    }

    /**
     * 删除
     */
    public function delete(int \$id, int \$entId): bool
    {
        \$model = \$this->dao->get(\$id, \$entId);
        if (!\$model) {
            throw new AdminException('数据不存在');
        }

        return \$model->delete();
    }

    /**
     * 详情
     */
    public function detail(int \$id, int \$entId): array
    {
        \$model = \$this->dao->get(\$id, \$entId);
        if (!\$model) {
            throw new AdminException('数据不存在');
        }

        return \$model->toArray();
    }
}
PHP;
}
