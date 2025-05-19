<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'description',
        'number_of_pages',
        'number_of_copies_available',
        'isbn',
        'language',
        'script',
        'binding',
        'dimensions',
    ];

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

}
