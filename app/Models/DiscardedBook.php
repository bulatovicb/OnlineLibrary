<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscardedBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'librarian_id',
        'discarded_at',
    ];

    protected $casts = [
        'discarded_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }
}
