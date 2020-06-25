<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

class AuthController extends Controller
{
    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'nrp' => 'required',
            'password' => 'required|min:5'
        ]);

        $name = $request->input('name');
        $nrp = $request->input('nrp');
        $password = $request->input('password');

        $user = new User([
            'name' => $name,
            'nrp' => $nrp,
            'password' => bcrypt($password)
        ]);

        $credentials = [
            'nrp' => $nrp,
            'password' => $password
        ];

        if ($user->save()) {

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'msg' => 'nrp or Password are incorrect'
                    ], 404);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'msg' => 'failed_to_create_token'
                ], 404);
            }

            $user->signin = [
                'href' => 'api/v1/user/signin',
                'method' => 'POST',
                'param' => 'nrp,password'
            ];
            $response = [
                'msg' => 'User created',
                'user' => $user,
                'token' => $token
            ];
            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'An error occured'
        ];
    }

    public function signin(Request $request)
    {

        $this->validate($request, [
            'nrp' => 'required',
            'password' => 'required|min:5'
        ]);

        $nrp = $request->input('nrp');
        $password = $request->input('password');

        if ($user = User::where('nrp', $nrp)->first()) {
            $credentials = [
                'nrp' => $nrp,
                'password' => $password
            ];

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'msg' => 'nrp or Password are incorrect'
                    ], 404);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'msg' => 'failed_to_create_token'
                ], 404);
            }

            $response = [
                'msg' => 'User signin',
                'user' => $user,
                'token' => $token
            ];

            return response()->json($response, 201);
        }
        $response = [
            'msg' => 'An error occured'
        ];

        return response()->json($response, 404);
    }
}
