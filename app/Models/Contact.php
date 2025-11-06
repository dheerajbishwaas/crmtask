<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'profile_image_path',
        'document_path',
        'status',
        'merged_into_id',
        'merge_summary',
        'merged_at',
    ];

    protected $casts = [
        'merge_summary' => 'array',
        'merged_at' => 'datetime',
    ];

    public function emails()
    {
        return $this->hasMany(ContactEmail::class);
    }

    public function phones()
    {
        return $this->hasMany(ContactPhone::class);
    }

    public function customFieldValues()
    {
        return $this->hasMany(ContactCustomFieldValue::class);
    }

    public function mergedInto()
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function mergedChildren()
    {
        return $this->hasMany(self::class, 'merged_into_id');
    }

    public function mergesAsMaster()
    {
        return $this->hasMany(ContactMerge::class, 'master_contact_id');
    }

    public function mergesAsSecondary()
    {
        return $this->hasMany(ContactMerge::class, 'secondary_contact_id');
    }
}
