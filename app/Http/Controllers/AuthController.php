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
        $this->middleware('jwt.auth', ['only' => ['profil', 'detail', 'key', 'allKey', 'logout', 'verifikasi', 'allUser', 'destroy', 'allAdmin', 'addAdmin']]);
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required',
            'password' => 'required|min:5',
            'angkatan' => 'required'
        ]);

        $email = $request->input('email');
        $name = $request->input('name');
        $password = $request->input('password');
        $angkatan = $request->input('angkatan');
        $role = 0;
        $nrp = Str::slug($request->input('name')) . '_' . time();


        $user = new User([
            'email' => $email,
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

    public function key(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $user = JWTAuth::toUser($request->bearerToken());
                if ($user->role == 5 || $user->role == 6) {

                    $key = rand(100000, 999999);

                    $invitation = new Invitation([
                        'invitation' => $key,

                    ]);
                    if ($invitation->save()) {
                        $response = [
                            'msg' => 'sukses',
                            'key' => $key
                        ];
                        return response()->json($response, 200);
                    }
                    $response = [
                        'msg' => 'gagal'
                    ];
                    return response()->json($response, 404);
                } else {
                    return response()->json(['message' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }


    public function AllKey(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $user = JWTAuth::toUser($request->bearerToken());
                if ($user->role == 5 || $user->role == 6) {

                    $key = Invitation::orderBy('created_at', 'desc')->get('invitation as key');

                    $response = [
                        'msg' => 'list semua key',
                        'key' => $key
                    ];

                    return response()->json($response, 200);
                } else {
                    return response()->json(['message' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
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

    public function verifikasi(Request $request)
    {

        $this->validate($request, [
            'key' => 'required',
            'nrp' => 'required',
            'role' => 'required'
        ]);

        $nrp = $request->input('nrp');
        $key = $request->input('key');
        $role = $request->input('role');

        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                if ($key = Invitation::where('invitation', $key)->first()) {

                    $user = JWTAuth::toUser($request->bearerToken());

                    $user->nrp = $nrp;
                    $user->role = $role;

                    if (!$user->save()) {
                        return response()->json([
                            'msg' => 'Error during update'
                        ], 404);
                    }

                    $key->delete();

                    $response = [
                        'msg' => 'User Updated',
                        'method' => $user
                    ];

                    return response()->json($response, 200);
                } else {
                    $response = [
                        'msg' => 'Invitation key salah',
                    ];

                    return response()->json($response, 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function cekverifikasi(Request $request)
    {

        $this->validate($request, [
            'key' => 'required',
        ]);

        $key = $request->input('key');

        if ($key = Invitation::where('invitation', $key)->first()) {

            $response = [
                'msg' => 'Key valid',
                'key' => $key
            ];

            return response()->json($response, 200);
        } else {
            $response = [
                'msg' => 'Key tidak valid',
            ];

            return response()->json($response, 204);
        }
    }

    public function allUser(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $user = JWTAuth::toUser($request->bearerToken());
                if ($user->role == 5 || $user->role == 6) {
                    $list_user = User::orderBy('created_at', 'desc')->simplePaginate(5);

                    $response = [
                        'user' => $list_user
                    ];

                    return response()->json($response, 200);
                } else {
                    return response()->json(['message' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }


    public function allAdmin(Request $request)
    {
        try {
            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $user = JWTAuth::toUser($request->bearerToken());
                if ($user->role == 5 || $user->role == 6) {
                    $list_admin = User::Where('role', 5)->select('id', 'name', 'angkatan', 'nrp', 'role')->get();
                    $list_developer = User::Where('role', 6)->select('id', 'name', 'angkatan', 'nrp', 'role')->get();
                    $response = [
                        'developer' => $list_developer,
                        'admin' => $list_admin
                    ];

                    return response()->json($response, 200);
                } else {
                    return response()->json(['message' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }


    public function addAdmin(Request $request)
    {
        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {

                $request->validate([
                    'id'              =>  'required',
                    'role' => 'required'
                ]);

                $id = $request->input('id');
                $role = $request->input('role');

                $user = JWTAuth::toUser($request->bearerToken());

                if ($user->role == 5 || $user->role == 6) {

                    $user_ganti = User::findOrFail($id);
                    if ($user_ganti->role != 6) {

                        $user_ganti->role = $role;

                        if (!$user_ganti->save()) {
                            return response()->json([
                                'msg' => 'Error during update'
                            ], 404);
                        }

                        $response = [
                            'msg' => 'User Updated',
                            'method' => $user_ganti
                        ];

                        return response()->json($response, 200);
                    } else {
                        return response()->json(['msg' => 'tidak bisa menghapus akun developer', 'role' => $user_ganti->role], 404);
                    }
                } else {
                    return response()->json(['message' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {

            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        //* POST
        $user_id = User::findOrFail($id);
        //* USER
        $user = JWTAuth::toUser($request->bearerToken());
        $user_role = $user->role;


        //* IMAGE
        $image_lama = explode('/', $user_id->image);
        $image_lama = public_path() . '/' . $image_lama[3] . '/' . $image_lama[4];


        try {
            if ($user_role != 5 && $user_role != 6) {
                return response()->json(['message' => 'bukan admin', 'role' => $user_role], 404);
            } else {
                if ($user_id->role != 6) {
                    if (!$user_id->delete()) {
                        return response()->json([
                            'msg' => 'Delete failed'
                        ], 404);
                    }
                    // DB::table('komentars')->join('posts', 'komentars.post_id', '=', 'posts.id')->where('post_id', $id)->delete();
                    $response = [
                        'msg' => 'User deleted',
                        'user' => $user_id,
                    ];

                    if ($image_lama != public_path() . '/post/default.jpg') {
                        File::delete($image_lama);
                    }

                    return response()->json($response, 200);
                } else {
                    return response()->json(['msg' => 'tidak bisa menghapus akun developer', 'role' => $user_id->role], 404);
                }
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }
}
