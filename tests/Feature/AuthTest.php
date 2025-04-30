<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_login_response_success(): void
    {

        // Act: send login request
    $response = $this->postJson('/api/login', [
        'username' => 'tesdela',
        'password' => '1234abcd',
    ]);


    // Assert login success
    $response->assertStatus(200);
    }
}
