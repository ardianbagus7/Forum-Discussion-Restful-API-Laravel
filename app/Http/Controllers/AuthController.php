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
        $this->middleware('jwt.auth', ['only' => ['profil', 'detail', 'logout', 'profilUserLain', 'notifall']]);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required',
            'password' => 'required|min:5',
            'angkatan' => 'required',
        ]);

        $email = $request->input('email');
        $name = $request->input('name');
        $password = $request->input('password');
        $angkatan = $request->input('angkatan');
        $role = 0;
        $nomer = $request->input('nomer');
        $nrp = Str::slug($request->input('name')) . '_' . time();


        $user = new User([
            'email' => $email,
            'name' => $name,
            'nrp' => $nrp,
            'password' => bcrypt($password),
            'angkatan' => $angkatan,
            'role' => $role,
            'nomer' => $nomer,
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
            'email' => $email,
            'password' => $password
        ];

        if ($user->save()) {

            $token = null;
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'msg' => 'email or Password are incorrect'
                    ], 404);
                }
            } catch (JWTException $e) {
                return response()->json([
                    'msg' => 'failed_to_create_token'
                ], 404);
            }

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


        return response()->json($response, 404);
    }

    public function signin(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|min:5'
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        if ($user = User::where('email', $email)->first()) {
            $credentials = [
                'email' => $email,
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
                $nomer = $request->input('nomer');

                $image_lama = explode('/', $user->image);
                $image_lama = public_path() . '/' . $image_lama[3] . '/' . $image_lama[4];

                $user->name = $name;
                $user->angkatan = $angkatan;
                $user->nomer = $nomer;

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
                $data = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->WHERE('user_id',  $user->id)->orderByRaw('created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.name', 'users.image as user_image', 'posts.created_at')->limit(5)->get();
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


    public function profilUserLain(Request $request, $id)
    {

        $user = User::findOrFail($id);
        $data = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->WHERE('user_id',  $id)->orderByRaw('created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.name', 'users.image as user_image', 'posts.created_at')->limit(5)->get();
        $response = [
            'msg' => 'succes',
            'user' => $user,
            'post' => $data,
        ];

        return response()->json($response, 200);
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

    public function notifall(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {

                $user = JWTAuth::toUser($request->bearerToken());
                $user_id = $user->id;

                $user_notif = DB::table('notifs')->WHERE('user_id', $user_id)->orderByRaw('created_at DESC')->paginate(10);

                $message = [
                    'notif' => $user_notif,
                ];

                return response()->json($message, 200);
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }


    public function notif($id)
    {

        $user_notif = DB::table('notifs')->WHERE('user_id', $id)->orderByRaw('created_at DESC')->first();

        return response()->json($user_notif, 200);
    }
}
