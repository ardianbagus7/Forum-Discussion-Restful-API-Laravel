<?php

namespace App\Http\Controllers;

use App\FormVerif;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function index(Request $request)
    {
        $form = DB::table('form_verifs')->join('users', 'form_verifs.user_id', '=', 'users.id')->orderByRaw('form_verifs.created_at DESC')->select('form_verifs.id' ,'form_verifs.verif_image', 'users.id as userId', 'users.name','users.email','users.role','form_verifs.nrp', 'users.image as user_image', 'form_verifs.created_at')->simplePaginate(10);

        $response = [
            'form' => $form
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
                    'nrp' => 'required',
                    'image' => 'required|image'
                ]);

                $user = JWTAuth::toUser($request->bearerToken());

                $user_id = $user->id;
                $nrp = $request->input('nrp');

                $form = new FormVerif([
                    'user_id' => $user_id,
                    'nrp' => $nrp
                ]);

                if ($request->has('image')) {
                    $image = $request->file('image');
                    // Make a image name based on user name and current timestamp
                    $name = Str::slug($request->input('title')) . '_' . time();
                    // Define folder path
                    $folder = '/form/';
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
                    $form->verif_image = url('/') . $filePath;
                } else {
                    $form->verif_image = url('/') . '/form/default.jpg';
                }


                if ($form->save()) {

                    $message = [
                        'msg' => 'form created',
                        'bug' => $form
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
