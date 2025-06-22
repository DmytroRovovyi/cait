<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    protected $primaryKey = 'telegram_id';
    public $incrementing = false;
    protected $keyType = 'int';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'telegram_id',
        'step',
        'title',
        'description',
    ];
}
