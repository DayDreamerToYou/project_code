<?php

namespace App\Services;

use App\Models\Printer;
use App\Models\Landing;
use App\Models\LandingDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Sales;
use App\Models\SalesDetail;
use Illuminate\Support\Facades\Log;

class PrinterService
{
    /**
     * 获取打印机列表
     */
    public function getList(int $page, int $pageSize, string $search = ''): array
    {
        $query = Printer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('printer_name', 'like', "%{$search}%")
                  ->orWhere('printer_ip', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $data = $query->orderBy('is_default', 'desc')
            ->orderBy('id', 'desc')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => ceil($total / $pageSize),
            ],
        ];
    }

    /**
     * 获取单个打印机
     */
    public function getById(int $id): ?Printer
    {
        return Printer::find($id);
    }

    /**
     * 添加打印机
     */
    public function add(array $data): Printer
    {
        if (!empty($data['is_default'])) {
            Printer::where('is_default', 1)->update(['is_default' => 0]);
        }

        return Printer::create([
            'printer_name' => $data['printer_name'],
            'printer_ip' => $data['printer_ip'],
            'printer_port' => $data['printer_port'],
            'printer_type' => $data['printer_type'] ?? 'ESC/POS',
            'is_default' => $data['is_default'] ?? 0,
            'status' => $data['status'] ?? 1,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * 编辑打印机
     */
    public function update(int $id, array $data): ?Printer
    {
        $printer = Printer::find($id);
        if (!$printer) {
            return null;
        }

        if (!empty($data['is_default'])) {
            Printer::where('is_default', 1)->where('id', '!=', $id)->update(['is_default' => 0]);
        }

        $printer->update([
            'printer_name' => $data['printer_name'] ?? $printer->printer_name,
            'printer_ip' => $data['printer_ip'] ?? $printer->printer_ip,
            'printer_port' => $data['printer_port'] ?? $printer->printer_port,
            'printer_type' => $data['printer_type'] ?? $printer->printer_type,
            'is_default' => $data['is_default'] ?? $printer->is_default,
            'status' => $data['status'] ?? $printer->status,
            'description' => $data['description'] ?? $printer->description,
        ]);

        return $printer->fresh();
    }

    /**
     * 删除打印机
     */
    public function delete(int $id): bool
    {
        return Printer::destroy($id) > 0;
    }

    /**
     * 设置默认打印机
     */
    public function setDefault(int $id): bool
    {
        Printer::where('is_default', 1)->update(['is_default' => 0]);
        return Printer::where('id', $id)->update(['is_default' => 1]) > 0;
    }

    /**
     * 测试打印机连接
     */
    public function testConnection(string $ip, int $port): array
    {
        $result = $this->sendToPrinter($ip, $port, "\x1B\x40Test Print\n\n\n\x1D\x56\x00");
        return $result;
    }

    /**
     * 网络打印到货记录
     */
    public function printLanding(int $landingId, ?int $printerId = null): array
    {
        $printer = $this->resolvePrinter($printerId);
        if (!$printer) {
            throw new \Exception('没有可用的打印机，请先在数据管理中配置打印机');
        }

        $landing = Landing::notDeleted()
            ->with(['supplier', 'boat', 'port'])
            ->find($landingId);

        if (!$landing) {
            throw new \Exception('到货记录不存在');
        }

        $details = LandingDetail::where('LandingID', $landingId)
            ->notDeleted()
            ->with(['stock'])
            ->get()
            ->toArray();

        $landingData = [
            'LandingID' => $landing->LandingID,
            'LandingDate' => $landing->LandingDate ? $landing->LandingDate->format('Y-m-d') : '-',
            'SupplierName' => $landing->supplier?->SupplierName ?? '-',
            'BoatName' => $landing->boat?->BoatName ?? '-',
            'BoatNo' => $landing->boat?->BoatNo ?? '-',
            'Port' => $landing->port?->Port ?? '-',
            'details' => $details,
        ];

        $printData = $this->generateLandingPrintData($landingData);
        $result = $this->sendToPrinter($printer->printer_ip, $printer->printer_port, $printData);

        if ($result['success']) {
            $result['message'] = "已通过打印机 [{$printer->printer_name}] 打印到货记录";
            $result['printer'] = $printer->printer_name;
        }

        return $result;
    }

    /**
     * 网络打印采购单
     */
    public function printPurchase(int $purchaseId, ?int $printerId = null): array
    {
        $printer = $this->resolvePrinter($printerId);
        if (!$printer) {
            throw new \Exception('没有可用的打印机，请先在数据管理中配置打印机');
        }

        $purchase = Purchase::notDeleted()
            ->with(['supplier'])
            ->find($purchaseId);

        if (!$purchase) {
            throw new \Exception('采购记录不存在');
        }

        $details = PurchaseDetail::where('PurchaseID', $purchaseId)
            ->notDeleted()
            ->with(['stock'])
            ->get()
            ->toArray();

        $purchaseData = [
            'PurchaseID' => $purchase->PurchaseID,
            'PurchaseDate' => $purchase->PurchaseDate ? $purchase->PurchaseDate->format('Y-m-d') : '-',
            'SupplierName' => $purchase->supplier?->SupplierName ?? '-',
            'Subtotal' => $purchase->Subtotal ?? 0,
            'GST' => $purchase->GST ?? 0,
            'Total' => $purchase->Total ?? 0,
            'details' => $details,
        ];

        $printData = $this->generatePurchasePrintData($purchaseData);
        $result = $this->sendToPrinter($printer->printer_ip, $printer->printer_port, $printData);

        if ($result['success']) {
            $result['message'] = "已通过打印机 [{$printer->printer_name}] 打印采购单";
            $result['printer'] = $printer->printer_name;
        }

        return $result;
    }

    /**
     * 网络打印销售单
     */
    public function printSales(int $salesId, ?int $printerId = null): array
    {
        $printer = $this->resolvePrinter($printerId);
        if (!$printer) {
            throw new \Exception('没有可用的打印机，请先在数据管理中配置打印机');
        }

        $sales = Sales::notDeleted()
            ->with(['customer'])
            ->find($salesId);

        if (!$sales) {
            throw new \Exception('销售记录不存在');
        }

        $details = SalesDetail::where('SalesID', $salesId)
            ->notDeleted()
            ->with(['stock'])
            ->get()
            ->toArray();

        $salesData = [
            'SalesID' => $sales->SalesID,
            'SaleDate' => $sales->SaleDate ?? '-',
            'CustomerName' => $sales->customer?->CustomerName ?? '-',
            'Subtotal' => $sales->Subtotal ?? 0,
            'GST' => $sales->GST ?? 0,
            'Total' => $sales->Total ?? 0,
            'details' => $details,
        ];

        $printData = $this->generateSalesPrintData($salesData);
        $result = $this->sendToPrinter($printer->printer_ip, $printer->printer_port, $printData);

        if ($result['success']) {
            $result['message'] = "已通过打印机 [{$printer->printer_name}] 打印销售单";
            $result['printer'] = $printer->printer_name;
        }

        return $result;
    }

    /**
     * 解析打印机（指定ID或使用默认）
     */
    private function resolvePrinter(?int $printerId): ?Printer
    {
        if ($printerId) {
            return Printer::where('id', $printerId)->where('status', 1)->first();
        }

        $printer = Printer::where('is_default', 1)->where('status', 1)->first();
        if (!$printer) {
            $printer = Printer::where('status', 1)->orderBy('id')->first();
        }

        return $printer;
    }

    /**
     * 发送数据到网络打印机
     */
    private function sendToPrinter(string $ip, int $port, string $data): array
    {
        try {
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($socket === false) {
                return [
                    'success' => false,
                    'message' => '无法创建socket: ' . socket_strerror(socket_last_error()),
                ];
            }

            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 5, 'usec' => 0]);

            $result = @socket_connect($socket, $ip, $port);

            if ($result === false) {
                socket_close($socket);
                return [
                    'success' => false,
                    'message' => "无法连接到打印机 {$ip}:{$port} - " . socket_strerror(socket_last_error()),
                ];
            }

            @socket_write($socket, $data, strlen($data));
            socket_close($socket);

            return [
                'success' => true,
                'message' => '打印任务已发送',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '打印异常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * 生成ESC/POS打印指令（到货记录）
     */
    private function generateLandingPrintData(array $landing): string
    {
        $data = "\x1B\x40";
        $data .= "\x1B\x61\x01";
        $data .= "\x1D\x21\x11";
        $data .= "LANDING RECORD\n";
        $data .= "\x1D\x21\x00";
        $data .= "\x1B\x61\x00";
        $data .= "================================\n";

        $data .= "\x1B\x45\x01" . "Landing ID: " . "\x1B\x45\x00" . ($landing['LandingID'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Date: " . "\x1B\x45\x00" . ($landing['LandingDate'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Supplier: " . "\x1B\x45\x00" . ($landing['SupplierName'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Boat: " . "\x1B\x45\x00" . ($landing['BoatName'] ?? '-') . " (" . ($landing['BoatNo'] ?? '-') . ")\n";
        $data .= "\x1B\x45\x01" . "Port: " . "\x1B\x45\x00" . ($landing['Port'] ?? '-') . "\n\n";
        $data .= "--------------------------------\n";

        if (!empty($landing['details'])) {
            foreach ($landing['details'] as $detail) {
                $stock = $detail['stock']['Stock'] ?? ($detail['StockID'] ?? '-');
                $binQty = $detail['BinQty'] ?? 0;
                $lWeight = $detail['L-Weight'] ?? 0;
                $ice = $detail['ICE'] ?? 'NO';
                $price = $detail['Price'] ?? 0;
                $total = $lWeight * $price;

                $data .= "\x1B\x45\x01" . mb_substr($stock, 0, 16, 'UTF-8') . "\n" . "\x1B\x45\x00";
                $data .= sprintf("  Bins:%-3d ICE:%-3s\n", $binQty, $ice);
                $data .= sprintf("  Weight:%6.2fkg Price:$%-7.2f\n", $lWeight, $price);
                $data .= sprintf("  Total:$%-7.2f\n", $total);
                $data .= "--------------------------------\n";
            }
        }

        $totalAmount = 0;
        if (!empty($landing['details'])) {
            foreach ($landing['details'] as $detail) {
                $totalAmount += ($detail['L-Weight'] ?? 0) * ($detail['Price'] ?? 0);
            }
        }

        $data .= "\n\x1D\x21\x11" . sprintf("TOTAL:    $%18.2f\n", $totalAmount) . "\x1D\x21\x00";
        $data .= "\n\n\n\x1D\x56\x00";

        return $data;
    }

    /**
     * 生成ESC/POS打印指令（采购单）
     */
    private function generatePurchasePrintData(array $purchase): string
    {
        $data = "\x1B\x40";
        $data .= "\x1B\x61\x01";
        $data .= "\x1D\x21\x11";
        $data .= "PURCHASE ORDER\n";
        $data .= "\x1D\x21\x00";
        $data .= "\x1B\x61\x00";
        $data .= "================================\n";

        $data .= "\x1B\x45\x01" . "Purchase ID: " . "\x1B\x45\x00" . ($purchase['PurchaseID'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Date: " . "\x1B\x45\x00" . ($purchase['PurchaseDate'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Supplier: " . "\x1B\x45\x00" . ($purchase['SupplierName'] ?? '-') . "\n\n";
        $data .= "--------------------------------\n";

        if (!empty($purchase['details'])) {
            foreach ($purchase['details'] as $detail) {
                $stock = $detail['stock']['Stock'] ?? ($detail['StockID'] ?? '-');
                $ice = $detail['ICE'] ?? 'NO';
                $greenKG = $detail['GreenKG'] ?? 0;
                $landedKG = $detail['LandedKG'] ?? 0;
                $price = $detail['Price'] ?? 0;
                $total = $detail['Total'] ?? 0;

                $data .= "\x1B\x45\x01" . mb_substr($stock, 0, 16, 'UTF-8') . "\n" . "\x1B\x45\x00";
                $data .= sprintf("  ICE:%-3s\n", $ice);
                $data .= sprintf("  Green:%6.2fkg Landed:%6.2fkg\n", $greenKG, $landedKG);
                $data .= sprintf("  Price:$%-7.2f Total:$%-7.2f\n", $price, $total);
                $data .= "--------------------------------\n";
            }
        }

        $data .= "\n" . sprintf("Subtotal: $%18.2f\n", $purchase['Subtotal'] ?? 0);
        $data .= sprintf("GST:      $%18.2f\n", $purchase['GST'] ?? 0);
        $data .= "\x1D\x21\x11" . sprintf("TOTAL:    $%18.2f\n", $purchase['Total'] ?? 0) . "\x1D\x21\x00";
        $data .= "\n\n\n\x1D\x56\x00";

        return $data;
    }

    /**
     * 生成ESC/POS打印指令（销售单）
     */
    private function generateSalesPrintData(array $sales): string
    {
        $data = "\x1B\x40";
        $data .= "\x1B\x61\x01";
        $data .= "\x1D\x21\x11";
        $data .= "SALES ORDER\n";
        $data .= "\x1D\x21\x00";
        $data .= "\x1B\x61\x00";
        $data .= "================================\n";

        $data .= "\x1B\x45\x01" . "Sales ID: " . "\x1B\x45\x00" . ($sales['SalesID'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Date: " . "\x1B\x45\x00" . ($sales['SaleDate'] ?? '-') . "\n";
        $data .= "\x1B\x45\x01" . "Customer: " . "\x1B\x45\x00" . ($sales['CustomerName'] ?? '-') . "\n\n";
        $data .= "--------------------------------\n";

        if (!empty($sales['details'])) {
            foreach ($sales['details'] as $detail) {
                $stock = $detail['stock']['Stock'] ?? ($detail['StockID'] ?? '-');
                $binQty = $detail['BinQty'] ?? 0;
                $gWeight = $detail['G-Weight'] ?? 0;
                $nWeight = $detail['N-Weight'] ?? 0;
                $price = $detail['Price'] ?? 0;
                $amount = $detail['Amount'] ?? 0;

                $data .= "\x1B\x45\x01" . mb_substr($stock, 0, 16, 'UTF-8') . "\n" . "\x1B\x45\x00";
                $data .= sprintf("  Bins:%-4d G.W:%6.2fkg N.W:%6.2fkg\n", $binQty, $gWeight, $nWeight);
                $data .= sprintf("  Price:$%-7.2f Amount:$%-7.2f\n", $price, $amount);
                $data .= "--------------------------------\n";
            }
        }

        $data .= "\n" . sprintf("Subtotal: $%18.2f\n", $sales['Subtotal'] ?? 0);
        $data .= sprintf("GST:      $%18.2f\n", $sales['GST'] ?? 0);
        $data .= "\x1D\x21\x11" . sprintf("TOTAL:    $%18.2f\n", $sales['Total'] ?? 0) . "\x1D\x21\x00";
        $data .= "\n\n\n\x1D\x56\x00";

        return $data;
    }
}
