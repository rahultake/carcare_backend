<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

class ReportController extends Controller
{
    public function index()
    {
        $data = [
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status','completed')->sum('total_amount'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
        ];

        return view('admin.reports.index', compact('data'));
    }
}
?>