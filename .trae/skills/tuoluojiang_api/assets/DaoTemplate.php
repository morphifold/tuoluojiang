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

namespace App\Http\Dao\{{MODULE}};

use App\Http\Dao\BaseDao;
use App\Http\Model\{{MODULE}}\{{NAME}};

/**
 * {{DESCRIPTION}}数据访问对象
 */
class {{NAME}}Dao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return {{NAME}}::class;
    }

    /**
     * 获取带关联的列表
     * @param array $where 查询条件
     * @param array $with 关联加载
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return \Illuminate\Support\Collection
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function getListWithRelations(array $where, array $with = [], int $page = 0, int $limit = 0): \Illuminate\Support\Collection
    {
        return $this->select($where, ['*'], $with, $page, $limit);
    }

    /**
     * 获取统计数据
     * @param int $entid 企业ID
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function getStatistics(int $entid): array
    {
        $total = $this->count(['entid' => $entid]);
        $active = $this->count(['entid' => $entid, 'status' => 1]);
        $inactive = $this->count(['entid' => $entid, 'status' => 0]);
        
        return compact('total', 'active', 'inactive');
    }

    /**
     * 批量更新状态
     * @param array $ids ID数组
     * @param int $status 状态值
     * @return int
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function batchUpdateStatus(array $ids, int $status): int
    {
        return $this->getModel()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }
}
