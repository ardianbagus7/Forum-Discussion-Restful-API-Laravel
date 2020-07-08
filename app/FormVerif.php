<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FormVerif extends Model
{
    protected $fillable = ['user_id', 'nrp', 'verif_image'];
}
