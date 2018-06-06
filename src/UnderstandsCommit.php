<?php

namespace Reshadman\Committed;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait UnderstandsCommit
 * @package Reshadman\Committed
 */
trait UnderstandsCommit
{
    /**
     * New model events which behave upon
     * the commit event of the model's connection.
     *
     * @var array
     */
    protected static $mergingObservableEvents = [
        'committed',
        'committedCreation',
        'committedUpdate',
        'committedDelete',
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
        return array_merge(parent::getObservableEvents(), static::$mergingObservableEvents);
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
        parent::registerModelEvent('committed', $callback);
    }

    /**
     * After a resource has been created.
     *
     * @param $callback
     */
    protected static function committedCreation($callback)
    {
        parent::registerModelEvent('committed_creation', $callback);
    }

    /**
     * After an update has been committed.
     *
     * @param $callback
     */
    protected static function committedUpdate($callback)
    {
        parent::registerModelEvent('committed_update', $callback);
    }

    /**
     * After a delete has been committed.
     *
     * @param $callback
     */
    protected static function committedDelete($callback)
    {
        parent::registerModelEvent('committed_delete', $callback);
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
        if ($this->getConnection()->transactionLevel() <= 0) {

            $this->fireModelEvent($commitAction);

        } else {

            TransactionEventsSubscriber::addModelCallback($this, function () use ($commitAction) {

                $this->fireModelEvent($commitAction);

            });

        }

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