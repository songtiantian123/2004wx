<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MediaModel extends Model
{
    // 表名
    protected $table = 'media';
    //主键自增
    protected $primaryKey = 'id';
    public $timestamps = false;
}
