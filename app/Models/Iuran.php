<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'amount',
    'status',
    'proof_files',
])]
class Iuran extends Model
{
    protected $table = 'iuran';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'due_date',
    ];

    public function proofFiles()
    {
        return $this->morphOne(UploadedFile::class, 'fileable_id');
    }
}
