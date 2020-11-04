<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StudentModel extends Model
{
    // 表名
    protected $table = 'student';
    //主键自增
    protected $primaryKey = 'id';
    public $timestamps = false;
}
