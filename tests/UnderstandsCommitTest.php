<?php

namespace Reshadman\Committed\Tests;


use Reshadman\Committed\TransactionEventsSubscriber;

class UnderstandsCommitTest extends TestCase
{
    public function test_works_upon_save()
    {
        $user = null;
        $data = [
            'email' => 'rezashadman.me@gmail.com',
            'name' => 'Reza Shadman'
        ];

        // Create a new user inside transaction.
        // Check that commit callback is added to be run after commit
        // Open another transaction create another user.
        // Ensure that commit callback is not ran for the new user.
        // Check that callback container has callbacks of both users.
        // Rollback the new user transaction.
        // Ensure that new user commit callback is not ran and never will be ran in future.
        // Ensure the wrapper transaction commit has ran the callback for the first user
        // Ensure that there is nothing in the commit callback container.
        // Create another user without any transaction.
        // Check that mocked commit callback is fired immediately and is not added to the
        // event commit container.

        \DB::transaction(
            function () use (&$user, $data) {
                $user = UserStub::create($data);

                $this->assertEquals(count(TransactionEventsSubscriber::getCallbackTree()), 1);

                \DB::beginTransaction();

                $nestedUser = UserStub::create($data);

                $this->assertEquals($nestedUser->committedMessage, 'Nothing.');

                $this->assertEquals(count(TransactionEventsSubscriber::getCallbackTree()), 2);

                \DB::rollback();

                $this->assertEquals($nestedUser->committedMessage, 'Nothing.');

                $this->assertEquals($user->committedMessage, 'Nothing.');
            }
        );

        $this->assertEquals($user->committedMessage, 'I am committed.');

        $this->assertEquals(count(TransactionEventsSubscriber::getCallbackTree()), 0);

        $user2 = UserStub::create($data);

        $this->assertEquals(count(TransactionEventsSubscriber::getCallbackTree()), 0);

        $this->assertEquals($user2->committedMessage, 'I am committed.');
    }
}