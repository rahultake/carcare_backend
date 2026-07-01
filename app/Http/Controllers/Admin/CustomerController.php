<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = User::withCount('orders')->paginate(20);
        return view('admin.customers.index', compact('customers'));
    }

    public function show($id)
    {
        $customer = User::with(['orders','addresses'])->findOrFail($id);
        return view('admin.customers.show', compact('customer'));
    }
    public function destroy($id): RedirectResponse
    {
        $customer = User::findOrFail($id);

        // ❗ SAFETY CHECK: Do not delete if customer has orders
        if ($customer->orders()->exists()) {
            return redirect()
                ->route('admin.customers.index')
                ->with('error', 'Customer has orders and cannot be deleted.');
        }

        // Delete related addresses
        $customer->addresses()->delete();

        // Delete customer
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
?>