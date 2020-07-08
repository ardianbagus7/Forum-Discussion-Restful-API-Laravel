<?php

namespace App\Http\Controllers;

use App\Bug;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;

class BugController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function index(Request $request)
    {
        $posts = DB::table('bugs')->join('users', 'bugs.user_id', '=', 'users.id')->orderByRaw('bugs.created_at DESC')->select('bugs.id', 'deskripsi', 'bugs.image as bug_image', 'users.id as userId', 'users.name', 'users.image as user_image', 'bugs.created_at')->simplePaginate(10);

        $response = [
            'posts' => $posts
        ];

        return response()->json($response, 200);
    }

    public function store(Request $request)
    {
        try {

            if (!$user = JWTAuth::toUser($request->bearerToken())) {
                return response()->json(['message' => 'user_not_found'], 404);
            } else {
                $this->validate($request, [
                    'deskripsi' => 'required',
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $deskripsi = $request->input('deskripsi');

                $bug = new Bug([
                    'deskripsi' => $deskripsi,
                    'user_id' => $user_id
                ]);

                if ($request->has('image')) {
                    $image = $request->file('image');
                    // Make a image name based on user name and current timestamp
                    $name = Str::slug($request->input('title')) . '_' . time();
                    // Define folder path
                    $folder = '/bug/';
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
                    $bug->image = url('/') . $filePath;
                } else {
                    $bug->image = url('/') . '/bug/default.jpg';
                }


                if ($bug->save()) {

                    $message = [
                        'msg' => 'bug created',
                        'bug' => $bug
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
}
