<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Policy;


class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'student_id',
        'librarian_id',
        'rented_at',
        'returned_at',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    public function getDaysRentedAttribute()
    {
        return Carbon::now()->diffInDays($this->rented_at);
    }

    public function getIsOverdueAttribute()
    {
        $policy= Policy::where('name', 'rental_period')->first();
        $rentalPeriodDays = $policy ? $policy->period : 30;
        return $this->days_rented > $rentalPeriodDays && $this-> returned_at=== null;
    }
}
