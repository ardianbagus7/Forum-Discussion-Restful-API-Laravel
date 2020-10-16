<?php

namespace App\Http\Controllers;

use App\FormVerif;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;

use App\Notif;
use App\User;
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
        $form = DB::table('form_verifs')->join('users', 'form_verifs.user_id', '=', 'users.id')->orderByRaw('form_verifs.created_at DESC')->select('form_verifs.id', 'form_verifs.verif_image', 'users.id as userId', 'users.name', 'users.email', 'users.role', 'form_verifs.nrp', 'users.image as user_image', 'form_verifs.created_at')->simplePaginate(10);

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
                $user_name = $user->name;
                $user_image = $user->image;
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

                     //notif admin kalau ada form baru
                    $admin = User::where('role', 5 )->orWhere('role',6)->get('id as userId');
                    foreach ($admin as $id_admin) {
                        $pesan = $user_name . ' mengirim form request invitation key ';
                        $id_admin = $id_admin->userId;
                        $notif = new Notif([
                            'imagePost' => $form->verif_image,
                            'image' => $user_image,
                            'user_id' => $id_admin,
                            'user_pesan_id' => $user_id,
                            'post_id' => $form->id,
                            'pesan' => $pesan,
                            'read' => 0,
                        ]);

                        $notif->save();
                    }
                    
                    // NOTIF PUSH FCM
                    $admin_fcm = User::where('role', 5 )->orWhere('role',6)->get('fcm');
                    foreach ($admin_fcm as $id_admin) {
                        $pesan = $user_name . ' mengirim form request invitation key ';
                
                        $fcm = $id_admin->fcm;
 
                        $url = "https://fcm.googleapis.com/fcm/send";            
                        $header = [
                        'authorization: key=AAAADBUA_Nc:APA91bG8p3HpAYzG20j-eUKgrt7CTBmwUT6Zl8pRybsW-Q05Qzwkz0feCjRqqTuI4SBq3NAZnKj0KsGSGKV39hu2JcLZY1lGQwaXLYXQ5msjGPJ2HtKFeDwu0RdiZ7hJu5pudSd9GO56',
                            'content-type: application/json'
                        ];    
                
                        $postdata = '{
                            "to" : "' . $fcm . '",
                                "notification" : {
                                    "title":"Tune Notifikasi",
                                    "text" : "' . $pesan . '"
                                    "image": "' . $form->verif_image . '"
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
                    return response()->json($message, 201);
                }

                $response = [
                    'msg' => 'Error during creating'
                ];

                return response()->json($admin, 404);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Something went wrong'], 404);
        }
    }
}
