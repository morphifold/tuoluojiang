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

namespace App\Http\Service\{{MODULE}};

use App\Http\Dao\{{MODULE}}\{{NAME}}Dao;
use App\Http\Service\BaseService;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * {{DESCRIPTION}}服务类
 */
class {{NAME}}Service extends BaseService
{
    public function __construct({{NAME}}Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表数据
     * @param array $where 查询条件
     * @param array $field 查询字段
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function getList(array $where, array $field = ['*'], int $page = 0, int $limit = 0): array
    {
        [$page, $limit, $defaultLimit] = $this->getPageValue($page > 0, $limit > 0);
        
        $where['entid'] = $this->entId();
        
        $list = $this->dao->select($where, $field, [], $page, $limit ?: $defaultLimit);
        $count = $this->dao->count($where);
        
        return $this->listData($list, $count);
    }

    /**
     * 获取单条数据
     * @param int $id 主键ID
     * @param array $field 查询字段
     * @param array $with 关联加载
     * @return array
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function get(int $id, array $field = ['*'], array $with = []): array
    {
        $where = [
            'id' => $id,
            'entid' => $this->entId(),
        ];
        
        return toArray($this->dao->get($where, $field, $with));
    }

    /**
     * 创建数据
     * @param array $data 数据
     * @return mixed
     * @throws BindingResolutionException
     */
    public function create(array $data): mixed
    {
        return $this->transaction(function () use ($data) {
            $data['entid'] = $this->entId(false);
            $data['creator_id'] = $this->uuId(false);
            
            return $this->dao->create($data);
        });
    }

    /**
     * 更新数据
     * @param int $id 主键ID
     * @param array $data 更新数据
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function update(int $id, array $data): int
    {
        return $this->transaction(function () use ($id, $data) {
            $where = [
                'id' => $id,
                'entid' => $this->entId(),
            ];
            
            return $this->dao->update($where, $data);
        });
    }

    /**
     * 删除数据
     * @param int $id 主键ID
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function delete(int $id): int
    {
        $where = [
            'id' => $id,
            'entid' => $this->entId(),
        ];
        
        return $this->dao->delete($where);
    }

    /**
     * 修改状态
     * @param int $id 主键ID
     * @param int $status 状态值
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function updateStatus(int $id, int $status): int
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * 批量删除
     * @param array $ids ID数组
     * @return int
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function batchDelete(array $ids): int
    {
        $count = 0;
        
        $this->transaction(function () use ($ids, &$count) {
            foreach ($ids as $id) {
                $count += $this->delete((int) $id);
            }
        });
        
        return $count;
    }
}
