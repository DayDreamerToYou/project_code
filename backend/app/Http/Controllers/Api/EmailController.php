<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use App\Services\PurchaseService;
use App\Services\LandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private PurchaseService $purchaseService,
        private LandingService $landingService
    ) {}

    /**
     * 发送带PDF附件的邮件
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'pdfData' => 'required|string',
            'filename' => 'nullable|string',
        ]);

        try {
            $result = $this->emailService->sendInvoiceEmail(
                $validated['to'],
                $validated['subject'],
                $validated['body'] ?? 'Please find the attached PDF file.',
                $validated['pdfData'],
                $validated['filename'] ?? ''
            );

            return $this->success($result, '邮件发送成功');
        } catch (\Exception $e) {
            Log::error('邮件发送失败', ['error' => $e->getMessage()]);
            return $this->error('邮件发送失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新采购记录邮件发送状态
     */
    public function updatePurchaseEmailSent(Request $request)
    {
        $validated = $request->validate([
            'PurchaseID' => 'required|integer',
            'EmailSent' => 'required|integer|in:0,1',
        ]);

        try {
            $purchase = \App\Models\Purchase::notDeleted()->find($validated['PurchaseID']);
            if (!$purchase) {
                return $this->error('采购记录不存在');
            }

            $purchase->update(['EmailSent' => $validated['EmailSent']]);

            return $this->success(['PurchaseID' => $purchase->PurchaseID, 'EmailSent' => $purchase->EmailSent], '状态更新成功');
        } catch (\Exception $e) {
            Log::error('更新邮件状态失败', ['error' => $e->getMessage()]);
            return $this->error('更新邮件状态失败: ' . $e->getMessage());
        }
    }
}
