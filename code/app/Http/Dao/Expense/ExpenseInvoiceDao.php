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
use App\Http\Model\Expense\ExpenseInvoice;
use crmeb\interfaces\ResourceDaoInterface;
use crmeb\traits\dao\BatchSearchTrait;
use crmeb\traits\dao\ListSearchTrait;
use crmeb\traits\dao\TogetherSearchTrait;

/**
 * 进项发票Dao
 * Class ExpenseInvoiceDao.
 */
class ExpenseInvoiceDao extends BaseDao implements ResourceDaoInterface
{
    use ListSearchTrait;
    use TogetherSearchTrait;
    use BatchSearchTrait;

    /**
     * 设置模型
     */
    protected function setModel(): string
    {
        return ExpenseInvoice::class;
    }

    /**
     * 根据发票代码和号码查询活跃且正常的发票（防重排他查询）
     * 处于异常标记(作废/红冲/假票)或软删除状态的发票解除防重约束
     */
    public function findActiveByCodeAndNumber(int $entId, string $invoiceCode, string $invoiceNumber, ?int $excludeId = null): ?ExpenseInvoice
    {
        $query = $this->getModel()
            ->where('entid', $entId)
            ->where('invoice_code', trim($invoiceCode))
            ->where('invoice_number', trim($invoiceNumber))
            ->where('abnormal_status', ExpenseInvoice::ABNORMAL_NORMAL);

        if ($excludeId !== null && $excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->with(['user'])->first();
    }

    /**
     * 获取列表
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = []): array
    {
        $query = $this->search($where)->select($field);

        if (! empty($with)) {
            $query->with($with);
        }

        if ($page && $limit) {
            $query->forPage($page, $limit);
        } elseif ($limit) {
            $query->limit($limit);
        }

        if ($sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->orderByDesc($v);
                    } else {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                $query->orderByDesc($sort);
            }
        } else {
            $query->orderByDesc('id');
        }

        return $query->get()->toArray();
    }

    /**
     * 条件计数
     */
    public function count($where = [], array $with = []): int
    {
        return $this->search($where)->count();
    }
}
