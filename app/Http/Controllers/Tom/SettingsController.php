<?php

namespace App\Http\Controllers\Tom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tom\SettingsUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('settings.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $avatar = $user->avatar;

        $user->fill($request->validated());

        if ($request->hasFile('avatar')) {
            
            if ($avatar && Storage::disk('public')->exists('users/' . $user->username . '/' . $avatar)) {
                Storage::disk('public')->delete('users/' . $user->username . '/' . $avatar);
            }

            $file = $request->file('avatar');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            
            //public/storage/users/{username}/{filename}
            $file->storeAs('users/' . $user->username, $filename, 'public');

            $user->avatar = $filename;
        }

        $request->user()->save();

        return Redirect::route('settings.edit')->with('status', 'settings-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
