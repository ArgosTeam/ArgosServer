<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotificationTest extends TestCase {
    function setUp() {
        parent::setUp();
    }

    public function testGetNotifs() {
        $tokenResponse = $this->call('POST',
                                     '/oauth/token',
                                     [
                                         'grant_type' => 'password',
                                         'client_id' => '2',
                                         'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
                                         'username' => 'aure.girard@gmail.com',
                                         'password' => 'toto',
                                         'scope' => '*'
                                     ]);
        $token = json_decode($tokenResponse->getContent(), true);

        
        $response = $this->call('GET',
                                '/api/notifs',
                                [], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }

    public function testMarkAsRead() {
        $tokenResponse = $this->call('POST',
                                     '/oauth/token',
                                     [
                                         'grant_type' => 'password',
                                         'client_id' => '2',
                                         'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
                                         'username' => 'aure.girard@gmail.com',
                                         'password' => 'toto',
                                         'scope' => '*'
                                     ]);
        $token = json_decode($tokenResponse->getContent(), true);

        
        $response = $this->call('GET',
                                '/api/notifs',
                                [], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);

        $data = json_decode($response->getContent());

        print_r($data);
        $response2 = $this->call('GET',
                                 '/api/notif/mark_read',
                                 [
                                     'notification_id' => $data->notification_id
                                 ], [], [],
                                 ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response2->status());
    }
}