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
        $dob = date('Y-m-d G:i:s', strtotime('+4 days'));
        $data = [
            'csrf_token' => csrf_token(),
            'grant_type' => 'password',
            'client_id' => '2',
            'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
            'phone' => '06111111111',
            'nickname' => 'aure.girard@gmail.com',
            'dob' => $dob,
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

    public function testRegisterManual2() {
        $dob = date('Y-m-d G:i:s', strtotime('+4 days'));
        $data = [
            'csrf_token' => csrf_token(),
            'grant_type' => 'password',
            'client_id' => '2',
            'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
            'phone' => '0450202809',
            'nickname' => 'aure.girardeau@gmail.com',
            'password' => 'toto',
            'password_confirm' => 'toto',
            'dob' => $dob,
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
                                    'client_id' => '2',
                                    'client_secret' => 'H9c9USUmSWsw2yxqxrnPbXl8sPvRfDCxztFc7xZ8',
                                    'username' => 'aure.girard@gmail.com',
                                    'password' => 'toto',
                                    'scope' => '*'
                                ]);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
