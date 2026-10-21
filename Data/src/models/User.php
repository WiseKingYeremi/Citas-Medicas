<?php
declare(strict_types=1);    

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model{

    protected $table = 'usuarios';
    
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'rol_id',
        'activo',
    ];

    protected $hidden = [
        'password_hash'
    ];

    protected $casts = [
        'id' => 'integer',
        'rol_id' => 'integer',
        'activo' => 'boolean'
    ];

    function usuario(){
        return $this->belongsTo(User::class, 'usuario_id');
    }
}