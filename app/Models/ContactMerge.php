<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMerge extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_contact_id',
        'secondary_contact_id',
        'merged_attributes',
        'merged_custom_fields',
        'merged_files',
        'status',
        'merged_at',
    ];

    protected $casts = [
        'merged_attributes' => 'array',
        'merged_custom_fields' => 'array',
        'merged_files' => 'array',
        'merged_at' => 'datetime',
    ];

    public function masterContact()
    {
        return $this->belongsTo(Contact::class, 'master_contact_id');
    }

    public function secondaryContact()
    {
        return $this->belongsTo(Contact::class, 'secondary_contact_id');
    }
}
