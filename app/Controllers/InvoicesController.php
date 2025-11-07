<?php
namespace App\Controllers;

use App\Core\{Controller, Response};
use App\Services\InvoiceService;
use RuntimeException;

class InvoicesController extends Controller
{
    public function issue(string $orderId): Response
    {
        if ($response = $this->requireAuth('finance.manage')) {
            return $response;
        }

        $service = new InvoiceService();
        try {
            $service->issueForOrder((int)$orderId);
            return $this->redirectWith('/orders', ['success' => 'Hisob-faktura chiqarildi']);
        } catch (RuntimeException $e) {
            return $this->redirectWith('/orders', ['error' => $e->getMessage()]);
        }
    }
}
