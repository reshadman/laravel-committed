<?php

namespace Reshadman\Committed;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait UnderstandsCommit
 * @package Reshadman\Committed
 */
trait UnderstandsCommit
{
    protected static $registeredCommitActions = [
        'committed' => 0,
        'committedCreation' => 0,
        'committedUpdate' => 0,
        'committedDelete' => 0
    ];

    /**
     * Allows developers to subscribe
     * to commit related events just like native eloquent
     * events in observer classes.
     *
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(parent::getObservableEvents(), array_keys(static::$registeredCommitActions));
    }

    /**
     * Add listeners for native model events which then will be
     * queued for transaction commit or will be fire immediately
     * if no transaction for this connection is open.
     *
     * @return void
     */
    protected static function bootUnderstandsCommit()
    {
        foreach (static::nativeEventsToTransactionEvents() as $on => $commitAction) {

            static::$on(function ($model) use ($on, $commitAction) {
                /** @var Model|$this $model */
                return $model->markNativeEventForCommitAction($commitAction);

            });

        }
    }

    /**
     * After a resource has been created or saved.
     *
     * @param $callback
     */
    protected static function committed($callback)
    {
        static::$registeredCommitActions['committed']++;
        parent::registerModelEvent('committed', $callback);
    }

    /**
     * After a resource has been created.
     *
     * @param $callback
     */
    protected static function committedCreation($callback)
    {
        static::$registeredCommitActions['committedCreation']++;
        parent::registerModelEvent('committedCreation', $callback);
    }

    /**
     * After an update has been committed.
     *
     * @param $callback
     */
    protected static function committedUpdate($callback)
    {
        static::$registeredCommitActions['committedUpdate']++;
        parent::registerModelEvent('committedUpdate', $callback);
    }

    /**
     * After a delete has been committed.
     *
     * @param $callback
     */
    protected static function committedDelete($callback)
    {
        static::$registeredCommitActions['committedDelete']++;
        parent::registerModelEvent('committedDelete', $callback);
    }

    /**
     * If there is no transaction open for this model's connection
     * we will fire it immediately else we will add it to our
     * global container, which will be flushed upon commit of this model's
     * connection transaction at given level.
     *
     * @param $commitAction
     * @return $this
     */
    protected function markNativeEventForCommitAction($commitAction)
    {
        if (static::$registeredCommitActions[$commitAction] <= 0) {
              return $this;
        }
        
        if ($this->getConnection()->transactionLevel() <= 0) {
            $this->fireModelEvent($commitAction);
            return $this;
        }

        TransactionEventsSubscriber::addModelCallback($this, function () use ($commitAction) {
            $this->fireModelEvent($commitAction);
        });
        
        return $this;
    }

    /**
     * Maps native model events to transaction events.
     *
     * @return array
     */
    protected static function nativeEventsToTransactionEvents()
    {
        return [
            'saved' => 'committed',
            'created' => 'committedCreation',
            'updated' => 'committedUpdate',
            'deleted' => 'committedDelete',
        ];
    }
}
