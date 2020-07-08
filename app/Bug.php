<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    protected $fillable = ['user_id', 'deskripsi', 'image'];
}
