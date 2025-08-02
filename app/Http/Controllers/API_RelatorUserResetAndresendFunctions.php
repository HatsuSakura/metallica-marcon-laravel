<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;



class API_RelatorUserResetAndresendFunctions extends Controller
{
    public function resendVerification(Request $request, User $user)
    {
        // Ensure the user is not already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json(['type' => 'info', 'message' => 'L\'utente risulta aver già verificato la mail'], 200);
        }

        // Send the verification email
        $user->sendEmailVerificationNotification();
        return response()->json(['type' => 'success',  'message' => 'Mail inviata correttamente all\'utente ' . $user->email], 200);
    }

    public function sendPasswordResetEmail(Request $request, User $user)
    {
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
    
            if ($status === Password::RESET_LINK_SENT) {
                return response()->json(['type' => 'success', 'message' => 'Password reset link sent to ' . $user->email]);
            } else {
                return response()->json(['type' => 'error', 'message' => __($status)]);
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Password reset error: ' . $e->getMessage());
    
            return response()->json(['type' => 'error', 'message' =>  $e->getMessage()]);
            //return response()->json(['type' => 'error', 'message' => 'An unexpected error occurred.']);
        }
    }

}
