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

use App\Http\Model\Expense\ExpenseInvoice;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 腾讯云增值税发票识别服务 (VatInvoiceOCR)
 *
 * 规范：
 * - 接口端点：https://ocr.tencentcloudapi.com
 * - Action: VatInvoiceOCR, Version: 2018-11-19
 * - 采用标准腾讯云 API 3.0 签名 (TC3-HMAC-SHA256)，PHP 8 原生 hash_hmac 与 Guzzle 实现，零第三方臃肿 SDK
 * - 支持平滑降级容灾：未配置凭据或网络异常时不阻塞业务，优雅回退手动录入
 *
 * Class TencentInvoiceOcrService.
 */
class TencentInvoiceOcrService
{
    public const HOST = 'ocr.tencentcloudapi.com';
    public const ACTION = 'VatInvoiceOCR';
    public const VERSION = '2018-11-19';
    public const SERVICE = 'ocr';
    public const ALGORITHM = 'TC3-HMAC-SHA256';

    protected Client $httpClient;

    public function __construct(?Client $client = null)
    {
        $this->httpClient = $client ?? new Client([
            'timeout'         => 15.0,
            'connect_timeout' => 5.0,
            'http_errors'     => false,
        ]);
    }

    /**
     * 获取腾讯云 SecretId
     */
    public function getSecretId(): string
    {
        $val = null;
        try {
            $val = config('services.tencent_ocr.secret_id');
        } catch (Throwable) {
            $val = null;
        }
        return trim((string) ($val ?: env('TENCENT_OCR_SECRET_ID', '')));
    }

    /**
     * 获取腾讯云 SecretKey
     */
    public function getSecretKey(): string
    {
        $val = null;
        try {
            $val = config('services.tencent_ocr.secret_key');
        } catch (Throwable) {
            $val = null;
        }
        return trim((string) ($val ?: env('TENCENT_OCR_SECRET_KEY', '')));
    }

    /**
     * 获取服务地域 (默认 ap-guangzhou)
     */
    public function getRegion(): string
    {
        $val = null;
        try {
            $val = config('services.tencent_ocr.region');
        } catch (Throwable) {
            $val = null;
        }
        $region = trim((string) ($val ?: env('TENCENT_OCR_REGION', 'ap-guangzhou')));
        return $region !== '' ? $region : 'ap-guangzhou';
    }
    public function isConfigured(): bool
    {
        return $this->getSecretId() !== '' && $this->getSecretKey() !== '';
    }

    /**
     * 识别发票图片文件
     *
     * @param string $filePath 本地发票图片物理路径或相对路径
     * @return array [
     *     'success' => bool,
     *     'message' => string,
     *     'data'    => array,
     * ]
     */
    public function recognize(string $filePath): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => '未配置腾讯云OCR凭据',
                'data'    => [],
            ];
        }

        $realPath = $this->resolveFilePath($filePath);
        if (! file_exists($realPath) || ! is_readable($realPath)) {
            return [
                'success' => false,
                'message' => '发票原件文件不存在或无法读取: ' . basename($filePath),
                'data'    => [],
            ];
        }

        $content = @file_get_contents($realPath);
        if ($content === false || $content === '') {
            return [
                'success' => false,
                'message' => '读取发票文件内容失败',
                'data'    => [],
            ];
        }

        $base64 = base64_encode($content);

        return $this->recognizeBase64($base64);
    }

    /**
     * 通过 Base64 编码识别增值税发票
     */
    public function recognizeBase64(string $imageBase64): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => '未配置腾讯云OCR凭据',
                'data'    => [],
            ];
        }

        try {
            $payload = json_encode([
                'ImageBase64' => $imageBase64,
            ], JSON_UNESCAPED_UNICODE);

            $timestamp = time();
            $headers = $this->buildHeaders($payload, $timestamp);

            $response = $this->httpClient->post('https://' . self::HOST, [
                'headers' => $headers,
                'body'    => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = (string) $response->getBody();

            $json = json_decode($rawBody, true);
            if (! is_array($json) || ! isset($json['Response'])) {
                $this->logWarning('腾讯云发票OCR返回格式异常', [
                    'status_code' => $statusCode,
                    'body'        => mb_substr($rawBody, 0, 500),
                ]);

                return [
                    'success' => false,
                    'message' => '腾讯云OCR返回数据异常 (HTTP ' . $statusCode . ')',
                    'data'    => [],
                ];
            }

            $resp = $json['Response'];

            // 腾讯云业务错误结构捕获
            if (isset($resp['Error'])) {
                $errCode = $resp['Error']['Code'] ?? 'Unknown';
                $errMsg = $resp['Error']['Message'] ?? '识别失败';
                $this->logWarning("腾讯云发票OCR业务错误 [{$errCode}]: {$errMsg}");

                return [
                    'success' => false,
                    'message' => '腾讯云OCR识别失败: ' . $errMsg,
                    'data'    => [],
                ];
            }

            $standardized = $this->standardizeResponse($resp);

            return [
                'success' => true,
                'message' => '识别成功',
                'data'    => $standardized,
            ];
        } catch (Throwable $e) {
            $this->logWarning('腾讯云发票OCR网络或调用异常: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'message' => '腾讯云OCR调用异常: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * 安全记录 warning 日志，框架未启动时静默忽略
     */
    protected function logWarning(string $message, array $context = []): void
    {
        try {
            Log::warning($message, $context);
        } catch (Throwable) {
            // 框架容器未就绪时容错，不中断核心业务流程
        }
    }

    /**
     * 构建腾讯云 API 3.0 (TC3-HMAC-SHA256) 请求头
     */
    public function buildHeaders(string $payload, int $timestamp): array
    {
        $secretId = $this->getSecretId();
        $secretKey = $this->getSecretKey();
        $region = $this->getRegion();
        $date = gmdate('Y-m-d', $timestamp);

        // 1. 规范请求串 (CanonicalRequest)
        $httpRequestMethod = 'POST';
        $canonicalUri = '/';
        $canonicalQueryString = '';
        $canonicalHeaders = "content-type:application/json; charset=utf-8\nhost:" . self::HOST . "\nx-tc-action:" . strtolower(self::ACTION) . "\n";
        $signedHeaders = 'content-type;host;x-tc-action';
        $hashedRequestPayload = hash('sha256', $payload);

        $canonicalRequest = $httpRequestMethod . "\n"
            . $canonicalUri . "\n"
            . $canonicalQueryString . "\n"
            . $canonicalHeaders . "\n"
            . $signedHeaders . "\n"
            . $hashedRequestPayload;

        // 2. 待签名字符串 (StringToSign)
        $credentialScope = $date . '/' . self::SERVICE . '/tc3_request';
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);

        $stringToSign = self::ALGORITHM . "\n"
            . $timestamp . "\n"
            . $credentialScope . "\n"
            . $hashedCanonicalRequest;

        // 3. 计算派生签名密钥并签名
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $secretKey, true);
        $secretService = hash_hmac('sha256', self::SERVICE, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);

        // 4. 构建 Authorization 授权串
        $authorization = self::ALGORITHM
            . ' Credential=' . $secretId . '/' . $credentialScope
            . ', SignedHeaders=' . $signedHeaders
            . ', Signature=' . $signature;

        return [
            'Authorization'  => $authorization,
            'Content-Type'   => 'application/json; charset=utf-8',
            'Host'           => self::HOST,
            'X-TC-Action'    => self::ACTION,
            'X-TC-Timestamp' => (string) $timestamp,
            'X-TC-Version'   => self::VERSION,
            'X-TC-Region'    => $region,
        ];
    }

    /**
     * 将腾讯云返回结果标准化为系统统一发票要素
     *
     * 字段规范：
     * - invoice_type: 字符串标识 (special, general, digital_special, digital_general, toll, quota 等)
     * - invoice_type_code: 数字代码 (1:专票, 2:普票, 3:数电专票, 4:数电普票, 5:通行费, 6:定额发票 等)
     * - invoice_code: 发票代码
     * - invoice_number: 发票号码
     * - amount: 不含税金额
     * - tax_amount: 税额
     * - total_amount: 价税合计
     * - invoice_date: 开票日期 (YYYY-MM-DD)
     * - seller_name: 销售方名称
     * - buyer_name: 购买方名称
     * - check_code: 校验码
     */
    public function standardizeResponse(array $resp): array
    {
        // 1. 将 VatInvoiceInfos 键值列表转为字典
        $map = [];
        if (! empty($resp['VatInvoiceInfos']) && is_array($resp['VatInvoiceInfos'])) {
            foreach ($resp['VatInvoiceInfos'] as $item) {
                if (isset($item['Name'])) {
                    $name = trim((string) $item['Name']);
                    $val = isset($item['Value']) ? trim((string) $item['Value']) : '';
                    $map[$name] = $val;
                }
            }
        }

        // 2. 提取发票代码
        $invoiceCode = $map['发票代码'] ?? ($map['代码'] ?? '');
        $invoiceCode = preg_replace('/[^\d]/', '', $invoiceCode) ?? '';

        // 3. 提取发票号码
        $invoiceNumber = $map['发票号码'] ?? ($map['数电票号码'] ?? ($map['发票号'] ?? ($map['号码'] ?? '')));
        $invoiceNumber = preg_replace('/[^\d]/', '', $invoiceNumber) ?? '';

        // 4. 提取开票日期 (格式化为 YYYY-MM-DD)
        $rawDate = $map['开票日期'] ?? ($map['日期'] ?? '');
        $invoiceDate = $this->parseInvoiceDate($rawDate);

        // 5. 校验码
        $checkCode = $map['校验码'] ?? ($map['校 验 码'] ?? '');
        $checkCode = trim(preg_replace('/\s+/', '', $checkCode) ?? '');

        // 6. 不含税金额
        $rawAmount = $map['合计金额'] ?? ($map['金额'] ?? ($map['不含税金额'] ?? ''));
        $amount = $this->parseAmount($rawAmount);

        // 7. 税额
        $rawTaxAmount = $map['合计税额'] ?? ($map['税额'] ?? '');
        $taxAmount = $this->parseAmount($rawTaxAmount);

        // 8. 价税合计
        $rawTotalAmount = $map['价税合计(小写)'] ?? ($map['小写金额'] ?? ($map['价税合计'] ?? ($map['总金额'] ?? ($map['合计'] ?? ''))));
        $totalAmount = $this->parseAmount($rawTotalAmount);

        // 金额合理推导容错
        if ($totalAmount > 0 && $amount > 0 && $taxAmount <= 0) {
            $taxAmount = round($totalAmount - $amount, 2);
        } elseif ($totalAmount > 0 && $taxAmount > 0 && $amount <= 0) {
            $amount = round($totalAmount - $taxAmount, 2);
        } elseif ($amount > 0 && $taxAmount > 0 && $totalAmount <= 0) {
            $totalAmount = round($amount + $taxAmount, 2);
        }

        // 9. 购买方名称与销售方名称
        $buyerName = $this->cleanEntityName($map['购买方名称'] ?? ($map['购买方'] ?? ($map['购方名称'] ?? '')));
        $sellerName = $this->cleanEntityName($map['销售方名称'] ?? ($map['销售方'] ?? ($map['销方名称'] ?? '')));

        // 10. 发票类型判定与映射
        $rawInvoiceName = $map['发票名称'] ?? ($map['发票种类'] ?? ($map['发票类型'] ?? ''));
        [$invoiceType, $invoiceTypeCode] = $this->determineInvoiceType($rawInvoiceName, $invoiceNumber, $invoiceCode);

        // 数电发票强制抹除冗余发票代码
        if (in_array($invoiceType, ['digital_special', 'digital_general', ExpenseInvoice::TYPE_ELECTRONIC], true) && strlen($invoiceNumber) === 20) {
            $invoiceCode = '';
        }

        return [
            'invoice_type'      => $invoiceType,
            'invoice_type_code' => $invoiceTypeCode,
            'invoice_code'      => $invoiceCode,
            'invoice_number'    => $invoiceNumber,
            'amount'            => $amount,
            'tax_amount'        => $taxAmount,
            'total_amount'      => $totalAmount,
            'invoice_date'      => $invoiceDate,
            'seller_name'       => $sellerName,
            'buyer_name'        => $buyerName,
            'check_code'        => $checkCode,
            'raw_invoice_name'  => $rawInvoiceName,
        ];
    }

    /**
     * 判断发票类型及数字编码
     * 1:专票, 2:普票, 3:数电专票, 4:数电普票, 5:通行费, 6:定额发票 等
     */
    protected function determineInvoiceType(string $rawName, string $invoiceNumber, string $invoiceCode): array
    {
        $text = $rawName;

        // 通行费发票
        if (str_contains($text, '通行费')) {
            return [ExpenseInvoice::TYPE_TOLL, 5];
        }

        // 定额发票
        if (str_contains($text, '定额')) {
            return [ExpenseInvoice::TYPE_QUOTA, 6];
        }

        // 加油/成品油
        if (str_contains($text, '加油') || str_contains($text, '成品油')) {
            return [ExpenseInvoice::TYPE_PETROL, 7];
        }

        $is20Digits = strlen($invoiceNumber) === 20;

        // 增值税专用发票分支
        if (str_contains($text, '专用发票') || str_contains($text, '专票')) {
            if (str_contains($text, '数电') || str_contains($text, '全电') || str_contains($text, '全面数字化') || ($is20Digits && empty($invoiceCode))) {
                return ['digital_special', 3];
            }
            return [ExpenseInvoice::TYPE_SPECIAL, 1];
        }

        // 增值税普通发票分支
        if (str_contains($text, '普通发票') || str_contains($text, '普票')) {
            if (str_contains($text, '数电') || str_contains($text, '全电') || str_contains($text, '全面数字化') || ($is20Digits && empty($invoiceCode))) {
                return ['digital_general', 4];
            }
            if (str_contains($text, '电子')) {
                return [ExpenseInvoice::TYPE_ELECTRONIC, 4];
            }
            return [ExpenseInvoice::TYPE_GENERAL, 2];
        }

        // 数电发票通用标识（20位号码且代码为空）
        if ($is20Digits && empty($invoiceCode)) {
            return ['digital_general', 4];
        }

        // 默认兜底增值税普通发票
        return [ExpenseInvoice::TYPE_GENERAL, 2];
    }

    /**
     * 日期解析为 YYYY-MM-DD
     */
    protected function parseInvoiceDate(string $rawDate): ?string
    {
        $raw = trim($rawDate);
        if ($raw === '') {
            return null;
        }

        // 匹配 2023年05月20日 / 2023-05-20 / 2023/05/20 / 2023.05.20 / 20230520
        if (preg_match('/(\d{4})[^\d]?(\d{1,2})[^\d]?(\d{1,2})/u', $raw, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * 金额文本解析为浮点数
     */
    protected function parseAmount(string $raw): float
    {
        $val = trim($raw);
        if ($val === '' || str_contains($val, '免税') || str_contains($val, '不征税') || str_contains($val, '***')) {
            return 0.00;
        }

        // 剔除货币符号、千分位逗号
        $cleaned = preg_replace('/[^\d.-]/', '', $val);
        if ($cleaned === '' || ! is_numeric($cleaned)) {
            return 0.00;
        }

        return round((float) $cleaned, 2);
    }

    /**
     * 清理购销方公司名称
     */
    protected function cleanEntityName(string $name): string
    {
        $val = trim($name);
        $val = preg_replace('/^[\s:：]+|[\s:：]+$/u', '', $val) ?? $val;
        return mb_substr($val, 0, 100);
    }

    /**
     * 解析物理路径
     */
    protected function resolveFilePath(string $filePath): string
    {
        if (file_exists($filePath)) {
            return $filePath;
        }

        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/' . ltrim($filePath, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $basePath = base_path($filePath);
        if (file_exists($basePath)) {
            return $basePath;
        }

        return $filePath;
    }
}
