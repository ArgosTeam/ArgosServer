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
                                [
                                    'types' => ['NewPublicPicture']
                                ], [], [],
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
                                ['types' => ['NewPublicPicture']], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);

        $data = json_decode($response->getContent());

        $notifications_id = [];
        foreach ($data as $notification) {
            $notifications_id[] = $notification->notification_id;
        }
        
        $response2 = $this->call('POST',
                                 '/api/notif/mark_read',
                                 [
                                     'notifications_id' => $notifications_id
                                 ], [], [],
                                 ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response2->getContent());
        $this->assertEquals(200, $response2->status());
    }
}