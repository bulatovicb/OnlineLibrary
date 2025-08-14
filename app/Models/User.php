<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model represents a system user (either a student or a librarian)
 */
class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'jmbg',
        'profile_picture',
        'role_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the role associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Checks if the user has the librarian role.
     *
     * @return bool
     */
    public function isLibrarian(): bool
    {
        return $this->role_id === Role::LIBRARIAN;
    }

    public function getRouteKeyName()
    {
        return 'username';
    }

    /**
     * Sends the password reset notification.
     *
     * @param $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }


    public function rentedBooks()
    {
        return $this->hasMany(Rental::class, 'student_id');
    }

    public function rentedOutBooks()
    {
        return $this->hasMany(Rental::class, 'librarian_id');
    }

    public function discardedBooks()
    {
        return $this->hasMany(DiscardedBook::class, 'librarian_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'student_id');
    }

    public function handledReservations()
    {
        return $this->hasMany(Reservation::class, 'librarian_id');
    }

}
