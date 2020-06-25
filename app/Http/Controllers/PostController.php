<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
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
        $posts = Post::all();
        foreach ($posts as $post) {
            $post->view_post = [
                'href' => 'api/v1/post/' . $post->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all posts',
            'posts' => $posts
        ];

        return response()->json($response, 200);
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
            'title' => 'required',
            'description' => 'required',
            'kategori' => 'required',
            'user_id' => 'required',
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $kategori = $request->input('kategori');
        $user_id = $request->input('user_id');

        $post = new Post([
            'kategori' => $kategori,
            'title' => $title,
            'description' => $description
        ]);

        if ($post->save()) {
            $post->view_post = [
                'href' => 'api/v1/post/' . $post->id,
                'method' => 'GET'
            ];
            $message = [
                'msg' => 'Post created',
                'post' => $post
            ];
            return response()->json($message, 201);
        }

        $response = [
            'msg' => 'Error during creating'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $post = Post::with('users')->where('id', $id)->firstOrFail();
        $komentar = DB::table('posts')->join('komentars', 'posts.id', '=', 'komentars.post_id')->join('users', 'komentars.user_id', '=', 'users.id')->where('post_id',$id)->select('user_id', 'name', 'nrp', 'komentar', 'komentars.created_at')->get();

        $post->view_setting = [
            'href' => 'api/v1/post',
            'method' => 'GET'
        ];

        $post->komentar = $komentar;

        $response = [
            'msg' => 'Post information',
            'post' => $post

        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'kategori' => 'required',
            'user_id' => 'required',
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $kategori = $request->input('kategori');
        $user_id = $request->input('user_id');

        $post = Post::with('users')->findOrFail($id);

        if (!$post->users()->where('users.id', $user_id)->first()) {
            return response()->json([
                'msg' => 'user not registered for post, update not successful'
            ], 401);
        }

        $post->kategori = $kategori;
        $post->title = $title;
        $post->description = $description;

        if (!$post->save()) {
            return response()->json([
                'msg' => 'Error during update'
            ], 404);
        }

        $post->view_post = [
            'href' => 'api/v1/post/' . $post->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Post Updated',
            'method' => $post
        ];

        return response()->json($response, 200);
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
        $users = $post->users;
        $post->users()->detach(); //melepaskan relasi user terhadap post kebalikan attach

        if (!$post->delete()) {
            foreach ($users as $user) {
                $post->user()->attach($user);
            }
            return response()->json([
                'msg' => 'Delete failed'
            ], 404);
        }

        $response = [
            'msg' => 'Post deleted',
            'create' => [
                'href' => 'api/v1/post',
                'method' => 'POST',
                'params' => 'title,description,kategori'
            ]
        ];

        return response()->json($response, 200);
    }
}
