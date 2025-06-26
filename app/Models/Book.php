<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public const SCRIPTS = ['cyrillic', 'latin', 'arabic'];
    public const BINDINGS = ['hardcover', 'paperback', 'spiral-bound'];
    public const DIMENSIONS = ['A1', 'A2', '21cm x 29.7cm', '15cm x 21cm'];
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

    /**
     * Get all authors associated with this book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    /**
     * Get all images associated with this book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(Image::class);
    }


    /**
     * Get all categories associated with this book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Get all genres associated with this book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * Get the publisher that published this book.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
    }
}
