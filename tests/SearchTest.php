
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

    public function testContacts() {
        $tokenResponse = $this->call('POST',
                                '/oauth/token',
                                [
                                    'grant_type' => 'password',
                                    'client_id' => '2',
                                    'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
                                    'username' => 'aure.girardeau@gmail.com',
                                    'password' => 'toto',
                                    'scope' => '*'
                                ]);
        $token = json_decode($tokenResponse->getContent(), true);
        $response = $this->call('GET',
                                '/api/search/contacts',[
                                    'name_begin' => '',
                                    'known_only' => false,
                                    'id' => -1
                                ],[],[], ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }

    public function testEvents() {
        $tokenResponse = $this->call('POST',
                                '/oauth/token',
                                [
                                    'grant_type' => 'password',
                                    'client_id' => '2',
                                    'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
                                    'username' => 'aure.girardeau@gmail.com',
                                    'password' => 'toto',
                                    'scope' => '*'
                                ]);
        $token = json_decode($tokenResponse->getContent(), true);
        $response = $this->call('GET',
                                '/api/search/events',[
                                    'name_begin' => '',
                                    'known_only' => true,
                                    'id' => -1
                                ],[],[], ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());
    }
}
