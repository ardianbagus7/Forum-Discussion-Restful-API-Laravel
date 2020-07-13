<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notif extends Model
{
    protected $fillable = ['user_id', 'post_id', 'pesan' , 'read', 'image','user_pesan_id','imagePost'];
}
