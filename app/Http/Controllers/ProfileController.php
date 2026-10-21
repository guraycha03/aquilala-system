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
}
