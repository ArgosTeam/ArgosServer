<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FavoritesTest extends TestCase {

    function setUp() {
        parent::setUp();
    }

    public function testFavorites() {
        $tokenResponse = $this->call('POST',
                                     '/oauth/token',
                                     [
                                         'grant_type' => 'password',
                                         'client_id' => '2',
                                         'client_secret' => 'Sf1lYjZkcXBkS8DyC1txUUo18gjP1BtOlBVdwDzb',
                                         'username' => 'aure.girardeau@gmail.com',
                                         'password' => 'toto',
                                         'scope' => '*'
                                     ]);
        $token = json_decode($tokenResponse->getContent(), true);

        
        $response = $this->call('GET',
                                '/api/friend/favorites',
                                [], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);


        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}