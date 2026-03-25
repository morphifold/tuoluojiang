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
use Spatie\RouteAttributes\Attributes\*;

/**
 * {{DESCRIPTION}}控制器
 */
#[Prefix('ent/{{ROUTE}}')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class {{NAME}}Controller extends AuthController
{
    /**
     * 列表
     */
    #[Get('list', '{{DESCRIPTION}}列表')]
    public function index()
    {
        $data = [];
        return $this->success($data);
    }

    /**
     * 添加
     */
    #[Post('save', '添加{{DESCRIPTION}}')]
    public function store({{NAME}}Request $request)
    {
        $data = $request->postMore([
            ['field1', ''],
            ['field2', ''],
        ]);
        
        return $this->success('common.create.succ');
    }

    /**
     * 修改
     */
    #[Put('update/{id}', '修改{{DESCRIPTION}}')]
    public function update($id, {{NAME}}Request $request)
    {
        $data = $request->postMore([
            ['field1', ''],
            ['field2', ''],
        ]);
        
        return $this->success('common.update.succ');
    }

    /**
     * 删除
     */
    #[Delete('delete/{id}', '删除{{DESCRIPTION}}')]
    public function destroy($id)
    {
        return $this->success('common.delete.succ');
    }

    /**
     * 详情
     */
    #[Get('detail/{id}', '{{DESCRIPTION}}详情')]
    public function show($id)
    {
        $data = [];
        return $this->success($data);
    }
}
