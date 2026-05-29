<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\InvoiceMail;

class EmailService
{
    /**
     * 发送带PDF附件的邮件
     */
    public function sendInvoiceEmail(string $to, string $subject, string $body, string $pdfBase64, string $pdfFilename = ''): array
    {
        try {
            // 验证邮箱格式
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('无效的邮箱地址');
            }

            // 解码PDF数据
            $pdfContent = base64_decode($pdfBase64);
            if ($pdfContent === false) {
                throw new \Exception('PDF数据解码失败');
            }

            // 生成文件名
            if (empty($pdfFilename)) {
                $pdfFilename = 'Invoice_' . date('YmdHis') . '.pdf';
            }

            // 发送邮件
            Mail::to($to)->send(new InvoiceMail($subject, $body, $pdfContent, $pdfFilename));

            Log::info('邮件发送成功', ['to' => $to, 'subject' => $subject]);

            return [
                'success' => true,
                'message' => '邮件发送成功',
            ];
        } catch (\Exception $e) {
            Log::error('邮件发送失败', ['to' => $to, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
