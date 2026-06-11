<?php

namespace App\Tests\Api;

class ApiAuthTest extends ApiTestCase
{
    public function test_login_valid_credentials_returns_jwt(): void
    {
        $this->client->request(
            'POST', '/api/login_check',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'admin', 'password' => 'admin'])
        );
        $data = $this->assertJsonResponse(200);
        $this->assertArrayHasKey('token', $data);
        $this->assertMatchesRegularExpression('/^[\w-]+\.[\w-]+\.[\w-]+$/', $data['token']);
    }

    public function test_login_wrong_password_returns_401(): void
    {
        $this->client->request(
            'POST', '/api/login_check',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'admin', 'password' => 'wrongpassword'])
        );
        $this->assertStatusCode(401);
    }

    public function test_login_unknown_user_returns_401(): void
    {
        $this->client->request(
            'POST', '/api/login_check',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'nobody', 'password' => 'password'])
        );
        $this->assertStatusCode(401);
    }

    public function test_protected_endpoint_without_token_returns_401(): void
    {
        $this->client->request('GET', '/api/user');
        $this->assertStatusCode(401);
    }

    public function test_protected_endpoint_with_garbage_token_returns_401(): void
    {
        $this->client->request(
            'GET', '/api/user',
            [], [], ['HTTP_AUTHORIZATION' => 'Bearer notavalidtoken']
        );
        $this->assertStatusCode(401);
    }

    public function test_valid_token_on_admin_endpoint_returns_200(): void
    {
        $token = $this->getTokenFor('admin', 'admin');
        $this->client->request('GET', '/api/user', [], [], $this->makeAuthHeaders($token));
        $this->assertStatusCode(200);
    }

    public function test_regular_user_token_on_admin_only_endpoint_returns_403(): void
    {
        $token = $this->getTokenFor('jakub.simunek');
        $this->client->request('GET', '/api/user', [], [], $this->makeAuthHeaders($token));
        $this->assertStatusCode(403);
    }
}
