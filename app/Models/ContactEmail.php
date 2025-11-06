<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactEmail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'email',
        'is_primary',
        'origin_contact_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function originContact()
    {
        return $this->belongsTo(Contact::class, 'origin_contact_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
