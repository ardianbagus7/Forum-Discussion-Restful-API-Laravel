<?php

namespace App\Http\Controllers;

use App\Komentar;
use App\Notif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

class KomentarController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {

                $this->validate($request, [
                    'komentar' => 'required',
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $user_name = $user->name;
                $user_image = $user->image;
                $post_id = $request->input('post_id');
                $story_id = $request->input('story_id');
                $komentar = $request->input('komentar');

                // POST TARGET NOTIF
                if ($post_id != null) {
                    $user_notif = DB::table('posts')->WHERE('id', $post_id)->first();
                    $image_post_notif = $user_notif->image;
                    $user_id_notif = $user_notif->user_id;
                } else if ($story_id != null) {
                    $user_notif = DB::table('stories')->WHERE('id', $story_id)->first();
                    $image_post_notif = $user_notif->image;
                    $user_id_notif = $user_notif->user_id;
                }
                $user_tujuan = DB::table('users')->WHERE('id', $user_id_notif)->first();
                $fcm_notif = $user_tujuan->fcm;


                if ($user_id_notif != $user_id) {

                    $pesan = $user_name . ' mengomentari: ' . $komentar;

                    $notif = new Notif([
                        'imagePost' => $image_post_notif,
                        'image' => $user_image,
                        'user_id' => $user_id_notif,
                        'user_pesan_id' => $user_id,
                        'post_id' => $post_id,
                        'pesan' => $pesan,
                        'read' => 0,
                    ]);

                    $notif->save();

                    // NOTIF PUSH FCM
                    $url = "https://fcm.googleapis.com/fcm/send";
                    $header = [
                        'authorization: key=AAAADBUA_Nc:APA91bG8p3HpAYzG20j-eUKgrt7CTBmwUT6Zl8pRybsW-Q05Qzwkz0feCjRqqTuI4SBq3NAZnKj0KsGSGKV39hu2JcLZY1lGQwaXLYXQ5msjGPJ2HtKFeDwu0RdiZ7hJu5pudSd9GO56',
                        'content-type: application/json'
                    ];

                    $postdata = '{
                        "to" : "' . $fcm_notif . '",
                            "notification" : {
                                "title":"Tune Notifikasi",
                                "text" : "' . $pesan . '"
                                "image": "' . $image_post_notif . '"
                            },
                        
                    }';

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                    $result = curl_exec($ch);
                    curl_close($ch);
                }
                //

                $komentar_db = new Komentar([
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'story_id' => $story_id,
                    'komentar' => $komentar
                ]);

                if ($komentar_db->save()) {
                    $message = [
                        'msg' => 'Komentar created',
                        'user id' => $user_id,
                        'post id' => $post_id,
                        'story_id' => $story_id,
                        'komentar' => $komentar,
                    ];
                    return response()->json($message, 201);
                }

                $response = [
                    'msg' => 'Error during creating',
                ];

                return response()->json($response, 404);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {

            $komentar = Komentar::findOrFail($id);
            $user = JWTAuth::toUser($request->bearerToken());
            $user_id = $user->id;
            $role = $request->input('role');

            if ($user_id != $komentar->user_id && $role != 6) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {


                if (!$komentar->delete()) {
                    return response()->json([
                        'msg' => 'Delete failed'
                    ], 404);
                }

                $response = [
                    'msg' => 'Komentar deleted',
                ];
                return response()->json($response, 200);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }
}
