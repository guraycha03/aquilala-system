<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminArchiveController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $type = $request->string('type', 'users')->toString();
        $types = [
            'users' => [
                'label' => 'Account Users',
                'description' => 'Archived sign-in accounts that no longer appear in User Management.',
                'count' => User::onlyTrashed()->count(),
            ],
            'members' => [
                'label' => 'Members',
                'description' => 'Archived association member records hidden from the roster.',
                'count' => Member::onlyTrashed()->count(),
            ],
            'harvest-reports' => [
                'label' => 'Harvest Reports',
                'description' => 'Older harvest records archived from All Harvest Reports.',
                'count' => \App\Models\HarvestReport::onlyTrashed()->count(),
            ],
        ];

        abort_unless(isset($types[$type]), 404);

        return view('admin.archive.index', [
            'type' => $type,
            'types' => $types,
            'records' => match ($type) {
                'users' => User::onlyTrashed()->latest('archived_at')->get(),
                'members' => Member::onlyTrashed()->latest('archived_at')->get(),
                'harvest-reports' => \App\Models\HarvestReport::onlyTrashed()
                    ->with(['farmer', 'member'])
                    ->latest('archived_at')
                    ->get(),
            },
        ]);
    }

    public function restoreUser(int $user): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        User::onlyTrashed()->findOrFail($user)->restore();

        return redirect()
            ->route('admin.archive', ['type' => 'users'])
            ->with('success', 'Account user restored.');
    }

    public function restoreMember(int $member): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        Member::onlyTrashed()->findOrFail($member)->restore();

        return redirect()
            ->route('admin.archive', ['type' => 'members'])
            ->with('success', 'Member restored.');
    }
}
