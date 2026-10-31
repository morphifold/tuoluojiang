<?php

declare(strict_types=1);

if (! class_exists('Swoole\Table')) {
    eval('namespace Swoole { class Table { public const TYPE_INT = 1; public const TYPE_STRING = 2; public const TYPE_FLOAT = 3; } }');
}
if (! function_exists('swoole_cpu_num')) {
    function swoole_cpu_num(): int {
        return 4;
    }
}

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Model\Expense\ExpenseInvoice;
use App\Http\Service\Expense\TencentInvoiceOcrService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

echo "=== 开始腾讯云增值税发票识别 (VatInvoiceOCR) 自动化验证 ===\n\n";

$passedCount = 0;
$totalCount = 0;

function assertCondition(bool $condition, string $description): void
{
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
        exit(1);
    }
}

// --------------------------------------------------------------------------
// 1. 验证未配置凭据时的平滑降级 (核心容灾)
// --------------------------------------------------------------------------
echo "1. 验证未配置凭据时的平滑降级容灾...\n";
putenv('TENCENT_OCR_SECRET_ID=');
putenv('TENCENT_OCR_SECRET_KEY=');
$unconfiguredService = new TencentInvoiceOcrService();

assertCondition(! $unconfiguredService->isConfigured(), 'isConfigured() 返回 false');

$result = $unconfiguredService->recognize('/tmp/non_existent.jpg');
assertCondition($result['success'] === false, '未配置凭据时 recognize() success=false');
assertCondition(str_contains($result['message'], '未配置腾讯云OCR凭据'), '未配置凭据时提示正确: ' . $result['message']);
assertCondition(empty($result['data']), '未配置凭据时 data 为空数组');

// --------------------------------------------------------------------------
// 2. 验证腾讯云 API 3.0 (TC3-HMAC-SHA256) 签名生成算法
// --------------------------------------------------------------------------
echo "\n2. 验证 TC3-HMAC-SHA256 签名算法结构...\n";
putenv('TENCENT_OCR_SECRET_ID=AKIDtestSecretId123456');
putenv('TENCENT_OCR_SECRET_KEY=testSecretKey987654');
putenv('TENCENT_OCR_REGION=ap-guangzhou');

$service = new TencentInvoiceOcrService();
assertCondition($service->isConfigured(), '设置环境变量后 isConfigured() 返回 true');

$timestamp = 1700000000;
$payload = json_encode(['ImageBase64' => 'dGVzdA=='], JSON_UNESCAPED_UNICODE);
$headers = $service->buildHeaders($payload, $timestamp);

assertCondition(isset($headers['Authorization']), '请求头中包含 Authorization');
assertCondition(str_starts_with($headers['Authorization'], 'TC3-HMAC-SHA256 Credential=AKIDtestSecretId123456/'), 'Authorization 包含正确的算法与 SecretId');
assertCondition(str_contains($headers['Authorization'], 'SignedHeaders=content-type;host;x-tc-action'), 'SignedHeaders 包含 content-type;host;x-tc-action');
assertCondition(str_contains($headers['Authorization'], 'Signature='), 'Authorization 包含 Signature');
assertCondition($headers['Host'] === 'ocr.tencentcloudapi.com', 'Host 为 ocr.tencentcloudapi.com');
assertCondition($headers['X-TC-Action'] === 'VatInvoiceOCR', 'X-TC-Action 为 VatInvoiceOCR');
assertCondition($headers['X-TC-Version'] === '2018-11-19', 'X-TC-Version 为 2018-11-19');
assertCondition($headers['X-TC-Region'] === 'ap-guangzhou', 'X-TC-Region 为 ap-guangzhou');
assertCondition($headers['X-TC-Timestamp'] === (string) $timestamp, 'X-TC-Timestamp 为预设时间戳');

// --------------------------------------------------------------------------
// 3. 验证数电发票（全电发票）要素提取与标准化映射
// --------------------------------------------------------------------------
echo "\n3. 验证数电发票要素提取与字段标准化...\n";
$mockDigitalInvoice = [
    'VatInvoiceInfos' => [
        ['Name' => '发票名称', 'Value' => '电子发票（增值税专用发票）'],
        ['Name' => '发票号码', 'Value' => '24442000000012345678'],
        ['Name' => '发票代码', 'Value' => ''],
        ['Name' => '开票日期', 'Value' => '2024年05月20日'],
        ['Name' => '合计金额', 'Value' => '¥ 10,000.00'],
        ['Name' => '合计税额', 'Value' => '¥ 1,300.00'],
        ['Name' => '价税合计(小写)', 'Value' => '¥ 11,300.00'],
        ['Name' => '销售方名称', 'Value' => '上海数电网络技术有限公司'],
        ['Name' => '购买方名称', 'Value' => '北京科技创新有限公司'],
        ['Name' => '校验码', 'Value' => ''],
    ],
];

$parsed = $service->standardizeResponse($mockDigitalInvoice);
assertCondition($parsed['invoice_type'] === 'digital_special', '数电专票识别为 digital_special');
assertCondition($parsed['invoice_type_code'] === 3, '数电专票对应编码 3');
assertCondition($parsed['invoice_number'] === '24442000000012345678', '发票号码提取正确');
assertCondition($parsed['invoice_code'] === '', '数电发票代码强制为空');
assertCondition($parsed['invoice_date'] === '2024-05-20', '开票日期格式化为 2024-05-20');
assertCondition($parsed['amount'] === 10000.00, '不含税金额正确: 10000.00');
assertCondition($parsed['tax_amount'] === 1300.00, '税额正确: 1300.00');
assertCondition($parsed['total_amount'] === 11300.00, '价税合计正确: 11300.00');
assertCondition($parsed['seller_name'] === '上海数电网络技术有限公司', '销售方名称正确');
assertCondition($parsed['buyer_name'] === '北京科技创新有限公司', '购买方名称正确');

// --------------------------------------------------------------------------
// 4. 验证传统增值税专用发票要素提取
// --------------------------------------------------------------------------
echo "\n4. 验证传统增值税专用发票要素提取...\n";
$mockSpecialInvoice = [
    'VatInvoiceInfos' => [
        ['Name' => '发票名称', 'Value' => '北京增值税专用发票'],
        ['Name' => '发票代码', 'Value' => '1100201130'],
        ['Name' => '发票号码', 'Value' => '87654321'],
        ['Name' => '开票日期', 'Value' => '2023-11-15'],
        ['Name' => '合计金额', 'Value' => '500.00'],
        ['Name' => '合计税额', 'Value' => '30.00'],
        ['Name' => '价税合计(小写)', 'Value' => '530.00'],
        ['Name' => '销售方名称', 'Value' => '北京某某商贸有限公司'],
        ['Name' => '购买方名称', 'Value' => '北京某某科技有限责任公司'],
        ['Name' => '校验码', 'Value' => '12345678901234567890'],
    ],
];

$parsedSpecial = $service->standardizeResponse($mockSpecialInvoice);
assertCondition($parsedSpecial['invoice_type'] === ExpenseInvoice::TYPE_SPECIAL, '传统专票识别为 special');
assertCondition($parsedSpecial['invoice_type_code'] === 1, '传统专票对应编码 1');
assertCondition($parsedSpecial['invoice_code'] === '1100201130', '发票代码提取正确');
assertCondition($parsedSpecial['invoice_number'] === '87654321', '发票号码提取正确');
assertCondition($parsedSpecial['check_code'] === '12345678901234567890', '校验码提取正确');
assertCondition($parsedSpecial['total_amount'] === 530.00, '价税合计正确');

// --------------------------------------------------------------------------
// 5. 验证通行费发票与定额发票识别
// --------------------------------------------------------------------------
echo "\n5. 验证通行费发票与定额发票识别...\n";
$mockTollInvoice = [
    'VatInvoiceInfos' => [
        ['Name' => '发票名称', 'Value' => '通行费增值税电子普通发票'],
        ['Name' => '发票号码', 'Value' => '12345678'],
        ['Name' => '价税合计(小写)', 'Value' => '50.00'],
        ['Name' => '合计金额', 'Value' => '48.54'],
    ],
];
$parsedToll = $service->standardizeResponse($mockTollInvoice);
assertCondition($parsedToll['invoice_type'] === ExpenseInvoice::TYPE_TOLL, '通行费发票识别为 toll');
assertCondition($parsedToll['invoice_type_code'] === 5, '通行费发票对应编码 5');
assertCondition($parsedToll['tax_amount'] === 1.46, '税额缺失时自动通过价税合计与不含税推导: 1.46');

$mockQuotaInvoice = [
    'VatInvoiceInfos' => [
        ['Name' => '发票名称', 'Value' => '通用定额发票'],
        ['Name' => '发票号码', 'Value' => '99887766'],
        ['Name' => '价税合计(小写)', 'Value' => '100.00'],
    ],
];
$parsedQuota = $service->standardizeResponse($mockQuotaInvoice);
assertCondition($parsedQuota['invoice_type'] === ExpenseInvoice::TYPE_QUOTA, '定额发票识别为 quota');
assertCondition($parsedQuota['invoice_type_code'] === 6, '定额发票对应编码 6');

// --------------------------------------------------------------------------
// 6. 验证 Guzzle Mock 模拟腾讯云 HTTP 请求成功与失败
// --------------------------------------------------------------------------
echo "\n6. 验证通过 HTTP Mock 调用的成功与错误容错...\n";

// 6.1 模拟成功识别
$mockOcrSuccessBody = json_encode([
    'Response' => [
        'VatInvoiceInfos' => [
            ['Name' => '发票名称', 'Value' => '电子发票（普通发票）'],
            ['Name' => '发票号码', 'Value' => '24112000000099887766'],
            ['Name' => '开票日期', 'Value' => '2024-06-01'],
            ['Name' => '价税合计(小写)', 'Value' => '666.00'],
            ['Name' => '合计金额', 'Value' => '600.00'],
            ['Name' => '合计税额', 'Value' => '66.00'],
            ['Name' => '销售方名称', 'Value' => '数电普票销售方'],
            ['Name' => '购买方名称', 'Value' => '数电普票购买方'],
        ],
        'RequestId' => 'test-req-id-12345',
    ],
]);

$mockHandler = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], $mockOcrSuccessBody),
]);
$handlerStack = HandlerStack::create($mockHandler);
$mockClient = new Client(['handler' => $handlerStack]);

$serviceWithMock = new TencentInvoiceOcrService($mockClient);
$mockRecognize = $serviceWithMock->recognizeBase64('mock_base64_data');
assertCondition($mockRecognize['success'] === true, 'Mock HTTP 调用成功 success=true');
assertCondition($mockRecognize['data']['invoice_number'] === '24112000000099887766', 'Mock 识别号码正确');
assertCondition($mockRecognize['data']['invoice_type'] === 'digital_general', '20位数电普票识别为 digital_general');
assertCondition($mockRecognize['data']['invoice_type_code'] === 4, '数电普票对应编码 4');

// 6.2 模拟腾讯云业务错误返回 (如图片模糊 FailedOperation.OcrFailed)
$mockOcrErrorBody = json_encode([
    'Response' => [
        'Error' => [
            'Code' => 'FailedOperation.OcrFailed',
            'Message' => '发票识别失败，图像模糊或非增值税发票',
        ],
        'RequestId' => 'test-req-id-error',
    ],
]);
$mockHandlerError = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], $mockOcrErrorBody),
]);
$handlerStackError = HandlerStack::create($mockHandlerError);
$mockClientError = new Client(['handler' => $handlerStackError]);
$serviceWithError = new TencentInvoiceOcrService($mockClientError);

$mockErrResult = $serviceWithError->recognizeBase64('mock_base64_data');
assertCondition($mockErrResult['success'] === false, '业务错误时返回 success=false，不抛出异常');
assertCondition(str_contains($mockErrResult['message'], '发票识别失败'), '错误提示包含腾讯云返回的信息');

// 6.3 模拟网络异常 (如 500 服务器错误)
$mockHandler500 = new MockHandler([
    new Response(500, ['Content-Type' => 'application/json'], 'Internal Server Error'),
]);
$handlerStack500 = HandlerStack::create($mockHandler500);
$mockClient500 = new Client(['handler' => $handlerStack500]);
$serviceWith500 = new TencentInvoiceOcrService($mockClient500);

$mock500Result = $serviceWith500->recognizeBase64('mock_base64_data');
assertCondition($mock500Result['success'] === false, 'HTTP 500 异常时返回 success=false，不抛出异常');

// --------------------------------------------------------------------------
// 7. 验证 ExpenseInvoiceService 与 OCR 驱动的业务层整合与降级
// --------------------------------------------------------------------------
echo "\n7. 验证 ExpenseInvoiceService 业务层调用与优雅降级...\n";

use App\Http\Dao\Expense\ExpenseInvoiceDao;
use App\Http\Dao\Expense\ExpenseInvoiceLogDao;
use App\Http\Service\Expense\ExpenseInvoiceService;
use App\Http\Service\Expense\InvoicePdfParserService;

// 创建一个临时测试图片
$tempImageDir = sys_get_temp_dir() . '/test_invoices';
if (! is_dir($tempImageDir)) {
    @mkdir($tempImageDir, 0777, true);
}
$testImagePath = $tempImageDir . '/test_invoice_' . uniqid() . '.jpg';
file_put_contents($testImagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

// 7.1 测试未配置 OCR 时的优雅降级
$fakeDao = new class extends ExpenseInvoiceDao {
    public function __construct() {}
    public function findActiveByCodeAndNumber(int $entId, string $invoiceCode, string $invoiceNumber, ?int $excludeId = null): ?\App\Http\Model\Expense\ExpenseInvoice {
        return null;
    }
};
$fakeLogDao = new class extends ExpenseInvoiceLogDao {
    public function __construct() {}
};

$unconfiguredOcr = new TencentInvoiceOcrService();
$serviceIntegration = new ExpenseInvoiceService($fakeDao, $fakeLogDao, new InvoicePdfParserService(), $unconfiguredOcr);

$parsedDegrade = $serviceIntegration->parseInvoiceFile(1, 1, null, $testImagePath);
assertCondition($parsedDegrade['ocr_recognized'] === false, '未配置凭据时 parseInvoiceFile 返回 ocr_recognized=false');
assertCondition($parsedDegrade['invoice_number'] === '', '降级模式下发票号码为空');
assertCondition($parsedDegrade['file_type'] === 'jpg', '文件类型正确保留为 jpg');
assertCondition($parsedDegrade['file_path'] === $testImagePath, '原件路径正确保留');

// 7.2 测试配置 Mock 成功返回时的智能要素填充
$mockHandlerForIntegration = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], $mockOcrSuccessBody),
]);
$mockClientForIntegration = new Client(['handler' => HandlerStack::create($mockHandlerForIntegration)]);
$serviceWithFreshMock = new TencentInvoiceOcrService($mockClientForIntegration);
$mockServiceIntegration = new ExpenseInvoiceService($fakeDao, $fakeLogDao, new InvoicePdfParserService(), $serviceWithFreshMock);
$parsedSuccess = $mockServiceIntegration->parseInvoiceFile(1, 1, null, $testImagePath);
assertCondition($parsedSuccess['ocr_recognized'] === true, 'OCR 成功时 parseInvoiceFile 返回 ocr_recognized=true');
assertCondition($parsedSuccess['invoice_number'] === '24112000000099887766', '智能识别并填充了发票号码');
assertCondition($parsedSuccess['total_amount'] === 666.00, '智能识别并填充了价税合计 666.00');
assertCondition($parsedSuccess['seller_name'] === '数电普票销售方', '智能识别并填充了销售方名称');
assertCondition($parsedSuccess['is_duplicate'] === false, '实时附带验重状态正常通过');

@unlink($testImagePath);

echo "\n============================================\n";
echo " 全部验证通过！共 {$passedCount}/{$totalCount} 项检查均已完成。\n";
echo "============================================\n";
