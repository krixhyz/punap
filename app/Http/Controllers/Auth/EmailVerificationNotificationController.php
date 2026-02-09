<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request)
    {
        if (method_exists($request->user(), 'hasVerifiedEmail') && $request->user()->hasVerifiedEmail()) {
            return back();
        }

        if (method_exists($request->user(), 'sendEmailVerificationNotification')) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'verification-link-sent');
    }
}
