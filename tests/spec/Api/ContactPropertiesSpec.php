<?php

namespace spec\Fungku\HubSpot\Api;

use Fungku\HubSpot\Http\Client;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ContactPropertiesSpec extends ObjectBehavior
{
    function let(Client $client)
    {
        $this->beConstructedWith('demo', $client);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Fungku\HubSpot\Api\ContactProperties');
    }
}
