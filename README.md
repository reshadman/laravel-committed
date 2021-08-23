# Deprecation Notice
This project is deprecated and abondoned. Laravel now has some nice features fore defining event subscribers that are called after commit. The message queue jobs can also be pushed only if committed. So there is no need for another approach in the community but if you are interested, the concept is still simple and usable even though 
overridining too much behavior of eloquent can cause trouble. Just use the simple implementation provided by the core team.  

# Laravel Committed
![Build Status](https://travis-ci.org/reshadman/laravel-committed.svg?branch=master)

Adds **committed** event to Eloquent default event callbacks (created, creating, saved, saving...).

## Installation 
```bash
composer require reshadman/laravel-committed
```

> This package supports Laravel 5.5.* and 5.6.* .

## Usage

The service provider is registered automatically with discovery or you can Add the following service provider to your `app.php` config
file:

```php
<?php

return [
    // other stuff...
    'providers' => [
        // Other providers...
        \Reshadman\Committed\CommittedServiceProvider::class    
    ]  
];
```

Include the following `UnderstandsCommit` trait in to your model:

```php
<?php

use Reshadman\Committed\UnderstandsCommit;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use UnderstandsCommit;
    
    protected static function boot()
    {
        static::committed(function (User $user) {
            
            \Log::info($user->getKey()); 
        
        });
        
        static::committedCreation(function ($user) {});
        
        static::committedUpdate(function ($user) {});
        
        static::committedDelete(function () {});
        
        parent::boot();
    }
}
```

## Notices

 - Nested transactions are supported. 
 Whenever you roll back a nested transaction, all the containing
 callbacks will be ignored and deleted immediately. Callbacks
 happening inside a nested transaction will be fired whenever the
 very very top parent transaction is committed. 
 - Commit callbacks will not be fired on rollback or exceptions.
 - Callbacks are immediately fired if their model's connection
 is not inside an active transaction.
 - Multiple database connections are supported, but you
 need to consider that if you open a transaction inside a different
 connection than your target model, your target model's commit callbacks will be
 fired immediately.
 

## Why do we need **committed** event in Eloquent?
Sometimes you start a business transaction and then you may roll it back, or
an exception may be thrown, Consider a case where you have
sent a confirmation email when the user signs up, but because
of an unexpected error you have rolled back the transaction,
and the user is not actually created but she has received confirmation
email, you can control this kind of situation by ensuring that
your event is fired upon a successful commit, not just **saved**. 

## Running tests
Clone the repo, perform a composer install and run:

```vendor/bin/phpunit```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

