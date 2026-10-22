<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ProfileController
 *
 * The one thing a member can change about their own account here: the
 * picture that follows them around the system.
 *
 * The picture is theirs, on the public disk, and the account holds the
 * path. Removing it puts the account back on its initials, so there is
 * always something to show.
 */
class ProfileController extends Controller
{
    /**
     * Upload (or replace) this account's picture.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Please choose a picture.',
            'photo.image' => 'Please choose an image file.',
            'photo.mimes' => 'Use a JPG, PNG, or WebP image.',
            'photo.max' => 'The picture must be smaller than 2 MB.',
        ]);

        $user = $request->user();

        $this->deletePhoto($user);

        $path = $validated['photo']->store('user-photos', ['disk' => 'public']);
        $user->forceFill(['profile_photo' => $path])->save();

        return response()->json([
            'ok' => true,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Remove this account's picture. The account goes back to its initials.
     */
    public function removePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->deletePhoto($user);
        $user->forceFill(['profile_photo' => null])->save();

        return response()->json([
            'ok' => true,
            'url' => null,
            'initials' => $user->initials(),
        ]);
    }

    /**
     * Clear the old file so a replaced picture does not linger on disk.
     */
    private function deletePhoto($user): void
    {
        if (! $user->profile_photo) {
            return;
        }

        Storage::disk('public')->delete($user->profile_photo);
    }

    /**
     * Update this account's own address.
     *
     * The address is the one part of the profile a member keeps current:
     * where they farm or trade changes, and a stale address sends the
     * association's people to the wrong place. Everything else on the
     * profile — name, role, status, farm details — is the association's
     * record and is changed from the admin side.
     */
    public function updateAddress(Request $request)
    {
        $user = $request->user();

        // The association's own records are corrected from the admin
        // side, where the registry can be checked.
        if ($user->role === 'admin') {
            abort(403, 'The association\'s address is corrected from the admin side.');
        }

        $validated = $request->validate([
            'address'  => ['nullable', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city'     => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'address'  => $validated['address'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'city'     => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
        ])->save();

        return back()->with('success', 'Your address has been updated.');
    }
}
