<?php

namespace App\Http\Controllers;

use App\Models\Librarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LibrarianController extends Controller
{
    public function store(Request $request){
        $validator=Validator::make($request->all(),[
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|unique:librarians,email',
            'username' => 'required|string|unique:librarians,username',
            'jmbg' => 'required|regex:/^\d{13}$/|unique:librarians,jmbg',
            'password' => 'required|min:8',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(),400);
        }
        $librarian=Librarian::create([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email'),
            'username' => $request->get('username'),
            'jmbg' => $request->get('jmbg'),
            'password' => Hash::make($request->get('password')),

        ]);

        return response()->json(['message' => 'Librarian created successfully.','librarian' => $librarian]);
    }
}
