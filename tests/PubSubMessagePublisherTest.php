<?php

namespace GenTux\GooglePubSub\Tests;

use GenTux\GooglePubSub\PubSubMessage;
use GenTux\GooglePubSub\Tests\Stubs\AccountsCustomerCreatedMessage;
use Mockery;

class PubSubMessagePublisherTest extends \PHPUnit_Framework_TestCase
{
    /** @var PubSubMessage */
    protected $message;

    public function setUp()
    {
        $this->message = new AccountsCustomerCreatedMessage('production', 'acme-anvil');
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /** @test */
    public function it_statically_handles_a_routing_key()
    {
        $this->assertTrue(AccountsCustomerCreatedMessage::handles('accounts.customer.created'));
        $this->assertFalse(AccountsCustomerCreatedMessage::handles('accounts.customer.deleted'));
    }

    /** @test */
    public function it_has_a_topic()
    {
        $this->assertEquals('production-v1-customer', $this->message->topic());
    }

    /** @test */
    public function it_publishes_a_message()
    {
        $googleClient = Mockery::mock('overload:Google_Client');
        $googleClient->shouldReceive('useApplicationDefaultCredentials')
            ->shouldReceive('setScopes')
            ->shouldReceive('getLogger');

        Mockery::mock('overload:Google_Service_Pubsub_Resource_ProjectsTopics')
            ->shouldReceive('publish');

        $this->message->publish([
            'firstName' => 'G.K.',
            'lastName' => 'Testerton',
        ]);
    }
}
