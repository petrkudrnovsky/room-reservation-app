<?php

namespace App\Tests\Api;

use App\Entity\AppUser;

class ApiUserTest extends ApiTestCase
{
    private string $adminToken;
    private string $simunekToken;
    private int $simunekId;
    private int $dvorakId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken   = $this->getTokenFor('admin', 'admin');
        $this->simunekToken = $this->getTokenFor('jakub.simunek');
        $this->simunekId    = $this->getUserId('jakub.simunek');
        $this->dvorakId     = $this->getUserId('jan.dvorak');
    }

    // ── LIST ────────────────────────────────────────────────────────────────────

    public function test_list_users_as_admin(): void
    {
        $this->client->request('GET', '/api/user', [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertArrayHasKey('appUsers', $data);
        $this->assertCount(20, $data['appUsers']);
    }

    public function test_list_users_as_regular_returns_403(): void
    {
        $this->client->request('GET', '/api/user', [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    // ── DETAIL ──────────────────────────────────────────────────────────────────

    public function test_detail_own_profile(): void
    {
        $this->client->request('GET', "/api/user/{$this->simunekId}", [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $this->assertSame('jakub.simunek', $data['username']);
    }

    public function test_detail_other_profile_returns_403(): void
    {
        $this->client->request('GET', "/api/user/{$this->dvorakId}", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    public function test_detail_any_user_as_admin(): void
    {
        $this->client->request('GET', "/api/user/{$this->simunekId}", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_nonexistent_user_returns_404(): void
    {
        $this->client->request('GET', '/api/user/99999', [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(404);
    }

    // ── CREATE ──────────────────────────────────────────────────────────────────

    public function test_create_user_as_admin(): void
    {
        $body = [
            'username'   => 'new.test.user',
            'password'   => 'testpassword123',
            'firstName'  => 'New',
            'secondName' => 'User',
        ];
        $this->client->request('POST', '/api/user', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('new.test.user', $data['username']);
    }

    public function test_create_user_as_regular_returns_403(): void
    {
        $body = ['username' => 'new.user', 'password' => 'test', 'firstName' => 'New', 'secondName' => 'User'];
        $this->client->request('POST', '/api/user', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_create_user_duplicate_username_returns_400(): void
    {
        $body = [
            'username'   => 'jakub.simunek', // already exists
            'password'   => 'testpassword',
            'firstName'  => 'Jakub',
            'secondName' => 'Duplicate',
        ];
        $this->client->request('POST', '/api/user', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $this->assertStatusCode(400);
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────────

    public function test_update_own_profile(): void
    {
        $body = [
            'id'         => $this->simunekId, // required so uniqueness check excludes this user
            'username'   => 'jakub.simunek',
            'password'   => 'newpassword',
            'firstName'  => 'Jakub Updated',
            'secondName' => 'Šimůnek',
        ];
        $this->client->request('PUT', "/api/user/{$this->simunekId}", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('Jakub Updated', $data['firstName']);
    }

    public function test_update_other_profile_returns_403(): void
    {
        $body = [
            'id'         => $this->dvorakId,
            'username'   => 'jan.dvorak',
            'password'   => 'newpassword',
            'firstName'  => 'Hacked',
            'secondName' => 'Dvořák',
        ];
        $this->client->request('PUT', "/api/user/{$this->dvorakId}", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────

    public function test_delete_user_as_admin(): void
    {
        $anezkaId = $this->getUserId('anezka.rihova');
        $this->client->request('DELETE', "/api/user/$anezkaId", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(204);
        $this->em->clear();
        $this->assertNull($this->em->find(AppUser::class, $anezkaId));
    }

    public function test_delete_user_as_regular_returns_403(): void
    {
        $anezkaId = $this->getUserId('anezka.rihova');
        $this->client->request('DELETE', "/api/user/$anezkaId", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    // ── REGISTER ────────────────────────────────────────────────────────────────

    public function test_register_new_user_without_auth(): void
    {
        $body = [
            'username'   => 'new.registered.user',
            'password'   => 'securepassword',
            'firstName'  => 'New',
            'secondName' => 'Registered',
        ];
        $this->client->request('POST', '/api/user/register', [], [], $this->jsonHeaders(), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('new.registered.user', $data['username']);
        $this->assertNotContains('ROLE_SUPER_ADMIN', $data['roles']);
        $this->assertEmpty($data['memberRooms']);
        $this->assertEmpty($data['memberGroups']);
    }

    public function test_register_with_member_rooms_set_returns_400(): void
    {
        $body = [
            'username'   => 'new.reg2',
            'password'   => 'securepassword',
            'firstName'  => 'New',
            'secondName' => 'Reg',
            'memberRooms' => [1],
        ];
        $this->client->request('POST', '/api/user/register', [], [], $this->jsonHeaders(), json_encode($body));
        $this->assertStatusCode(400);
    }

    public function test_register_without_password_returns_400(): void
    {
        $body = [
            'username'   => 'new.nopass',
            'firstName'  => 'No',
            'secondName' => 'Password',
        ];
        $this->client->request('POST', '/api/user/register', [], [], $this->jsonHeaders(), json_encode($body));
        $this->assertStatusCode(400);
    }

    public function test_register_with_duplicate_username_returns_400(): void
    {
        $body = [
            'username'   => 'admin',
            'password'   => 'somepassword',
            'firstName'  => 'Duplicate',
            'secondName' => 'Admin',
        ];
        $this->client->request('POST', '/api/user/register', [], [], $this->jsonHeaders(), json_encode($body));
        $this->assertStatusCode(400);
    }
}
