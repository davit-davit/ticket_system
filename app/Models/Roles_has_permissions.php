<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roles_has_permissions extends Model
{
    use HasFactory;

    protected $table = "roles_has_permissions";

    protected $fillable = [
        "role_id", "permission_id"
    ];
    
    protected $primarykey = "id";

    public $timestamps = true;

    public function Permisions() {
        return $this->hasOne('App\Models\Permission', 'id', 'permission_id');
    }

    public function getCreatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }

    public function getUpdatedAtAttribute($value) {
        return $this->asDateTime($value)->setTimezone('Asia/Tbilisi')->format("Y-m-d - H:i:s");
    }
}
