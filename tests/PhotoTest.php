<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class   PhotoTest extends TestCase
{

    function setUp() {
        parent::setUp();
    }

    public function testFetch() {
        //  Get token        
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

        // Test Fetch
        $response = $this->call('GET',
                    '/api/fetch',
                    [
                        'farRight' => 'lat/lng: (54.38655404338235, -5.277385468750026)',
                        'farLeft' => 'lat/lng: (54.38655404338235, -7.254924531250026)',
                        'nearLeft' => 'lat/lng: (52.2879010895274, -7.254924531250026)',
                        'nearRight' => 'lat/lng: (52.2879010895274, -5.277385468750026)',
                        'hashtag' => null,
                        'userId' => null,
                        'groupId' => null
                    ], [], [],
                    ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());
    }
}