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

namespace App\Http\Controller\AdminApi\Expense;

use App\Http\Controller\AdminApi\AuthController;
use App\Http\Service\Expense\ExpenseInvoiceService;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Put;

/**
 * 进项发票与报销防重控制器
 * Class ExpenseInvoiceController.
 */
#[Prefix('ent/expense/invoice')]
#[Middleware(['auth.admin', 'ent.auth', 'ent.log'])]
class ExpenseInvoiceController extends AuthController
{
    public function __construct(ExpenseInvoiceService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 员工个人发票夹列表
     */
    #[Get('my', '员工发票列表')]
    public function getMyList(): mixed
    {
        $params = $this->request->getMore([
            ['page', 1],
            ['limit', 15],
            ['status', ''],
            ['invoice_type', ''],
            ['abnormal_status', ''],
            ['start_date', ''],
            ['end_date', ''],
            ['min_amount', ''],
            ['max_amount', ''],
            ['keyword', ''],
        ]);

        $data = $this->service->getMyList($this->entId, (int) $this->userInfo['id'], $params);
        return $this->success($data);
    }

    /**
     * 员工手动补录发票
     */
    #[Post('my', '员工手动录入发票')]
    public function saveMy(): mixed
    {
        $data = $this->request->postMore([
            ['invoice_type', 'general'],
            ['invoice_code', ''],
            ['invoice_number', ''],
            ['amount', 0.00],
            ['tax_amount', 0.00],
            ['total_amount', 0.00],
            ['invoice_date', ''],
            ['seller_name', ''],
            ['buyer_name', ''],
            ['file_path', ''],
            ['file_name', ''],
            ['file_type', ''],
            ['mark', ''],
        ]);

        $invoice = $this->service->saveMyInvoice($this->entId, (int) $this->userInfo['id'], $data);
        return $this->success('发票保存成功', $invoice->toArray());
    }

    /**
     * 员工发票详情
     */
    #[Get('my/{id}', '员工发票详情')]
    public function myDetail(int $id): mixed
    {
        $data = $this->service->getMyDetail($this->entId, (int) $this->userInfo['id'], $id);
        return $this->success($data);
    }

    /**
     * 员工修改发票
     */
    #[Put('my/{id}', '员工修改发票')]
    public function updateMy(int $id): mixed
    {
        $data = $this->request->postMore([
            ['invoice_type', null],
            ['invoice_code', null],
            ['invoice_number', null],
            ['amount', null],
            ['tax_amount', null],
            ['total_amount', null],
            ['invoice_date', null],
            ['seller_name', null],
            ['buyer_name', null],
            ['file_path', null],
            ['file_name', null],
            ['file_type', null],
            ['mark', null],
        ]);

        $filterData = array_filter($data, fn ($val) => $val !== null);
        $invoice = $this->service->updateMyInvoice($this->entId, (int) $this->userInfo['id'], $id, $filterData);
        return $this->success('发票修改成功', $invoice->toArray());
    }

    /**
     * 员工删除发票
     */
    #[Delete('my/{id}', '员工删除发票')]
    public function deleteMy(int $id): mixed
    {
        $this->service->deleteMyInvoice($this->entId, (int) $this->userInfo['id'], $id);
        return $this->success('发票删除成功');
    }

    /**
     * 发票原件上传与服务端纯代码PDF解析
     */
    #[Post('my/parse', '员工发票原件上传解析')]
    public function parseFile(): mixed
    {
        $file = $this->request->file('file');
        $filePath = $this->request->input('file_path');

        $result = $this->service->parseInvoiceFile($this->entId, (int) $this->userInfo['id'], $file, $filePath);

        // 只有真正提取到发票要素才算解析成功；未提取到则完成原件上传并提示手动录入
        $parsed = ! empty($result['invoice_number']) || ! empty($result['total_amount']) || ! empty($result['seller_name']);
        $message = $parsed ? '发票要素已智能识别，请核对' : '原件已上传，请手动补录发票要素';
        return $this->success($message, $result);
    }

    /**
     * 发票代码+号码实时验重接口 (支持 GET 与 POST)
     */
    #[Get('check', '发票实时验重(GET)')]
    public function checkGet(): mixed
    {
        $invoiceCode = (string) $this->request->input('invoice_code', '');
        $invoiceNumber = (string) $this->request->input('invoice_number', '');
        $excludeId = $this->request->input('exclude_id') ? (int) $this->request->input('exclude_id') : null;

        $res = $this->service->checkDuplicate($this->entId, $invoiceCode, $invoiceNumber, $excludeId);
        return $this->success($res);
    }

    #[Post('check', '发票实时验重(POST)')]
    public function checkPost(): mixed
    {
        [$invoiceCode, $invoiceNumber, $excludeId] = $this->request->postMore([
            ['invoice_code', ''],
            ['invoice_number', ''],
            ['exclude_id', null],
        ], true);

        $excludeId = $excludeId ? (int) $excludeId : null;
        $res = $this->service->checkDuplicate($this->entId, (string) $invoiceCode, (string) $invoiceNumber, $excludeId);
        return $this->success($res);
    }

    /**
     * 财务端全局发票台账列表
     */
    #[Get('finance', '财务全局发票列表')]
    public function financeList(): mixed
    {
        $params = $this->request->getMore([
            ['page', 1],
            ['limit', 15],
            ['uid', ''],
            ['dep_id', ''],
            ['frame_id', ''],
            ['dep_name', ''],
            ['status', ''],
            ['abnormal_status', ''],
            ['invoice_type', ''],
            ['start_date', ''],
            ['end_date', ''],
            ['reimbursed_start', ''],
            ['reimbursed_end', ''],
            ['keyword', ''],
            ['keyword_user', ''],
        ]);

        $data = $this->service->getFinanceList($this->entId, $params);
        return $this->success($data);
    }

    /**
     * 财务端标记发票异常状态 (作废/红冲/假票/恢复正常)
     */
    #[Post('finance/abnormal', '财务标记发票异常状态')]
    public function markAbnormal(): mixed
    {
        [$id, $abnormalStatus, $reason] = $this->request->postMore([
            ['id', 0],
            ['abnormal_status', 0],
            ['abnormal_reason', ''],
        ], true);

        if (empty($id)) {
            return $this->fail('请提供有效的发票ID');
        }

        $invoice = $this->service->markAbnormal(
            $this->entId,
            (int) $this->userInfo['id'],
            (int) $id,
            (int) $abnormalStatus,
            (string) $reason
        );

        return $this->success('发票状态更新成功', $invoice->toArray());
    }

    /**
     * 财务发票台账导出 Excel
     */
    #[Get('finance/export', '财务发票导出Excel')]
    public function exportFinance(): mixed
    {
        $params = $this->request->getMore([
            ['uid', ''],
            ['dep_id', ''],
            ['frame_id', ''],
            ['dep_name', ''],
            ['status', ''],
            ['abnormal_status', ''],
            ['invoice_type', ''],
            ['start_date', ''],
            ['end_date', ''],
            ['keyword', ''],
            ['keyword_user', ''],
        ]);

        $result = $this->service->exportFinanceList($this->entId, $params);

        if ($this->request->input('download')) {
            return response()->download(public_path($result['file_path']), $result['file_name'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return $this->success('导出成功', $result);
    }

    /**
     * 报销单绑定发票
     */
    #[Post('bind', '报销单绑定发票')]
    public function bind(): mixed
    {
        [$applyId, $invoiceIds] = $this->request->postMore([
            ['apply_id', 0],
            ['invoice_ids', []],
        ], true);

        $res = $this->service->bindInvoices(
            $this->entId,
            (int) $this->userInfo['id'],
            (int) $applyId,
            (array) $invoiceIds
        );

        return $this->success('发票绑定成功', $res);
    }

    /**
     * 报销单解绑发票
     */
    #[Post('unbind', '报销单解绑发票')]
    public function unbind(): mixed
    {
        [$applyId, $invoiceIds] = $this->request->postMore([
            ['apply_id', 0],
            ['invoice_ids', []],
        ], true);

        $res = $this->service->unbindInvoices(
            $this->entId,
            (int) $this->userInfo['id'],
            (int) $applyId,
            (array) $invoiceIds
        );

        return $this->success('发票解绑成功', $res);
    }
}
