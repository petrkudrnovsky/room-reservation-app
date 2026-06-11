<?php

namespace App\Tests\Voter;

use App\Entity\AppUser;
use App\Entity\Group;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Service\ReservationManagerInterface;
use App\Voter\GroupVoter;
use App\Voter\ReservationVoter;
use App\Voter\RoomVoter;
use App\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VoterTest extends TestCase
{
    private ReservationManagerInterface $reservationManager;
    private RoomVoter $roomVoter;
    private ReservationVoter $reservationVoter;
    private GroupVoter $groupVoter;
    private UserVoter $userVoter;

    protected function setUp(): void
    {
        $this->reservationManager = $this->createMock(ReservationManagerInterface::class);
        $this->roomVoter          = new RoomVoter($this->reservationManager);
        $this->reservationVoter   = new ReservationVoter();
        $this->groupVoter         = new GroupVoter();
        $this->userVoter          = new UserVoter();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function vote(Voter $voter, string $attr, mixed $subject, ?AppUser $user): int
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $voter->vote($token, $subject, [$attr]);
    }

    private function makeUser(bool $superAdmin = false): AppUser
    {
        $user = new AppUser();
        if ($superAdmin) {
            $user->addRole('ROLE_SUPER_ADMIN');
        }
        return $user;
    }

    private function setId(AppUser $user, int $id): void
    {
        $ref = new \ReflectionProperty(AppUser::class, 'id');
        $ref->setValue($user, $id);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // RoomVoter
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_room_view_index_always_granted(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_INDEX, null, null));
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_INDEX, null, $this->makeUser()));
    }

    public function test_room_view_detail_non_private_granted_without_auth(): void
    {
        $room = new Room();
        $room->setIsPrivate(false);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, null));
    }

    public function test_room_view_detail_private_room_denied_without_auth(): void
    {
        $room = new Room();
        $room->setIsPrivate(true);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, null));
    }

    public function test_room_view_detail_granted_for_super_admin(): void
    {
        $this->reservationManager->method('hasUserCurrentOrFutureReservations')->willReturn(false);
        $room = new Room();
        $room->setIsPrivate(true);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $this->makeUser(true)));
    }

    public function test_room_view_detail_granted_for_room_member(): void
    {
        $this->reservationManager->method('hasUserCurrentOrFutureReservations')->willReturn(false);
        $user = $this->makeUser();
        $room = new Room();
        $room->setIsPrivate(true);
        $room->addMember($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $user));
    }

    public function test_room_view_detail_granted_for_room_admin(): void
    {
        $this->reservationManager->method('hasUserCurrentOrFutureReservations')->willReturn(false);
        $user = $this->makeUser();
        $room = new Room();
        $room->setIsPrivate(true);
        $room->addAdmin($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $user));
    }

    public function test_room_view_detail_granted_for_group_member_of_owning_group(): void
    {
        $this->reservationManager->method('hasUserCurrentOrFutureReservations')->willReturn(false);
        $user = $this->makeUser();
        $group = new Group();
        $room = new Room();
        $room->setIsPrivate(true);
        $room->addOwningGroup($group);
        $user->addMemberGroup($group); // use owning-side of the inverse relation so getMemberGroups() is updated
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $user));
    }

    public function test_room_view_detail_granted_when_user_has_future_reservation(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->setIsPrivate(true);
        $this->reservationManager->expects($this->once())
            ->method('hasUserCurrentOrFutureReservations')
            ->with($room, $user)
            ->willReturn(true);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $user));
    }

    public function test_room_view_detail_denied_for_unrelated_user(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->setIsPrivate(true);
        $this->reservationManager->method('hasUserCurrentOrFutureReservations')->willReturn(false);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::VIEW_DETAIL, $room, $user));
    }

    public function test_room_create_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::CREATE, null, $this->makeUser(true)));
    }

    public function test_room_create_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::CREATE, null, $this->makeUser()));
    }

    public function test_room_edit_granted_for_room_admin(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->addAdmin($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::EDIT, $room, $user));
    }

    public function test_room_edit_granted_for_super_admin(): void
    {
        $room = new Room();
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::EDIT, $room, $this->makeUser(true)));
    }

    public function test_room_edit_denied_for_member_only(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->addMember($user);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::EDIT, $room, $user));
    }

    public function test_room_delete_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::DELETE, new Room(), $this->makeUser(true)));
    }

    public function test_room_delete_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::DELETE, new Room(), $this->makeUser()));
    }

    public function test_room_can_toggle_lock_granted_for_room_admin(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->addAdmin($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::CAN_TOGGLE_LOCK, $room, $user));
    }

    public function test_room_can_toggle_lock_granted_for_active_reservation_holder(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_ACTIVE);
        $reservation->setReservedFor($user);
        $room = new Room();
        $room->addReservation($reservation);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->roomVoter, RoomVoter::CAN_TOGGLE_LOCK, $room, $user));
    }

    public function test_room_can_toggle_lock_denied_for_approved_not_active_holder(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_APPROVED);
        $reservation->setReservedFor($user);
        $room = new Room();
        $room->addReservation($reservation);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->roomVoter, RoomVoter::CAN_TOGGLE_LOCK, $room, $user));
    }

    public function test_room_voter_abstains_for_unsupported_attribute(): void
    {
        $this->assertSame(Voter::ACCESS_ABSTAIN, $this->vote($this->roomVoter, 'unsupported_attr', new Room(), $this->makeUser()));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ReservationVoter
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_reservation_view_index_all_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_INDEX_ALL, null, $this->makeUser(true)));
    }

    public function test_reservation_view_index_all_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_INDEX_ALL, null, $this->makeUser()));
    }

    public function test_reservation_create_granted_for_room_member(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->addMember($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CREATE, $room, $user));
    }

    public function test_reservation_create_granted_for_room_admin(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $room->addAdmin($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CREATE, $room, $user));
    }

    public function test_reservation_create_granted_for_group_member_of_owning_group(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $room = new Room();
        $room->addOwningGroup($group);
        $group->addMember($user);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CREATE, $room, $user));
    }

    public function test_reservation_create_denied_for_unrelated_user(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::CREATE, $room, $user));
    }

    public function test_reservation_create_granted_for_super_admin(): void
    {
        $room = new Room();
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CREATE, $room, $this->makeUser(true)));
    }

    public function test_reservation_view_detail_granted_for_owner(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setReservedFor($user);
        $reservation->setRoom(new Room());
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_DETAIL, $reservation, $user));
    }

    public function test_reservation_view_detail_granted_for_visitor(): void
    {
        $visitor = $this->makeUser();
        $room = new Room();
        $reservation = new Reservation();
        $reservation->setReservedFor($this->makeUser());
        $reservation->addVisitor($visitor);
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_DETAIL, $reservation, $visitor));
    }

    public function test_reservation_view_detail_granted_for_room_admin(): void
    {
        $admin = $this->makeUser();
        $room = new Room();
        $room->addAdmin($admin);
        $reservation = new Reservation();
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_DETAIL, $reservation, $admin));
    }

    public function test_reservation_view_detail_denied_for_unrelated_user(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $reservation = new Reservation();
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::VIEW_DETAIL, $reservation, $user));
    }

    public function test_reservation_edit_granted_for_pending_owner(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_PENDING);
        $reservation->setReservedFor($user);
        $reservation->setRoom(new Room());
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::EDIT, $reservation, $user));
    }

    public function test_reservation_edit_denied_for_approved_owner(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_APPROVED);
        $reservation->setReservedFor($user);
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::EDIT, $reservation, $user));
    }

    public function test_reservation_edit_granted_for_room_admin(): void
    {
        $admin = $this->makeUser();
        $room = new Room();
        $room->addAdmin($admin);
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_APPROVED);
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::EDIT, $reservation, $admin));
    }

    public function test_reservation_delete_granted_for_owner(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setReservedFor($user);
        $reservation->setRoom(new Room());
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::DELETE, $reservation, $user));
    }

    public function test_reservation_delete_denied_for_non_owner_non_admin(): void
    {
        $user = $this->makeUser();
        $reservation = new Reservation();
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom(new Room());
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::DELETE, $reservation, $user));
    }

    public function test_reservation_approve_granted_for_room_admin(): void
    {
        $admin = $this->makeUser();
        $room = new Room();
        $room->addAdmin($admin);
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_PENDING);
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CAN_APPROVE, $reservation, $admin));
    }

    public function test_reservation_approve_denied_for_owner_only(): void
    {
        $user = $this->makeUser();
        $room = new Room();
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_PENDING);
        $reservation->setReservedFor($user);
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->reservationVoter, ReservationVoter::CAN_APPROVE, $reservation, $user));
    }

    public function test_reservation_reject_granted_for_room_admin(): void
    {
        $admin = $this->makeUser();
        $room = new Room();
        $room->addAdmin($admin);
        $reservation = new Reservation();
        $reservation->setStatus(Reservation::STATUS_PENDING);
        $reservation->setReservedFor($this->makeUser());
        $reservation->setRoom($room);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->reservationVoter, ReservationVoter::CAN_REJECT, $reservation, $admin));
    }

    public function test_reservation_voter_abstains_for_unsupported_attribute(): void
    {
        $this->assertSame(Voter::ACCESS_ABSTAIN, $this->vote($this->reservationVoter, 'unsupported', new Reservation(), $this->makeUser()));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GroupVoter
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_group_view_index_granted_for_authenticated_user(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::VIEW_INDEX, null, $this->makeUser()));
    }

    public function test_group_view_index_denied_for_null_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->groupVoter, GroupVoter::VIEW_INDEX, null, null));
    }

    public function test_group_view_detail_granted_for_member(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $user->addMemberGroup($group); // updates AppUser::memberGroups which the voter checks
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::VIEW_DETAIL, $group, $user));
    }

    public function test_group_view_detail_granted_for_admin(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $user->addAdminGroup($group); // updates AppUser::adminGroups which the voter checks
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::VIEW_DETAIL, $group, $user));
    }

    public function test_group_view_detail_denied_for_non_member(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->groupVoter, GroupVoter::VIEW_DETAIL, $group, $user));
    }

    public function test_group_view_detail_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::VIEW_DETAIL, new Group(), $this->makeUser(true)));
    }

    public function test_group_create_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::CREATE, null, $this->makeUser(true)));
    }

    public function test_group_create_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->groupVoter, GroupVoter::CREATE, null, $this->makeUser()));
    }

    public function test_group_edit_granted_for_group_admin(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $user->addAdminGroup($group); // updates AppUser::adminGroups which the voter checks
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::EDIT, $group, $user));
    }

    public function test_group_edit_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::EDIT, new Group(), $this->makeUser(true)));
    }

    public function test_group_edit_denied_for_group_member_only(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $group->addMember($user);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->groupVoter, GroupVoter::EDIT, $group, $user));
    }

    public function test_group_delete_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->groupVoter, GroupVoter::DELETE, new Group(), $this->makeUser(true)));
    }

    public function test_group_delete_denied_for_group_admin(): void
    {
        $user = $this->makeUser();
        $group = new Group();
        $group->addAdmin($user);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->groupVoter, GroupVoter::DELETE, $group, $user));
    }

    public function test_group_voter_abstains_for_unsupported_attribute(): void
    {
        $this->assertSame(Voter::ACCESS_ABSTAIN, $this->vote($this->groupVoter, 'unsupported', new Group(), $this->makeUser()));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // UserVoter
    // ═══════════════════════════════════════════════════════════════════════════

    public function test_user_view_index_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::VIEW_INDEX, null, $this->makeUser(true)));
    }

    public function test_user_view_index_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->userVoter, UserVoter::VIEW_INDEX, null, $this->makeUser()));
    }

    public function test_user_view_detail_granted_for_self(): void
    {
        $user = $this->makeUser();
        $this->setId($user, 42);
        $same = $this->makeUser();
        $this->setId($same, 42);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::VIEW_DETAIL, $same, $user));
    }

    public function test_user_view_detail_granted_for_super_admin_viewing_other(): void
    {
        $admin = $this->makeUser(true);
        $this->setId($admin, 1);
        $other = $this->makeUser();
        $this->setId($other, 2);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::VIEW_DETAIL, $other, $admin));
    }

    public function test_user_view_detail_denied_for_regular_user_viewing_other(): void
    {
        $user = $this->makeUser();
        $this->setId($user, 1);
        $other = $this->makeUser();
        $this->setId($other, 2);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->userVoter, UserVoter::VIEW_DETAIL, $other, $user));
    }

    public function test_user_edit_granted_for_self(): void
    {
        $user = $this->makeUser();
        $this->setId($user, 5);
        $same = $this->makeUser();
        $this->setId($same, 5);
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::EDIT, $same, $user));
    }

    public function test_user_edit_denied_for_regular_user_editing_other(): void
    {
        $user = $this->makeUser();
        $this->setId($user, 1);
        $other = $this->makeUser();
        $this->setId($other, 2);
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->userVoter, UserVoter::EDIT, $other, $user));
    }

    public function test_user_delete_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::DELETE, new AppUser(), $this->makeUser(true)));
    }

    public function test_user_delete_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->userVoter, UserVoter::DELETE, new AppUser(), $this->makeUser()));
    }

    public function test_user_create_granted_for_super_admin(): void
    {
        $this->assertSame(Voter::ACCESS_GRANTED, $this->vote($this->userVoter, UserVoter::CREATE, null, $this->makeUser(true)));
    }

    public function test_user_create_denied_for_regular_user(): void
    {
        $this->assertSame(Voter::ACCESS_DENIED, $this->vote($this->userVoter, UserVoter::CREATE, null, $this->makeUser()));
    }

    public function test_user_voter_abstains_for_unsupported_attribute(): void
    {
        $this->assertSame(Voter::ACCESS_ABSTAIN, $this->vote($this->userVoter, 'unsupported', new AppUser(), $this->makeUser()));
    }
}
