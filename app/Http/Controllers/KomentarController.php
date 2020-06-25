<?php

namespace App\Http\Controllers;

use App\Komentar;
use Illuminate\Http\Request;

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
        $this->validate($request, [
            'user_id' => 'required',
            'post_id' => 'required',
            'komentar' => 'required',
        ]);

        $user_id = $request->input('user_id');
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
            return response()->json($message,201);
        }

        $response = [
            'msg' => 'Error during creating'
        ];

        return response()->json($response,404);
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
