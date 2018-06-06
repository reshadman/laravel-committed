<?php

namespace Reshadman\Committed\Tests;


use Illuminate\Database\Eloquent\Model;
use Reshadman\Committed\UnderstandsCommit;

class UserStub extends Model {

    use UnderstandsCommit;

    protected $table = 'users';
    protected $guarded = [];

    public $committedMessage = 'Nothing.';

    protected static function boot()
    {
        static::committed(function (UserStub $user) {
            $user->committedMessage = 'I am committed.';
        });

        parent::boot();
    }
}