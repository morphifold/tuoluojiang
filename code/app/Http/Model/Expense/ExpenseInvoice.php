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
use App\Http\Model\Approve\ApproveApply;
use App\Http\Model\BaseModel;
use crmeb\traits\model\TimeDataTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 进项发票模型
 * Class ExpenseInvoice.
 */
class ExpenseInvoice extends BaseModel
{
    use SoftDeletes;
    use TimeDataTrait;

    /**
     * 业务状态
     */
    public const STATUS_UNBOUND = 0;    // 未绑定报销
    public const STATUS_BINDING = 1;    // 已绑定报销(审批中)
    public const STATUS_REIMBURSED = 2; // 已报销(审批通过归档锁定)

    /**
     * 异常标记状态
     */
    public const ABNORMAL_NORMAL = 0; // 正常
    public const ABNORMAL_VOID = 1;   // 作废
    public const ABNORMAL_RED = 2;    // 红冲
    public const ABNORMAL_FAKE = 3;   // 假票

    /**
     * 发票类型
     */
    public const TYPE_SPECIAL = 'special';                 // 增值税专用发票
    public const TYPE_GENERAL = 'general';                 // 增值税普通发票
    public const TYPE_ELECTRONIC = 'electronic';           // 数电票/电子发票
    public const TYPE_DIGITAL_SPECIAL = 'digital_special'; // 全电专用发票
    public const TYPE_DIGITAL_GENERAL = 'digital_general'; // 全电普通发票
    public const TYPE_QUOTA = 'quota';                     // 定额发票
    public const TYPE_TOLL = 'toll';                       // 通行费发票
    public const TYPE_PETROL = 'petrol';                   // 加油发票
    public const TYPE_OTHER = 'other';                     // 其他发票
    /**
     * @var string
     */
    protected $table = 'expense_invoice';

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
        'amount'          => 'float',
        'tax_amount'      => 'float',
        'total_amount'    => 'float',
        'status'          => 'integer',
        'abnormal_status' => 'integer',
        'created_at'      => 'datetime:Y-m-d H:i:s',
        'updated_at'      => 'datetime:Y-m-d H:i:s',
        'reimbursed_at'   => 'datetime:Y-m-d H:i:s',
        'deleted_at'      => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 获取状态文本
     */
    public static function getStatusText(int $status): string
    {
        return match ($status) {
            self::STATUS_UNBOUND    => '未报销',
            self::STATUS_BINDING    => '报销审批中',
            self::STATUS_REIMBURSED => '已报销',
            default                 => '未知',
        };
    }

    /**
     * 获取异常状态文本
     */
    public static function getAbnormalStatusText(int $status): string
    {
        return match ($status) {
            self::ABNORMAL_NORMAL => '正常',
            self::ABNORMAL_VOID   => '已作废',
            self::ABNORMAL_RED    => '已红冲',
            self::ABNORMAL_FAKE   => '假票',
            default               => '未知',
        };
    }

    /**
     * 获取发票类型文本
     */
    public static function getTypeText(string $type): string
    {
        return match ($type) {
            self::TYPE_SPECIAL, '1'                 => '增值税专用发票',
            self::TYPE_GENERAL, '2'                 => '增值税普通发票',
            self::TYPE_DIGITAL_SPECIAL, '3'         => '全电专用发票',
            self::TYPE_DIGITAL_GENERAL, '4'         => '全电普通发票',
            self::TYPE_ELECTRONIC                   => '增值税电子普通发票',
            self::TYPE_TOLL, '5'                    => '通行费发票',
            self::TYPE_QUOTA, '6'                   => '定额发票',
            self::TYPE_PETROL, '7'                  => '加油发票',
            default                                 => '其他发票',
        };
    }
    /**
     * 关联录入员工
     */
    public function user(): HasOne
    {
        return $this->hasOne(Admin::class, 'id', 'uid')->select(['id', 'name', 'avatar', 'uid', 'phone', 'account']);
    }

    /**
     * 关联审批申请单
     */
    public function apply(): HasOne
    {
        return $this->hasOne(ApproveApply::class, 'id', 'apply_id');
    }

    /**
     * 关联发票操作日志
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ExpenseInvoiceLog::class, 'invoice_id', 'id')->orderByDesc('id');
    }

    /**
     * 企业作用域
     */
    public function scopeEntid($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('entid', (int) $value);
        }
    }

    /**
     * 用户作用域
     */
    public function scopeUid($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('uid', (int) $value);
        }
    }

    /**
     * 状态作用域
     */
    public function scopeStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('status', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('status', (int) $value);
        }
    }

    /**
     * 异常状态作用域
     */
    public function scopeAbnormalStatus($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('abnormal_status', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('abnormal_status', (int) $value);
        }
    }

    /**
     * 发票类型作用域
     */
    public function scopeInvoiceType($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('invoice_type', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('invoice_type', (string) $value);
        }
    }

    /**
     * 关键词搜索作用域
     */
    public function scopeKeyword($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $value = trim((string) $value);
            $query->where(function ($q) use ($value) {
                $q->where('invoice_number', 'like', "%{$value}%")
                    ->orWhere('invoice_code', 'like', "%{$value}%")
                    ->orWhere('seller_name', 'like', "%{$value}%")
                    ->orWhere('buyer_name', 'like', "%{$value}%")
                    ->orWhere('mark', 'like', "%{$value}%");
            });
        }
    }
}
