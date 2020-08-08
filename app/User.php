<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\UserWallet;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

     // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = \bcrypt($value);
    }

    public function transactions()
    {
        return $this->hasMany(UserWallet::class);
    }

    public function validTransactions()
    {
        return $this->transactions()->where('status', 1);
    }

    public function credit()
    {
        return $this->validTransactions()
                    ->where('type', 'credit')
                    ->sum('amount');
    }

    public function debit()
    {
        return $this->validTransactions()
                    ->where('type', 'debit')
                    ->sum('amount');
    }

    public function balance()
    {
        return $this->credit() - $this->debit();
    }

    public function allowWithdraw($amount) : bool
    {
        return $this->balance() >= $amount;
    }

    public function confirmPayment($token)
    {
        
        return $this->transactions()
                    ->where('type', 'debit')
                    ->where('status', 0)
                    ->where('token', $token)
                    ->first();
    } 

    public function validateInfoUser($id, $tel)
    {
        return $this->transactions()
                    ->where('identification', $id)
                    ->where('telephone', $tel)
                    ->first();
    }
}
