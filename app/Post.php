<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'description', 'kategori'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
