<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tasks;

class Status extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "statuses";

    protected $fillable = [ "name" ];

    protected $dates = [ "deleted_at" ];

    protected $primarykey = "id";

    public $timestamps = true;

    public function getCreatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }

    public function getUpdatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }

    public function getDeletedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }
}
