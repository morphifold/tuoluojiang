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

namespace App\Http\Requests\{{MODULE}};

use App\Http\Requests\ApiValidate;

class {{NAME}}Request extends ApiValidate
{
    /**
     * 错误提示信息
     */
    protected $message = [
        'field1.required' => '请填写字段1',
        'field2.max'      => '字段2不能超过50个字符',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'create' => ['field1', 'field2'],
        'update' => ['field1', 'field2'],
    ];

    /**
     * 验证规则
     */
    protected function rules()
    {
        return [
            'field1' => 'required',
            'field2' => 'max:50',
        ];
    }
}
