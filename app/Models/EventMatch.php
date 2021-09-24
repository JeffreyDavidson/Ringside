<?php

namespace App\Models;

use App\Models\Concerns\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventMatch extends Model
{
    use HasFactory,
        Unguarded;

    public function referees()
    {
        return $this->belongsToMany(Referee::class);
    }

    public function titles()
    {
        return $this->belongsToMany(Title::class);
    }

    public function competitors()
    {
        return $this->morphTo();
    }
}
