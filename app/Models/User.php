<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User model represents a system user (either a student or a librarian)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'role',
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
}
