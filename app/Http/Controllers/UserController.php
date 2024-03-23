<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function register(Request $request){
        $validation = Validator::make($request->all(),[
            'name' =>'required|string',
            "email"=>'required|string|unique:users',
            'password'=>'required|string|confirmed'
        ]);
        if($validation->fails()){
            return response()->json($validation->errors()->all(),400);
        }
        $validated = $validation->validated();
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_id' => Uuid::uuid4(),
        ]);
        return response()->json(['user'=>$user],201);
    }
    public function login(Request $request){
        $validation = Validator::make($request->all(),[
            "email"=>'required|string',
            'password'=>'required|string'
        ]);
        if($validation->fails()){
            return response()->json($validation->errors()->all(),400);
        }
        $validated = $validation->validated();
        $user = User::where('email',$validated['email'])->first();
        if(!$user){
            return response()->json(['error'=>"Email Not registered"],400);
        }
        if(!Hash::check($validated['password'],$user->password)){
            return response()->json(['error'=>'Invalid Credentials'],400);
        }
        $token = $user->createToken('myapptoken')->plainTextToken;
        return response()->json(['user'=>$user,'token'=>$token],200)->withCookie(cookie()->forever('at',$token));

    }
    public function logout(Request $request){

        $request->user()->currentAccessToken()->delete();
        $response =  [
            'message' => 'logged out'
        ];
        return response($response,200);

    }
    public function index(Request $request){
        return response()->json(User::all(),200);
    }
    
    public function getUserData(Request $request){
        if(!$request->hasCookie("at")){
            return response()->json([
                'message' => "Unauthenticated1"
            ],401);
        }
        if($token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->cookie("at"))){
            $user = $token->tokenable;
        }
        else{
            return response()->json([
                'message' => "unauthenticated2"
            ]);
        }
        if(is_null($user)){
            return response()->json([
                'message' => "Unauthenticated3"
            ],401);
        }
        return response() -> json([
            'user' => $user,
            'access_token' => $request -> cookie('at')
        ]);
    }
}
