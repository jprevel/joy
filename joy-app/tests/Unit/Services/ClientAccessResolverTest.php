<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ClientAccessResolver;
use App\Services\RoleDetectionService;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ClientAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    private RoleDetectionService $roleDetectionService;
    private ClientAccessResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleDetectionService = Mockery::mock(RoleDetectionService::class);
        $this->resolver = new ClientAccessResolver($this->roleDetectionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_resolves_client_with_client_id_for_admin(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create(['name' => 'Test Client']);

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($admin, Mockery::on(function ($arg) use ($client) {
                return $arg->id === $client->id;
            }))
            ->andReturn(true);

        // Act
        $result = $this->resolver->resolveClient($client->id, $admin);

        // Assert
        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($client->id, $result->id);
        $this->assertEquals('Test Client', $result->name);
    }

    /** @test */
    public function it_resolves_client_from_user_for_client_role(): void
    {
        // Arrange
        $client = Client::factory()->create(['name' => 'User Client']);
        $clientUser = User::factory()->client()->make(); // Use make() instead of create()

        // Mock the client relationship
        $clientUser->setRelation('client', $client);

        $this->roleDetectionService
            ->shouldReceive('isClient')
            ->once()
            ->with($clientUser)
            ->andReturn(true);

        // Act
        $result = $this->resolver->resolveClient(null, $clientUser);

        // Assert
        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals($client->id, $result->id);
    }

    /** @test */
    public function it_throws_exception_when_admin_missing_client_id(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();

        $this->roleDetectionService
            ->shouldReceive('isClient')
            ->once()
            ->with($admin)
            ->andReturn(false);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('client_id parameter required');

        // Act
        $this->resolver->resolveClient(null, $admin);
    }

    /** @test */
    public function it_throws_exception_when_agency_missing_client_id(): void
    {
        // Arrange
        $agency = User::factory()->agency()->create();

        $this->roleDetectionService
            ->shouldReceive('isClient')
            ->once()
            ->with($agency)
            ->andReturn(false);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('client_id parameter required');

        // Act
        $this->resolver->resolveClient(null, $agency);
    }

    /** @test */
    public function it_validates_access_passes_for_authorized_user(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create();

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($admin, $client)
            ->andReturn(true);

        // Act & Assert - should not throw exception
        $this->resolver->validateAccess($admin, $client);

        // If we get here, validation passed
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_access_throws_for_unauthorized_user(): void
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $user = User::factory()->client()->make();
        $user->setRelation('client', $client1);

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($user, $client2)
            ->andReturn(false);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You do not have access to this client');

        // Act
        $this->resolver->validateAccess($user, $client2);
    }

    /** @test */
    public function it_validates_access_passes_for_client_own_data(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $clientUser = User::factory()->client()->make();
        $clientUser->setRelation('client', $client);

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($clientUser, $client)
            ->andReturn(true);

        // Act & Assert
        $this->resolver->validateAccess($clientUser, $client);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_resolves_and_validates_in_one_call(): void
    {
        // Arrange
        $agency = User::factory()->agency()->create();
        $client = Client::factory()->create(['name' => 'Agency Client']);

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($agency, Mockery::on(function ($arg) use ($client) {
                return $arg->id === $client->id;
            }))
            ->andReturn(true);

        // Act
        $result = $this->resolver->resolveClient($client->id, $agency);

        // Assert - resolveClient should handle both resolution and validation
        $this->assertEquals($client->id, $result->id);
    }

    /** @test */
    public function it_throws_model_not_found_exception_for_invalid_client_id(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $nonExistentClientId = 99999;

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->resolver->resolveClient($nonExistentClientId, $admin);
    }

    /** @test */
    public function it_handles_agency_user_accessing_assigned_client(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $agency = User::factory()->agency()->create();

        $this->roleDetectionService
            ->shouldReceive('canAccessClient')
            ->once()
            ->with($agency, Mockery::on(function ($arg) use ($client) {
                return $arg->id === $client->id;
            }))
            ->andReturn(true);

        // Act
        $result = $this->resolver->resolveClient($client->id, $agency);

        // Assert
        $this->assertEquals($client->id, $result->id);
    }

    /** @test */
    public function it_handles_client_user_without_client_id_parameter(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $clientUser = User::factory()->client()->make();
        $clientUser->setRelation('client', $client);

        $this->roleDetectionService
            ->shouldReceive('isClient')
            ->once()
            ->with($clientUser)
            ->andReturn(true);

        // Act
        $result = $this->resolver->resolveClient(null, $clientUser);

        // Assert
        $this->assertEquals($client->id, $result->id);
    }
}
