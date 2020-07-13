<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Komentar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

use Intervention\Image\ImageManagerStatic as Image;

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
        $posts = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->orderByRaw('posts.created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.id as userId', 'users.name', 'users.image as user_image', 'posts.created_at')->paginate(10);

        $response = [
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
        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $this->validate($request, [
                    'title' => 'required',
                    'description' => 'required',
                    'kategori' => 'required',
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $title = $request->input('title');
                $description = $request->input('description');
                $kategori = $request->input('kategori');

                $post = new Post([
                    'kategori' => $kategori,
                    'title' => $title,
                    'description' => $description,
                    'user_id' => $user_id
                ]);

                if ($request->has('image')) {
                    $image = $request->file('image');
                    // Make a image name based on user name and current timestamp
                    $name = Str::slug($request->input('title')) . '_' . time();
                    // Define folder path
                    $folder = '/post/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image

                    $name = !is_null($name) ? $name : Str::random(25);

                    $img = Image::make($image->getRealPath());

                    $img->resize(500, 500, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save(public_path($filePath));

                    // $file = $image->storeAs($folder, $name . '.' . $image->getClientOriginalExtension(), 'public');

                    // Set user profile image path in database to filePath
                    $post->image = url('/') . $filePath;
                } else {
                    $post->image = url('/') . '/post/default.jpg';
                }


                if ($post->save()) {

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

        $post = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->where('posts.id', $id)->orderByRaw('posts.created_at DESC')->select('posts.id', 'title', 'kategori', 'description', 'posts.image as post_image', 'users.id as userId', 'users.name', 'users.image as user_image', 'posts.created_at')->get();
        $komentar = DB::table('posts')->join('komentars', 'posts.id', '=', 'komentars.post_id')->join('users', 'komentars.user_id', '=', 'users.id')->where('post_id', $id)->orderByRaw('komentars.created_at ASC')->select('komentars.id', 'komentars.user_id', 'users.image', 'name', 'nrp', 'users.image', 'komentar', 'komentars.created_at')->get();

        $response = [
            'msg' => 'Post information',
            'post' => $post,
            'komentar' => $komentar,

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

        $post = Post::findOrFail($id);
        $user = JWTAuth::toUser($request->bearerToken());
        $user_id = $user->id;
        $image_lama = explode('/', $post->image);
        $image_lama = public_path() . '/' . $image_lama[3] . '/' . $image_lama[4];
        try {
            if ($user_id != $post->user_id && $user->role != 6 && $user->role != 5) {
                return response()->json(['message' => 'bukan creator post'], 404);
            } else {

                $this->validate($request, [
                    'title' => 'required',
                    'description' => 'required',
                    'kategori' => 'required',
                ]);

                if ($request->has('image')) {
                    $image = $request->file('image');
                    // Make a image name based on user name and current timestamp
                    $name = Str::slug($request->input('title')) . '_' . time();
                    // Define folder path
                    $folder = '/post/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath =  $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image

                    $name = !is_null($name) ? $name : Str::random(25);

                    $img = Image::make($image->getRealPath());

                    $img->resize(500, 500, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save(public_path($filePath));

                    if ($image_lama != public_path() . '/post/default.jpg') {
                        File::delete($image_lama);
                    }

                    // $file = $img->save($folder, $name . '.' . $image->getClientOriginalExtension(), 'public');

                    // Set user profile image path in database to filePath
                    $post->image = url('/') . $filePath;
                } else {
                }


                $title = $request->input('title');
                $description = $request->input('description');
                $kategori = $request->input('kategori');

                $post->kategori = $kategori;
                $post->title = $title;
                $post->description = $description;

                if (!$post->save()) {
                    return response()->json([
                        'msg' => 'Error during update'
                    ], 404);
                }

                $response = [
                    'msg' => 'Post Updated',
                    'method' => $post
                ];

                return response()->json($response, 200);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //* POST
        $post = Post::findOrFail($id);
        //* USER
        $user = JWTAuth::toUser($request->bearerToken());
        $user_id = $user->id;


        //* IMAGE
        $image_lama = explode('/', $post->image);
        $image_lama = public_path() . '/' . $image_lama[3] . '/' . $image_lama[4];


        try {

            if ($user_id != $post->user_id && $user->role != 6 && $user->role != 5) {
                return response()->json(['message' => 'bukan creator post'], 404);
            } else {
                if (!$post->delete()) {
                    return response()->json([
                        'msg' => 'Delete failed'
                    ], 404);
                }
                // DB::table('komentars')->join('posts', 'komentars.post_id', '=', 'posts.id')->where('post_id', $id)->delete();
                $response = [
                    'msg' => 'Post deleted',
                ];

                if ($image_lama != public_path() . '/post/default.jpg') {
                    File::delete($image_lama);
                }

                return response()->json($response, 200);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
        ]);

        $title = $request->input('title');

        $data = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->WHERE('title', 'like', '%' . $title . '%')->orderByRaw('created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.name', 'users.image as user_image', 'posts.created_at')->get();

        $response = [
            'msg' => 'search succes',
            'posts' => $data
        ];

        return response()->json($response, 200);
    }


    public function filter(Request $request)
    {
        $this->validate($request, [
            'kategori' => 'required',
        ]);

        $kategori = $request->input('kategori');

        $data = DB::table('posts')->join('users', 'posts.user_id', '=', 'users.id')->WHERE('kategori', 'like', '%' . $kategori . '%')->orderByRaw('posts.created_at DESC')->select('posts.id', 'title', 'kategori', 'posts.image as post_image', 'users.id as userId', 'users.name', 'users.image as user_image', 'posts.created_at')->paginate(10);

        $response = [
            'posts' => $data
        ];

        return response()->json($response, 200);
    }
}
