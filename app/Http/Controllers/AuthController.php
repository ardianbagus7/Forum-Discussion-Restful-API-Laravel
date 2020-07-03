<?php

namespace App\Http\Controllers;

use App\Invitation;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Str;
use App\UploadTrait;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;


use Intervention\Image\ImageManagerStatic as Image;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => ['profil', 'detail', 'logout']]);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'nrp' => 'required',
            'password' => 'required|min:5',
            'key' => 'required',
            'angkatan' => 'required'
        ]);

        $name = $request->input('name');
        $nrp = $request->input('nrp');
        $password = $request->input('password');
        $key = $request->input('key');
        $angkatan = $request->input('angkatan');
        $role = $request->input('role');

        if (!$request->has('role')) {
            $role = 0;
        }
        if ($key = Invitation::where('invitation', $key)->first()) {

            $user = new User([
                'name' => $name,
                'nrp' => $nrp,
                'password' => bcrypt($password),
                'angkatan' => $angkatan,
                'role' => $role
            ]);

            if ($request->has('image')) {
                $image = $request->file('image');
                // Make a image name based on user name and current timestamp
                $name = Str::slug($request->input('name')) . '_' . time();
                // Define folder path
                $folder = '/profile/';
                // Make a file path where image will be stored [ folder path + file name + file extension]
                $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                // Upload image

                $name = !is_null($name) ? $name : Str::random(25);

                $img = Image::make($image->getRealPath());

                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                })->save(public_path($filePath));

                // $file = $image->storeAs($folder, $name . '.' . $image->getClientOriginalExtension(), 'public');

                // Set user profile image path in database to filePath
                $user->image = url('/') . $filePath;
            } else {
                $user->image = url('/') . '/profile/default.jpg';
            }

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
                $key->delete();
                return response()->json($response, 201);
            }

            $response = [
                'msg' => 'An error occured'
            ];
        }
        $response = [
            'msg' => 'Key salah'
        ];

        return response()->json($response, 404);
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

            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'An error occured'
        ];

        return response()->json($response, 404);
    }

    public function key(Request $request)
    {
        $key = Str::random(6);

        $invitation = new Invitation([
            'invitation' => $key,

        ]);
        if ($invitation->save()) {
            $response = [
                'msg' => 'sukses'
            ];
            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'gagal'
        ];
        return response()->json($response, 404);
    }

    public function profil(Request $request)
    {
        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {

                $request->validate([
                    'name'              =>  'required',
                    'angkatan' => 'required',
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $name = $request->input('name');
                $angkatan = $request->input('angkatan');

                $image_lama = explode('/', $user->image);
                $image_lama = public_path() . '/' . $image_lama[3] . '/' . $image_lama[4];

                $user->name = $name;
                $user->angkatan = $angkatan;

                if ($request->has('image')) {


                    $image = $request->file('image');
                    // Make a image name based on user name and current timestamp
                    $name = Str::slug($request->input('name')) . '_' . time();
                    // Define folder path
                    $folder = '/profile/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image

                    $name = !is_null($name) ? $name : Str::random(25);

                    $img = Image::make($image->getRealPath());

                    $img->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save(public_path($filePath));

                    // $file = $image->storeAs($folder, $name . '.' . $image->getClientOriginalExtension(), 'public');
                    // delete image lama
                    if ($image_lama != public_path() . '/profile/default.jpg') {
                        File::delete($image_lama);
                    }
                    // Set user profile image path in database to filePath
                    $user->image = url('/') . $filePath;
                }

                if (!$user->save()) {
                    return response()->json([
                        'msg' => 'Error during update'
                    ], 404);
                }

                $response = [
                    'msg' => 'User Updated',
                    'method' => $user
                ];

                return response()->json($response, 200);
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function detail(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $user = JWTAuth::toUser($request->bearerToken());
                $data = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->WHERE('user_id',  $user->id)->orderByRaw('created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.name', 'users.image as user_image', 'posts.created_at')->get();
                $response = [
                    'msg' => 'succes',
                    'user' => $user,
                    'post' => $data,
                ];

                return response()->json($response, 200);
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function logout(Request $request)
    {

        $token = $request->bearerToken();

        try {
            JWTAuth::parseToken()->invalidate($token);

            return response()->json([
                'error'   => false,
                'message' => trans('auth.logged_out')
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'error'   => true,
                'message' => trans('auth.token.missing')
            ], 500);
        }
    }
}
