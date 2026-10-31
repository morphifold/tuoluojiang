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

namespace App\Http\Dao\Expense;

use App\Http\Dao\BaseDao;
use App\Http\Model\Expense\ExpenseInvoiceLog;

/**
 * 进项发票日志Dao
 * Class ExpenseInvoiceLogDao.
 */
class ExpenseInvoiceLogDao extends BaseDao
{
    /**
     * 设置模型
     */
    protected function setModel(): string
    {
        return ExpenseInvoiceLog::class;
    }

    /**
     * 获取发票的操作日志列表
     */
    public function getLogsByInvoiceId(int $invoiceId): array
    {
        return $this->getModel()
            ->where('invoice_id', $invoiceId)
            ->with(['user'])
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }
}
