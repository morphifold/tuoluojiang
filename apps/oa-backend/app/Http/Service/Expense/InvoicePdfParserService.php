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

/**
 * 电子发票PDF解析器
 * 服务端纯代码解析国内增值税专用发票、普通发票、全电/数电发票等PDF文本
 * 无外部第三方收费API依赖，具备多级流解析与降级容错机制
 * Class InvoicePdfParserService.
 */
class InvoicePdfParserService
{
    /**
     * 解析PDF文件
     *
     * @param string $filePath 本地文件绝对或相对路径
     * @return array 解析出的发票结构化数据
     */
    public function parse(string $filePath): array
    {
        $realPath = $this->resolveFilePath($filePath);
        if (! file_exists($realPath)) {
            return $this->getEmptyResult($filePath);
        }

        $text = $this->extractText($realPath);
        return $this->parseInvoiceText($text, $filePath);
    }

    /**
     * 解析二进制内容
     */
    public function parseContent(string $content, string $virtualFileName = 'invoice.pdf'): array
    {
        $text = $this->extractTextFromBinary($content);
        return $this->parseInvoiceText($text, $virtualFileName);
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

    /**
     * 从PDF提取纯文本
     * 策略：
     * 1. 尝试系统 pdftotext CLI 工具（保真度最高）
     * 2. 纯 PHP PDF 流解密与操作符解析（FlateDecode解压 + BT/ET字符提取）
     * 3. 原始文本/十六进制/Unicode回退提取
     */
    public function extractText(string $filePath): string
    {
        $text = '';

        // 策略1: pdftotext CLI
        if ($this->hasPdftotext()) {
            $cmd = 'pdftotext -layout ' . escapeshellarg($filePath) . ' - 2>/dev/null';
            $output = @shell_exec($cmd);
            if (is_string($output) && trim($output) !== '') {
                $text .= "\n" . $output;
            }
        }

        // 策略2: 纯 PHP 流解析
        $content = @file_get_contents($filePath);
        if ($content !== false && $content !== '') {
            $streamText = $this->extractTextFromBinary($content);
            if ($streamText !== '') {
                $text .= "\n" . $streamText;
            }
        }

        return trim($text);
    }

    /**
     * 纯 PHP 从 PDF 二进制流提取文本
     */
    public function extractTextFromBinary(string $content): string
    {
        $extractedText = '';

        // 1. 匹配所有 PDF stream 对象
        if (preg_match_all('/<<(?P<dict>[^>]*)>>\s*stream[\r\n]+(?P<data>.*?)[\r\n]+endstream/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $dict = $match['dict'];
                $data = $match['data'];

                // 解压缩 FlateDecode 数据流
                if (stripos($dict, 'FlateDecode') !== false) {
                    $uncompressed = @gzuncompress($data);
                    if ($uncompressed === false) {
                        $uncompressed = @gzinflate(substr($data, 2));
                    }
                    if ($uncompressed === false) {
                        $uncompressed = @gzinflate($data);
                    }
                    if ($uncompressed !== false) {
                        $data = $uncompressed;
                    }
                }

                // 从解压后的内容流中提取文本
                $extractedText .= ' ' . $this->parseContentStreamText($data);

                // 如果是 XMP 元数据流，提取 XML 文本
                if (stripos($dict, 'Metadata') !== false) {
                    $cleanXml = strip_tags($data);
                    $extractedText .= ' ' . $cleanXml;
                }
            }
        }

        // 2. 兜底：直接扫描常见的未压缩文本与 UTF-16BE 字符
        $extractedText .= ' ' . $this->scanDirectText($content);

        return $extractedText;
    }

    /**
     * 解析 PDF 内容操作流 (BT ... ET, Tj, TJ, ')
     */
    protected function parseContentStreamText(string $stream): string
    {
        $text = '';

        // 匹配 BT ... ET 文本块
        if (preg_match_all('/BT[\s\S]*?ET/s', $stream, $textBlocks)) {
            foreach ($textBlocks[0] as $block) {
                // 匹配 (string) Tj / ' / "
                if (preg_match_all('/\((?P<str>(?:[^\\\\\)]|\\\\.)*)\)\s*(?:Tj|\'|")/s', $block, $tjMatches)) {
                    foreach ($tjMatches['str'] as $rawStr) {
                        $text .= $this->decodePdfString($rawStr) . ' ';
                    }
                }

                // 匹配 [(str) 10 (str2)] TJ
                if (preg_match_all('/\[(?P<array>[\s\S]*?)\]\s*TJ/s', $block, $tjArrayMatches)) {
                    foreach ($tjArrayMatches['array'] as $tjArray) {
                        if (preg_match_all('/\((?P<str>(?:[^\\\\\)]|\\\\.)*)\)/s', $tjArray, $innerMatches)) {
                            foreach ($innerMatches['str'] as $innerStr) {
                                $text .= $this->decodePdfString($innerStr);
                            }
                            $text .= ' ';
                        }
                        // 匹配十六进制数组 <00410042>
                        if (preg_match_all('/<(?P<hex>[0-9a-fA-F\s]+)>/s', $tjArray, $hexInner)) {
                            foreach ($hexInner['hex'] as $hexStr) {
                                $text .= $this->decodeHexPdfString($hexStr);
                            }
                            $text .= ' ';
                        }
                    }
                }

                // 匹配单独的十六进制字符串 <00410042> Tj
                if (preg_match_all('/<(?P<hex>[0-9a-fA-F\s]+)>\s*Tj/s', $block, $hexMatches)) {
                    foreach ($hexMatches['hex'] as $hexStr) {
                        $text .= $this->decodeHexPdfString($hexStr) . ' ';
                    }
                }
            }
        }

        return $text;
    }

    /**
     * 解码 PDF 括号字符串转义字符
     */
    protected function decodePdfString(string $str): string
    {
        // 处理八进制转义 \040
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        // 处理字符转义
        $replace = [
            '\\n'  => "\n",
            '\\r'  => "\r",
            '\\t'  => "\t",
            '\\b'  => "\x08",
            '\\f'  => "\x0c",
            '\\('  => '(',
            '\\)'  => ')',
            '\\\\' => '\\',
        ];
        $str = strtr($str, $replace);

        // 检查是否为 UTF-16BE（以 \xFE\xFF 开头）
        if (str_starts_with($str, "\xFE\xFF")) {
            return mb_convert_encoding(substr($str, 2), 'UTF-8', 'UTF-16BE');
        }

        // 编码侦测与转换
        $encoding = mb_detect_encoding($str, ['UTF-8', 'GB18030', 'GBK', 'BIG5', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $converted = @mb_convert_encoding($str, 'UTF-8', $encoding);
            if ($converted !== false) {
                return $converted;
            }
        }

        return $str;
    }

    /**
     * 解码十六进制 PDF 文本
     */
    protected function decodeHexPdfString(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex);
        if (strlen($hex) % 2 !== 0) {
            $hex .= '0';
        }

        $bin = @pack('H*', $hex);
        if ($bin === false) {
            return '';
        }

        if (str_starts_with($bin, "\xFE\xFF")) {
            return mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');
        }

        // 尝试按 UTF-16BE 转换（双字节中文编码特征）
        if (strlen($bin) >= 2 && strlen($bin) % 2 === 0) {
            $utf16 = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16BE');
            if ($utf16 !== false && preg_match('/[\x{4e00}-\x{9fa5}]/u', $utf16)) {
                return $utf16;
            }
        }

        $encoding = mb_detect_encoding($bin, ['UTF-8', 'GB18030', 'GBK', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            return (string) @mb_convert_encoding($bin, 'UTF-8', $encoding);
        }

        return $bin;
    }

    /**
     * 兜底直接扫描
     */
    protected function scanDirectText(string $content): string
    {
        $matched = '';

        // 匹配发票代码 (10-12位连续数字)
        if (preg_match_all('/(?:发票代码|代码)[：:\s]*([0-9]{10,12})/u', $content, $m)) {
            $matched .= ' ' . implode(' ', $m[0]);
        }

        // 匹配发票号码 (8-20位数字)
        if (preg_match_all('/(?:发票号码|号码)[：:\s]*([0-9]{8,20})/u', $content, $m)) {
            $matched .= ' ' . implode(' ', $m[0]);
        }

        // 匹配日期
        if (preg_match_all('/20[2-3][0-9]\s*[年\-\/.]\s*(?:0?[1-9]|1[0-2])\s*[月\-\/.]\s*(?:0?[1-9]|[12][0-9]|3[01])\s*日?/u', $content, $m)) {
            $matched .= ' ' . implode(' ', $m[0]);
        }

        return $matched;
    }

    /**
     * 核心正则提取业务字段
     */
    public function parseInvoiceText(string $text, string $fileName = ''): array
    {
        $result = $this->getEmptyResult($fileName);
        if (trim($text) === '') {
            return $result;
        }

        // 统一清除不可见控制字符，保留正常空格换行
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // 1. 发票类型识别
        $result['invoice_type'] = $this->matchInvoiceType($cleanText);

        // 2. 发票代码提取 (数电票无发票代码)
        $result['invoice_code'] = $this->matchInvoiceCode($cleanText, $result['invoice_type']);

        // 3. 发票号码提取 (传统8位，数电票20位)
        $result['invoice_number'] = $this->matchInvoiceNumber($cleanText);

        // 若号码为20位且代码为空，进一步强化认定为数电发票
        if (strlen($result['invoice_number']) === 20 && empty($result['invoice_code'])) {
            $result['invoice_type'] = ExpenseInvoice::TYPE_ELECTRONIC;
            $result['invoice_code'] = '';
        }

        // 4. 开票日期提取
        $result['invoice_date'] = $this->matchInvoiceDate($cleanText);

        // 5. 金额提取 (价税合计、不含税金额、税额)
        [$totalAmount, $amount, $taxAmount] = $this->matchAmounts($cleanText);
        $result['total_amount'] = $totalAmount;
        $result['amount'] = $amount;
        $result['tax_amount'] = $taxAmount;

        // 6. 购买方名称
        $result['buyer_name'] = $this->matchBuyerName($cleanText);

        // 7. 销售方名称
        $result['seller_name'] = $this->matchSellerName($cleanText);

        return $result;
    }

    /**
     * 匹配发票类型
     */
    protected function matchInvoiceType(string $text): string
    {
        if (preg_match('/(?:电子发票（增值税专用发票）|增值税专用发票|增值税专票)/u', $text)) {
            return ExpenseInvoice::TYPE_SPECIAL;
        }

        if (preg_match('/(?:全面数字化的电子发票|数电发票|数电票|电子发票（普通发票）|电子发票（航空运输电子客票行程单）|电子发票（铁路电子客票）)/u', $text)) {
            return ExpenseInvoice::TYPE_ELECTRONIC;
        }

        if (preg_match('/通行费/u', $text)) {
            return ExpenseInvoice::TYPE_TOLL;
        }

        if (preg_match('/(?:加油|成品油)/u', $text)) {
            return ExpenseInvoice::TYPE_PETROL;
        }

        if (preg_match('/定额/u', $text)) {
            return ExpenseInvoice::TYPE_QUOTA;
        }

        if (preg_match('/(?:增值税电子普通发票|增值税普通发票|增值税普票)/u', $text)) {
            return ExpenseInvoice::TYPE_GENERAL;
        }

        return ExpenseInvoice::TYPE_GENERAL;
    }

    /**
     * 匹配发票代码
     */
    protected function matchInvoiceCode(string $text, string $invoiceType): string
    {
        if ($invoiceType === ExpenseInvoice::TYPE_ELECTRONIC) {
            return '';
        }

        if (preg_match('/(?:发票代码|代\s*码)[：:\s]*([0-9]{10,12})\b/u', $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/(?:\bCode\b)[：:\s]*([0-9]{10,12})/i', $text, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * 匹配发票号码
     */
    protected function matchInvoiceNumber(string $text): string
    {
        // 1. 显式标签匹配 (发票号码: 12345678 或 24442000000012345678)
        if (preg_match('/(?:发票号码|发票号|号\s*码)[：:\s]*([0-9]{8,20})\b/u', $text, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/(?:\bNo\b|\bNumber\b)[：:\s]*([0-9]{8,20})/i', $text, $m)) {
            return trim($m[1]);
        }

        // 2. 数电发票特征：20位连续数字 (通常以2开头)
        if (preg_match('/\b(2[0-9]{19})\b/', $text, $m)) {
            return $m[1];
        }

        // 3. 20位任意连续数字
        if (preg_match('/\b([0-9]{20})\b/', $text, $m)) {
            return $m[1];
        }

        // 4. 传统发票特征：独立8位数字
        if (preg_match('/\b([0-9]{8})\b/', $text, $m)) {
            return $m[1];
        }

        return '';
    }

    /**
     * 匹配开票日期
     */
    protected function matchInvoiceDate(string $text): ?string
    {
        // 模式1: 开票日期：2024年05月18日
        if (preg_match('/(?:开票日期|日\s*期)[：:\s]*([0-9]{4})\s*[年\-\/.]\s*([0-9]{1,2})\s*[月\-\/.]\s*([0-9]{1,2})\s*日?/u', $text, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // 模式2: 英文标签 Date: 2024-05-18
        if (preg_match('/(?:\bDate\b)[：:\s]*([0-9]{4})\s*[\-\/.]\s*([0-9]{1,2})\s*[\-\/.]\s*([0-9]{1,2})/i', $text, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // 模式3: 独立年月日常规格式
        if (preg_match('/\b(20[2-3][0-9])\s*[年\-\/.]\s*(0?[1-9]|1[0-2])\s*[月\-\/.]\s*(0?[1-9]|[12][0-9]|3[01])\s*日?\b/u', $text, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }

    /**
     * 匹配各项金额 (价税合计、金额、税额)
     */
    protected function matchAmounts(string $text): array
    {
        $totalAmount = 0.00;
        $amount = 0.00;
        $taxAmount = 0.00;

        // 1. 价税合计（小写）/ 总金额
        if (preg_match('/(?:价税合计.*?（小写）|价税合计.*?\(小写\)|（小写）|\(小写\)|合\s*计\s*金额)[：:\s]*[¥￥\s]*([0-9]+(?:\.[0-9]{2})?)/u', $text, $m)) {
            $totalAmount = (float) $m[1];
        } elseif (preg_match('/(?:价税合计)[^0-9¥￥\n\r]*[¥￥\s]*([0-9]+(?:\.[0-9]{2})?)/u', $text, $m)) {
            $totalAmount = (float) $m[1];
        }

        // 2. 不含税金额：兼容"金额""合计""不含税金额"标签，"价税合计"中的"合计"不参与匹配
        if (preg_match('/(?:不含税金额|(?<![价税])合\s*计(?:\s*金额)?|(?<![价税])金\s*额)[^0-9¥￥\n\r]*[¥￥\s]*([0-9]+(?:\.[0-9]{2})?)/u', $text, $m)) {
            $amount = (float) $m[1];
        }

        // 3. 税额合计
        if (preg_match('/(?:合\s*计\s*税\s*额|税\s*额)[^0-9¥￥\n\r*免税]*[¥￥\s]*([0-9]+(?:\.[0-9]{2})?|\*{1,3}|免税)/u', $text, $m)) {
            $val = trim($m[1]);
            $taxAmount = ($val === '*' || $val === '***' || $val === '免税') ? 0.00 : (float) $val;
        }

        // 4. 金额互相校验与自愈
        if ($totalAmount > 0 && $amount > 0 && $taxAmount <= 0) {
            $taxAmount = round(max(0.00, $totalAmount - $amount), 2);
        } elseif ($amount > 0 && $taxAmount > 0 && $totalAmount <= 0) {
            $totalAmount = round($amount + $taxAmount, 2);
        } elseif ($totalAmount > 0 && $amount <= 0) {
            // 若只匹配到总价，不含税金额默认以总价为准
            $amount = round(max(0.00, $totalAmount - $taxAmount), 2);
        } elseif ($totalAmount > 0 && $amount > 0 && $taxAmount > 0 && abs($amount + $taxAmount - $totalAmount) > 0.01) {
            // 三项同时命中但不自洽时，以价税合计为锚修正不含税金额
            $amount = round(max(0.00, $totalAmount - $taxAmount), 2);
        }

        return [$totalAmount, $amount, $taxAmount];
    }

    /**
     * 匹配购买方名称
     */
    protected function matchBuyerName(string $text): string
    {
        if (preg_match('/(?:购\s*买\s*方|受票方)[\s\S]*?(?:名\s*称)[：:\s]*([^\r\n]{2,80})/u', $text, $m)) {
            return $this->cleanEntityName($m[1]);
        }

        return '';
    }

    /**
     * 匹配销售方名称
     */
    protected function matchSellerName(string $text): string
    {
        if (preg_match('/(?:销\s*售\s*方|出票方|开票人信息|销售方信息)[\s\S]*?(?:名\s*称)[：:\s]*([^\r\n]{2,80})/u', $text, $m)) {
            return $this->cleanEntityName($m[1]);
        }

        return '';
    }

    /**
     * 清理企业名称杂质：纯文本流里名称后往往紧跟下一个字段标签，按标签截断并去掉后缀说明
     */
    protected function cleanEntityName(string $name): string
    {
        $name = trim($name);
        $parts = preg_split(
            '/(?:购\s*买\s*方|销\s*售\s*方|受票方|出票方|开票人|统一社会信用代码|纳税人识别号|地\s*址|电\s*话|开户行|银行账号|价税合计|项目名称|备\s*注)/u',
            $name,
            2
        );
        if (is_array($parts) && $parts !== []) {
            $name = $parts[0];
        }
        // 注意：trim 的字符集按字节匹配，不能混入全角冒号等多字节字符，否则会截断末尾汉字
        $name = trim($name);
        $name = preg_replace('/^[\s:：]+|[\s:：]+$/u', '', $name) ?? $name;
        return mb_substr($name, 0, 100);
    }

    /**
     * 检查系统是否安装 pdftotext
     */
    protected function hasPdftotext(): bool
    {
        static $available = null;
        if ($available === null) {
            $path = @shell_exec('which pdftotext 2>/dev/null');
            $available = is_string($path) && trim($path) !== '';
        }
        return $available;
    }

    /**
     * 空发票数据结构
     */
    public function getEmptyResult(string $fileName = ''): array
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return [
            'invoice_type'   => ExpenseInvoice::TYPE_GENERAL,
            'invoice_code'   => '',
            'invoice_number' => '',
            'invoice_date'   => null,
            'total_amount'   => 0.00,
            'amount'         => 0.00,
            'tax_amount'     => 0.00,
            'seller_name'    => '',
            'buyer_name'     => '',
            'file_name'      => basename($fileName),
            'file_type'      => $extension ?: 'pdf',
            'file_path'      => $fileName,
        ];
    }
}
