<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;

class SearchTest extends TestCase
{

    function setUp() {
        parent::setUp();
    }

    public function testSearchRelatives() {
        $tokenResponse = $this->call('POST',
                                '/oauth/token',
                                [
                                    'grant_type' => 'password',
                                    'client_id' => '4',
                                    'client_secret' => '5nLIzZv1vnYGIgrOWIWjOtR2uSm9Qpvd70IHZqGB',
                                    'username' => 'aure.girardeau@gmail.com',
                                    'password' => 'toto',
                                    'scope' => '*'
                                ]);
        $token = json_decode($tokenResponse->getContent(), true);
        //$this->header('Authorization: ' . $token['access_token']);
        $response = $this->call('GET',
                                '/api/searchrelatives/z',[],[],[], ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());
    }
}