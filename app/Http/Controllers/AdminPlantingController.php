<?php

namespace App\Http\Controllers;

use App\Models\FarmerCrop;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPlantingController extends Controller
{
    /**
     * Planting Requests — every farmer crop batch, newest first, with the
     * pending ones front and center. Admins confirm a batch here before the
     * farmer can advance its growth stage or file a harvest report.
     */
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'pending');
        if (! in_array($status, ['pending', 'confirmed', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $crops = FarmerCrop::with(['crop', 'user', 'confirmedBy'])
            ->when($status !== 'all', fn ($q) => $q->where('confirmation_status', $status))
            ->orderByDesc('planting_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'pending' => FarmerCrop::pendingConfirmation()->count(),
            'confirmed' => FarmerCrop::confirmed()->count(),
            'rejected' => FarmerCrop::rejected()->count(),
            'total' => FarmerCrop::count(),
        ];

        return view('admin.plantings.index', compact('crops', 'stats', 'status'));
    }

    public function confirm(FarmerCrop $farmerCrop)
    {
        if ($farmerCrop->isConfirmed()) {
            return back()->with('success', 'This planting is already confirmed.');
        }

        $farmerCrop->update([
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
            'admin_remarks' => null,
        ]);

        ActivityLogger::cropConfirmed($farmerCrop);

        return back()->with('success', "Planting confirmed — {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}) is now active.");
    }

    public function reject(Request $request, FarmerCrop $farmerCrop)
    {
        if ($farmerCrop->isRejected()) {
            return back()->with('success', 'This planting was already rejected.');
        }

        $validated = $request->validate([
            'admin_remarks' => 'nullable|string|max:255',
        ]);

        $farmerCrop->update([
            'confirmation_status' => FarmerCrop::CONFIRMATION_REJECTED,
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
            'admin_remarks' => $validated['admin_remarks'] ?? null,
        ]);

        ActivityLogger::cropRejected($farmerCrop, $validated['admin_remarks'] ?? null);

        return back()->with('success', "Planting rejected — {$farmerCrop->crop?->name} ({$farmerCrop->batch_name}).");
    }
}
