<?php

namespace Reshadman\Committed;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;

class TransactionEventsSubscriber
{
    /**
     * A tree containing of connection names including their
     * transaction levels + callbacks to be executed upon commit.
     *
     * @var array
     */
    protected static $connectionCallbackTree = [];

    /**
     * We will iterate through each callback registered for the
     * given transaction, by given transaction we mean any callback
     * registered for "[connectionName]@[transactionLevel].
     *
     * We run each callback and remove it from the container of callbacks.
     *
     * @param TransactionCommitted $transaction
     */
    public function onTransactionCommit(TransactionCommitted $transaction)
    {
        $identifier = static::extractIdentifierFromConnectionAfter($transaction->connection);

        if (isset(static::$connectionCallbackTree[$identifier])) {


            foreach (static::$connectionCallbackTree[$identifier] as $index => $callback) {

                unset(static::$connectionCallbackTree[$identifier][$index]);

                $callback($transaction);

            }

            // Don't blow up memory in an environment with lots of connections (like multi tenant apps.).
            if (empty(static::$connectionCallbackTree[$identifier])) {
                unset(static::$connectionCallbackTree[$identifier]);
            }

        }
    }

    /**
     * We will forget all registered callbacks for a given transaction.
     *
     * @param TransactionRolledBack $transaction
     */
    public function onTransactionRollback(TransactionRolledBack $transaction)
    {
        $identifier = static::extractIdentifierFromConnectionAfter($transaction->connection);

        if (isset(static::$connectionCallbackTree[$identifier])) {

            unset(static::$connectionCallbackTree[$identifier]);

        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen(
            TransactionCommitted::class,
            TransactionEventsSubscriber::class . '@' . 'onTransactionCommit'
        );

        $events->listen(
            TransactionRolledBack::class,
            TransactionEventsSubscriber::class . '@' . 'onTransactionRollback'
        );
    }

    /**
     * Add model for executing later delayed callbacks upon transaction commit.
     *
     * @param Model $model
     * @param \Closure $callback
     */
    public static function addModelCallback($model, $callback)
    {

        $connectionIdentifier = static::extractIdentifierFromConnectionDuringRun($model->getConnection());
        static::$connectionCallbackTree[$connectionIdentifier][] = $callback;
    }


    /**
     * @param Connection $connection
     * @return string
     */
    protected static function extractIdentifierFromConnectionDuringRun($connection)
    {
       return $connection->getName() . '@' . (string)$connection->transactionLevel();
    }

    /**
     * @param Connection $connection
     * @return string
     */
    protected static function extractIdentifierFromConnectionAfter($connection)
    {
        return $connection->getName() . '@' . (string)($connection->transactionLevel() + 1);
    }

    public static function getCallbackTree()
    {
        return static::$connectionCallbackTree;
    }
}