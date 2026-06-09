# CLAUDE.md - Classroom reservation system

This file is the primary context document for working on this codebase as part of a Normalized Systems (NS) theory assignment. It covers: what the system does, what the assignment requires, and a full reference to NS theory principles needed for the analysis and refactoring.

---

## Assignment overview

The task is to analyze this codebase against NS theory principles and refactor it to comply with them.

**Three deliverables:**
1. A written report covering NS-based code review (modularity, evolvability, the 4 theorems) and a description of how the code was refactored.
2. A refactored codebase that complies with NS theory.
3. A 15-30 minute oral defense discussing the report and refactored code.

**Approach:**
- First step: analyze the existing code and identify NS violations (ripple effects, coupling issues, mixed concerns, data coupling, stateless workflows, etc.).
- Second step: refactor to comply with the 4 NS theorems.
- Document every decision: why a violation exists, what it causes, and how the refactoring addresses it.

---

## System description

The system is a classroom reservation web application built with **Symfony (PHP) + Doctrine ORM**. It manages room reservations at a university, including an approval workflow and a REST API for hardware door control.

### Core entities

- **User** - authenticated identity with one or more roles
- **Room** - a physical room or classroom, belongs to a building and optionally a group
- **Building** - groups rooms by physical location
- **Group** - organizational unit (e.g. department); rooms and users can belong to groups; groups can have sub-groups (hierarchical, transitive membership)
- **Reservation** - a time-bounded booking of a room for a user; goes through a request-approval lifecycle
- **ReservationVisitor** - a user added to an existing reservation by the owner or manager
- **DoorState** - represents the current lock/unlock state of a room (persisted in the database, not physical hardware)

### Role hierarchy

- **Unauthenticated user** - read-only access to public room availability
- **Authenticated user** - can view and manage their own reservation requests
- **Room user** - can request reservations for rooms they are assigned to; requires approval
- **Room manager** (extends room user) - can approve reservations for their rooms; can reserve on behalf of others
- **Group member** - can request reservations for rooms in their group; requires approval
- **Group manager** (extends room manager) - can approve reservations for all rooms in their group (transitively); can assign/remove room managers within their group
- **Administrator** - full access; creates groups, sub-groups, rooms, buildings; assigns managers

### Reservation lifecycle

1. A room user or group member submits a reservation request.
2. A room manager or group manager approves or rejects it.
3. If approved, the reservation becomes active during the booked time window.
4. During an active reservation, the owner can unlock the room via double card swipe (handled by hardware; the server receives an explicit unlock or lock request).
5. Once unlocked, all card swipes are granted access until the room is explicitly locked again.

### REST API surface

- **Users** - list (with filtering), detail
- **Groups** - list (with filtering), add/remove users, add/remove rooms
- **Rooms** - list (with filtering), detail, lock, unlock
- **Reservations** - full CRUD, approval, add/remove visitors, access verification endpoint

**Access verification logic** (the core of the door API): a user has access to a room if any of the following is true:
- The room is currently unlocked (DoorState = unlocked)
- The user is a permanent room user of that room
- The user has an approved reservation for that room that is currently active

### Tech stack

- PHP, Symfony framework (routing, dependency injection, security, forms)
- Doctrine ORM (entity mapping, repositories, migrations)
- REST API (JSON responses)
- HTML5 + CSS (custom stylesheet, print variant)
- JavaScript + AJAX for dynamic UI behavior

---

## Normalized Systems theory - reference

This section contains all NS theory needed to analyze and refactor the codebase. It is derived from the NIE-NSS course.

### The central problem

Traditional software increases in complexity with every change. A small change can propagate through the entire codebase, requiring modifications in many places. This is called a **combinatorial effect** (or ripple effect): a bounded input (the size of the change) produces an unbounded output (the number of code modifications), scaled by the size of the system rather than the size of the change itself.

NS theory is a formal methodology for building software that avoids combinatorial effects. It originates from Systems Theory (dynamic stability: a bounded input must produce a bounded output) and Thermodynamics (entropy: systems tend toward disorder unless actively managed).

### The 4 NS theorems (principles)

It can be proven that violating any of these theorems provably introduces combinatorial effects. Following all four is a necessary (but not sufficient) condition to avoid them. Custom code (craftings) can still introduce combinatorial effects through poor design.

#### 1. Separation of concerns

An action entity may contain only a single task or change driver.

A **change driver** is anything that could cause a future change: a business rule, a technology dependency, a data structure dependency, an authorization rule, etc.

- Each change driver must be isolated in its own module.
- A change should affect exactly one place.
- New change drivers must be implemented in new modules; other modules communicate with them via a stable API.
- This requires good encapsulation: functions, classes, and external modules (e.g. logging, persistence) must each be encapsulated so their internals don't leak.

**What to look for in the codebase:** controllers doing validation + business logic + persistence + authorization in one method; service classes with multiple unrelated responsibilities; entities containing business rule logic.

#### 2. Data version transparency

When a data structure changes, modules that consume that data should not be affected.

- Encapsulate data in classes/objects and pass the whole object (stamp coupling), not individual fields as separate parameters (data coupling).
- If a function takes `(name, address, phone)` as separate parameters and a new field `email` is added to the entity, the function signature must change - ripple effect.
- If the function takes the whole `Customer` object, adding `email` doesn't change the signature - the function simply ignores fields it doesn't use.

**What to look for in the codebase:** methods or constructors that take individual scalar fields that belong to an entity; array-based data passing where a typed object should be used; DTO objects that duplicate entity fields without encapsulation.

#### 3. Action version transparency

When a new version of an action is introduced (bug fix, new variant, changed business rule), existing callers must not need to change.

- Encapsulate actions behind stable interfaces.
- Use a delegation/versioning pattern: add a new version of the action alongside the old one; a delegator decides which version to invoke based on context.
- All versions coexist in the codebase so no legacy code is broken.
- The caller interacts with a stable interface and is not aware of which version executes.

**What to look for in the codebase:** directly modifying an existing service method when requirements change (instead of adding a new version); callers that contain `if`/`switch` logic to select between variants of an action (the selection belongs in a delegator, not the caller); duplicate logic spread across multiple places because versioning was not used.

#### 4. Separation of states

Every action should produce a persistent state. Workflows must be asynchronous and stateful, with persistent states between actions.

- Calling an action and reacting to its result must be decoupled by a persistent state. The workflow can then resume, retry, or branch independently.
- Stateful workflows enable observability, stopping, resuming, branching, and resetting.
- A new state returned from an action should not require adding state-handling code everywhere; the structure returned from an action should be generic enough to accommodate new states.

**What to look for in the codebase:** synchronous request-response chains where one service directly calls another and acts on the return value (no persistent state in between); reservation approval workflows that are purely in-memory; missing status fields on entities that should track lifecycle state; error handling that is deeply coupled to callers rather than encoded as a persistent state.

### High cohesion and low coupling

These are GRASP principles that work alongside the 4 theorems.

- **High cohesion**: each module contains exactly one responsibility. Variants of a module do not require copying the whole module.
- **Low coupling**: connections between modules should be minimized and carefully designed. The goal is not zero coupling but preventing change propagation through the connections. Encapsulation of connections is the key mechanism.
- Beware: high cohesion without controlled coupling leads to exponential ripple costs. Both must be applied together.

### The NS element - building block

In a fully NS-compliant system, every class is part of an **element**: a set of classes centered on one core class, surrounded by concern-handling classes.

Structure of an element:
- **Core class** - one of the 5 element types (see below); contains the business logic
- **Surrounding classes** - one per cross-cutting concern (persistence, access control, logging, error handling, etc.); each delegates to a third-party framework/library
- **Stable, technology-agnostic interface** exposed to the outside

This structure prevents combinatorial effects because:
- If a third-party library changes, only the surrounding class that interfaces with it is affected.
- The core class and other elements are not affected.
- The interface remains stable regardless of the underlying technology.

This is not expected to be fully implemented in the refactoring (that would require a code generator). However, the element structure is the architectural target: the refactoring should move in this direction.

### 5 element types

- **Data elements** - represent data structures (e.g. `Reservation`, `Room`, `User`)
- **Task elements** - contain executable operations (e.g. `CreateReservation`, `ApproveReservation`, `VerifyAccess`)
- **Flow elements** - orchestrate and control the sequence of tasks (e.g. the reservation approval workflow)
- **Connector elements** - handle communication with external systems (e.g. the door hardware API, Doctrine persistence)
- **Trigger elements** - handle event-based or time-based activation (e.g. a scheduled job that expires old reservations)

Use these types to classify existing classes in the codebase during the review.

### Cross-cutting concerns in this codebase

The following cross-cutting concerns are present and need to be analyzed for correct integration:

- **Persistence** (Doctrine ORM) - data storage and retrieval
- **Access control / authorization** (Symfony Security, Voters) - who can do what
- **Validation** (Symfony Validator or manual) - input correctness
- **Error handling** - how failures are reported and propagated
- **Logging** - if present
- **Transaction management** - Doctrine transactions around multi-step operations
- **REST serialization** - converting entities to JSON responses

For each concern, the analysis should determine: is it embedded in business logic (violation) or encapsulated in a dedicated layer (compliant)?

### Modularity and variation gain

- Module variants grow additively (m modules × v variants = m×v total).
- Product/configuration variants grow multiplicatively (v^m possible combinations).
- This variation gain is the power of modular design - but only if coupling is controlled. Without it, the same exponential factor applies to ripple costs.

### What a refactoring toward NS compliance looks like in Symfony/PHP

This is not a complete NS-generated system (that would require expanders and a code generator). The goal is to make the existing code comply with the 4 theorems to the extent possible within a manual refactoring:

- **Separation of concerns**: split fat controllers into thin controllers + dedicated service classes; each service class has one responsibility; authorization logic moves to Symfony Voters or dedicated authorization services, not mixed into business logic.
- **Data version transparency**: replace method signatures that take individual scalar fields with typed objects or existing Doctrine entities; introduce value objects where appropriate.
- **Action version transparency**: introduce interfaces for services; when a business rule changes, add a new implementation class behind the interface and use a delegator/strategy pattern to route to it - do not modify the existing implementation.
- **Separation of states**: ensure each step in the reservation workflow produces a persisted state change on the entity (e.g. `PENDING`, `APPROVED`, `ACTIVE`, `LOCKED`, `UNLOCKED`); avoid synchronous chains where a service calls another service and acts directly on the return value without persisting an intermediate state.

---

## How to approach the analysis

1. Map all existing classes to NS element types (data, task, flow, connector, trigger). Classes that don't fit cleanly into one type are likely violating separation of concerns.
2. For each class, identify all change drivers present in it. More than one change driver = separation of concerns violation.
3. Check all method signatures for data coupling (individual scalar parameters that belong to an entity).
4. Identify all places where an action is modified in place rather than versioned.
5. Trace the reservation approval workflow and identify all points where state should be persisted but isn't.
6. List all cross-cutting concerns and determine where they bleed into the core business logic.

Each identified violation should be documented with: the class/method, the theorem violated, the combinatorial effect it would cause if a specific change were made, and the proposed fix.