<?php

namespace Larasoft\PusherPushNotifications\Test;

use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notifiable;
use Larasoft\PusherPushNotifications\PusherChannel;
use Illuminate\Notifications\Notification;
use Larasoft\PusherPushNotifications\PusherMessage;
use PHPUnit_Framework_TestCase;
use Mockery;
use Pusher;

class ChannelTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pusher = Mockery::mock(Pusher::class);

        $this->events = Mockery::mock(Dispatcher::class);

        $this->channel = new PusherChannel($this->pusher, $this->events);

        $this->notification = new TestNotification;

        $this->notifiable = new TestNotifiable;
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    /** @test */
    public function it_can_send_a_notification()
    {
        $message = $this->notification->toPushNotification($this->notifiable);

        $data = $message->toArray();

        $this->pusher->shouldReceive('notify')->with('interest_name', $data, true)->andReturn(['status' => 202]);

        $this->channel->send($this->notifiable, $this->notification);
    }

    /** @test */
    public function it_fires_failure_event_on_failure()
    {
        $message = $this->notification->toPushNotification($this->notifiable);

        $data = $message->toArray();

        $this->pusher->shouldReceive('notify')->with('interest_name', $data, true)->andReturn(['status' => 500]);

        $this->events->shouldReceive('fire')->with(Mockery::type(NotificationFailed::class));

        $this->channel->send($this->notifiable, $this->notification);
    }
}

class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForPusherPushNotifications()
    {
        return 'interest_name';
    }
}

class TestNotification extends Notification
{
    public function toPushNotification($notifiable)
    {
        return new PusherMessage();
    }
}
