<?php

namespace App\Tests\Api;

use App\Entity\AccessLog;
use App\Entity\Reservation;
use App\Entity\Room;

class ApiRoomTest extends ApiTestCase
{
    private string $adminToken;
    private string $dvorakToken;
    private string $simunekToken;
    private string $rihovanToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken   = $this->getTokenFor('admin', 'admin');
        $this->dvorakToken  = $this->getTokenFor('jan.dvorak');
        $this->simunekToken = $this->getTokenFor('jakub.simunek');
        $this->rihovanToken = $this->getTokenFor('anezka.rihova');
    }

    // ── LIST ────────────────────────────────────────────────────────────────────

    public function test_list_rooms_as_admin_includes_all(): void
    {
        $this->client->request('GET', '/api/room', [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertArrayHasKey('rooms', $data);
        $this->assertCount(16, $data['rooms']);
    }

    public function test_list_rooms_as_member_includes_member_and_public_rooms(): void
    {
        $this->client->request('GET', '/api/room', [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $names = array_column($data['rooms'], 'name');
        $this->assertContains('Computer Lab T9:301', $names);
        $this->assertContains('Study Room BS', $names);
        $this->assertNotContains('Lecture Hall T9:300', $names);
    }

    public function test_list_rooms_unauthenticated_returns_only_public_rooms(): void
    {
        // Only non-private rooms (TK:BS) should appear for anonymous users.
        // If this test returns 500, it reveals a bug in RoomController::list
        // where hasApprovedReservation is called with a null user.
        $this->client->request('GET', '/api/room', [], [], $this->jsonHeaders());
        $data = $this->assertJsonResponse(200);
        $this->assertCount(1, $data['rooms']);
        $this->assertSame('Study Room BS', $data['rooms'][0]['name']);
    }

    public function test_list_rooms_filter_by_building_code(): void
    {
        $this->client->request('GET', '/api/room?building_code=T9', [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertCount(4, $data['rooms']);
        foreach ($data['rooms'] as $room) {
            $this->assertSame('T9', $room['buildingCode']);
        }
    }

    // ── DETAIL ──────────────────────────────────────────────────────────────────

    public function test_detail_public_room_without_auth(): void
    {
        $id = $this->getRoomId('TK', 'BS');
        $this->client->request('GET', "/api/room/$id");
        $data = $this->assertJsonResponse(200);
        $this->assertFalse($data['isPrivate']);
    }

    public function test_detail_private_room_without_auth_returns_401(): void
    {
        $id = $this->getRoomId('T9', '301');
        $this->client->request('GET', "/api/room/$id");
        $this->assertStatusCode(401);
    }

    public function test_detail_private_room_as_member(): void
    {
        $id = $this->getRoomId('T9', '301');
        $this->client->request('GET', "/api/room/$id", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_private_room_as_non_member_returns_403(): void
    {
        $id = $this->getRoomId('T9', '301');
        $this->client->request('GET', "/api/room/$id", [], [], $this->makeAuthHeaders($this->rihovanToken));
        $this->assertStatusCode(403);
    }

    public function test_detail_group_member_can_view_group_room(): void
    {
        // ondrej.sedlacek is in KSI group; KSI owns TH:A-9101
        $id = $this->getRoomId('TH:A', '9101');
        $ondrejToken = $this->getTokenFor('ondrej.sedlacek');
        $this->client->request('GET', "/api/room/$id", [], [], $this->makeAuthHeaders($ondrejToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_nonexistent_room_returns_404(): void
    {
        $this->client->request('GET', '/api/room/99999', [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(404);
    }

    // ── CREATE ──────────────────────────────────────────────────────────────────

    public function test_create_room_as_admin(): void
    {
        $buildingId = $this->getBuildingId('TK');
        $body = ['name' => 'Test Room X', 'code' => 'X01', 'isPrivate' => true, 'buildingId' => $buildingId];
        $this->client->request('POST', '/api/room', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('Test Room X', $data['name']);
    }

    public function test_create_room_as_regular_user_returns_403(): void
    {
        $body = ['name' => 'Test Room', 'code' => 'Y01', 'isPrivate' => true];
        $this->client->request('POST', '/api/room', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_create_room_without_auth_returns_401(): void
    {
        $body = ['name' => 'Test Room', 'code' => 'Z01', 'isPrivate' => true];
        $this->client->request('POST', '/api/room', [], [], $this->jsonHeaders(), json_encode($body));
        $this->assertStatusCode(401);
    }

    public function test_create_room_missing_name_returns_400(): void
    {
        $buildingId = $this->getBuildingId('TK');
        $body = ['code' => 'BAD01', 'isPrivate' => true, 'buildingId' => $buildingId];
        $this->client->request('POST', '/api/room', [], [], $this->makeAuthHeaders($this->adminToken), json_encode($body));
        $this->assertStatusCode(400);
    }

    // ── EDIT ─────────────────────────────────────────────────────────────────────

    public function test_edit_room_as_room_admin(): void
    {
        $id = $this->getRoomId('TH:A', '9105');
        $buildingId = $this->getBuildingId('TH:A');
        $body = ['name' => 'Renamed Seminar Room', 'code' => '9105', 'isPrivate' => true, 'buildingId' => $buildingId];
        $this->client->request('PUT', "/api/room/$id", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('Renamed Seminar Room', $data['name']);
    }

    public function test_edit_room_as_member_only_returns_403(): void
    {
        $id = $this->getRoomId('T9', '301');
        $body = ['name' => 'Hacked Room', 'code' => '301', 'isPrivate' => true];
        $this->client->request('PUT', "/api/room/$id", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    // ── DELETE ───────────────────────────────────────────────────────────────────

    public function test_delete_room_as_admin(): void
    {
        $id = $this->getRoomId('TK', 'BS');
        $this->client->request('DELETE', "/api/room/$id", [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Room::class, $id));
    }

    public function test_delete_room_as_room_admin_returns_403(): void
    {
        // jan.dvorak is room admin but not super admin; DELETE requires ROLE_SUPER_ADMIN
        $id = $this->getRoomId('TH:A', '9105');
        $this->client->request('DELETE', "/api/room/$id", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(403);
    }

    // ── ACCESS CHECK ─────────────────────────────────────────────────────────────

    public function test_access_check_locked_room_returns_false(): void
    {
        $id = $this->getRoomId('T9', '301');
        $this->client->request('GET', "/api/room/$id/access", [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $this->assertFalse($data['hasAccess']);
    }

    public function test_access_check_unlocked_room_member_returns_true(): void
    {
        $id = $this->getRoomId('T9', '301');
        $this->forceRoomLockState($id, Room::LOCK_STATE_UNLOCKED);
        $this->client->request('GET', "/api/room/$id/access", [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $this->assertTrue($data['hasAccess']);
    }

    public function test_access_check_unauthenticated_returns_false(): void
    {
        $id = $this->getRoomId('TK', 'BS');
        $this->client->request('GET', "/api/room/$id/access");
        $data = $this->assertJsonResponse(200);
        $this->assertFalse($data['hasAccess']);
    }

    public function test_access_check_creates_access_log_entry(): void
    {
        $id = $this->getRoomId('T9', '301');
        $countBefore = (int) $this->em->createQueryBuilder()
            ->select('COUNT(l.id)')->from(AccessLog::class, 'l')
            ->getQuery()->getSingleScalarResult();

        $this->client->request('GET', "/api/room/$id/access", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(200);

        $this->em->clear();
        $countAfter = (int) $this->em->createQueryBuilder()
            ->select('COUNT(l.id)')->from(AccessLog::class, 'l')
            ->getQuery()->getSingleScalarResult();
        $this->assertSame($countBefore + 1, $countAfter);
    }

    public function test_access_check_active_reservation_holder_gets_access(): void
    {
        $roomId = $this->getRoomId('TH:A', '9101');
        $reservationId = $this->getReservationId('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservationId, Reservation::STATUS_ACTIVE);
        $this->forceRoomLockState($roomId, Room::LOCK_STATE_UNLOCKED);

        $ondrejToken = $this->getTokenFor('ondrej.sedlacek');
        $this->client->request('GET', "/api/room/$roomId/access", [], [], $this->makeAuthHeaders($ondrejToken));
        $data = $this->assertJsonResponse(200);
        $this->assertTrue($data['hasAccess']);
    }

    // ── TOGGLE LOCK ───────────────────────────────────────────────────────────────

    public function test_toggle_lock_as_room_admin_unlocks_then_locks(): void
    {
        $id = $this->getRoomId('TH:A', '9105');
        $this->client->request('PATCH', "/api/room/$id/toggleLock", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $data = $this->assertJsonResponse(200);
        $this->assertStringContainsStringIgnoringCase('unlocked', $data['message']);

        $this->client->request('PATCH', "/api/room/$id/toggleLock", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $data = $this->assertJsonResponse(200);
        $this->assertStringContainsStringIgnoringCase('locked', $data['message']);
    }

    public function test_toggle_lock_as_non_admin_returns_403(): void
    {
        $id = $this->getRoomId('TH:A', '9105');
        $this->client->request('PATCH', "/api/room/$id/toggleLock", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    public function test_toggle_lock_as_active_reservation_holder(): void
    {
        $roomId = $this->getRoomId('TH:A', '9101');
        $reservationId = $this->getReservationId('Bachelor Thesis Consultation');
        $this->forceReservationStatus($reservationId, Reservation::STATUS_ACTIVE);

        $ondrejToken = $this->getTokenFor('ondrej.sedlacek');
        $this->client->request('PATCH', "/api/room/$roomId/toggleLock", [], [], $this->makeAuthHeaders($ondrejToken));
        $this->assertStatusCode(200);
    }

    public function test_toggle_lock_approved_not_active_reservation_holder_returns_403(): void
    {
        // r4 is APPROVED (not active) → ondrej cannot toggle lock
        $roomId = $this->getRoomId('TH:A', '9101');
        $ondrejToken = $this->getTokenFor('ondrej.sedlacek');
        $this->client->request('PATCH', "/api/room/$roomId/toggleLock", [], [], $this->makeAuthHeaders($ondrejToken));
        $this->assertStatusCode(403);
    }
}
