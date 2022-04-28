<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetPass extends Model
{
    use HasFactory;

    protected $table = "reset_password";
    
    protected $fillable = [
        "random_string", "email"
    ];

    protected $primarykey = "id";

    public $timestamps = true;

    public function getCreatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }

    public function getUpdatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }
}
