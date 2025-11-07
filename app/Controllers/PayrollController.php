<?php
namespace App\Controllers;

use App\Core\{Controller, Response};
use App\Services\PayrollService;

class PayrollController extends Controller
{
    public function index(): Response
    {
        if ($response = $this->requireAuth('payroll.manage')) {
            return $response;
        }

        $month = $_GET['month'] ?? date('Y-m');
        $flash = $this->consumeFlash();
        $service = new PayrollService();
        $results = $flash['payroll_results'] ?? $service->compute($month, false);

        return $this->view('payroll/index', [
            'month' => $month,
            'results' => $results,
            'flash' => $flash,
        ]);
    }

    public function compute(): Response
    {
        if ($response = $this->requireAuth('payroll.manage')) {
            return $response;
        }

        $month = $_POST['month'] ?? date('Y-m');
        $persist = isset($_POST['persist']) && $_POST['persist'] === '1';
        $service = new PayrollService();
        $results = $service->compute($month, $persist);

        return $this->redirectWith('/payroll?month=' . urlencode($month), [
            'success' => 'Ish haqi hisoblandi',
            'payroll_results' => $results,
        ]);
    }
}
