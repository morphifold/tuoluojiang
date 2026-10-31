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

namespace App\Http\Service\Expense;

use App\Http\Dao\Expense\ExpenseInvoiceDao;
use App\Http\Dao\Expense\ExpenseInvoiceLogDao;
use App\Http\Model\Admin\Admin;
use App\Http\Model\Expense\ExpenseInvoice;
use App\Http\Model\Expense\ExpenseInvoiceLog;
use App\Http\Service\BaseService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * 进项发票与报销防重核心业务类
 * Class ExpenseInvoiceService.
 */
class ExpenseInvoiceService extends BaseService
{
    public function __construct(
        ExpenseInvoiceDao $dao,
        protected ExpenseInvoiceLogDao $logDao,
        protected InvoicePdfParserService $pdfParser,
        protected TencentInvoiceOcrService $tencentOcrService
    ) {
        $this->dao = $dao;
    }

    /**
     * 发票查重核心逻辑【重中之重】
     * 规则：
     * 1. 强防重拦截依据：发票代码 + 发票号码（全电/数电发票代码存为空字符串）。
     * 2. 只有处于正常状态（abnormal_status = 0）且未被软删除（deleted_at IS NULL）的发票构成排他防重拦截。
     * 3. 处于异常标记（作废/红冲/假票）或员工删除未报销的发票（软删除）自动解除排他防重，允许修正后重新录入。
     * 4. 命中重复时，清晰提示录入员工姓名及录入时间。
     */
    public function checkDuplicate(int $entId, string $invoiceCode, string $invoiceNumber, ?int $excludeId = null): array
    {
        $invoiceCode = trim($invoiceCode);
        $invoiceNumber = trim($invoiceNumber);

        if ($invoiceNumber === '') {
            return [
                'is_duplicate'  => false,
                'exist_invoice' => null,
                'message'       => '发票号码为空，无法进行验重',
            ];
        }

        $exist = $this->dao->findActiveByCodeAndNumber($entId, $invoiceCode, $invoiceNumber, $excludeId);

        if (! $exist) {
            return [
                'is_duplicate'  => false,
                'exist_invoice' => null,
                'message'       => '验重通过，库中无重复发票记录',
            ];
        }

        $userName = $exist->user?->name ?: ($exist->user?->account ?: ('员工#' . $exist->uid));
        $createdAt = $exist->created_at ? (string) $exist->created_at : '未知时间';

        $codeTip = $invoiceCode !== '' ? "【代码：{$invoiceCode}，号码：{$invoiceNumber}】" : "【号码：{$invoiceNumber}】";
        $message = "发票{$codeTip}已存在，由员工【{$userName}】于【{$createdAt}】录入，请勿重复报销！";

        return [
            'is_duplicate'  => true,
            'exist_invoice' => [
                'id'          => $exist->id,
                'uid'         => $exist->uid,
                'user_name'   => $userName,
                'created_at'  => (string) $exist->created_at,
                'status'      => $exist->status,
                'status_text' => ExpenseInvoice::getStatusText($exist->status),
                'apply_id'    => $exist->apply_id,
            ],
            'message'       => $message,
        ];
    }

    /**
     * 员工端发票列表
     */
    public function getMyList(int $entId, int $uid, array $params): array
    {
        $query = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('uid', $uid);

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        // 发票类型筛选
        if (! empty($params['invoice_type'])) {
            $query->where('invoice_type', (string) $params['invoice_type']);
        }

        // 异常状态筛选
        if (isset($params['abnormal_status']) && $params['abnormal_status'] !== '') {
            $query->where('abnormal_status', (int) $params['abnormal_status']);
        }

        // 开票日期区间
        if (! empty($params['start_date'])) {
            $query->whereDate('invoice_date', '>=', $params['start_date']);
        }
        if (! empty($params['end_date'])) {
            $query->whereDate('invoice_date', '<=', $params['end_date']);
        }

        // 金额区间
        if (isset($params['min_amount']) && $params['min_amount'] !== '') {
            $query->where('total_amount', '>=', (float) $params['min_amount']);
        }
        if (isset($params['max_amount']) && $params['max_amount'] !== '') {
            $query->where('total_amount', '<=', (float) $params['max_amount']);
        }

        // 关键字搜索
        if (! empty($params['keyword'])) {
            $kw = trim((string) $params['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('invoice_number', 'like', "%{$kw}%")
                    ->orWhere('invoice_code', 'like', "%{$kw}%")
                    ->orWhere('seller_name', 'like', "%{$kw}%")
                    ->orWhere('buyer_name', 'like', "%{$kw}%")
                    ->orWhere('mark', 'like', "%{$kw}%");
            });
        }

        $count = $query->count();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 15)));

        $list = $query->with(['apply:id,status,number'])
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                $arr = $item->toArray();
                $arr['status_text'] = ExpenseInvoice::getStatusText($item->status);
                $arr['abnormal_status_text'] = ExpenseInvoice::getAbnormalStatusText($item->abnormal_status);
                $arr['invoice_type_text'] = ExpenseInvoice::getTypeText($item->invoice_type);
                $arr['file_url'] = $item->file_path ? link_file('/' . ltrim($item->file_path, '/')) : '';
                return $arr;
            })
            ->toArray();

        // 统计汇总：按员工全量口径汇总，不受列表筛选条件影响
        $statQuery = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('uid', $uid);

        $stat = [
            'total_count'       => (int) (clone $statQuery)->count(),
            'total_amount'      => (float) (clone $statQuery)->sum('total_amount'),
            'unbound_count'     => (int) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_UNBOUND)->count(),
            'unbound_amount'    => (float) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_UNBOUND)->sum('total_amount'),
            'binding_count'     => (int) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_BINDING)->count(),
            'binding_amount'    => (float) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_BINDING)->sum('total_amount'),
            'reimbursed_count'  => (int) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_REIMBURSED)->count(),
            'reimbursed_amount' => (float) (clone $statQuery)->where('status', ExpenseInvoice::STATUS_REIMBURSED)->sum('total_amount'),
        ];

        return [
            'list'  => $list,
            'count' => $count,
            'stat'  => $stat,
        ];
    }

    /**
     * 员工获取发票详情
     */
    public function getMyDetail(int $entId, int $uid, int $id): array
    {
        $invoice = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('uid', $uid)
            ->where('id', $id)
            ->with(['user', 'apply', 'logs.user'])
            ->first();

        if (! $invoice) {
            throw $this->exception('未找到该发票信息或无权查看');
        }

        $data = $invoice->toArray();
        $data['status_text'] = ExpenseInvoice::getStatusText($invoice->status);
        $data['abnormal_status_text'] = ExpenseInvoice::getAbnormalStatusText($invoice->abnormal_status);
        $data['invoice_type_text'] = ExpenseInvoice::getTypeText($invoice->invoice_type);
        $data['file_url'] = $invoice->file_path ? link_file('/' . ltrim($invoice->file_path, '/')) : '';

        return $data;
    }

    /**
     * 员工手动录入发票
     */
    public function saveMyInvoice(int $entId, int $uid, array $data): ExpenseInvoice
    {
        $invoiceCode = trim((string) ($data['invoice_code'] ?? ''));
        $invoiceNumber = trim((string) ($data['invoice_number'] ?? ''));

        if ($invoiceNumber === '') {
            throw $this->exception('发票号码不能为空');
        }

        $totalAmount = (float) ($data['total_amount'] ?? 0);
        if ($totalAmount <= 0 && (! isset($data['amount']) || (float) $data['amount'] <= 0)) {
            throw $this->exception('发票金额必须大于0');
        }

        // 发票类型识别与标准化（支持数字代码 1:专票, 2:普票, 3:数电专票, 4:数电普票, 5:通行费, 6:定额, 7:加油）
        $typeMap = [
            '1' => ExpenseInvoice::TYPE_SPECIAL,
            '2' => ExpenseInvoice::TYPE_GENERAL,
            '3' => ExpenseInvoice::TYPE_DIGITAL_SPECIAL,
            '4' => ExpenseInvoice::TYPE_DIGITAL_GENERAL,
            '5' => ExpenseInvoice::TYPE_TOLL,
            '6' => ExpenseInvoice::TYPE_QUOTA,
            '7' => ExpenseInvoice::TYPE_PETROL,
        ];
        $invoiceType = (string) ($data['invoice_type'] ?? ExpenseInvoice::TYPE_GENERAL);
        if (isset($typeMap[$invoiceType])) {
            $invoiceType = $typeMap[$invoiceType];
        }
        if (strlen($invoiceNumber) === 20 && $invoiceCode === '') {
            if ($invoiceType !== ExpenseInvoice::TYPE_DIGITAL_SPECIAL && $invoiceType !== ExpenseInvoice::TYPE_SPECIAL) {
                $invoiceType = ExpenseInvoice::TYPE_DIGITAL_GENERAL;
            }
        }

        // 核心防重复校验
        $check = $this->checkDuplicate($entId, $invoiceCode, $invoiceNumber);
        if ($check['is_duplicate']) {
            throw $this->exception($check['message']);
        }

        $amount = isset($data['amount']) ? (float) $data['amount'] : $totalAmount;
        $taxAmount = isset($data['tax_amount']) ? (float) $data['tax_amount'] : 0.00;
        if ($totalAmount <= 0) {
            $totalAmount = round($amount + $taxAmount, 2);
        }

        $invoiceDate = ! empty($data['invoice_date']) ? Carbon::parse($data['invoice_date'])->toDateString() : null;

        return $this->transaction(function () use ($entId, $uid, $invoiceCode, $invoiceNumber, $invoiceType, $totalAmount, $amount, $taxAmount, $invoiceDate, $data) {
            /** @var ExpenseInvoice $invoice */
            $invoice = $this->dao->create([
                'entid'           => $entId,
                'uid'             => $uid,
                'invoice_type'    => $invoiceType,
                'invoice_code'    => $invoiceCode,
                'invoice_number'  => $invoiceNumber,
                'amount'          => $amount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
                'invoice_date'    => $invoiceDate,
                'seller_name'     => trim((string) ($data['seller_name'] ?? '')),
                'buyer_name'      => trim((string) ($data['buyer_name'] ?? '')),
                'status'          => ExpenseInvoice::STATUS_UNBOUND,
                'abnormal_status' => ExpenseInvoice::ABNORMAL_NORMAL,
                'abnormal_reason' => '',
                'file_path'       => (string) ($data['file_path'] ?? ''),
                'file_name'       => (string) ($data['file_name'] ?? ''),
                'file_type'       => (string) ($data['file_type'] ?? ''),
                'mark'            => (string) ($data['mark'] ?? ''),
                'apply_id'        => 0,
            ]);

            $this->recordLog(
                $entId,
                $invoice->id,
                $uid,
                ExpenseInvoiceLog::ACTION_CREATE,
                null,
                $invoice->toArray(),
                '员工录入发票（号码：' . $invoiceNumber . '）'
            );

            return $invoice;
        });
    }

    /**
     * 员工编辑发票
     */
    public function updateMyInvoice(int $entId, int $uid, int $id, array $data): ExpenseInvoice
    {
        /** @var ExpenseInvoice $invoice */
        $invoice = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('uid', $uid)
            ->where('id', $id)
            ->first();

        if (! $invoice) {
            throw $this->exception('未找到可修改的发票或无权操作');
        }

        // 状态机校验：已绑定报销或已完成归档锁定的发票不可随意修改
        if ($invoice->status === ExpenseInvoice::STATUS_BINDING) {
            throw $this->exception('该发票正处于报销审批中，不可修改！如需调整请先撤销报销审批');
        }
        if ($invoice->status === ExpenseInvoice::STATUS_REIMBURSED) {
            throw $this->exception('该发票已报销归档锁定，严禁修改！');
        }

        $invoiceCode = isset($data['invoice_code']) ? trim((string) $data['invoice_code']) : $invoice->invoice_code;
        $invoiceNumber = isset($data['invoice_number']) ? trim((string) $data['invoice_number']) : $invoice->invoice_number;

        if ($invoiceNumber === '') {
            throw $this->exception('发票号码不能为空');
        }

        // 查重时排除当前发票ID
        $check = $this->checkDuplicate($entId, $invoiceCode, $invoiceNumber, $id);
        if ($check['is_duplicate']) {
            throw $this->exception($check['message']);
        }

        $oldData = $invoice->toArray();

        $updateData = [
            'invoice_code'   => $invoiceCode,
            'invoice_number' => $invoiceNumber,
        ];

        if (isset($data['invoice_type'])) {
            $typeMap = [
                '1' => ExpenseInvoice::TYPE_SPECIAL,
                '2' => ExpenseInvoice::TYPE_GENERAL,
                '3' => ExpenseInvoice::TYPE_DIGITAL_SPECIAL,
                '4' => ExpenseInvoice::TYPE_DIGITAL_GENERAL,
                '5' => ExpenseInvoice::TYPE_TOLL,
                '6' => ExpenseInvoice::TYPE_QUOTA,
                '7' => ExpenseInvoice::TYPE_PETROL,
            ];
            $rawType = (string) $data['invoice_type'];
            $updateData['invoice_type'] = $typeMap[$rawType] ?? $rawType;
        }
        if (isset($data['amount'])) {
            $updateData['amount'] = (float) $data['amount'];
        }
        if (isset($data['tax_amount'])) {
            $updateData['tax_amount'] = (float) $data['tax_amount'];
        }
        if (isset($data['total_amount'])) {
            $updateData['total_amount'] = (float) $data['total_amount'];
        }
        if (isset($data['invoice_date'])) {
            $updateData['invoice_date'] = ! empty($data['invoice_date']) ? Carbon::parse($data['invoice_date'])->toDateString() : null;
        }
        if (isset($data['seller_name'])) {
            $updateData['seller_name'] = trim((string) $data['seller_name']);
        }
        if (isset($data['buyer_name'])) {
            $updateData['buyer_name'] = trim((string) $data['buyer_name']);
        }
        if (isset($data['file_path'])) {
            $updateData['file_path'] = (string) $data['file_path'];
        }
        if (isset($data['file_name'])) {
            $updateData['file_name'] = (string) $data['file_name'];
        }
        if (isset($data['file_type'])) {
            $updateData['file_type'] = (string) $data['file_type'];
        }
        if (isset($data['mark'])) {
            $updateData['mark'] = (string) $data['mark'];
        }

        return $this->transaction(function () use ($invoice, $updateData, $entId, $id, $uid, $oldData) {
            $invoice->update($updateData);
            $newData = $invoice->fresh()->toArray();

            $this->recordLog(
                $entId,
                $id,
                $uid,
                ExpenseInvoiceLog::ACTION_UPDATE,
                $oldData,
                $newData,
                '员工修改发票信息'
            );

            return $invoice;
        });
    }

    /**
     * 员工删除发票
     * 软删除释放排他防重，允许修正后重新录入
     */
    public function deleteMyInvoice(int $entId, int $uid, int $id): bool
    {
        /** @var ExpenseInvoice $invoice */
        $invoice = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('uid', $uid)
            ->where('id', $id)
            ->first();

        if (! $invoice) {
            throw $this->exception('未找到该发票或无权操作');
        }

        if ($invoice->status === ExpenseInvoice::STATUS_BINDING) {
            throw $this->exception('该发票正处于报销审批中，不可删除！如需删除请先解绑或撤回审批单');
        }
        if ($invoice->status === ExpenseInvoice::STATUS_REIMBURSED) {
            throw $this->exception('该发票已报销归档锁定，不可删除！');
        }

        $oldData = $invoice->toArray();

        return (bool) $this->transaction(function () use ($invoice, $entId, $id, $uid, $oldData) {
            $invoice->delete();

            $this->recordLog(
                $entId,
                $id,
                $uid,
                ExpenseInvoiceLog::ACTION_DELETE,
                $oldData,
                null,
                '员工删除未报销发票（软删除，释放防重锁定）'
            );

            return true;
        });
    }

    /**
     * 发票原件上传与PDF服务端解析
     */
    public function parseInvoiceFile(int $entId, int $uid, $file, ?string $filePath = null): array
    {
        $savedFilePath = '';
        $originalFileName = '';
        $extension = 'pdf';

        if ($file instanceof UploadedFile) {
            $originalFileName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $dateDir = 'uploads/invoices/' . date('Ymd');
            $targetDir = public_path($dateDir);

            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $newFileName = date('His') . '_' . Str::random(8) . '.' . $extension;
            $file->move($targetDir, $newFileName);

            $savedFilePath = $dateDir . '/' . $newFileName;
            $fullPath = $targetDir . '/' . $newFileName;
        } elseif (! empty($filePath)) {
            $savedFilePath = $filePath;
            $originalFileName = basename($filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $fullPath = public_path($filePath);
            if (! file_exists($fullPath)) {
                $fullPath = $filePath;
            }
        } else {
            throw $this->exception('未提供发票原件文件或文件路径');
        }

        // 解析电子发票数据
        $parsed = [];
        if (in_array($extension, ['pdf'])) {
            $parsed = $this->pdfParser->parse($fullPath);
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            // 图片类型优先调用腾讯云增值税发票识别 (VatInvoiceOCR)
            $ocrResult = $this->tencentOcrService->recognize($fullPath);
            if ($ocrResult['success'] && ! empty($ocrResult['data'])) {
                $parsed = array_merge($this->pdfParser->getEmptyResult($originalFileName), $ocrResult['data']);
                $parsed['ocr_recognized'] = true;
            } else {
                // OCR 未配置或识别失败，优雅降级，保持现状
                $parsed = $this->pdfParser->getEmptyResult($originalFileName);
                $parsed['ocr_recognized'] = false;
                $parsed['ocr_message'] = $ocrResult['message'] ?? '';
            }
        } else {
            // 其他文件类型降级结构
            $parsed = $this->pdfParser->getEmptyResult($originalFileName);
            $parsed['ocr_recognized'] = false;
        }

        $parsed['file_path'] = $savedFilePath;
        $parsed['file_name'] = $originalFileName;
        $parsed['file_type'] = $extension;

        // 实时附带查重状态
        if (! empty($parsed['invoice_number'])) {
            $dupCheck = $this->checkDuplicate($entId, (string) $parsed['invoice_code'], (string) $parsed['invoice_number']);
            $parsed['is_duplicate'] = $dupCheck['is_duplicate'];
            $parsed['duplicate_message'] = $dupCheck['message'];
            $parsed['exist_invoice'] = $dupCheck['exist_invoice'];
        } else {
            $parsed['is_duplicate'] = false;
            $parsed['duplicate_message'] = '';
            $parsed['exist_invoice'] = null;
        }

        return $parsed;
    }

    /**
     * 财务端全局发票台账列表
     */
    public function getFinanceList(int $entId, array $params): array
    {
        $query = $this->dao->getModel()
            ->where('entid', $entId);

        // 指定员工
        if (! empty($params['uid'])) {
            $query->where('uid', (int) $params['uid']);
        }

        // 指定部门/架构：dep_id/frame_id 按部门 ID，dep_name 按主部门名称模糊匹配
        $frameId = (int) ($params['dep_id'] ?? ($params['frame_id'] ?? 0));
        if ($frameId > 0) {
            $frameUserIds = DB::table('frame_assist')
                ->where('frame_id', $frameId)
                ->pluck('user_id')
                ->toArray();
            $query->whereIn('uid', $frameUserIds ?: [0]);
        } elseif (! empty($params['dep_name'])) {
            $depName = trim((string) $params['dep_name']);
            $depUserIds = DB::table('frame_assist')
                ->join('frame', 'frame.id', '=', 'frame_assist.frame_id')
                ->where('frame_assist.is_mastart', 1)
                ->where('frame.name', 'like', "%{$depName}%")
                ->pluck('frame_assist.user_id')
                ->toArray();
            $query->whereIn('uid', $depUserIds ?: [0]);
        }

        // 报销状态
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        // 异常状态
        if (isset($params['abnormal_status']) && $params['abnormal_status'] !== '') {
            $query->where('abnormal_status', (int) $params['abnormal_status']);
        }

        // 发票类型
        if (! empty($params['invoice_type'])) {
            $query->where('invoice_type', (string) $params['invoice_type']);
        }

        // 开票日期区间
        if (! empty($params['start_date'])) {
            $query->whereDate('invoice_date', '>=', $params['start_date']);
        }
        if (! empty($params['end_date'])) {
            $query->whereDate('invoice_date', '<=', $params['end_date']);
        }

        // 报销归档日期区间
        if (! empty($params['reimbursed_start'])) {
            $query->whereDate('reimbursed_at', '>=', $params['reimbursed_start']);
        }
        if (! empty($params['reimbursed_end'])) {
            $query->whereDate('reimbursed_at', '<=', $params['reimbursed_end']);
        }

        // 关键词搜索
        if (! empty($params['keyword'])) {
            $kw = trim((string) $params['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('invoice_number', 'like', "%{$kw}%")
                    ->orWhere('invoice_code', 'like', "%{$kw}%")
                    ->orWhere('seller_name', 'like', "%{$kw}%")
                    ->orWhere('buyer_name', 'like', "%{$kw}%")
                    ->orWhere('mark', 'like', "%{$kw}%")
                    ->orWhereHas('user', function ($sub) use ($kw) {
                        $sub->where('name', 'like', "%{$kw}%")
                            ->orWhere('account', 'like', "%{$kw}%");
                    });
            });
        }

        // 报销人筛选：姓名 / 账号 / 手机号模糊匹配
        if (! empty($params['keyword_user'])) {
            $kwUser = trim((string) $params['keyword_user']);
            $query->whereHas('user', function ($sub) use ($kwUser) {
                $sub->where('name', 'like', "%{$kwUser}%")
                    ->orWhere('account', 'like', "%{$kwUser}%")
                    ->orWhere('phone', 'like', "%{$kwUser}%");
            });
        }

        $count = $query->count();
        $totalAmount = (float) (clone $query)->sum('total_amount');
        $unboundCount = (int) (clone $query)->where('status', ExpenseInvoice::STATUS_UNBOUND)->count();
        $unboundAmount = (float) (clone $query)->where('status', ExpenseInvoice::STATUS_UNBOUND)->sum('total_amount');
        $bindingCount = (int) (clone $query)->where('status', ExpenseInvoice::STATUS_BINDING)->count();
        $bindingAmount = (float) (clone $query)->where('status', ExpenseInvoice::STATUS_BINDING)->sum('total_amount');
        $reimbursedCount = (int) (clone $query)->where('status', ExpenseInvoice::STATUS_REIMBURSED)->count();
        $reimbursedAmount = (float) (clone $query)->where('status', ExpenseInvoice::STATUS_REIMBURSED)->sum('total_amount');
        $abnormalCount = (int) (clone $query)->where('abnormal_status', '>', ExpenseInvoice::ABNORMAL_NORMAL)->count();
        $abnormalAmount = (float) (clone $query)->where('abnormal_status', '>', ExpenseInvoice::ABNORMAL_NORMAL)->sum('total_amount');

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? 15)));

        $list = $query->with(['user.frame', 'apply:id,status,number'])
            ->orderByDesc('id')
            ->forPage($page, $limit)
            ->get()
            ->map(function ($item) {
                $arr = $item->toArray();
                $arr['status_text'] = ExpenseInvoice::getStatusText($item->status);
                $arr['abnormal_status_text'] = ExpenseInvoice::getAbnormalStatusText($item->abnormal_status);
                $arr['invoice_type_text'] = ExpenseInvoice::getTypeText($item->invoice_type);
                $arr['user_name'] = $item->user?->name ?: ($item->user?->account ?: '用户#' . $item->uid);
                // 主部门口径与全站一致：Admin::frame() 取 frame_assist.is_mastart = 1
                $arr['frame_id'] = $item->user?->frame?->id ?: 0;
                $arr['dep_name'] = $item->user?->frame?->name ?: '';
                $arr['file_url'] = $item->file_path ? link_file('/' . ltrim($item->file_path, '/')) : '';
                return $arr;
            })
            ->toArray();

        return [
            'list'  => $list,
            'count' => $count,
            'stat'  => [
                'total_count'       => $count,
                'total_amount'      => $totalAmount,
                'unbound_count'     => $unboundCount,
                'unbound_amount'    => $unboundAmount,
                'binding_count'     => $bindingCount,
                'binding_amount'    => $bindingAmount,
                'reimbursed_count'  => $reimbursedCount,
                'reimbursed_amount' => $reimbursedAmount,
                'abnormal_count'    => $abnormalCount,
                'abnormal_amount'   => $abnormalAmount,
            ],
        ];
    }

    /**
     * 财务端标记发票异常状态
     * 标记作废/红冲/假票会释放防重排他占用，允许修正后重新录入
     */
    public function markAbnormal(int $entId, int $operatorUid, int $id, int $abnormalStatus, string $reason): ExpenseInvoice
    {
        /** @var ExpenseInvoice $invoice */
        $invoice = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('id', $id)
            ->first();

        if (! $invoice) {
            throw $this->exception('未找到该发票信息');
        }

        if (! in_array($abnormalStatus, [
            ExpenseInvoice::ABNORMAL_NORMAL,
            ExpenseInvoice::ABNORMAL_VOID,
            ExpenseInvoice::ABNORMAL_RED,
            ExpenseInvoice::ABNORMAL_FAKE,
        ], true)) {
            throw $this->exception('无效的异常状态值');
        }

        $oldData = $invoice->toArray();
        $statusText = ExpenseInvoice::getAbnormalStatusText($abnormalStatus);

        return $this->transaction(function () use ($invoice, $abnormalStatus, $reason, $statusText, $entId, $id, $operatorUid, $oldData) {
            $invoice->update([
                'abnormal_status' => $abnormalStatus,
                'abnormal_reason' => trim($reason),
            ]);

            $remark = "财务人员将发票异常状态变更为【{$statusText}】";
            if ($reason !== '') {
                $remark .= "，原因：{$reason}";
            }

            $this->recordLog(
                $entId,
                $id,
                $operatorUid,
                ExpenseInvoiceLog::ACTION_ABNORMAL,
                $oldData,
                $invoice->fresh()->toArray(),
                $remark
            );

            return $invoice;
        });
    }

    /**
     * 财务导出发票台账 Excel
     */
    public function exportFinanceList(int $entId, array $params): array
    {
        $query = $this->dao->getModel()
            ->where('entid', $entId);

        if (! empty($params['uid'])) {
            $query->where('uid', (int) $params['uid']);
        }

        $frameId = (int) ($params['dep_id'] ?? ($params['frame_id'] ?? 0));
        if ($frameId > 0) {
            $frameUserIds = DB::table('frame_assist')
                ->where('frame_id', $frameId)
                ->pluck('user_id')
                ->toArray();
            $query->whereIn('uid', $frameUserIds ?: [0]);
        } elseif (! empty($params['dep_name'])) {
            $depName = trim((string) $params['dep_name']);
            $depUserIds = DB::table('frame_assist')
                ->join('frame', 'frame.id', '=', 'frame_assist.frame_id')
                ->where('frame_assist.is_mastart', 1)
                ->where('frame.name', 'like', "%{$depName}%")
                ->pluck('frame_assist.user_id')
                ->toArray();
            $query->whereIn('uid', $depUserIds ?: [0]);
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', (int) $params['status']);
        }

        if (isset($params['abnormal_status']) && $params['abnormal_status'] !== '') {
            $query->where('abnormal_status', (int) $params['abnormal_status']);
        }

        if (! empty($params['invoice_type'])) {
            $query->where('invoice_type', (string) $params['invoice_type']);
        }

        if (! empty($params['start_date'])) {
            $query->whereDate('invoice_date', '>=', $params['start_date']);
        }
        if (! empty($params['end_date'])) {
            $query->whereDate('invoice_date', '<=', $params['end_date']);
        }

        if (! empty($params['keyword'])) {
            $kw = trim((string) $params['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('invoice_number', 'like', "%{$kw}%")
                    ->orWhere('invoice_code', 'like', "%{$kw}%")
                    ->orWhere('seller_name', 'like', "%{$kw}%")
                    ->orWhere('buyer_name', 'like', "%{$kw}%")
                    ->orWhere('mark', 'like', "%{$kw}%");
            });
        }

        // 报销人筛选：姓名 / 账号 / 手机号模糊匹配
        if (! empty($params['keyword_user'])) {
            $kwUser = trim((string) $params['keyword_user']);
            $query->whereHas('user', function ($sub) use ($kwUser) {
                $sub->where('name', 'like', "%{$kwUser}%")
                    ->orWhere('account', 'like', "%{$kwUser}%")
                    ->orWhere('phone', 'like', "%{$kwUser}%");
            });
        }

        $invoices = $query->with(['user.frame', 'apply:id,status,number'])
            ->orderByDesc('id')
            ->get();

        // 构造 Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('进项发票台账');

        $headers = [
            '序号',
            '录入员工',
            '所属部门',
            '发票类型',
            '发票代码',
            '发票号码',
            '开票日期',
            '价税合计(元)',
            '不含税金额(元)',
            '税额(元)',
            '销售方名称',
            '购买方名称',
            '报销状态',
            '关联审批单',
            '报销归档时间',
            '异常状态',
            '异常原因',
            '事由备注',
            '录入时间',
        ];

        // 填充表头
        $colIndex = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($colIndex . '1', $header);
            $colIndex++;
        }

        // 表头样式
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerRange = "A1:{$lastCol}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F4F7');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 填充数据行
        $rowIndex = 2;
        $num = 1;
        foreach ($invoices as $inv) {
            $userName = $inv->user?->name ?: ($inv->user?->account ?: '用户#' . $inv->uid);
            $depName = $inv->user?->frame?->name ?: '-';
            $applySn = $inv->apply?->number ?: ($inv->apply_id ? ('单号#' . $inv->apply_id) : '-');

            $sheet->setCellValue('A' . $rowIndex, $num++);
            $sheet->setCellValue('B' . $rowIndex, $userName);
            $sheet->setCellValue('C' . $rowIndex, $depName);
            $sheet->setCellValue('D' . $rowIndex, ExpenseInvoice::getTypeText($inv->invoice_type));
            $sheet->setCellValueExplicit('E' . $rowIndex, (string) $inv->invoice_code, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $rowIndex, (string) $inv->invoice_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowIndex, $inv->invoice_date ?: '-');
            $sheet->setCellValue('H' . $rowIndex, number_format((float) $inv->total_amount, 2, '.', ''));
            $sheet->setCellValue('I' . $rowIndex, number_format((float) $inv->amount, 2, '.', ''));
            $sheet->setCellValue('J' . $rowIndex, number_format((float) $inv->tax_amount, 2, '.', ''));
            $sheet->setCellValue('K' . $rowIndex, $inv->seller_name ?: '-');
            $sheet->setCellValue('L' . $rowIndex, $inv->buyer_name ?: '-');
            $sheet->setCellValue('M' . $rowIndex, ExpenseInvoice::getStatusText($inv->status));
            $sheet->setCellValue('N' . $rowIndex, $applySn);
            $sheet->setCellValue('O' . $rowIndex, $inv->reimbursed_at ? (string) $inv->reimbursed_at : '-');
            $sheet->setCellValue('P' . $rowIndex, ExpenseInvoice::getAbnormalStatusText($inv->abnormal_status));
            $sheet->setCellValue('Q' . $rowIndex, $inv->abnormal_reason ?: '-');
            $sheet->setCellValue('R' . $rowIndex, $inv->mark ?: '-');
            $sheet->setCellValue('S' . $rowIndex, (string) $inv->created_at);

            $rowIndex++;
        }

        // 自动列宽
        for ($col = 'A'; $col <= $lastCol; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 保存文件
        $exportDir = 'uploads/export/invoices';
        $fullExportDir = public_path($exportDir);
        if (! is_dir($fullExportDir)) {
            @mkdir($fullExportDir, 0755, true);
        }

        $fileName = '进项发票台账_' . date('Ymd_His') . '_' . Str::random(6) . '.xlsx';
        $relativeFilePath = $exportDir . '/' . $fileName;
        $fullFilePath = $fullExportDir . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullFilePath);

        return [
            'file_name' => $fileName,
            'file_path' => $relativeFilePath,
            'url'       => link_file($relativeFilePath),
            'count'     => count($invoices),
        ];
    }

    /**
     * 报销单绑定发票接口
     */
    public function bindInvoices(int $entId, int $uid, int $applyId, array $invoiceIds): array
    {
        if ($applyId <= 0) {
            throw $this->exception('请提供有效的审批单ID');
        }

        if (empty($invoiceIds)) {
            throw $this->exception('请至少选择一张发票进行绑定');
        }

        $invoices = $this->dao->getModel()
            ->where('entid', $entId)
            ->whereIn('id', $invoiceIds)
            ->get();

        if ($invoices->count() !== count($invoiceIds)) {
            throw $this->exception('部分选择的发票不存在或无权操作');
        }

        $boundIds = [];
        $totalAmount = 0.00;

        $this->transaction(function () use ($invoices, $applyId, $entId, $uid, &$boundIds, &$totalAmount) {
            foreach ($invoices as $invoice) {
                // 异常状态检查
                if ($invoice->abnormal_status !== ExpenseInvoice::ABNORMAL_NORMAL) {
                    $statusName = ExpenseInvoice::getAbnormalStatusText($invoice->abnormal_status);
                    throw $this->exception("发票【号码：{$invoice->invoice_number}】处于异常状态（{$statusName}），不可绑定报销！");
                }

                // 业务状态检查：必须是未绑定状态
                if ($invoice->status !== ExpenseInvoice::STATUS_UNBOUND) {
                    if ($invoice->status === ExpenseInvoice::STATUS_BINDING && $invoice->apply_id === $applyId) {
                        // 已经绑定到当前单据，幂等跳过
                        $boundIds[] = $invoice->id;
                        $totalAmount += (float) $invoice->total_amount;
                        continue;
                    }
                    $statusDesc = ExpenseInvoice::getStatusText($invoice->status);
                    throw $this->exception("发票【号码：{$invoice->invoice_number}】当前为【{$statusDesc}】状态，不可绑定！");
                }

                $oldData = $invoice->toArray();
                $invoice->update([
                    'status'   => ExpenseInvoice::STATUS_BINDING,
                    'apply_id' => $applyId,
                ]);

                $this->recordLog(
                    $entId,
                    $invoice->id,
                    $uid,
                    ExpenseInvoiceLog::ACTION_BIND,
                    $oldData,
                    $invoice->fresh()->toArray(),
                    "绑定至报销审批单 #{$applyId}"
                );

                $boundIds[] = $invoice->id;
                $totalAmount += (float) $invoice->total_amount;
            }
        });

        return [
            'apply_id'     => $applyId,
            'bound_count'  => count($boundIds),
            'bound_ids'    => $boundIds,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * 报销单解绑发票接口
     */
    public function unbindInvoices(int $entId, int $uid, int $applyId, array $invoiceIds = []): array
    {
        if ($applyId <= 0) {
            throw $this->exception('请提供有效的审批单ID');
        }

        $query = $this->dao->getModel()
            ->where('entid', $entId)
            ->where('apply_id', $applyId);

        if (! empty($invoiceIds)) {
            $query->whereIn('id', $invoiceIds);
        }

        $invoices = $query->get();
        if ($invoices->isEmpty()) {
            return [
                'apply_id'     => $applyId,
                'unbound_count'=> 0,
                'unbound_ids'  => [],
            ];
        }

        $unboundIds = [];

        $this->transaction(function () use ($invoices, $applyId, $entId, $uid, &$unboundIds) {
            foreach ($invoices as $invoice) {
                // 已报销锁定归档的禁止解绑
                if ($invoice->status === ExpenseInvoice::STATUS_REIMBURSED) {
                    throw $this->exception("发票【号码：{$invoice->invoice_number}】已完成报销归档锁定，严禁解绑！");
                }

                $oldData = $invoice->toArray();
                $invoice->update([
                    'status'   => ExpenseInvoice::STATUS_UNBOUND,
                    'apply_id' => 0,
                ]);

                $this->recordLog(
                    $entId,
                    $invoice->id,
                    $uid,
                    ExpenseInvoiceLog::ACTION_UNBIND,
                    $oldData,
                    $invoice->fresh()->toArray(),
                    "从报销审批单 #{$applyId} 解绑"
                );

                $unboundIds[] = $invoice->id;
            }
        });

        return [
            'apply_id'      => $applyId,
            'unbound_count' => count($unboundIds),
            'unbound_ids'   => $unboundIds,
        ];
    }

    /**
     * 审批流通过事件回调：锁定归档为“已报销”
     */
    public function onApprovePassed(int $applyId): void
    {
        $invoices = $this->dao->getModel()
            ->where('apply_id', $applyId)
            ->where('status', ExpenseInvoice::STATUS_BINDING)
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->transaction(function () use ($invoices, $applyId, $now) {
            foreach ($invoices as $invoice) {
                $oldData = $invoice->toArray();
                $invoice->update([
                    'status'        => ExpenseInvoice::STATUS_REIMBURSED,
                    'reimbursed_at' => $now,
                ]);

                $this->recordLog(
                    (int) $invoice->entid,
                    $invoice->id,
                    (int) $invoice->uid,
                    ExpenseInvoiceLog::ACTION_REIMBURSE,
                    $oldData,
                    $invoice->fresh()->toArray(),
                    "报销审批单 #{$applyId} 审核通过，发票归档锁定为已报销"
                );
            }
        });
    }

    /**
     * 审批流驳回事件回调：重置为“未绑定”
     */
    public function onApproveRejected(int $applyId): void
    {
        $invoices = $this->dao->getModel()
            ->where('apply_id', $applyId)
            ->where('status', ExpenseInvoice::STATUS_BINDING)
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $this->transaction(function () use ($invoices, $applyId) {
            foreach ($invoices as $invoice) {
                $oldData = $invoice->toArray();
                $invoice->update([
                    'status'   => ExpenseInvoice::STATUS_UNBOUND,
                    'apply_id' => 0,
                ]);

                $this->recordLog(
                    (int) $invoice->entid,
                    $invoice->id,
                    (int) $invoice->uid,
                    ExpenseInvoiceLog::ACTION_UNBIND,
                    $oldData,
                    $invoice->fresh()->toArray(),
                    "报销审批单 #{$applyId} 审批驳回，发票解除绑定"
                );
            }
        });
    }

    /**
     * 审批流撤销事件回调：重置为“未绑定”
     */
    public function onApproveRevoked(int $applyId): void
    {
        $invoices = $this->dao->getModel()
            ->where('apply_id', $applyId)
            ->where('status', ExpenseInvoice::STATUS_BINDING)
            ->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $this->transaction(function () use ($invoices, $applyId) {
            foreach ($invoices as $invoice) {
                $oldData = $invoice->toArray();
                $invoice->update([
                    'status'   => ExpenseInvoice::STATUS_UNBOUND,
                    'apply_id' => 0,
                ]);

                $this->recordLog(
                    (int) $invoice->entid,
                    $invoice->id,
                    (int) $invoice->uid,
                    ExpenseInvoiceLog::ACTION_UNBIND,
                    $oldData,
                    $invoice->fresh()->toArray(),
                    "报销审批单 #{$applyId} 撤销申请，发票解除绑定"
                );
            }
        });
    }

    /**
     * 记录发票操作日志
     */
    protected function recordLog(
        int $entId,
        int $invoiceId,
        int $uid,
        string $action,
        ?array $oldData,
        ?array $newData,
        string $remark
    ): void {
        $this->logDao->create([
            'entid'      => $entId,
            'invoice_id' => $invoiceId,
            'uid'        => $uid,
            'action'     => $action,
            'old_data'   => $oldData,
            'new_data'   => $newData,
            'remark'     => $remark,
        ]);
    }
}
