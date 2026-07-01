<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingInquiry;

class TrainingInquiryController extends Controller
{
    public function index()
    {
        $inquiries = TrainingInquiry::latest()
                    ->paginate(20);

        return view(
            'admin.training.inquiries.index',
            compact('inquiries')
        );
    }

    public function destroy($id)
    {
        $inquiry = TrainingInquiry::findOrFail($id);

        $inquiry->delete();

        return redirect()
            ->route('admin.training-inquiries.index')
            ->with(
                'success',
                'Inquiry Deleted Successfully'
            );
    }
}