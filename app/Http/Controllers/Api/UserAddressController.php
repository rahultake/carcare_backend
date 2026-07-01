<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    public function index()
    {
        $addresses = Auth::user()->addresses()->get();

        return response()->json([
            'success' => true,
            'addresses' => $addresses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'gstin_number' => 'nullable|string|max:100',
        ]);

        $address = UserAddress::create([
            'user_id' => Auth::id(),
            ...$request->only([
                'address_line1', 'address_line2', 'phone', 'city', 'state', 'postal_code', 'country', 'company_name', 'gstin_number'
            ])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully',
            'address' => $address
        ]);
    }

    public function update(Request $request, $id)
    {
        $address = UserAddress::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $request->validate([
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'gstin_number' => 'nullable|string|max:100',
        ]);

        $address->update($request->only([
            'address_line1', 'address_line2', 'phone', 'city', 'state', 'postal_code', 'country', 'company_name', 'gstin_number'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'address' => $address
        ]);
    }

    public function destroy($id)
    {
        $address = UserAddress::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully'
        ]);
    }
}
?>