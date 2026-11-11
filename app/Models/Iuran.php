<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Iuran extends Model
{
    use HasUuids;
    protected $table = 'iuran';

    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'due_date',
    ];

    public function proofFiles()
    {
        return $this->morphOne(UploadedFile::class, 'fileable');
    }
}
