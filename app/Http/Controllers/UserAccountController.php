<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageServiceProvider;
use Intervention\Image\Laravel\Facades\Image;






class UserAccountController extends Controller
{
    public function create(){
        return inertia('UserAccount/Create');
    }

    public function store(Request $request)
    {
        $user = User::create($request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:5|confirmed'
        ]));
        //$user->password = Hash::make($user->password); // DON'T ADD THIS LINE!!!
        //$user->save(); // DON'T ADD THIS LINE!!!
        
        Auth::login($user); //user logged in right nuow, just after created
        event(new Registered($user));
    
        return redirect()->route('listing.index')
            ->with('success', 'Account created!');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        return inertia(
            'UserAccount/Index', 
            [
                'user' => $user,
                'warehouses' => Warehouse::all(),
            ]
        );
    }

        /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, User $user)
    {
        //Gate::authorize('update', $user);

        $user = $request->user();

        return inertia(
            'UserAccount/Edit',
            [
                'user' => $user,
                'warehouses' => Warehouse::all(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //dd(request()->all());
        $validated = $request->validate([
            'name' => 'required',
            'surname' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $avatar = $request->validate([
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $user->update($validated);
        if($request->hasFile('avatar')){
            $avatar = $request->input('avatar');
            //$avatar = preg_replace('/data:image\/\w+;base64,/', '', $avatar );
            //$avatar = preg_replace('/ /', '+', $avatar );
            //$file = base64_decode( $avatar ); 
            //$file = $request->file('avatar');
            $filename = "avatar-".$user->id. "-" . uniqid() . '.' . 'jpg'; //$file->getClientOriginalExtension();
            $location = public_path('storage/avatars/'. $filename);
            Image::read($avatar)->resize(256, 256)->save($location);
            $oldAvatar = $user->avatar;
            $user->avatar = $filename;
            $user->save();
            if($oldAvatar != "/fallback-avatar.png" && $oldAvatar != null){
                Storage::delete(str_replace("/storage/", "public/", $oldAvatar) );
            }
        }

        return redirect()->route('user-account.index')->with('success', 'Utente modificato con successo!');
    }




    public function storeAvatar(Request $request){
        $request->validate([
            'avatar' => 'required'
        ]);

        $user = auth()->user();

        //$manager = new ImageManager(new Driver());
        //$image = $manager->read($request->file("avatar"));
        //$imageData = $image->cover(256, 256)->toJpeg();

        //$img = Image::make( $file )->resize(256, 256);
        //Storage::put("public/avatars/pippo_A_res.jpg", $img->response('jpg') );

        // decode the base64 file 
        $avatar = $request->input('avatar');
        $avatar = preg_replace('/data:image\/\w+;base64,/', '', $avatar );
        $avatar = preg_replace('/ /', '+', $avatar );
        $file = base64_decode( $avatar ); 
        //$file = $request->file('avatar');
        $filename = "avatar-".$user->id. "-" . uniqid() . '.' . 'jpg'; //$file->getClientOriginalExtension();
        $location = public_path('storage/avatars/'. $filename);
        Image::make($file)->resize(256,256)->save($location);

        $oldAvatar = $user->avatar;

        $user->avatar = $filename;
        $user->save();
        if($oldAvatar != "/fallback-avatar.png"){
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar) );
        }
       
        return redirect('/profile/'.$user->id);

        /*
        $savedFile = Storage::put("public/avatars", $request->file("avatar") );
        $img = Image::make( str_replace("public","storage", $savedFile) )->resize(256, 256);
        $img->response('jpg');
        $type = 'jpg';
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($img);
        //return "<img src=$base64>";
        */
      
    }



}
