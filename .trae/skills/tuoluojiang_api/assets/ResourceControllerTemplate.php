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

namespace App\Http\Controller\AdminApi\{{MODULE}};

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Requests\{{MODULE}}\{{NAME}}Request;
use Crmeb\Interfaces\ResourceControllerInterface;
use Crmeb\Traits\ResourceControllerTrait;
use Spatie\RouteAttributes\Attributes\*;

/**
 * {{DESCRIPTION}}资源控制器
 * 使用ResourceControllerTrait快速实现CRUD
 */
#[Prefix('ent/{{ROUTE}}')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class {{NAME}}Controller extends AuthController implements ResourceControllerInterface
{
    use ResourceControllerTrait;

    /**
     * 获取验证类名称.场景
     * 格式: 类名.场景名
     */
    protected function getRequestClassName(): string
    {
        return {{NAME}}Request::class . '.create';
    }

    /**
     * 获取搜索字段配置
     * 格式: [字段名, 默认值]
     */
    protected function getSearchField(): array
    {
        return [
            ['name', ''],           // 名称搜索
            ['status', ''],         // 状态筛选
            ['created_at', ''],     // 创建时间
        ];
    }

    /**
     * 获取请求参数字段
     * 格式: [字段名, 默认值]
     */
    protected function getRequestFields(): array
    {
        return [
            ['name', ''],           // 名称
            ['description', ''],    // 描述
            ['status', 1],          // 状态，默认启用
        ];
    }
}
