<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Status;

class Tasks extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        "files" => "array"
    ];

    protected $table = "tasks";

    protected $fillable = [
        "name", "case", "project_name", "description", "files", "status_id", "user_id"
    ];

    protected $primarykey = "id";

    protected $dates = ["deleted_at"];

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