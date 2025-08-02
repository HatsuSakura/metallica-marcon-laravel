<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create(){
        return inertia('Auth/Login');
    }

    public function store(Request $request){
        
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => 'Autenticazione fallita.',
            ]);
        }

        // Controllo can_login appena dopo login riuscito (ed scludo login di warehouse_worker che non sono abilitati)
        if (!auth()->user()->can_login) {
            Auth::logout();

            throw ValidationException::withMessages([
                'base' => 'Questo utente non è autorizzato ad accedere al sistema.',
            ]);
        }

        // utilizzo il metodo REGENERATE per rigenerare immediatamente il token di sessione appena effettuato il login
        $request->session()->regenerate();

        // ritireziona ad una pagina gestita da Laravel che viene passata come parametro
        //return redirect()->intended('/relator/customer')->with('success', 'Utente loggato con successo: Benvenuto!');

        $user = auth()->user();

        if (
            $user->role === UserRole::LOGISTIC
        ||  $user->role === UserRole::WAREHOUSE_CHIEF) {
            return redirect()->route('relator.dashboard');
        }


        if ( $user->role === UserRole::WAREHOUSE_MANAGER) {
            return redirect()->route('worker.journeyCargo.index');
        }        

    
        if ($user->role === UserRole::DRIVER) {
            return redirect()->route('driver.journey.index');
        }

        if (
            $user->role === UserRole::PROGRAMMER
        ||  $user->is_admin) {
            return redirect()->route('relator.dashboard');
        }

        return redirect()->route('user-account.index')->with('success', 'Dashboard non ancora costruita per questo ruolo');
    

    }

    public function destroy(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken(); //regenerate the CRF token

        return redirect()->route('login')->with('success', 'Sessione utente conclusa con successo: Arrivederci!');
    }
}
