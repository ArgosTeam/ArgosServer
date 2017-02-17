<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Event;

class EventTest extends TestCase
{

    function setUp() {
        parent::setUp();
    }

    public function testAdd() {
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
        $expires = date('Y-m-d G:i:s', strtotime('+7 days'));
        $start = date('Y-m-d G:i:s', strtotime('+4 days'));
        $response = $this->call('POST',
                                '/api/event/add',[
                                    'lat' => 49.5746472,
                                    'lng' => 50.456738,
                                    'description' => 'Ceci est un évènement test',
                                    'name' => "event_test",
                                    'start' => $start,
                                    'expires' => $expires,
                                    'public' => true
                                ],[],[], ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());

    }

    public function testJoin() {
        
        $tokenResponse = $this->call('POST',
                                     '/oauth/token',
                                     [
                                         'grant_type' => 'password',
                                         'client_id' => '1',
                                         'client_secret' => '8KD1qlhGoguCBCTZDgWsRtV1cU6OZtRrsOJT0cjb',
                                         'username' => 'aure.giardeau@gmail.com',
                                         'password' => 'toto',
                                         'scope' => '*'
                                     ]);
        $token = json_decode($tokenResponse->getContent(), true);

        
        $response = $this->call('POST',
                                '/api/event/join',
                                [
                                    'event_id' => 3
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        
        $this->assertEquals(200, $response->status());
    }

    public function testAccept() {
        
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

        
        $response = $this->call('POST',
                                '/api/event/accept',
                                [
                                    'user_id' => 1,
                                    'event_id' => 3
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        
        $this->assertEquals(200, $response->status());
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
                                '/api/event/infos',
                                [
                                    'id' => 3
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }

    public function testProfilePic() {
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

        $path = __DIR__ . '/images/test_event.jpeg';
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = base64_encode($data);

        // Test upload
        $response = $this->call('POST',
                    '/api/event/profile_pic',
                    [
                        'image' => $base64,
                        'event_id' => 3
                    ], [], [],
                    ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }

    public function testPhotoLink() {
        
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

        
        $response = $this->call('POST',
                                '/api/event/photo/link',
                                [
                                    'photo_id' => 29,
                                    'event_id' => 3
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        $this->assertEquals(200, $response->status());
    }

    public function testAlbum() {
        
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
                                '/api/event/photos',
                                [
                                    'event_id' => 3
                                ], [], [],
                                ['HTTP_Authorization' => 'Bearer ' . $token['access_token']]);
        print_r($response->getContent());
        $this->assertEquals(200, $response->status());
    }
}