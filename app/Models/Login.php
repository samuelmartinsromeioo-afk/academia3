<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class login extends Model
{
    protected $table = 'logins';
    protected $fillable = [
        'email',
        'senha',
    ];
    use HasFactory;
}
