<?php

declare(strict_types=1);

/**
 * +----------------------------------------------------------------------
 * | 陀螺匠 [ 赋能开发者，助力企业发展 ]
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016~2026 https://www.tuoluojiang.com All rights reserved.
 * +----------------------------------------------------------------------
 * | Licensed 陀螺匠并不是自由软件，未经许可不能去掉陀螺匠相关版权
 * +----------------------------------------------------------------------
 * | Author: 陀螺匠 Team <admin@tuoluojiang.com>
 * +----------------------------------------------------------------------
 */

namespace App\Http\Model\Expense;

use App\Http\Model\Admin\Admin;
use App\Http\Model\BaseModel;
use crmeb\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 进项发票日志模型
 * Class ExpenseInvoiceLog.
 */
class ExpenseInvoiceLog extends BaseModel
{
    use TimeDataTrait;

    public const ACTION_CREATE = 'create';       // 创建录入
    public const ACTION_UPDATE = 'update';       // 修改
    public const ACTION_DELETE = 'delete';       // 删除
    public const ACTION_ABNORMAL = 'abnormal';   // 标记异常
    public const ACTION_BIND = 'bind';           // 绑定报销审批
    public const ACTION_UNBIND = 'unbind';       // 解绑报销审批
    public const ACTION_REIMBURSE = 'reimburse'; // 审批通过归档报销

    /**
     * @var string
     */
    protected $table = 'expense_invoice_log';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 字段黑名单.
     * @var array
     */
    protected $guarded = [];

    /**
     * 字段转换.
     * @var array
     */
    protected $casts = [
        'old_data'   => 'array',
        'new_data'   => 'array',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 关联操作人员
     */
    public function user(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone', 'account']);
    }

    /**
     * 关联发票
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ExpenseInvoice::class, 'invoice_id', 'id');
    }
}
