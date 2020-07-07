<?php

namespace App\Http\Controllers;

use App\Feedback;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth');
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
                    $feedback = DB::table('feedback')->join('users', 'users.id', '=', 'feedback.user_id')->orderByRaw('created_at DESC')->select('feedback.id','user_id','name','nrp','image','role','deskripsi','feedback.created_at')->paginate(10);

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
