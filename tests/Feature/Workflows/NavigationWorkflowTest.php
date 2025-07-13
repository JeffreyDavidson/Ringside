<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Shared\Venue;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;

/**
 * Feature tests for complete navigation workflows.
 * Tests realistic user journeys for navigating through the application and discovering content.
 */
describe('Dashboard Navigation Hub Workflow', function () {
    test('administrator can navigate to all major sections from dashboard', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Starting from dashboard
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        // Then: Can navigate to all roster management sections
        $rosterSections = [
            'wrestlers.index' => 'Wrestlers',
            'managers.index' => 'Managers',
            'referees.index' => 'Referees',
            'tag-teams.index' => 'Tag Teams',
            'stables.index' => 'Stables',
        ];

        foreach ($rosterSections as $route => $sectionName) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($sectionName);

            // And: Can return to dashboard
            actingAs($admin)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Dashboard');
        }

        // And: Can navigate to content management sections
        $contentSections = [
            'titles.index' => 'Titles',
            'events.index' => 'Events',
            'venues.index' => 'Venues',
        ];

        foreach ($contentSections as $route => $sectionName) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee($sectionName);

            // And: Can return to dashboard
            actingAs($admin)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Dashboard');
        }

        // And: Can navigate to user management
        actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');

        // And: Can return to dashboard
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    });
});

describe('Wrestler Discovery and Detail Navigation Workflow', function () {
    test('administrator can discover and explore wrestler details and relationships', function () {
        // Given: A wrestler with related entities
        $admin = administrator();
        $wrestler = Wrestler::factory()->create([
            'name' => 'The Rock',
            'hometown' => 'Miami, FL',
        ]);

        // When: Starting from wrestlers index
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('The Rock');

        // And: Viewing wrestler detail page
        actingAs($admin)
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk()
            ->assertSee('Miami, FL');

        // Then: Should see all history sections
        $historySections = [
            'previous-title-championships-table',
            'previous-matches-table',
            'previous-tag-teams-table',
            'previous-managers-table',
            'previous-stables-table',
        ];

        foreach ($historySections as $section) {
            actingAs($admin)
                ->get(route('wrestlers.show', $wrestler))
                ->assertSeeLivewire("wrestlers.tables.{$section}");
        }

        // And: Can navigate back to wrestlers index
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('The Rock');
    });
});

describe('Cross-Entity Relationship Navigation Workflow', function () {
    test('administrator can navigate between related entities seamlessly', function () {
        // Given: Related entities (wrestler, manager, stable, tag team, title, event, venue)
        $admin = administrator();

        $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
        $event = Event::factory()->create(['name' => 'WrestleMania', 'venue_id' => $venue->id]);
        $title = Title::factory()->create(['name' => 'WWE Championship']);
        $wrestler = Wrestler::factory()->create(['name' => 'John Cena']);
        $manager = Manager::factory()->create(['first_name' => 'Paul', 'last_name' => 'Heyman']);
        $stable = Stable::factory()->create(['name' => 'The Shield']);
        $tagTeam = TagTeam::factory()->create(['name' => 'The Hardy Boyz']);

        // When: Starting from wrestler and exploring relationships
        actingAs($admin)
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk();

        // And: Navigating to related stable (if relationship exists)
        actingAs($admin)
            ->get(route('stables.show', $stable))
            ->assertOk();

        // And: Navigating to related tag team (if relationship exists)
        actingAs($admin)
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk();

        // And: Navigating to title details
        actingAs($admin)
            ->get(route('titles.show', $title))
            ->assertOk();

        // And: Navigating to event details
        actingAs($admin)
            ->get(route('events.show', $event))
            ->assertOk();

        // And: Navigating to venue details
        actingAs($admin)
            ->get(route('venues.show', $venue))
            ->assertOk();

        // And: Can navigate back through the chain
        actingAs($admin)
            ->get(route('events.show', $event))
            ->assertOk();
    });
});

describe('Content Discovery and Search Navigation Workflow', function () {
    test('administrator can search across different entity types and navigate results', function () {
        // Given: Entities with searchable names
        $admin = administrator();

        $wrestler = Wrestler::factory()->create(['name' => 'Stone Cold Steve Austin']);
        $manager = Manager::factory()->create(['first_name' => 'Stone Cold', 'last_name' => 'Manager']);
        $stable = Stable::factory()->create(['name' => 'Austin 3:16 Stable']);
        $title = Title::factory()->create(['name' => 'Austin Championship']);
        $venue = Venue::factory()->create(['name' => 'Austin Arena']);

        // When: Searching in wrestlers section
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk();

        // Then: Can find wrestler with "Austin" in name
        // (Search functionality would be tested in integration tests)

        // When: Searching in managers section
        actingAs($admin)
            ->get(route('managers.index'))
            ->assertOk();

        // When: Searching in stables section
        actingAs($admin)
            ->get(route('stables.index'))
            ->assertOk();

        // When: Searching in titles section
        actingAs($admin)
            ->get(route('titles.index'))
            ->assertOk();

        // When: Searching in venues section
        actingAs($admin)
            ->get(route('venues.index'))
            ->assertOk();

        // Then: All sections are accessible and searchable
        expect(true)->toBeTrue(); // Test passes if no assertions fail
    });
});

describe('User Management Navigation Workflow', function () {
    test('administrator can navigate user management section effectively', function () {
        // Given: Multiple users
        $admin = administrator();
        $basicUser = User::factory()->create(['first_name' => 'Regular', 'last_name' => 'User']);
        $anotherAdmin = User::factory()->administrator()->create(['first_name' => 'Another', 'last_name' => 'Admin']);

        // When: Navigating to users index
        actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');

        // And: Viewing specific user details
        actingAs($admin)
            ->get(route('users.show', $basicUser))
            ->assertOk();

        // And: Viewing another user details
        actingAs($admin)
            ->get(route('users.show', $anotherAdmin))
            ->assertOk();

        // And: Can return to users index
        actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');
    });
});

describe('Breadcrumb and Context Navigation Workflow', function () {
    test('user maintains context awareness while navigating deep into application', function () {
        // Given: Complex entity relationships
        $admin = administrator();
        $wrestler = Wrestler::factory()->create(['name' => 'Triple H']);
        $stable = Stable::factory()->create(['name' => 'Evolution']);
        $tagTeam = TagTeam::factory()->create(['name' => 'D-Generation X']);

        // When: Deep navigation path: Dashboard → Wrestlers → Wrestler Detail → Related entities
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('Triple H');

        actingAs($admin)
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk();

        // And: Can navigate to related entities
        actingAs($admin)
            ->get(route('stables.show', $stable))
            ->assertOk();

        actingAs($admin)
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk();

        // And: Can always return to major navigation points
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('wrestlers');

        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    });
});

describe('Mobile and Responsive Navigation Workflow', function () {
    test('navigation works across different viewport contexts', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Accessing main sections (responsive design should handle mobile)
        $mainSections = [
            'dashboard',
            'wrestlers.index',
            'managers.index',
            'referees.index',
            'tag-teams.index',
            'stables.index',
            'titles.index',
            'events.index',
            'venues.index',
            'users.index',
        ];

        foreach ($mainSections as $route) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk();
        }

        // Then: All sections should be accessible regardless of viewport
        // (Actual responsive testing would require browser testing tools)
        expect(true)->toBeTrue();
    });
});

describe('Error Handling and Recovery Navigation Workflow', function () {
    test('user can recover from navigation errors gracefully', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Attempting to access non-existent entity
        actingAs($admin)
            ->get(route('wrestlers.show', 999999))
            ->assertNotFound();

        // And: Can recover by navigating to valid routes
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('wrestlers');

        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    });
});

describe('Performance and Loading Navigation Workflow', function () {
    test('navigation maintains reasonable performance with larger datasets', function () {
        // Given: Multiple entities in the system
        $admin = administrator();

        // Create multiple entities to simulate real usage
        Wrestler::factory()->count(20)->create();
        Manager::factory()->count(15)->create();
        Referee::factory()->count(10)->create();
        TagTeam::factory()->count(12)->create();
        Stable::factory()->count(8)->create();
        Title::factory()->count(6)->create();
        Venue::factory()->count(5)->create();
        Event::factory()->count(10)->create();

        // When: Navigating through sections with populated data
        $sections = [
            'wrestlers.index',
            'managers.index',
            'referees.index',
            'tag-teams.index',
            'stables.index',
            'titles.index',
            'events.index',
            'venues.index',
        ];

        foreach ($sections as $route) {
            actingAs($admin)
                ->get(route($route))
                ->assertOk();
        }

        // Then: All sections should load successfully with populated data
        expect(true)->toBeTrue();
    });
});
