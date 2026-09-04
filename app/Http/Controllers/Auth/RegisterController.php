<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AcceptWeddingInvitation;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Models\WeddingInvitation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            'invitation' => $this->pendingInvitation($request),
        ]);
    }

    public function store(RegisterRequest $request, AcceptWeddingInvitation $accept): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'role' => UserRole::Customer,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if ($invitation = $accept->fromSession($user)) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'Selamat datang, '.$user->name.'! Anda kini menguruskan "'.$invitation->wedding->title.'" bersama '.$invitation->inviter->name.'.');
        }

        return redirect()
            ->intended($user->homeRoute())
            ->with('status', 'Selamat datang ke Neekah, '.$user->name.'!');
    }

    /**
     * The invitation whose link brought this visitor here, if any.
     */
    private function pendingInvitation(Request $request): ?WeddingInvitation
    {
        $token = $request->session()->get(AcceptWeddingInvitation::SESSION_KEY);

        return $token
            ? WeddingInvitation::with(['wedding', 'inviter'])->where('token', $token)->first()
            : null;
    }
}
