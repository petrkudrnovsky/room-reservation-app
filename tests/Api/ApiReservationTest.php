<?php

namespace App\Tests\Api;

use App\Entity\Reservation;

class ApiReservationTest extends ApiTestCase
{
    private string $adminToken;
    private string $dvorakToken;
    private string $tomasToken;
    private string $simunekToken;
    private string $ondrejToken;
    private string $rihovanToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken   = $this->getTokenFor('admin', 'admin');
        $this->dvorakToken  = $this->getTokenFor('jan.dvorak');
        $this->tomasToken   = $this->getTokenFor('tomas.blaha');
        $this->simunekToken = $this->getTokenFor('jakub.simunek');
        $this->ondrejToken  = $this->getTokenFor('ondrej.sedlacek');
        $this->rihovanToken = $this->getTokenFor('anezka.rihova');
    }

    // ── LIST ────────────────────────────────────────────────────────────────────

    public function test_list_reservations_as_admin_returns_all(): void
    {
        $this->client->request('GET', '/api/reservation', [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertArrayHasKey('reservations', $data);
        $this->assertCount(8, $data['reservations']);
    }

    public function test_list_reservations_as_regular_user_returns_403(): void
    {
        $this->client->request('GET', '/api/reservation', [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    public function test_list_reservations_filter_by_status_pending(): void
    {
        $this->client->request('GET', '/api/reservation?status=pending', [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertCount(3, $data['reservations']);
        foreach ($data['reservations'] as $r) {
            $this->assertSame('pending', $r['status']);
        }
    }

    public function test_list_reservations_filter_by_room(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $this->client->request('GET', "/api/reservation?room=$roomId", [], [], $this->makeAuthHeaders($this->adminToken));
        $data = $this->assertJsonResponse(200);
        $this->assertNotEmpty($data['reservations']);
        foreach ($data['reservations'] as $r) {
            $this->assertStringContainsString("/api/room/$roomId", $r['roomUrl']);
        }
    }

    // ── DETAIL ──────────────────────────────────────────────────────────────────

    public function test_detail_as_owner(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('GET', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->simunekToken));
        $data = $this->assertJsonResponse(200);
        $this->assertSame('PA1 Practice Session', $data['title']);
        $this->assertSame('pending', $data['status']);
    }

    public function test_detail_as_visitor(): void
    {
        // r7 (Workshop: Applied Cryptography) has michal.holub as visitor
        $id = $this->getReservationId('Workshop: Applied Cryptography');
        $michalToken = $this->getTokenFor('michal.holub');
        $this->client->request('GET', "/api/reservation/$id", [], [], $this->makeAuthHeaders($michalToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_as_room_admin(): void
    {
        // r1 is in T9:301; tomas.blaha is admin of T9:301
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('GET', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->tomasToken));
        $this->assertStatusCode(200);
    }

    public function test_detail_as_unrelated_user_returns_403(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('GET', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->rihovanToken));
        $this->assertStatusCode(403);
    }

    public function test_detail_nonexistent_reservation_returns_404(): void
    {
        $this->client->request('GET', '/api/reservation/99999', [], [], $this->makeAuthHeaders($this->adminToken));
        $this->assertStatusCode(404);
    }

    // ── CREATE ──────────────────────────────────────────────────────────────────

    public function test_create_reservation_as_room_member(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('jakub.simunek');
        $body = [
            'title'         => 'New Test Session',
            'startDatetime' => $this->futureDatetime('+3 days', '10:00'),
            'endDatetime'   => $this->futureDatetime('+3 days', '12:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('POST', '/api/reservation', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('New Test Session', $data['title']);
        $this->assertSame('pending', $data['status']);
    }

    public function test_create_reservation_as_group_member(): void
    {
        // ondrej.sedlacek is KSI member; KSI owns TH:A-9101
        $roomId = $this->getRoomId('TH:A', '9101');
        $userId = $this->getUserId('ondrej.sedlacek');
        $body = [
            'title'         => 'Ondrej Session',
            'startDatetime' => $this->futureDatetime('+4 days', '14:00'),
            'endDatetime'   => $this->futureDatetime('+4 days', '16:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('POST', '/api/reservation', [], [], $this->makeAuthHeaders($this->ondrejToken), json_encode($body));
        $this->assertStatusCode(201);
    }

    public function test_create_reservation_for_non_member_room_returns_403(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('anezka.rihova');
        $body = [
            'title'         => 'Forbidden Session',
            'startDatetime' => $this->futureDatetime('+3 days', '10:00'),
            'endDatetime'   => $this->futureDatetime('+3 days', '12:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('POST', '/api/reservation', [], [], $this->makeAuthHeaders($this->rihovanToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_create_reservation_with_past_startdate_returns_400(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('jakub.simunek');
        $body = [
            'title'         => 'Past Session',
            'startDatetime' => '2020-01-01 10:00:00',
            'endDatetime'   => '2020-01-01 12:00:00',
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('POST', '/api/reservation', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(400);
    }

    public function test_create_reservation_missing_title_returns_400(): void
    {
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('jakub.simunek');
        $body = [
            'startDatetime' => $this->futureDatetime('+3 days', '10:00'),
            'endDatetime'   => $this->futureDatetime('+3 days', '12:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('POST', '/api/reservation', [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $this->assertStatusCode(400);
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────────

    public function test_update_pending_reservation_as_owner(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('jakub.simunek');
        $body = [
            'title'         => 'PA1 Updated Title',
            'startDatetime' => $this->futureDatetime('+7 days', '10:00'),
            'endDatetime'   => $this->futureDatetime('+7 days', '12:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('PUT', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('PA1 Updated Title', $data['title']);
    }

    public function test_update_approved_reservation_as_owner_returns_403(): void
    {
        // r3 is approved; eva.bartosova is the owner; owners cannot edit approved reservations
        // Use +10 days to avoid overlap with r3's own time slot (+1 day 09:00-11:00)
        $id = $this->getReservationId('KSI Department Seminar');
        $roomId = $this->getRoomId('TH:A', '9105');
        $userId = $this->getUserId('eva.bartosova');
        $evaToken = $this->getTokenFor('eva.bartosova');
        $body = [
            'title'         => 'Updated Seminar',
            'startDatetime' => $this->futureDatetime('+10 days', '09:00'),
            'endDatetime'   => $this->futureDatetime('+10 days', '11:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('PUT', "/api/reservation/$id", [], [], $this->makeAuthHeaders($evaToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    public function test_update_reservation_as_room_admin_regardless_of_status(): void
    {
        // r3 is approved in TH:A-9105; jan.dvorak is admin of TH:A-9105
        $id = $this->getReservationId('KSI Department Seminar');
        $roomId = $this->getRoomId('TH:A', '9105');
        $userId = $this->getUserId('eva.bartosova');
        $body = [
            'title'         => 'Seminar Updated by Admin',
            'startDatetime' => $this->futureDatetime('+2 days', '09:00'),
            'endDatetime'   => $this->futureDatetime('+2 days', '11:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('PUT', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->dvorakToken), json_encode($body));
        $data = $this->assertJsonResponse(201);
        $this->assertSame('Seminar Updated by Admin', $data['title']);
    }

    public function test_update_reservation_as_unrelated_returns_403(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $roomId = $this->getRoomId('T9', '301');
        $userId = $this->getUserId('jakub.simunek');
        $body = [
            'title'         => 'Hacked',
            'startDatetime' => $this->futureDatetime('+7 days', '10:00'),
            'endDatetime'   => $this->futureDatetime('+7 days', '12:00'),
            'room'          => $roomId,
            'reservedFor'   => $userId,
        ];
        $this->client->request('PUT', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->rihovanToken), json_encode($body));
        $this->assertStatusCode(403);
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────

    public function test_delete_reservation_as_owner(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('DELETE', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Reservation::class, $id));
    }

    public function test_delete_reservation_as_room_admin(): void
    {
        // r1 is in T9:301; tomas.blaha is admin of T9:301
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('DELETE', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->tomasToken));
        $this->assertStatusCode(204);
    }

    public function test_delete_reservation_as_unrelated_returns_403(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('DELETE', "/api/reservation/$id", [], [], $this->makeAuthHeaders($this->rihovanToken));
        $this->assertStatusCode(403);
    }

    // ── APPROVE / REJECT ────────────────────────────────────────────────────────

    public function test_approve_pending_reservation_as_room_admin(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('PATCH', "/api/reservation/$id/approve", [], [], $this->makeAuthHeaders($this->tomasToken));
        $data = $this->assertJsonResponse(200);
        $this->assertSame('approved', $data['status']);
        $this->assertNotNull($data['approvedByUrl']);
    }

    public function test_approve_already_approved_reservation_returns_400(): void
    {
        // r3 is already approved; jan.dvorak is admin of TH:A-9105
        $id = $this->getReservationId('KSI Department Seminar');
        $this->client->request('PATCH', "/api/reservation/$id/approve", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(400);
    }

    public function test_approve_as_non_admin_returns_403(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('PATCH', "/api/reservation/$id/approve", [], [], $this->makeAuthHeaders($this->simunekToken));
        $this->assertStatusCode(403);
    }

    public function test_reject_pending_reservation_as_room_admin(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $this->client->request('PATCH', "/api/reservation/$id/reject", [], [], $this->makeAuthHeaders($this->tomasToken));
        $data = $this->assertJsonResponse(200);
        $this->assertSame('rejected', $data['status']);
    }

    public function test_reject_approved_reservation_returns_400(): void
    {
        $id = $this->getReservationId('KSI Department Seminar');
        $this->client->request('PATCH', "/api/reservation/$id/reject", [], [], $this->makeAuthHeaders($this->dvorakToken));
        $this->assertStatusCode(400);
    }

    // ── VISITORS ────────────────────────────────────────────────────────────────

    public function test_add_visitor_to_pending_reservation_as_owner(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $anezkaId = $this->getUserId('anezka.rihova');
        $body = ['id' => $anezkaId];
        $this->client->request('PATCH', "/api/reservation/$id/visitor", [], [], $this->makeAuthHeaders($this->simunekToken), json_encode($body));
        $data = $this->assertJsonResponse(200);
        $this->assertCount(1, $data['visitorsUrls']);
    }

    public function test_remove_visitor_from_reservation(): void
    {
        // r7 (Workshop: Applied Cryptography) has michal.holub as visitor; petra.novakova is admin of TH:A-1154
        $id = $this->getReservationId('Workshop: Applied Cryptography');
        $michalId = $this->getUserId('michal.holub');
        $petraToken = $this->getTokenFor('petra.novakova');
        $this->client->request('DELETE', "/api/reservation/$id/visitor/$michalId", [], [], $this->makeAuthHeaders($petraToken));
        $this->assertStatusCode(204);
    }

    public function test_add_visitor_as_unrelated_returns_403(): void
    {
        $id = $this->getReservationId('PA1 Practice Session');
        $body = ['id' => $this->getUserId('david.mares')];
        $this->client->request('PATCH', "/api/reservation/$id/visitor", [], [], $this->makeAuthHeaders($this->rihovanToken), json_encode($body));
        $this->assertStatusCode(403);
    }
}
