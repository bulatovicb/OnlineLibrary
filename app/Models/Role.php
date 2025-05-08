<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Role model represents user roles within the system (Students or librarians)
 */
class Role extends Model
{
    use HasFactory;

    const STUDENT = 1;
    const LIBRARIAN = 2;
    protected $fillable = ['name'];

    /**
     * Get all users associated with this role.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
