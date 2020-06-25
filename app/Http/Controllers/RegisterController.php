<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Post;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'post_id' => 'required',
            'user_id' => 'required',
        ]);

        $post_id = $request->input('post_id');
        $user_id = $request->input('user_id');

        $post = Post::findOrFail($post_id);
        $user = User::findOrFail($user_id);
            
  /*      $message = [
            'msg' => 'User is already registered for post',
            'user' => $user,
            'post' => $post,
            'unregister' => [
                'href' => 'api/v1/post/registration/' . $post->id,
                'method' => 'DELETE'
            ]
        ];

        if ($post->users()->where('users.id', $user->id)->first()) {
            return response()->json($message, 404);
        }
*/
        $user->posts()->attach($post);
        $response = [
            'msg' => 'User registered for post',
            'post' => $post,
            'user' => $user,
            'unregister' => 'api/v1/post/registration/' . $post->id,
            'method' => 'DELETE'
        ];

        return response()->json($response, 201);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->users()->detach();

        $response = [
            'msg' => 'User unregistered for post',
            'post' => $post,
            'user' => 'tbd',
            'register' => [
                'href' => 'api/v1/post/registration/',
                'method' => 'POST',
                'params' => 'user_id, post_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
