<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Get the number of days since the book was rented.
     *
     * Calculates the difference in full days between the current date and the rental start date.
     *
     * @return int
     */
    public function getDaysRentedAttribute()
    {
        return Carbon::now()->diffInDays($this->rented_at);
    }

    /**
     * Determine if the rental period has been exceeded.
     *
     * Fetches the rental period policy from the database (default is 30 days if policy is not set).
     * Returns true if the number of rented days exceeds the allowed period and the book has not been returned.
     *
     * @return bool
     */
    public function getIsOverdueAttribute()
    {
        $policy = Policy::where('name', 'rental_period')->first();
        $rentalPeriodDays = $policy ? $policy->period : 30;
        return $this->days_rented > $rentalPeriodDays && $this->returned_at === null;
    }

    /**
     *  Get the allowed rental period for a book.
     *
     *  Retrieves the rental period from the 'Policy' table where the policy name is 'rental_period'.
     *  If no such policy is found in the database, a default rental period of 30 days is returned.
     * @return int
     */
    public function getRentalPeriodAttribute()
    {
        $policy= Policy::where('name', 'rental_period')->first();
        return $policy ? $policy->period : 30;
    }

}
