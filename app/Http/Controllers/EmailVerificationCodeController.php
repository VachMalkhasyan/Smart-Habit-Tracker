<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationMail;
use App\Models\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailVerificationCodeController extends Controller
{
    // Show the verify page
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $record = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        return Inertia::render('Auth/VerifyEmailCode', [
            'secondsUntilResend' => $record ? $record->secondsUntilResend() : 0,
            'email'              => $user->email,
        ]);
    }

    // Verify the submitted code
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user   = $request->user();
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->latest()
            ->first();

        if (!$record) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        if ($record->isExpired()) {
            return back()->withErrors(['code' => 'This code has expired. Please request a new one.']);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Clean up codes
        EmailVerificationCode::where('user_id', $user->id)->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Email verified! Welcome to Smart Habit Tracker 🎉');
    }

    // Resend code
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Your email is already verified.');
        }

        $record = EmailVerificationCode::where('user_id', $user->id)
            ->latest()
            ->first();

        // Enforce 60 second cooldown
        if ($record && !$record->canResend()) {
            return back()->withErrors([
                'code' => "Please wait {$record->secondsUntilResend()} seconds before resending."
            ]);
        }

        $this->sendCode($user);

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    // Helper — generate and send code
    public static function sendCode($user): void
    {
        // Delete old codes
        EmailVerificationCode::where('user_id', $user->id)->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'code'       => $code,
            'expires_at' => now()->addMinutes(30),
            'resent_at'  => now(),
        ]);

        Mail::to($user->email)->send(new EmailVerificationMail($user, $code));
    }
}
