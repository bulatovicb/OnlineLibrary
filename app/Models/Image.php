<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Image extends Model
{
    use HasFactory;

    public const TYPES = ['front_cover', 'back_cover', 'artwork'];

    protected $fillable = [
        'path',
        'type'
    ];

    /**
     * Rules related to Book image upload.
     *
     * @return array
     */
    public static function validationRules(): array
    {
        return [
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'image_types' => 'nullable|array',
            'image_types.*' => ['required', Rule::in(self::TYPES)],
        ];
    }

    /**
     * Gets the book associated with this image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

}
