<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotificationTest extends TestCase {
    function setUp() {
        parent::setUp();
    }

    public function testGeocoding() {
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
                                '/api/location/geocoding',
                                [
                                    'address' => '45 clarke street, Melbourne'
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}