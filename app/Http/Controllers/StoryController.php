<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;
use App\Story;
use App\User;

use Intervention\Image\ImageManagerStatic as Image;

class StoryController extends Controller
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
        $stories = DB::table('stories')->join('users', 'stories.user_id', '=', 'users.id')->orderByRaw('stories.created_at DESC')->select('stories.id', 'title', 'description', 'kategori', 'stories.image as stories_image', 'users.id as userId', 'users.name', 'users.role as userRole', 'users.image as user_image', 'stories.created_at')->paginate(10);

        $response = [
            'stories' => $stories
        ];

        return response()->json($response, 200);
    }

    public function komentar($id)
    {
        $komentar = DB::table('stories')->join('komentars', 'stories.id', '=', 'komentars.story_id')->join('users', 'komentars.user_id', '=', 'users.id')->where('story_id', $id)->orderByRaw('komentars.created_at ASC')->select('komentars.id', 'komentars.user_id', 'users.image', 'name', 'nrp', 'users.image', 'users.role', 'komentar', 'komentars.created_at')->paginate(10);

        $response = [
            'msg' => 'Komentar information',
            'komentar' => $komentar,

        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

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

                if ($user->role != 6 && $user->role != 5) {
                    return response()->json(['message' => 'bukan admin'], 404);
                } else {

                    $story = new Story([
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
                        $folder = '/stories/';
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
                        $story->image = url('/') . $filePath;
                    }

                    if ($story->save()) {

                        $message = [
                            'msg' => 'Story created',
                            'story' => $story
                        ];

                        // NOTIF PUSH FCM
                        $admin_fcm = User::get('fcm');
                        foreach ($admin_fcm as $id_admin) {
                            $pesan = $title;

                            $fcm = $id_admin->fcm;

                            if ($fcm != null) {
                                $url = "https://fcm.googleapis.com/fcm/send";
                                $header = [
                                    'authorization: key=AAAADBUA_Nc:APA91bG8p3HpAYzG20j-eUKgrt7CTBmwUT6Zl8pRybsW-Q05Qzwkz0feCjRqqTuI4SBq3NAZnKj0KsGSGKV39hu2JcLZY1lGQwaXLYXQ5msjGPJ2HtKFeDwu0RdiZ7hJu5pudSd9GO56',
                                    'content-type: application/json'
                                ];

                                $postdata = '{
                                "to" : "' . $fcm . '",
                                    "notification" : {
                                        "title":"Informasi Baru Hima Telkom",
                                        "text" : "' . $pesan . '"
                                        "image": "' . $story->image . '"
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
                        }
                        return response()->json($message, 201);
                    }

                    $response = [
                        'msg' => 'Error during creating'
                    ];

                    return response()->json($response, 404);
                }
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

        $post = Story::findOrFail($id);
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
                    $folder = '/stories/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath =  $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image

                    $name = !is_null($name) ? $name : Str::random(25);

                    $img = Image::make($image->getRealPath());

                    $img->resize(500, 500, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save(public_path($filePath));

                    if ($image_lama != public_path() . '/stories/default.jpg') {
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
        $post = Story::findOrFail($id);
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

                if ($image_lama != public_path() . '/stories/default.jpg') {
                    File::delete($image_lama);
                }

                return response()->json($response, 200);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }
}
