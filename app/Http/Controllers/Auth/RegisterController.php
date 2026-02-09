<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Users\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravolt\Avatar\Avatar;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default, this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        event(new Registered($user = $this->create($validated)));

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 201)
            : redirect($this->redirectPath());
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\Users\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function registered(RegisterRequest $request, $user)
    {
        $user->assignRole('user');

        // Create default avatar
        $fileName = Str::random(30).'.png';
        $avatar = new Avatar(config('laravolt.avatar'));
        $avatar->create($user->name)->save(storage_path('app/public/'.User::AVATAR_PATH.$fileName), 100);
        $user->avatar = $fileName;
        $user->initial_name = User::AVATAR_INITIAL_NAME;
        $user->update();

        flash()->success(
            __('messages.successfully_registered_please_check_your_email_to_confirm_your_account',
            ['email' => $user->email])
        );

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('login')]);
    }
}
