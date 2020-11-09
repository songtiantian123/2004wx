<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    // 表名
    protected $table = 'user';
    //主键自增
    protected $primaryKey = 'id';
    public $timestamps = false;
}
