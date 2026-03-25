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

namespace App\Http\Model\{{MODULE}};

use App\Http\Model\BaseModel;
use App\Http\Model\User\Admin;
use App\Http\Model\Company\Enterprise;

/**
 * {{DESCRIPTION}}模型
 */
class {{NAME}} extends BaseModel
{
    /**
     * 表名
     * @var string
     */
    protected $table = '{{TABLE}}';

    /**
     * 可填充字段
     * @var array
     */
    protected $fillable = [
        'entid',
        'name',
        'description',
        'status',
        'sort',
        'creator_id',
    ];

    /**
     * 字段类型转换
     * @var array
     */
    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 搜索器 - 名称
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeName($query, $value)
    {
        if ($value !== '') {
            $query->where('name', 'like', "%{$value}%");
        }
        return $query;
    }

    /**
     * 搜索器 - 状态
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeStatus($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
        return $query;
    }

    /**
     * 搜索器 - 企业ID
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '') {
            $query->where('entid', $value);
        }
        return $query;
    }

    /**
     * 搜索器 - 创建时间
     * @param mixed $query
     * @param mixed $value
     * @return mixed
     */
    public function scopeCreatedAt($query, $value)
    {
        if ($value !== '' && is_array($value)) {
            $query->whereBetween('created_at', $value);
        }
        return $query;
    }

    /**
     * 关联 - 企业
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class, 'entid', 'id');
    }

    /**
     * 关联 - 创建人
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(Admin::class, 'creator_id', 'uid');
    }
}
