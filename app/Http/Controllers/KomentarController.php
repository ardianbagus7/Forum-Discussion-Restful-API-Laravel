<?php

namespace App\Http\Controllers;

use App\Komentar;
use Illuminate\Http\Request;

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
                    'post_id' => 'required',
                    'komentar' => 'required',
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $post_id = $request->input('post_id');
                $komentar = $request->input('komentar');

                $komentar_db = new Komentar([
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'komentar' => $komentar
                ]);

                if ($komentar_db->save()) {
                    $message = [
                        'msg' => 'Komentar created',
                        'user id' => $user_id,
                        'post id' => $post_id,
                        'komentar' => $komentar,
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
