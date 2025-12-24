<?php

namespace App\Http\Controllers\Profiles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profiles\ChangeEmailRequest;
use App\Http\Requests\Profiles\ChangePasswordRequest;
use App\Http\Requests\Profiles\ChangeUsernameRequest;
use App\Http\Requests\Profiles\UpdateProfileRequest;
use App\Models\Users\User;
use App\Notifications\Users\UserChangedEmail;
use App\Notifications\Users\UserChangedPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:my-account')->only('index', 'update');
        $this->middleware('permission:change-password')->only('showChangePasswordForm', 'changePassword');
        $this->middleware('permission:change-username')->only('showChangeUsernameForm', 'changeUsername');
        $this->middleware('permission:change-email')->only('showChangeEmailForm', 'changeEmail');
        $this->middleware('permission:my-activities')->only('activities');
        $this->middleware('permission:recent-sessions')->only('recentSessions');
    }

    public function index()
    {
        if (config('core.custom_profile_enabled')) {
            return redirect()->route(config('core.profile_route_name'));
        }

        $user = User::findOrFail(auth()->id());

        return view('profiles.index', [
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        if (config('core.custom_profile_enabled')) {
            return response()->json(['success' => false, 'message' => __('Forbidden')], 403);
        }

        // Retrieve the validated input data...
        $validated = $request->validated();

        $user = User::findOrFail(Auth::id());
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->initial_name = $validated['initial_name'];

        if ($request->hasFile('avatar')) {
            Storage::delete(User::AVATAR_PATH . $user->avatar);
            $avatar = $request->file('avatar');
            $fileName = $avatar->hashName();
            $avatar->store(User::AVATAR_PATH);
            $user->avatar = $fileName;
            $user->initial_name = User::AVATAR_NOT_INITIAL_NAME;
        }

        if ($user->initial_name == User::AVATAR_INITIAL_NAME) {
            Storage::delete(User::AVATAR_PATH . $user->avatar);
            $fileName = Str::random(30) . '.png';
            Avatar::create($user->name)->save(storage_path('app/' . User::AVATAR_PATH . $fileName), 100);
            $user->avatar = $fileName;
        }

        if (config('core.ensure_profile_is_completed') && !$user->profile_completed) {
            $user->profile_completed = true;
        }

        $user->update();

        flash()->success(__('messages.profile_successfully_updated'));

        activity(__('messages.profile'))
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log(__('messages.profile_has_been_updated'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('profile.index')]);
    }

    public function showChangePasswordForm()
    {
        $user = User::findOrFail(auth()->id());

        $isPasswordSet = false;
        if ($user->password) {
            $isPasswordSet = true;
        }

        return view('profiles.change-password', [
            'user' => $user,
            'is_password_set' => $isPasswordSet,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::findOrFail(Auth::id());

        // Retrieve the validated input data...
        $validated = $request->validated();

        $user->password = Hash::make($validated['new_password']);
        $user->password_changed_at = now();
        $user->update();

        $user->notify(new UserChangedPassword());

        flash()->success(__('messages.change_password_successfully'));

        activity(__('messages.profile'))
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->log(__('messages.change_password_has_been_updated'));

        if (config('core.ensure_profile_is_completed') && !auth()->user()->profile_completed) {
            return response()->json(['success' => true, 'redirect' => route(config('core.profile_route_name'))]);
        }

        return response()->json(['success' => true, 'redirect' => route('profile.change-password-form')]);
    }

    public function showChangeUsernameForm()
    {
        $user = User::findOrFail(auth()->id());

        return view('profiles.change-username', [
            'user' => $user
        ]);
    }

    public function changeUsername(ChangeUsernameRequest $request)
    {
        $user = User::findOrFail(Auth::id());

        // Retrieve the validated input data...
        $validated = $request->validated();

        $user->username = $validated['username'];
        $user->update();

        flash()->success(__('messages.change_username_successfully'));

        activity(__('messages.profile'))
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->log(__('messages.change_username_has_been_updated'));

        return response()->json(['success' => true, 'redirect' => route('profile.change-username-form')]);
    }

    public function showChangeEmailForm()
    {
        $user = User::findOrFail(auth()->id());

        return view('profiles.change-email', [
            'user' => $user,
        ]);
    }

    public function changeEmail(ChangeEmailRequest $request)
    {
        $user = User::findOrFail(Auth::id());

        // Retrieve the validated input data...
        $validated = $request->validated();

        $user->email = $validated['email'];
        $user->save();

        if($user->wasChanged('email'))
        {
            //invalidate the previously verified email
            $user->update([
                'email_verified_at' => null
            ]);

            //email here to notify if user successfully changed email
            $user->notify(new UserChangedEmail());
        }

        flash()->success(__('messages.change_email_successfully'));

        activity(__('messages.profile'))
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->log(__('messages.change_email_has_been_updated'));

        return response()->json(['success' => true, 'redirect' => route('profile.change-email-form')]);
    }

    public function activities()
    {
        return view('profiles.activities');
    }

    public function recentSessions()
    {
        return view('profiles.recent-sessions');
    }
}
