# Normalized Systems Theory – Code Analysis

This document maps the codebase against the four NS theorems, identifies violations with their
combinatorial effects, and classifies all major classes by NS element type. It covers only the
analysis; the refactoring is a separate deliverable.

---

## 1. NS Element Classification

| Class | Assigned Element Type | Notes |
|---|---|---|
| `Entity/AppUser` | Data | Pure data structure with relationships |
| `Entity/Building` | Data | Pure data structure |
| `Entity/Group` | Data | Pure data structure with relationships |
| `Entity/Room` | Data | Data + computed display (`getCodeName`); `isLocked` state embedded here |
| `Entity/Reservation` | Data | Data + lifecycle constants; missing states (see §5) |
| `Repository/AppUserRepository` | Connector | Database access adapter |
| `Repository/BuildingRepository` | Connector | Database access adapter |
| `Repository/GroupRepository` | Connector | Database access adapter |
| `Repository/ReservationRepository` | Connector | Database access adapter |
| `Repository/RoomRepository` | Connector | Database access adapter |
| `Service/AppUserManager` | Task (mixed) | Multiple unrelated tasks bundled (see §3.1) |
| `Service/GroupManager` | Task (mixed) | Multiple tasks bundled (see §3.2) |
| `Service/ReservationManager` | Task (mixed) | CRUD + business logic + state transition bundled |
| `Service/RoomManager` | Task (mixed) | CRUD + business rules + lock management bundled (see §3.3) |
| `Controller/ReservationController` | Flow | Orchestrates reservation lifecycle; embeds business logic (see §3.4) |
| `Controller/RoomController (web)` | Flow | Thin, mostly acceptable |
| `Controller/AppUserController (web)` | Flow | Thin, mostly acceptable |
| `Controller/GroupController (web)` | Flow | Thin, mostly acceptable |
| `Api/Controller/ReservationController` | Flow | Orchestrates workflow; embeds approval business logic (see §3.5) |
| `Api/Controller/RoomController` | Flow | Embeds access-verification logic (see §3.6) |
| `Api/Controller/AppUserController` | Flow + Connector | URL generation mixed in |
| `Api/Controller/GroupController` | Flow + Connector | Authorization logic mixed in |
| `Voter/RoomVoter` | Task | Authorization rules; severely duplicated internals (see §3.7) |
| `Voter/ReservationVoter` | Task | Authorization rules; duplicated internals |
| `Voter/UserVoter` | Task | Authorization rules |
| `Voter/GroupVoter` | Task | Authorization rules |
| `Form/AppUserType` | Connector | UI binding; conditional fields driven by role are a concern mix |
| `Form/GroupType` | Connector | UI binding |
| `Form/ReservationType` | Connector | UI binding |
| `Form/RoomType` | Connector | UI binding |
| `Form/Model/AppUserTypeModel` | Data + Task | Data bag that performs entity conversion (`toEntity`/`fromEntity`) |
| `Form/Model/GroupTypeModel` | Data + Task | Same issue |
| `Form/Model/ReservationTypeModel` | Data + Task | Same issue; also holds validation metadata |
| `Form/Model/RoomTypeModel` | Data + Task | Same issue |
| `Api/Model/AppUserInput` | Data + Task | DTO that calls services inside `toEntity()` |
| `Api/Model/AppUserOutput` | Data + Connector | DTO; URL serialization mixed in |
| `Api/Model/GroupInput` | Data + Task | DTO calling services in `toEntity()` |
| `Api/Model/GroupOutput` | Data + Connector | URLs mixed in |
| `Api/Model/ReservationInput` | Data + Task | DTO calling services in `toEntity()` |
| `Api/Model/ReservationOutput` | Data + Connector | |
| `Api/Model/RoomInput` | Data + Task | DTO calling services in `toEntity()` |
| `Api/Model/RoomOutput` | Data + Connector | |
| `Form/Constraints/UniqueUsername(Validator)` | Task | Validation concern, acceptably isolated |
| `Form/Constraints/Timespan(Validator)` | Task | Validation concern, acceptably isolated |
| `Form/Constraints/RoomAvailability(Validator)` | Task | Validation concern; duplicated in two locations (see §6.5) |

---

## 2. Summary of Violations by Theorem

| # | Theorem | Severity | Count |
|---|---|---|---|
| 3.1–3.7 | Theorem 1 – Separation of Concerns | High | 7 findings |
| 4.1–4.5 | Theorem 2 – Data Version Transparency | High | 5 findings |
| 5.1–5.5 | Theorem 3 – Action Version Transparency | High | 5 findings |
| 6.1–6.5 | Theorem 4 – Separation of States | High | 5 findings |

---

## 3. Theorem 1 – Separation of Concerns

> An action entity may contain only a single task or change driver.

### 3.1 `AppUserManager` – Multiple Unrelated Tasks

**File:** [src/Service/AppUserManager.php](src/Service/AppUserManager.php)

The class bundles at least four distinct change drivers:

| Method | Change Driver |
|---|---|
| `saveToDatabase`, `removeFromDatabase`, `getAppUserById`, `getAppUserByUsername` | User CRUD lifecycle |
| `findAppUsersByFilters` | User search/filtering rules |
| `addMembers`, `addAdmins` | Group/Room membership management |
| `addApprovedReservation`, `addReservedReservation` | Reservation–user association |
| `isUniqueUsername` | Validation support |

**Combinatorial effect:** Adding a new filter criterion to user search requires modifying
`AppUserManager`. Adding a new membership type (e.g., "deputy admin") also requires modifying
`AppUserManager`. Both change drivers require touching the same class independently.

`removeFromDatabase` (lines 29–41) manually removes the user's reservations before removing the
user. This embeds reservation lifecycle management inside a user manager — two completely independent
change drivers co-located in one method.

---

### 3.2 `GroupManager` – Membership and Persistence Mixed

**File:** [src/Service/GroupManager.php](src/Service/GroupManager.php)

`addOwningGroups` and `addUserGroups` manage the many-to-many relationships between groups, rooms,
and users. These are not the same concern as persisting a group. A new type of group relationship
would require modifying the same class as a change to how groups are saved or deleted.

---

### 3.3 `RoomManager` – Overloaded With Distinct Responsibilities

**File:** [src/Service/RoomManager.php](src/Service/RoomManager.php)

Eight distinct change drivers are present in one class:

| Method | Change Driver |
|---|---|
| `saveToDatabase`, `deleteFromDatabase`, `getRoomById` | Room CRUD |
| `findRoomsByFilters` | Room search/filtering rules |
| `addRooms`, `addUserRooms` | Room–Group and Room–User relationship management |
| `hasUserCurrentOrFutureReservations`, `hasApprovedReservation`, `getOngoingReservation`, `isRoomFree` | Reservation-based business rules |
| `getOrderedReservations` | Reservation query / ordering |
| `unlockRoom`, `lockRoom` | Lock/unlock lifecycle |

**Combinatorial effect:** Changing the rule for what constitutes an "ongoing reservation" (e.g.,
adding a grace period) requires modifying `RoomManager`. Changing the lock/unlock mechanism (e.g.,
adding an unlock event log) also requires modifying `RoomManager`. These are completely independent
change drivers in the same class.

`deleteFromDatabase` (lines 29–36) manually iterates and removes reservations — embedding
reservation lifecycle management in a room service.

The parameter name `bool $false` in `addUserRooms` (line 150) is a symptom: a boolean flag is
being used to select between two different operations (admin assignment vs. member assignment) inside
a single method. These are two distinct actions conflated by a flag.

---

### 3.4 Web `ReservationController::approve` – Business Logic in a Flow Element

**File:** [src/Controller/ReservationController.php](src/Controller/ReservationController.php), lines 124–146

```php
public function approve(Request $request, Reservation $reservation,
    ReservationManager $reservationManager, ReservationRepository $reservationRepository): Response
{
    // ...
    $overlappingReservations = $reservationRepository->findOverlappingReservations(...);
    if(count($overlappingReservations) > 0) { ... }
    $reservation->setStatus(Reservation::STATUS_APPROVED);
    $reservation->setApprovedBy($this->getUser());
    $reservationManager->saveToDatabase($reservation);
    // ...
}
```

The controller directly:
- calls a repository to check overlaps (should be service logic),
- mutates the entity's status and `approvedBy` field (should be an `approveReservation` service method),
- persists.

**Change drivers present:** HTTP flow orchestration, overlap-check business rule,
approval-state-transition rule. Three drivers in one method.

**Combinatorial effect:** If the approval rule changes (e.g., send an email notification on
approval), the controller must be modified. If an API controller also handles approval (it does —
see §3.5), that change must be applied twice, in two separate places.

---

### 3.5 API `ReservationController::approve` and `reject` – Duplicated Business Logic

**File:** [src/Api/Controller/ReservationController.php](src/Api/Controller/ReservationController.php), lines 132–154 and 159–180

The same approval business logic that exists in the web controller is re-implemented here:

```php
$reservation->setStatus(Reservation::STATUS_APPROVED);
$reservation->setApprovedBy($this->getUser());
$reservation = $this->reservationManager->saveToDatabase($reservation);
```

Both the web and API controllers directly set the entity status and `approvedBy`. There is no
`ReservationManager::approve()` method. Any change to the approval transition (e.g., adding
`approvedAt` timestamp, sending a notification, recording an audit entry) must be applied in both
controllers independently.

**Combinatorial effect:** n controllers × 1 approval business rule change = n modifications. With
two controllers today it is 2; every new client (mobile API, CLI, etc.) adds another.

---

### 3.6 API `RoomController::hasAccess` – Access Verification Logic Embedded in Controller

**File:** [src/Api/Controller/RoomController.php](src/Api/Controller/RoomController.php), lines 138–169

According to the system description, access verification logic (the core of the door API) should be
encapsulated as its own task. Instead it is implemented inline in the controller method:

```php
public function hasAccess(int $id): bool
{
    $room = $this->findOrFail($id);
    if ($room->isIsLocked()) { return false; }

    $ongoingReservation = $this->roomManager->getOngoingReservation($room);
    if (!$ongoingReservation) {
        if ($this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
            || $room->getMembers()->contains($user)
            || $room->getOwningGroups()->exists(...)
        ) { return true; }
    } else {
        if ($this->isGranted(RoomVoter::HAS_FULL_ACCESS_TO_ROOM, $room)
            || $ongoingReservation->getReservedFor() === $user
            || $ongoingReservation->getVisitors()->contains($user)
        ) { return true; }
    }
    return false;
}
```

This mixes HTTP handling with multi-step access-check business logic. The rule "who may access a
room" is a distinct change driver: it may need to change independently of how the HTTP response is
formatted, how the room is fetched, or how authorization is enforced.

---

### 3.7 `RoomVoter` – Admin-Check Pattern Repeated in Six Methods

**File:** [src/Voter/RoomVoter.php](src/Voter/RoomVoter.php)

The following methods each contain an almost identical block:

```php
if (in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles()) ||
    $accessedRoom->getAdmins()->contains($currentUser)) {
    return true;
}
$owningGroups = $accessedRoom->getOwningGroups();
foreach ($owningGroups as $owningGroup) {
    if ($currentUser->getAdminGroups()->contains($owningGroup)) { return true; }
}
```

Present in: `canEdit`, `canEditMembers`, `canViewFullReservations`, `hasFullAccessToRoom`,
`canLock`, `canViewPendingReservations`.

The "is admin of this room (directly or via owning group)" rule is a single change driver copy-pasted
six times. If the admin-check logic changes (e.g., transitive sub-group admin access is added), it
must be updated in six places.

The same pattern is duplicated in `ReservationVoter` for the four methods `canViewDetail`,
`canEdit`, `canDelete`, `canApprove`, `canReject`.

---

## 4. Theorem 2 – Data Version Transparency

> When a data structure changes, modules that consume it must not be affected.
> Pass whole objects (stamp coupling), not individual fields (data coupling).

### 4.1 `AppUserManager::findAppUsersByFilters` – Individual Scalar Parameters

**File:** [src/Service/AppUserManager.php](src/Service/AppUserManager.php), line 63

```php
public function findAppUsersByFilters(
    ?string $username, ?string $name, ?string $email, ?string $phone): array
```

Each filter field is an individual parameter. If a new filter field is added (e.g., filter by role,
by group membership, or by registration date), the method signature must change. Every caller must
be updated — currently the API controller and potentially the web controller.

**Combinatorial effect:** 1 new filter criterion → method signature change → all n callers change.
With 4 existing parameters and 4 callers already in the codebase, adding `$role` requires touching
the method and every call site.

---

### 4.2 `RoomManager::findRoomsByFilters` – Mixed Scalar and Untyped Array

**File:** [src/Service/RoomManager.php](src/Service/RoomManager.php), line 79

```php
public function findRoomsByFilters(
    ?string $name, ?string $code, ?string $buildingCode, $filter): array
```

`$name`, `$code`, and `$buildingCode` are individual scalars while `$filter` is an untyped array.
The API controller builds this array inline with magic string keys (`'owningGroups'`, `'members'`,
`'admins'`). If a key is renamed or a new filter is moved from scalar to array, call sites break
without compile-time or IDE support.

---

### 4.3 `AppUserOutput::fromEntity` – Seven Separate URL Arrays

**File:** [src/Api/Model/AppUserOutput.php](src/Api/Model/AppUserOutput.php), lines 55–64

```php
public static function fromEntity(
    AppUser $appUser,
    array $memberGroupsUrls,
    array $adminGroupsUrls,
    array $memberRoomsUrls,
    array $adminRoomsUrls,
    array $approvedReservationsUrls,
    array $reservationsUrls
): self
```

Six separate URL arrays are passed as individual parameters. Adding a new relationship to
`AppUserOutput` (e.g., `visitingReservationsUrls`) requires changing this method's signature and all
four call sites in `AppUserController` that construct it.

The same structural problem is present in:
- `ReservationOutput::fromEntity` ([src/Api/Model/ReservationOutput.php](src/Api/Model/ReservationOutput.php), line 45) — 4 URL parameters
- `RoomOutput::fromEntity` — 4 URL arrays
- `GroupOutput::fromEntity` — 3 URL arrays

In every case, the "collection of hypermedia links" is an entity-level concept that should be passed
as a single typed object.

---

### 4.4 `ReservationInput::toEntity` – DTO Depends on Services

**File:** [src/Api/Model/ReservationInput.php](src/Api/Model/ReservationInput.php), lines 34–51

```php
public function toEntity(
    RoomManager $roomManager,
    AppUserManager $appUserManager,
    Reservation $reservation = new Reservation()
): Reservation
```

A data transfer object is accepting service objects as parameters. This couples a pure data
structure to the service layer. The DTO now has multiple change drivers: changes to the data schema
and changes to how services are structured both affect it.

The same issue is present in:
- `RoomInput::toEntity(AppUserManager, GroupManager, BuildingRepository, Room)`
- `GroupInput::toEntity(AppUserManager, RoomManager, Group)`
- `Form/Model/ReservationTypeModel::toEntity` (service-free but entity mutation mixed with data)
- `Form/Model/AppUserTypeModel::toEntity` (same)

---

### 4.5 `GroupManager::findGroupsByFilters` – Untyped Filter Array

**File:** [src/Service/GroupManager.php](src/Service/GroupManager.php)

```php
public function findGroupsByFilters(?string $name, $filters): array
```

`$filters` is an untyped parameter (`$filters` without type hint) holding magic-key arrays. The
same concerns as §4.2 apply.

---

## 5. Theorem 3 – Action Version Transparency

> When a new version of an action is introduced, existing callers must not change.
> Use a delegation/versioning pattern; add new implementations alongside old ones.

### 5.1 No Interfaces on Service Classes

None of the four service classes (`AppUserManager`, `GroupManager`, `ReservationManager`,
`RoomManager`) implement an interface. Controllers and other consumers depend directly on the
concrete class. This means:

- It is impossible to introduce an alternative implementation (e.g., a cached version, or a version
  that also sends notifications) without modifying every injection site.
- Changing a method signature in a service requires hunting all callers in the entire codebase.

**Combinatorial effect:** Introducing `ReservationManagerV2` that additionally logs approvals would
require: creating the new class, modifying every place that injects `ReservationManager` to select
between V1 and V2, and adding the selection logic to each call site — because there is no stable
interface or delegator.

---

### 5.2 Approval Logic Not Encapsulated as a Versioned Action

The reservation approval step is:
1. Check for overlapping reservations (web controller only — see §3.4)
2. Set `status = STATUS_APPROVED`
3. Set `approvedBy = $currentUser`
4. Persist

This logic exists inline in:
- Web `ReservationController::approve()` ([src/Controller/ReservationController.php:126](src/Controller/ReservationController.php))
- API `ReservationController::approve()` ([src/Api/Controller/ReservationController.php:132](src/Api/Controller/ReservationController.php))

There is no `ReservationManager::approve(Reservation, AppUser)` method. Adding a new approval
variant (e.g., "auto-approve if the user is a direct room member and the room is free") cannot be
done by adding a new implementation alongside the old one — it requires modifying the two controllers
simultaneously.

---

### 5.3 Boolean Flags as Implicit Version Selectors

Several methods use a boolean flag to select between two distinct operations:

**`RoomManager::addUserRooms`** ([src/Service/RoomManager.php:150](src/Service/RoomManager.php)):
```php
public function addUserRooms(?array $memberRooms, AppUser $appUser, bool $false): void
// $false controls whether to add as admin or as member
```

**`AppUserManager::addMembers` / `addAdmins`** ([src/Service/AppUserManager.php:99–139](src/Service/AppUserManager.php)):
```php
$group !== null ? $group->addMember($member) : $room->addMember($member);
```

**`ReservationManager::addReservations`** ([src/Service/ReservationManager.php:90](src/Service/ReservationManager.php)):
```php
if ($isApproved) {
    $reservation->setApprovedBy($appUser);
} else {
    $reservation->setReservedFor($appUser);
}
```

In all three cases, two semantically different actions are conflated into one method. According to
NS, adding a third variant (e.g., a "co-owner" role) requires modifying the existing method and all
its callers, rather than adding a new versioned action alongside the existing ones.

---

### 5.4 Duplicate Validation Constraints

The `Timespan` and `RoomAvailability` validation constraints are registered in two separate
locations:

- `Form/Model/ReservationTypeModel::loadValidatorMetadata` ([src/Form/Model/ReservationTypeModel.php:69](src/Form/Model/ReservationTypeModel.php))
- `Api/Model/ReservationInput::loadValidatorMetadata` ([src/Api/Model/ReservationInput.php:54](src/Api/Model/ReservationInput.php))

These are independent copies of the same validation action. If the `RoomAvailability` rule
changes (e.g., a buffer time is required between reservations), it must be updated in two DTOs.

---

### 5.5 `ReservationManager::prepareNewReservation` Is a Thin Wrapper

**File:** [src/Service/ReservationManager.php](src/Service/ReservationManager.php), lines 34–38

```php
public function prepareNewReservation(Reservation $reservation): Reservation
{
    $reservation->setStatus(Reservation::STATUS_PENDING);
    return $reservation;
}
```

The method does exactly one thing that callers could do themselves. The status assignment is
performed directly in the API controller (`$reservation->setStatus(Reservation::STATUS_PENDING)`,
line 96) without going through this method, showing the method is not used consistently. If a new
step is added to reservation preparation (e.g., set a creation timestamp), only one of the two paths
would be updated, causing divergence.

---

## 6. Theorem 4 – Separation of States

> Every action should produce a persistent state. Workflows must be asynchronous and stateful,
> with persistent states between actions.

### 6.1 `Reservation` Missing Lifecycle States

**File:** [src/Entity/Reservation.php](src/Entity/Reservation.php), lines 15–17

```php
public const STATUS_PENDING = 'pending';
public const STATUS_APPROVED = 'approved';
public const STATUS_REJECTED = 'rejected';
```

The reservation lifecycle is missing several states that are computed dynamically from timestamps
and other fields:

| Missing State | Currently Computed By |
|---|---|
| `ACTIVE` | `getOngoingReservation()` in `RoomManager` — timestamp range check |
| `EXPIRED` | Not modelled; approved reservations in the past remain `approved` |
| `CANCELLED` | Does not exist; the only option is deletion |

**Combinatorial effect:** Adding a new state (e.g., `ACTIVE`) requires identifying every place that
infers "active" from timestamps and updating those callers. Currently that is `RoomManager` (three
methods), `RoomVoter::canLock`, and `RoomController::hasAccess` — and any new code that reasons
about whether a reservation is currently in progress must also re-implement the same timestamp
comparison.

---

### 6.2 Room Lock State Is a Boolean, Not a Named State

**File:** [src/Entity/Room.php](src/Entity/Room.php), line 47

```php
#[ORM\Column]
private ?bool $isLocked = true;
```

The door control workflow involves:
1. A reservation becomes active.
2. The owner performs a double card swipe (unlock request).
3. All card swipes are granted access.
4. The owner explicitly locks again.

This is a multi-step stateful workflow, but the only persisted state is a boolean. Consequences:

- There is no record of *when* the room was unlocked, *by whom*, or *in the context of which
  reservation*.
- Adding an intermediate state (e.g., `UNLOCKING` for a confirmation step, or `OVERRIDE_UNLOCKED`
  for an admin-forced unlock) requires changing the column type and all code that reads `isIsLocked()`.
- No audit trail is possible.

The CLAUDE.md description mentions a `DoorState` entity — this entity does not exist in the
codebase. The lock state is embedded directly in `Room`, violating separation of states.

---

### 6.3 Synchronous Approval Workflow With No Intermediate State

**File:** [src/Controller/ReservationController.php](src/Controller/ReservationController.php), lines 126–146

The approval action is:
1. Check overlap (in-memory, not persisted)
2. Set status (state transition)
3. Flush

If the overlap check succeeds but the flush fails, no intermediate state was persisted — the action
cannot be retried, resumed, or observed. There is also no `approvedAt` timestamp to record when the
approval decision was made (only `approvedBy` is stored).

An NS-compliant workflow would persist a state after each meaningful step: e.g., after the overlap
check passes, an `APPROVAL_PENDING_WRITE` state could be committed before the final status update,
enabling recovery.

---

### 6.4 Access Verification Is Purely Computational — No Persistent Outcome

**File:** [src/Api/Controller/RoomController.php](src/Api/Controller/RoomController.php), lines 138–169

`RoomController::hasAccess()` computes whether a user may enter a room and returns a boolean. The
decision is never persisted. In a door-control system:

- Access granted / denied events should produce persistent records (audit log).
- The outcome of `hasAccess` is a state that should be observable, resettable, and resumable in
  the event of a hardware failure.

Adding an audit log later would require modifying `hasAccess` and every other code path that makes
an access decision — a combinatorial effect.

---

### 6.5 `isRoomFree` and `getOngoingReservation` Return Computed States

**File:** [src/Service/RoomManager.php](src/Service/RoomManager.php), lines 218–243

```php
public function getOngoingReservation(Room $room): ?Reservation { ... }
public function isRoomFree(Room $room): bool { ... }
```

Both methods scan all reservations and compare timestamps against `new \DateTime()`. The "room is
free" and "reservation is ongoing" states are not persisted — they are computed fresh on every call.

Multiple callers independently depend on these computed states:
- `RoomVoter::canLock` replicates the ongoing-reservation timestamp logic inline (lines 251–258)
- `RoomController::hasAccess` calls `getOngoingReservation`
- `RoomController::list` calls `hasApprovedReservation`

If the definition of "ongoing" changes (e.g., a 15-minute grace period after `endDatetime`), all
these locations must be updated. A persisted `ACTIVE` status on `Reservation` would make this a
single-change operation.

---

## 7. Cross-Cutting Concerns

### 7.1 Persistence Not Encapsulated

All four manager services call `$this->em->persist()` and `$this->em->flush()` directly. The ORM
is not behind a stable technology-agnostic interface. Replacing Doctrine with any other persistence
layer would require modifying all four services.

Additionally, cascade-deletion logic is implemented in application code:
- `AppUserManager::removeFromDatabase` (lines 29–41): iterates and removes reservations manually
- `RoomManager::deleteFromDatabase` (lines 29–36): same pattern

This logic should either be expressed in Doctrine cascade annotations on the entities or in a
dedicated deletion service.

### 7.2 Inline Authorization in API Controllers

Despite having Symfony Voters, the API controllers contain additional inline authorization checks.
`Api/Controller/RoomController::list` (lines 57–62) filters rooms inline using `$this->isGranted()`:

```php
$roomsOutput = array_filter(
    $this->roomManager->findRoomsByFilters($name, $code, $buildingCode, $filter),
    fn (Room $room) => $this->isGranted(RoomVoter::VIEW_DETAIL, $room)
        || $room->isIsPrivate() === false
        || $this->roomManager->hasApprovedReservation($room, $currentUser)
);
```

This mixes the HTTP filtering concern with the access-control concern. The "which rooms is this user
allowed to see" rule is a change driver that is now spread across both the voter and the controller.

Note also that `findRoomsByFilters` is called **twice** in `list()` (lines 58 and 71) — the same
database query is executed once to filter the list and once to map to output, a clear performance
and consistency bug caused by the inline authorization pattern.

### 7.3 URL Generation Duplicated Across API Controllers

Each API controller defines its own private URL-building helpers:
- `ReservationController::generateUrlIfNotNull`, `getVisitorsUrls`
- `RoomController::getUsersUrls`, `getGroupsUrls`, `getReservationsUrls`
- `AppUserController::getGroupsUrls`, `getRoomsUrls`, `getReservationsUrls`
- `GroupController::getUsersUrls`, `getRoomsUrls`

All of these follow the same pattern: map a collection to URLs using `$this->generateUrl()`. This
is a cross-cutting concern (REST hypermedia link generation) that is copy-pasted instead of
encapsulated in a single connector class.

### 7.4 Validation Duplicated Across Web and API DTOs

The `Timespan` and `RoomAvailability` constraints are registered separately in both
`Form/Model/ReservationTypeModel` and `Api/Model/ReservationInput`. If the availability rule
changes, two files must be modified.

Similarly, `UniqueUsername` is registered in both `Form/Model/AppUserTypeModel` and
`Api/Model/AppUserInput`.

### 7.5 `ReservationVoter::canCreate` Role Check Is Inconsistent

**File:** [src/Voter/ReservationVoter.php](src/Voter/ReservationVoter.php), line 75

```php
private function canCreate(AppUser $currentUser, Room $room): bool
{
    if ($currentUser->getRoles() === ['ROLE_SUPER_ADMIN']) { // strict equality!
        return true;
    }
    // ...
}
```

This uses strict array equality (`===`) to check for super-admin, which will fail if the user has
multiple roles (the array `['ROLE_SUPER_ADMIN', 'ROLE_USER']` is not equal to `['ROLE_SUPER_ADMIN']`).
All other voters use `in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles())`. This is an
inconsistency that means a super admin user with any additional role cannot create reservations
through the web controller — a bug likely introduced because the check was duplicated rather than
centralized.
