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
     * Show the edit form for the resource.
     *
     * @param Request $request
     * @return View
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load([
            'roles:id,name,color',
            'permissions:id,name',
        ]);

        return view('settings.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the current resource.
     *
     * @param SettingsUpdateRequest $request
     * @return RedirectResponse
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
     * Delete the current resource.
     *
     * @param Request $request
     * @return RedirectResponse
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
