<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class   UserTest extends TestCase
{
    function setUp() {
        parent::setUp();
    }

    public function testInfos() {
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

        
        $response = $this->call('GET',
                                '/api/user/infos',
                                [
                                    'id' => -1
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent(), true);
        $this->assertEquals(200, $response->status());

    }

}
