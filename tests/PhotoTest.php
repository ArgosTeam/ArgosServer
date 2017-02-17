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

        $path = __DIR__ . '/images/test_upload.jpeg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = base64_encode($data);

        // Test upload
        $response = $this->call('POST',
                    '/api/photo/upload',
                    [
                        'image' => $base64,
                        'description' => 'Ceci est une photo test upload',
                        'latitude' => -37.8267193,
                        'longitude' => 144.9592682,
                        'hashtags' => [
                            '#tropfrais',
                            '#arthurlapute'
                        ],
                        'mode' => 'normal',
                        'public' => true
                    ], [], [],
                    ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}