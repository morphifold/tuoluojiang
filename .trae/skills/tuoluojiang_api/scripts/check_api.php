#!/usr/bin/env php
<?php
/**
 * API 接口检查工具
 * 用途: 检查 API 接口是否符合开发规范
 * 用法: php check_api.php [控制器路径]
 *
 * 示例:
 *   php check_api.php                    # 检查所有 AdminApi 控制器
 *   php check_api.php User              # 检查 User 模块控制器
 *   php check_api.php User/UserController.php # 检查指定控制器
 */

declare(strict_types=1);

$baseDir = __DIR__ . '/../../..';
$controllerDir = "{$baseDir}/code/app/Http/Controller/AdminApi";

// 获取检查范围
$target = isset($argv[1]) ? $argv[1] : null;

$controllers = [];

if ($target === null) {
    // 检查所有控制器
    $controllers = getAllControllers($controllerDir);
} elseif (strpos($target, '/') !== false || strpos($target, '.php') !== false) {
    // 检查指定控制器文件
    $controllers[] = $controllerDir . '/' . ltrim($target, '/');
} else {
    // 检查指定模块的控制器
    $moduleDir = $controllerDir . '/' . ucfirst($target);
    if (is_dir($moduleDir)) {
        $controllers = getControllersInDir($moduleDir);
    } else {
        echo "错误: 模块不存在: {$target}\n";
        exit(1);
    }
}

if (empty($controllers)) {
    echo "未找到控制器文件\n";
    exit(0);
}

echo "===========================================\n";
echo "API 接口规范检查\n";
echo "===========================================\n";
echo "检查范围: " . count($controllers) . " 个控制器\n";
echo "\n";

$totalIssues = 0;

foreach ($controllers as $controllerPath) {
    echo "检查: " . getRelativePath($controllerPath, $baseDir) . "\n";
    $issues = checkController($controllerPath);

    if (empty($issues)) {
        echo "  ✓ 符合规范\n";
    } else {
        $totalIssues += count($issues);
        echo "  ✗ 发现 " . count($issues) . " 个问题:\n";
        foreach ($issues as $issue) {
            echo "    - {$issue}\n";
        }
    }
    echo "\n";
}

echo "===========================================\n";
if ($totalIssues === 0) {
    echo "✓ 所有检查通过！\n";
} else {
    echo "✗ 共发现 {$totalIssues} 个问题\n";
}
echo "===========================================\n";

exit($totalIssues > 0 ? 1 : 0);

/**
 * 获取所有控制器
 */
function getAllControllers(string $dir): array
{
    $controllers = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            $controllers = array_merge($controllers, getControllersInDir($path));
        } elseif (str_ends_with($item, 'Controller.php')) {
            $controllers[] = $path;
        }
    }

    return $controllers;
}

/**
 * 获取目录中的控制器
 */
function getControllersInDir(string $dir): array
{
    $controllers = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . '/' . $item;

        if (str_ends_with($item, 'Controller.php')) {
            $controllers[] = $path;
        }
    }

    return $controllers;
}

/**
 * 检查控制器
 */
function checkController(string $path): array
{
    $issues = [];
    $content = file_get_contents($path);

    // 1. 检查严格类型声明
    if (!preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)/', $content)) {
        $issues[] = '缺少严格类型声明: declare(strict_types=1);';
    }

    // 2. 检查版权注释
    if (!preg_match('/陀螺匠.*Copyright.*tuoluojiang/', $content)) {
        $issues[] = '缺少陀螺匠版权注释';
    }

    // 3. 检查命名空间
    if (!preg_match('/namespace\s+App\\\\Http\\\\Controller\\\\AdminApi/', $content)) {
        $issues[] = '命名空间不符合规范';
    }

    // 4. 检查继承 AuthController
    if (!preg_match('/extends\s+AuthController/', $content)) {
        $issues[] = '未继承 AuthController';
    }

    // 5. 检查路由属性
    if (!preg_match('/#\[Prefix\s*\(/', $content)) {
        $issues[] = '缺少 #[Prefix] 路由属性';
    }

    if (!preg_match('/#\[Middleware\s*\(/', $content)) {
        $issues[] = '缺少 #[Middleware] 中间件属性';
    }

    // 6. 检查中间件配置
    if (!preg_match('/auth\.admin/', $content)) {
        $issues[] = '中间件缺少 auth.admin';
    }

    // 7. 检查企业认证
    if (!preg_match('/ent\.auth/', $content)) {
        $issues[] = '中间件缺少 ent.auth';
    }

    // 8. 检查日志中间件
    if (!preg_match('/ent\.log/', $content)) {
        $issues[] = '中间件缺少 ent.log (建议添加)';
    }

    // 9. 检查服务注入
    if (preg_match('/class\s+\w+Controller/', $content) && !preg_match('/protected\s+\w+\s+\$service/', $content)) {
        $issues[] = '未注入服务类 (建议使用服务层)';
    }

    // 10. 检查返回语句
    if (preg_match('/public\s+function\s+\w+/', $content)) {
        // 检查是否有未使用统一响应方法的情况
        if (preg_match('/return\s+response\s*\(/', $content)) {
            $issues[] = '使用了 response() 而非 \$this->success() 或 \$this->fail()';
        }
    }

    return $issues;
}

/**
 * 获取相对路径
 */
function getRelativePath(string $path, string $baseDir): string
{
    return str_replace($baseDir . '/', '', $path);
}
