<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Registered;

class RelatorUserController extends Controller
{

    public function index(Request $request){
        $filters = [
            'deleted' => $request->boolean('deleted'),
            ...$request->only(['by', 'order']) // ... is like "merge array"
        ];

        return inertia(
            'Relator/User/Index',
            [
                'filters' => $filters,
                'users' => User::query()
                    //->users()
                    //->mostRecent() // managed by default 'by'
                    //->withCount('images')
                    //->withCount('offers')
                    //->filter($filters)
                    ->paginate(25)
                    ->withQueryString()
            ]);
    }

    public function show(User $user){
        return inertia(
            'Relator/User/Show',
            [
                'user' => $user,
                'warehouses' => Warehouse::all(),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', User::class);

        return inertia('Relator/User/Create', [
            'roles' => UserRole::toArray(),
            'warehouses' => Warehouse::all(),
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'name' => 'required',
            'surname' => 'required',
            //'email' => 'required|email|unique:users',
            'email' => [
                Rule::requiredIf(fn() => $request->input('role') !== UserRole::WAREHOUSE_WORKER->value),
                'email',
                'unique:users,email',
            ],
            //'password' => 'required|min:5|confirmed',
            'password' => [
                Rule::requiredIf(fn() => $request->input('role') !== UserRole::WAREHOUSE_WORKER->value),
                'min:5',
                'confirmed',
            ],
            //'can_login' => 'required|boolean',
            'is_admin' => 'nullable|boolean',
            'user_code' => 'nullable|min:3|max:3',
            'role' => ['required', Rule::in(UserRole::values())],
            'warehouse_ids' => 'nullable|array',  // Not required globally.
            'is_ragnista' => 'nullable|boolean',
        ]);

        /**
         * Security checks to avoid misconfigurations
        */
        // List roles that require warehouse assignment.
        $warehouseRoles = [
            UserRole::WAREHOUSE_CHIEF->value,
            UserRole::WAREHOUSE_MANAGER->value,
            UserRole::WAREHOUSE_WORKER->value
        ];

        // Clear warehouse_ids if not a warehouse role.
        if (!in_array($validated['role'], $warehouseRoles) ){
            $validated['warehouse_ids'] = null;
        }
        // Clear is_ragnista if not a warehouse role for 'ragno'.
        if (isset($validated['is_ragnista']) && $validated['is_ragnista'] === true && !( $validated['role'] == UserRole::WAREHOUSE_WORKER->value || $validated['role'] == UserRole::WAREHOUSE_MANAGER->value ) ) {
            $validated['is_ragnista'] = false;
        }

        // If the user is a warehouse worker, set can_login to false.
        if ($validated['role'] == UserRole::WAREHOUSE_WORKER->value) {
            $validated['can_login'] = false;
        } 

        $user = User::create(
            $validated
        );

        // If the user's role is one of the warehouse roles, enforce at least one warehouse id.
        if (in_array($validated['role'], $warehouseRoles) && (empty($validated['warehouse_ids']) || count($validated['warehouse_ids']) < 1)) {
            return redirect()->back()
                ->withErrors(['warehouse_ids' => 'Per questo ruolo, devi assegnare almeno un magazzino.'])
                ->withInput();
        }
        // Update the warehouse association if the role is one that requires warehouse access.
        if (in_array($validated['role'], $warehouseRoles)) {
            $user->warehouses()->sync($validated['warehouse_ids']);
        } else {
            // Optionally, if the role no longer requires warehouse access, you might remove any previous associations.
            $user->warehouses()->detach();
        }

        // invia la mail all'utente
        //event(new Registered($user));

        // Modifica per gestire i WORKER come utenti
        if ($user->can_login) {
            event(new Registered($user));
        }

        return redirect()->route('relator.user.index')->with('success', 'Utente ' . $request['name'] . ' creato con successo!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return inertia(
            'Relator/User/Edit',
            [
                'user' => $user,
                'roles' => UserRole::toArray(),
                'warehouses' => Warehouse::all(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {        
        $validated = $request->validate([
            'name' => 'required',
            'surname' => 'required',
            'role' => ['required', Rule::in(UserRole::values())],
            'warehouse_ids' => 'nullable|array',  // Not required globally.
            'user_code' => 'nullable|min:3|max:3',
            'is_admin' => 'nullable|boolean',
            //'email' => 'required|email|unique:users,email,' . $user->id,
            'email' => [
                Rule::requiredIf(fn() => $request->input('role') !== UserRole::WAREHOUSE_WORKER->value),
                'email',
                Rule::unique('users', 'email')->ignore($user->id), // ->whereNull('deleted_at'), // ->whereNull('deleted_at') garantisce il funzionamento anche con soft_deletes
            ],
            'is_ragnista' => 'nullable|boolean',
        ]);


        /**
         * Security checks to avoid misconfigurations
         */

        // List roles that require warehouse assignment.
        $warehouseRoles = [
            UserRole::WAREHOUSE_CHIEF->value,
            UserRole::WAREHOUSE_MANAGER->value,
            UserRole::WAREHOUSE_WORKER->value
        ];
        // Clear warehouse_ids if not a warehouse role.
        if (!in_array($validated['role'], $warehouseRoles) ){
            $validated['warehouse_ids'] = null;
        }
        // Clear is_ragnista if not a warehouse role for 'ragno'.
        if (isset($validated['is_ragnista']) && $validated['is_ragnista'] === true && !( $validated['role'] == UserRole::WAREHOUSE_WORKER->value || $validated['role'] == UserRole::WAREHOUSE_MANAGER->value ) ) {
            $validated['is_ragnista'] = false;
        }


        // If the user's role is one of the warehouse roles, enforce at least one warehouse id.
        if (in_array($validated['role'], $warehouseRoles) && (empty($validated['warehouse_ids']) || count($validated['warehouse_ids']) < 1)) {
            return redirect()->back()
                ->withErrors(['warehouse_ids' => 'Per questo ruolo, devi assegnare almeno un magazzino.'])
                ->withInput();
        }
        // Update the warehouse association if the role is one that requires warehouse access.
        if (in_array($validated['role'], $warehouseRoles)) {
            $user->warehouses()->sync($validated['warehouse_ids']);
        } else {
            // Optionally, if the role no longer requires warehouse access, you might remove any previous associations.
            $user->warehouses()->detach();
        }

        // If the user is a warehouse worker, set can_login to false.
        if ($validated['role'] == UserRole::WAREHOUSE_WORKER->value) {
            $validated['can_login'] = false;
        }
        else {
            // If the user is not a warehouse worker, set can_login to true.
            $validated['can_login'] = true;
        }
        
        $user->update($validated);

        return redirect()->route('relator.user.index')->with('success', 'Utente ' . $request['name'] . ' modificato con successo!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);
        $user->deleteOrFail();

        return redirect()->back()->with('success', 'Utente cancellato con successo');
    }

    public function restore(User $user){
        $user->restore();
        return redirect()->back()->with('success', 'Utente ripristinato con successo');
    }

}
