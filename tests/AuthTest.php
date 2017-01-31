<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{

    function setUp() {
        parent::setUp();
    }

    /*
    ** Test of AuthController@registerManual
    */
    public function testRegisterManual() {
        $data = [
            'csrf_token' => csrf_token(),
            'grant_type' => 'password',
            'client_id' => '1',
            'client_secret' => '8KD1qlhGoguCBCTZDgWsRtV1cU6OZtRrsOJT0cjb',
            'phone' => 'aure.girard@gmail.com',
            'email' => 'aure.girard@gmail.com',
            'firstname' => 'test',
            'lastname' => 'test',
            'password' => 'toto',
            'password_confirm' => 'toto',
            'sex' => 'male',
            'scope' => '*'
        ];
        $response = $this->json(
            'POST',
            '/register',
            $data)
                  ->seeJsonStructure([
                      "registered"
                  ]);
    }

    /*
    ** Test of Passport@Oauth@Token
    */
    public function testPassportGetToken() {
        $response = $this->call('POST',
                                '/oauth/token',
                                [
                                    'grant_type' => 'password',
                                    'client_id' => '1',
                                    'client_secret' => '8KD1qlhGoguCBCTZDgWsRtV1cU6OZtRrsOJT0cjb',
                                    'username' => 'aure.girard@gmail.com',
                                    'password' => 'toto',
                                    'scope' => '*'
                                ]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}