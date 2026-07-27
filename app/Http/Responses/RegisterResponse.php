<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * Generate the response after a successful registration.
     * It logs the user in and redirects to the configured home path.
     */
    public function toResponse($request)
    {
        // Retrieve the newly created user by email (register request includes email)
        $user = User::where('email', $request->email)->first();
        if ($user) {
            Auth::login($user);
        }

        return redirect()->intended('/dashboard');
    }
}
