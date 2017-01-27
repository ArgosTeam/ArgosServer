<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Event;

class EventTest extends TestCase
{

    function setUp() {
        parent::setUp();
    }

    public function testAdd() {
        $tokenResponse = $this->call('POST',
                                     '/oauth/token',
                                     [
                                         'grant_type' => 'password',
                                         'client_id' => '1',
                                         'client_secret' => '8KD1qlhGoguCBCTZDgWsRtV1cU6OZtRrsOJT0cjb',
                                         'username' => 'aure.girardeau@gmail.com',
                                         'password' => 'toto',
                                         'scope' => '*'
                                     ]);
        $token = json_decode($tokenResponse->getContent(), true);
        $response = $this->call('POST',
                                '/api/event/add',[
                                    'lat' => 49.5746472,
                                    'lng' => 50.456738,
                                    'name' => "event_test",
                                    'expires' => strtotime('+7 days', time()),
                                    'public' => true
                                ],[],[], ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());

    }
}