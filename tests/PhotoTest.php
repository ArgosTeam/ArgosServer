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
                        'farRight' => 'LatLng(54.38655404338235, -5.277385468750026)',
                        'farLeft' => 'LatLng(54.38655404338235, -7.254924531250026)',
                        'nearLeft' => 'LatLng(52.2879010895274, -7.254924531250026)',
                        'nearRight' => 'LatLng(52.2879010895274, -5.277385468750026)',
                        'filter' => [
                            'users' => [],
                            'hashtags' => [],
                            'groups' => []
                        ]
                    ], [], [],
                    ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }

    public function testUpload() {
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

        $stub = __DIR__ . './images/test.png';
        $name = str_random(8).'.png';
        $path = sys_get_temp_dir().'/'.$name;

        copy($stub, $path);

        $file = new UploadedFile($path, $name, filesize($path), 'image/jpeg', null, true);
        // Test upload
        $response = $this->call('POST',
                    '/api/user/profile_pic',
                    [
                        'image' => $file,
                    ], [], [],
                    ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}