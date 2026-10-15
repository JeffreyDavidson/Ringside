# Controller Contracts

These are Jeffrey's controller conventions. Apply them when writing or reviewing controller tests and architecture rules. Guidance updates alone do not authorize renaming, moving or deleting application classes.

## Two Concrete Shapes

| Shape | Name | Public HTTP handlers |
| --- | --- | --- |
| Resource controller | Singular resource plus `Controller`, such as `PostController` | A nonempty subset of `index`, `create`, `store`, `show`, `edit`, `update`, `destroy` |
| Action controller | Verb plus subject plus `Controller`, such as `PublishPostController` or `PreviewNewsletterIssueController` | Only `__invoke()` |

A resource controller can implement just `show()` or another needed subset; do not generate unused methods or routes. Resource names describe the resource even when there is no Eloquent model. An action controller handles one named HTTP action. A controller must not combine resource methods with `__invoke()` or add public handlers such as `publish()`, `approve()` or `download()` to a resource controller. Give those operations named invokable controllers.

## Preserve Resource Controllers

Standard resource operations belong in the corresponding singular resource controller using `index`, `create`, `store`, `show`, `edit`, `update` or `destroy`. Do not split these operations into invokable controllers such as `StorePostController`, `ShowPostController` or `UpdatePostController`. Preserve an existing resource controller rather than replacing its methods with one controller per operation. Even a resource with only a `show` endpoint uses `PostController::show()`; implement only the methods needed.

| Split resource operation | Resource-controller method |
| --- | --- |
| `StorePostController` | `PostController::store()` |
| `ShowPostController` | `PostController::show()` |
| `UpdatePostController` | `PostController::update()` |
| `DestroyPostController` | `PostController::destroy()` |

Reserve invokable controllers for distinct actions outside the standard resource operations, such as `PublishPostController`. Judge the actual routed responsibility, not just the verb: renaming ordinary post creation to `SavePostController` does not make it a separate business action. A complex resource operation still belongs in its standard method; delegate substantial business logic to the project's existing appropriate layer rather than splitting the HTTP controller.

Consolidate existing splits for the same resource and HTTP context during authorized application refactoring. Trace route registrations, callers, middleware, authorization, validation and response contracts before deciding ownership. `Admin\PostController` and `Api\PostController` may remain separate because they own different HTTP contracts; sharing an Eloquent model is not sufficient reason to combine controllers. Distinct nested resources also retain their own resource controllers. These boundaries do not justify splitting one resource context by standard operation.

When consolidation is authorized, preserve URLs, route names, middleware, model binding, authorization, validation, responses, persistence and side effects. Update route targets and the complete mirrored Feature coverage together, preserve the original behavioral cases, and run affected HTTP tests and required architecture checks. Reviewers flag inappropriate splits in the changed or relied-upon flow; an unrelated legacy split is a separate recommendation, not permission for a broad rewrite.

This is Jeffrey's required project convention. Laravel documents resource controllers and partial resource routes, but does not prohibit invokable implementations of CRUD. Updating this guidance does not itself authorize merging, renaming or deleting application classes.

## Prefer Resource Directories and Review the Whole Resource

When a resource has a resource controller and related invokable action controllers, prefer grouping them in a resource directory under `app/Http/Controllers`, for example:

```text
app/Http/Controllers/Posts/
├── PostController.php
├── PublishPostController.php
└── ArchivePostController.php
```

The folder groups HTTP ownership; it does not merge distinct actions into the resource controller or change the singular class-name convention. Follow the project's resource-directory naming pattern; `Posts/` is an example, not a new mandatory plural-directory rule. A lone controller does not need a directory solely for ceremony. Preserve distinct contexts such as `Admin/Posts` and `Api/Posts`, and do not group controllers merely because they reference the same model.

When reviewing a change to a resource's controllers or routes, inventory all controllers and registered routes serving that resource within the affected HTTP context, including related controllers outside the diff or in other directories. Read their responsibilities and relevant flow together to assess resource-method ownership, distinct action boundaries and whether grouping improves clarity. Do not infer membership solely from a filename prefix or a shared model. Record the inspected controllers/routes and identify missing access as a review limitation; do not claim a complete resource review after inspecting only the changed controller. Keep unrelated resources and contexts outside the review unless the changed flow depends on them.

Grouping is a preference: recommend it as an optional improvement when scattered controllers hinder clarity. Folder layout alone does not fail compliance or justify a new architecture/CI gate unless an explicit project rule makes it mandatory. Required controller shape, directory ownership, singular class names and matching Feature coverage still apply regardless of grouping. Reviewing sibling controllers does not authorize moving them or make unrelated legacy findings blockers.

Move controllers only during an authorized application refactor. Preserve the existing HTTP contracts, update namespaces/imports and route targets, and mirror the new full controller paths in Feature tests; for example, `Posts/PublishPostController.php` maps to `tests/Feature/Http/Controllers/Posts/PublishPostControllerTest.php`. Follow the preservation and verification requirements above.

## Singular Controller, Plural Resource Route

Resource-controller class names **must be singular**, including controllers in domain or nested namespaces: `PhotoController`, `PostController`, `EventController`, not `PhotosController`, `PostsController` or `EventsController`. This follows Laravel's documented resource-controller examples: `php artisan make:controller PhotoController --resource` and `Route::resource('photos', PhotoController::class)`. The resource URI and default route-name prefix are plural; they do not make the controller class plural. See [Laravel's resource-controller documentation](https://laravel.com/framework/docs/12.x/controllers#resource-controllers).

This is a required project naming convention based on the documented examples, not a claim that Laravel's runtime rejects plural PHP class names. A plural-name architecture assertion conflicts with the approved convention and must be flagged; passing that assertion is not naming compliance. Do not silently exempt domain or authentication controllers from the singular-name rule. Trace unusual resource words and explicit project exceptions instead of blindly singularizing every verb/subject action name.

Constructors and non-public implementation helpers are not HTTP handlers. Keep middleware in route files. Name controllers by the resource or action they own, not an internal collaborator or a generic label such as `Manager`, `Handler` or `Helper`.

## Directory Ownership and Base Classes

Everything under `app/Http/Controllers`, including nested namespaces, must be an HTTP controller or a justified base controller with real shared controller behavior. Helpers, traits, DTOs, queries, services, interfaces and enums belong in their appropriate layers, not this directory. Adding a `Controller` suffix to a utility does not make it a controller.

Do not create or retain an empty application base `Controller`, concrete or abstract, merely for inheritance. An empty subclass of a framework controller still adds no application behavior. Controllers can stand alone; neither instructions nor architecture tests should require inheritance from `App\Http\Controllers\Controller` or `Illuminate\Routing\Controller`. Retain a base only when its actual shared behavior justifies it, not as an exception invented to preserve an empty class.

When an approved application refactor removes an empty base, update its consumers, imports and inheritance-based tests together, then run the affected HTTP tests. Do not remove a base that has meaningful behavior without tracing that behavior first.

## Controller Responsibilities

Controllers coordinate the HTTP request and response: receive validated input, participate in the project's authorization flow, call appropriate existing collaborators and return the response/view/payload. Review the actual flow for substantial business workflows, multi-step state transitions and reusable query logic that belong in an existing Action, Query, Builder or other project-appropriate layer. Validation belongs in FormRequests and permission decisions in policies/gates under the project's conventions.

Keep straightforward reads and writes readable where the project's architecture permits them. Do not require an Action, service or ViewModel for every controller method, impose a line-count limit, or extract a one-use wrapper solely to make a controller appear thin. Cite the concrete responsibility and an appropriate existing layer before recommending extraction; application changes still require authorized scope.

## Matching Feature Coverage

Every concrete controller has a matching Feature test at its full mirrored path: `app/Http/Controllers/Admin/PublishPostController.php` maps to `tests/Feature/Http/Controllers/Admin/PublishPostControllerTest.php`. Genuine abstract bases are exempt. Shared cross-controller tests may supplement this ownership; they do not replace the matching file.

Exercise the actual registered HTTP routes. Cover the controller's response/view/payload contract and applicable authentication, authorization, validation, persistence and side effects. Failed writes must leave relevant state and effects unchanged. Add retry/stale-write cases where the behavior can be replayed; do not invent write scenarios for read-only controllers. Use explicit Arrange/Act/Assert, assign the response to `$response`, and use named routes.

## Coverage for Every Routed Handler

A matching Feature file establishes ownership, not complete coverage. Inventory every registered handler of each controller in the affected resource/context, including invokables and route-specific middleware or response differences. Map each handler to its meaningful success and applicable authentication, authorization, validation, scope and failure cases. Do not invent routes or unused methods to fill the seven-method resource template. For a public or read-only handler, mark inapplicable cases explicitly instead of inventing write or authentication behavior.

Retain a compact review map:

| Route name and HTTP verb | Controller handler | Applicable contract/guard | Feature file and named cases | Gap or not applicable |
| --- | --- | --- | --- | --- |

Read the actual tests and routed flow; a matching filename, a grep hit, coverage percentage or a green suite does not prove that every handler is tested. Identify missing handler cases in the reviewed flow and distinguish legacy gaps outside the authorized change. Missing access or an incomplete inventory is an explicit review limitation, not a complete-coverage claim.

For the real application path, intended failure causes, read contracts and per-handler refused-role coverage, read [endpoint tests](endpoint-tests.md) and [dependency isolation](isolation.md). Keep controllers, FormRequests, policies and internal Actions real in controller Feature tests; fake external boundaries appropriately. Assertions must establish the relevant returned records and state/effects, not only status codes. Check responsibility placement separately from HTTP behavior.

## Enforcement and Review

When implementation of enforcement is authorized, use the project's existing architecture-test tools to check every class recursively for the `Controller` suffix, allowed method shape, directory ownership, no empty application base and the full mirrored Feature path. Inspect imports and responsibilities as well as names: method/suffix checks alone cannot detect a disguised service. Review singular resource names and verb/subject meaning semantically rather than treating an arbitrary regex as proof.

An architecture test protects declarations; it does not prove authorization, routing or behavior. Run the matching Feature tests as well. Report an existing violation with its file and responsibility; a passing legacy inheritance check or a permanent allowlist is not compliance with the new convention. Do not claim CI enforcement exists until the checks are implemented and actually run.
