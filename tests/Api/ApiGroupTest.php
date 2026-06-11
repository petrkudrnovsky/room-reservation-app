<?php

namespace App\Tests\Api;

use App\Entity\Group;

class ApiGroupTest extends ApiTestCase
{
    private string $adminToken;
    private string $dvorakToken;
    private string $simunekToken;
    private string $rihovanToken;
    private int $ksiId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken   = $this->getTokenFor('admin', 'admin');
        $this->dvorakToken  = $this->getTokenFor('jan.dvorak');
        $this->simunekToken = $this->getTokenFor('jakub.simunek');
        $this->rihovanToken = $this->getTokenFor('anezka.rihova');
        $this->ksiId        = $this->getGroupId('Software Engineering');
    }

    // ── LIST ────────────────────────────────────────────────────────────────────

    public function test_list_groups_authenticated_returns_all(): void
    {
        $this->client->request('GET', '/api/group', [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $this->assertArrayHasKey('groups', $data);
        $this->assertCount(4, $data['groups']);
    }

    public function test_list_groups_unauthenticated_returns_401(): void
    {
        $this->client->request('GET', '/api/group');
        $this->assertStatusCode(401);
    }

    // ── DETAIL ──────────────────────────────────────────────────────────────────

    public function test_detail_as_group_member(): void
    {
        $this->client->request('GET', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $data = $this->assertJsonResponse(200);
        $this->assertStringContainsString('Software Engineering', $data['name']);
    }

    public function test_detail_as_non_member_returns_403(): void
    {
        // jakub.simunek is in no group
        $this->client->request('GET', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    public function test_detail_as_super_admin_always_succeeds(): void
    {
        $this->client->request('GET', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_nonexistent_group_returns_404(): void
    {
        $this->client->request('GET', '/api/group/99999', [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(404);
    }

    // ── CREATE ──────────────────────────────────────────────────────────────────

    public function test_create_group_as_admin(): void
    {
        $body = ['name' => 'New Research Group'];
        $this->client->request('POST', '/api/group', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('New Research Group', $data['name']);
    }

    public function test_create_group_as_regular_user_returns_403(): void
    {
        $body = ['name' => 'Unauthorized Group'];
        $this->client->request('POST', '/api/group', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_create_group_with_missing_name_returns_400(): void
    {
        $body = ['name' => 'AB']; // too short (min 3 chars)
        $this->client->request('POST', '/api/group', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $this->assertStatusCode(400);
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────────

    public function test_update_group_as_group_admin(): void
    {
        $body = ['name' => 'Updated Software Engineering Dept'];
        $this->client->request('PUT', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('Updated Software Engineering Dept', $data['name']);
    }

    public function test_update_group_as_non_admin_returns_403(): void
    {
        $body = ['name' => 'Hacked Group'];
        $this->client->request('PUT', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────

    public function test_delete_group_as_admin(): void
    {
        $kamId = $this->getGroupId('Applied Mathematics');
        $this->client->request('DELETE', "/api/group/$kamId", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Group::class, $kamId));
    }

    public function test_delete_group_as_group_admin_returns_403(): void
    {
        // jan.dvorak is group admin of KSI but not super admin; DELETE requires ROLE_SUPER_ADMIN
        $this->client->request('DELETE', "/api/group/{$this->ksiId}", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(403);
    }

    // ── MEMBER MANAGEMENT ───────────────────────────────────────────────────────

    public function test_add_member_to_group_as_group_admin(): void
    {
        $anezkaId = $this->getUserId('anezka.rihova');
        $body = ['id' => $anezkaId];
        $this->client->request('PATCH', "/api/group/{$this->ksiId}/user", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $data = $this->assertJsonResponse(200);
        $memberUrls = $data['members'];
        $this->assertTrue(
            count(array_filter($memberUrls, fn($url) => str_contains($url, "/api/user/$anezkaId"))) > 0
        );
    }

    public function test_add_member_as_non_admin_returns_403(): void
    {
        $body = ['id' => $this->getUserId('david.mares')];
        // jakub.simunek is not an admin of any group
        $this->client->request('PATCH', "/api/group/{$this->ksiId}/user", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_remove_member_as_group_admin(): void
    {
        // eva.bartosova is a member of KSI
        $evaId = $this->getUserId('eva.bartosova');
        $this->client->request('DELETE', "/api/group/{$this->ksiId}/user/$evaId", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(204);
    }

    public function test_remove_non_member_returns_400(): void
    {
        // anezka.rihova is NOT in KSI
        $anezkaId = $this->getUserId('anezka.rihova');
        $this->client->request('DELETE', "/api/group/{$this->ksiId}/user/$anezkaId", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(400);
    }

    // ── ADMIN MANAGEMENT ────────────────────────────────────────────────────────

    public function test_add_admin_as_super_admin(): void
    {
        $jakubId = $this->getUserId('jakub.simunek');
        $body = ['id' => $jakubId];
        $this->client->request('PATCH', "/api/group/{$this->ksiId}/admin", [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $data = $this->assertJsonResponse(200);
        $adminUrls = $data['admins'];
        $this->assertTrue(
            count(array_filter($adminUrls, fn($url) => str_contains($url, "/api/user/$jakubId"))) > 0
        );
    }

    public function test_add_admin_as_group_admin_returns_403(): void
    {
        // jan.dvorak is group admin but not super admin; only super admin can add admins
        $body = ['id' => $this->getUserId('jakub.simunek')];
        $this->client->request('PATCH', "/api/group/{$this->ksiId}/admin", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_remove_non_admin_returns_400(): void
    {
        // jakub.simunek is not an admin of KSI
        $jakubId = $this->getUserId('jakub.simunek');
        $this->client->request('DELETE', "/api/group/{$this->ksiId}/admin/$jakubId", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(400);
    }

    // ── ROOM MANAGEMENT ─────────────────────────────────────────────────────────

    public function test_add_room_to_group_as_group_admin(): void
    {
        // TK:BS has no owning group; add it to KSI
        $roomId = $this->getRoomId('TK', 'BS');
        $body = ['id' => $roomId];
        $this->client->request('PATCH', "/api/group/{$this->ksiId}/room", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $data = $this->assertJsonResponse(200);
        $roomUrls = $data['rooms'];
        $this->assertTrue(
            count(array_filter($roomUrls, fn($url) => str_contains($url, "/api/room/$roomId"))) > 0
        );
    }

    public function test_remove_room_from_group_as_group_admin(): void
    {
        // TH:A-9101 belongs to KSI
        $roomId = $this->getRoomId('TH:A', '9101');
        $this->client->request('DELETE', "/api/group/{$this->ksiId}/room/$roomId", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(204);
    }

    public function test_remove_room_not_in_group_returns_400(): void
    {
        // T9:301 belongs to KPS, not KSI
        $roomId = $this->getRoomId('T9', '301');
        $this->client->request('DELETE', "/api/group/{$this->ksiId}/room/$roomId", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(400);
    }
}
