<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'cpf_cnpj',
        'email',
        'password',
        'balance',
        'type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}

