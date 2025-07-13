<?php

declare(strict_types=1);

use App\Repositories\Concerns\ManagesMembers;
use App\Repositories\Support\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

/**
 * Unit tests for ManagesMembers trait in complete isolation.
 *
 * UNIT TEST SCOPE:
 * - Trait method behavior without business model dependencies
 * - Member addition with join dates
 * - Member removal with leave dates
 * - Current member management
 * - Date handling and pivot table operations
 *
 * This test ensures the ManagesMembers trait is completely agnostic and reusable
 * across any repository that manages many-to-many membership relationships.
 *
 * @see \App\Repositories\Concerns\ManagesMembers
 */
describe('ManagesMembers Trait', function () {
    beforeEach(function () {
        testTime()->freeze();
        
        // Create anonymous repository class for trait testing
        $this->repository = new class extends BaseRepository {
            use ManagesMembers;
            
            // Expose protected methods for testing
            public function testAddMember($group, $relationship, $member, $joinDate)
            {
                return $this->addMember($group, $relationship, $member, $joinDate);
            }
            
            public function testRemoveMember($group, $relationship, $member, $leaveDate)
            {
                return $this->removeMember($group, $relationship, $member, $leaveDate);
            }
            
            public function testRemoveCurrentMember($group, $relationship, $member, $leaveDate)
            {
                return $this->removeCurrentMember($group, $relationship, $member, $leaveDate);
            }
        };
    });

    describe('addMember method', function () {
        test('adds member with join date', function () {
            // Arrange
            $joinDate = Carbon::now();
            
            // Mock the relationship
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('attach')
                ->once()
                ->with(123, ['joined_at' => $joinDate->toDateTimeString()])
                ->andReturn(null);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('wrestlers')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(123);

            // Act
            $this->repository->testAddMember($groupModel, 'wrestlers', $memberModel, $joinDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets join date correctly', function () {
            // Arrange
            $joinDate = Carbon::parse('2024-01-15 10:00:00');
            
            // Mock the relationship
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('attach')
                ->once()
                ->with(456, ['joined_at' => $joinDate->toDateTimeString()])
                ->andReturn(null);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('members')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(456);

            // Act
            $this->repository->testAddMember($groupModel, 'members', $memberModel, $joinDate);

            // Assert - Mock expectations verified automatically
        });

        test('works with different relationship names', function () {
            // Arrange
            $joinDate = Carbon::now();
            
            // Mock the relationship
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('attach')
                ->once()
                ->with(789, ['joined_at' => $joinDate->toDateTimeString()])
                ->andReturn(null);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('managers')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(789);

            // Act
            $this->repository->testAddMember($groupModel, 'managers', $memberModel, $joinDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('removeMember method', function () {
        test('removes member by setting leave date', function () {
            // Arrange
            $leaveDate = Carbon::now();
            
            // Mock the relationship
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('updateExistingPivot')
                ->once()
                ->with(123, ['left_at' => $leaveDate->toDateTimeString()])
                ->andReturn(1);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('wrestlers')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(123);

            // Act
            $this->repository->testRemoveMember($groupModel, 'wrestlers', $memberModel, $leaveDate);

            // Assert - Mock expectations verified automatically
        });

        test('sets leave date correctly', function () {
            // Arrange
            $leaveDate = Carbon::parse('2024-12-31 15:30:00');
            
            // Mock the relationship
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('updateExistingPivot')
                ->once()
                ->with(456, ['left_at' => $leaveDate->toDateTimeString()])
                ->andReturn(1);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('members')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(456);

            // Act
            $this->repository->testRemoveMember($groupModel, 'members', $memberModel, $leaveDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('removeCurrentMember method', function () {
        test('removes only current member relationship', function () {
            // Arrange
            $leaveDate = Carbon::now();
            
            // Mock the relationship with pivot constraint
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('wherePivotNull')
                ->once()
                ->with('left_at')
                ->andReturnSelf();
            $relationshipMock->shouldReceive('updateExistingPivot')
                ->once()
                ->with(123, ['left_at' => $leaveDate->toDateTimeString()])
                ->andReturn(1);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('wrestlers')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(123);

            // Act
            $this->repository->testRemoveCurrentMember($groupModel, 'wrestlers', $memberModel, $leaveDate);

            // Assert - Mock expectations verified automatically
        });

        test('filters by null left_at correctly', function () {
            // Arrange
            $leaveDate = Carbon::parse('2024-06-15 12:00:00');
            
            // Mock the relationship with pivot constraint
            $relationshipMock = \Mockery::mock(BelongsToMany::class);
            $relationshipMock->shouldReceive('wherePivotNull')
                ->once()
                ->with('left_at')
                ->andReturnSelf();
            $relationshipMock->shouldReceive('updateExistingPivot')
                ->once()
                ->with(789, ['left_at' => $leaveDate->toDateTimeString()])
                ->andReturn(1);
            
            $groupModel = \Mockery::mock(Model::class);
            $groupModel->shouldReceive('currentMembers')->andReturn($relationshipMock);
            
            $memberModel = \Mockery::mock(Model::class);
            $memberModel->shouldReceive('getKey')->andReturn(789);

            // Act
            $this->repository->testRemoveCurrentMember($groupModel, 'currentMembers', $memberModel, $leaveDate);

            // Assert - Mock expectations verified automatically
        });
    });

    describe('trait behavior verification', function () {
        test('trait methods exist and are callable', function () {
            // Assert
            expect(method_exists($this->repository, 'addMember'))->toBeTrue();
            expect(method_exists($this->repository, 'removeMember'))->toBeTrue();
            expect(method_exists($this->repository, 'removeCurrentMember'))->toBeTrue();
        });

        test('trait is model agnostic', function () {
            // Arrange
            $joinDate = Carbon::now();
            
            // Create different mock models and relationships
            $group1 = \Mockery::mock(Model::class);
            $relationship1 = \Mockery::mock(BelongsToMany::class);
            $relationship1->shouldReceive('attach')->once()->andReturn(null);
            $group1->shouldReceive('wrestlers')->andReturn($relationship1);
            
            $group2 = \Mockery::mock(Model::class);
            $relationship2 = \Mockery::mock(BelongsToMany::class);
            $relationship2->shouldReceive('attach')->once()->andReturn(null);
            $group2->shouldReceive('managers')->andReturn($relationship2);
            
            $member1 = \Mockery::mock(Model::class);
            $member1->shouldReceive('getKey')->andReturn(1);
            
            $member2 = \Mockery::mock(Model::class);
            $member2->shouldReceive('getKey')->andReturn(2);

            // Act - Use same repository instance with different models
            $this->repository->testAddMember($group1, 'wrestlers', $member1, $joinDate);
            $this->repository->testAddMember($group2, 'managers', $member2, $joinDate);

            // Assert - Mock expectations verified (both models were used)
        });

        test('handles different relationship names correctly', function () {
            // Arrange
            $joinDate = Carbon::now();
            $relationshipNames = ['wrestlers', 'managers', 'members', 'tagTeams'];
            
            foreach ($relationshipNames as $index => $relationshipName) {
                $group = \Mockery::mock(Model::class);
                $relationship = \Mockery::mock(BelongsToMany::class);
                $relationship->shouldReceive('attach')->once()->andReturn(null);
                $group->shouldReceive($relationshipName)->andReturn($relationship);
                
                $member = \Mockery::mock(Model::class);
                $member->shouldReceive('getKey')->andReturn($index + 1);
                
                // Act
                $this->repository->testAddMember($group, $relationshipName, $member, $joinDate);
            }

            // Assert - All relationships were called (verified by mocks)
        });
    });

    describe('trait integration safety', function () {
        test('trait methods are protected and work through inheritance', function () {
            // Assert
            $reflection = new ReflectionClass($this->repository);
            
            expect($reflection->hasMethod('addMember'))->toBeTrue();
            expect($reflection->hasMethod('removeMember'))->toBeTrue();
            expect($reflection->hasMethod('removeCurrentMember'))->toBeTrue();
            
            // Methods should be protected in the trait
            expect($reflection->getMethod('addMember')->isProtected())->toBeTrue();
            expect($reflection->getMethod('removeMember')->isProtected())->toBeTrue();
            expect($reflection->getMethod('removeCurrentMember')->isProtected())->toBeTrue();
        });

        test('trait is reusable across multiple repository instances', function () {
            // Arrange
            $anotherRepository = new class extends BaseRepository {
                use ManagesMembers;
                
                // Expose protected method for testing
                public function testAddMember($group, $relationship, $member, $joinDate)
                {
                    return $this->addMember($group, $relationship, $member, $joinDate);
                }
            };
            
            $joinDate = Carbon::now();
            
            $group = \Mockery::mock(Model::class);
            $relationship = \Mockery::mock(BelongsToMany::class);
            $relationship->shouldReceive('attach')->once()->andReturn(null);
            $group->shouldReceive('wrestlers')->andReturn($relationship);
            
            $member = \Mockery::mock(Model::class);
            $member->shouldReceive('getKey')->andReturn(123);

            // Act - Use trait methods from different repository instance
            $anotherRepository->testAddMember($group, 'wrestlers', $member, $joinDate);

            // Assert - Repository can use the trait
            expect(method_exists($anotherRepository, 'addMember'))->toBeTrue();
        });
    });

    afterEach(function () {
        \Mockery::close();
    });
});