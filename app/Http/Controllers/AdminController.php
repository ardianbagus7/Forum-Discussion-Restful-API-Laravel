<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\File;
use App\Invitation;
use App\User;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth',['except'=>'cekverifikasi']);
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

    public function addFeedback(Request $request)
    {

        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {

                $this->validate($request, [
                    'deskripsi' => 'required'
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $deskripsi = $request->input('deskripsi');

                $komentar_db = new Feedback([
                    'user_id' => $user_id,
                    'deskripsi' => $deskripsi,
                ]);

                if ($komentar_db->save()) {
                    $message = [
                        'msg' => 'Feedback created',
                        'user id' => $user_id,
                        'deskripsi' => $deskripsi,
                    ];
                    return response()->json($message, 201);
                }

                $response = [
                    'msg' => 'Error during creating'
                ];

                return response()->json($response, 404);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function viewFeedback(Request $request)
    {
        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {


                $user = JWTAuth::toUser($request->bearerToken());
                $user_role = $user->role;

                if ($user_role == 5 || $user_role == 6) {
                    $feedback = DB::table('feedback')->join('users', 'users.id', '=', 'feedback.user_id')->orderByRaw('created_at DESC')->select('feedback.id', 'user_id', 'name', 'nrp', 'image', 'role', 'deskripsi', 'feedback.created_at')->paginate(10);

                    $response = [
                        'feedback' => $feedback,
                    ];

                    return response()->json($response, 200);
                } else {
                    return response()->json(['msg' => 'Bukan admin'], 404);
                }
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }
}
