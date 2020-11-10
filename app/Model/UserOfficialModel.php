<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserOfficialModel extends Model
{
    // 表名
    protected $table = 'user_official';
    //主键自增
    protected $primaryKey = 'id';
    public $timestamps = false;
}
