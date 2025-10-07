<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ResolveClientAccess;
use App\Services\ClientAccessResolver;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ResolveClientAccessTest extends TestCase
{
    use RefreshDatabase;

    private ClientAccessResolver $clientResolver;
    private ResolveClientAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientResolver = Mockery::mock(ClientAccessResolver::class);
        $this->middleware = new ResolveClientAccess($this->clientResolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_resolves_client_from_client_id_parameter(): void
    {
        // Arrange
        $user = User::factory()->admin()->create();
        $client = Client::factory()->create(['name' => 'Test Client']);

        $request = Request::create('/test', 'GET', ['client_id' => $client->id]);
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with($client->id, $user)
            ->andReturn($client);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled, $client) {
            $nextCalled = true;
            $this->assertEquals($client->id, $req->get('resolved_client')->id);
            return new JsonResponse(['success' => true]);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_resolves_client_from_authenticated_client_user(): void
    {
        // Arrange
        $client = Client::factory()->create();
        $user = User::factory()->client()->make();
        $user->setRelation('client', $client);

        $request = Request::create('/test', 'GET');
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with(null, $user)
            ->andReturn($client);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled, $client) {
            $nextCalled = true;
            $this->assertEquals($client->id, $req->get('resolved_client')->id);
            return new JsonResponse(['success' => true]);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertTrue($nextCalled);
    }

    /** @test */
    public function it_validates_user_has_access_to_client(): void
    {
        // Arrange
        $user = User::factory()->agency()->create();
        $client = Client::factory()->create();

        $request = Request::create('/test', 'GET', ['client_id' => $client->id]);
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with($client->id, $user)
            ->andReturn($client);

        $next = function ($req) {
            return new JsonResponse(['success' => true]);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /** @test */
    public function it_returns_403_when_access_denied(): void
    {
        // Arrange
        $user = User::factory()->client()->create();
        $otherClient = Client::factory()->create();

        $request = Request::create('/test', 'GET', ['client_id' => $otherClient->id]);
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with($otherClient->id, $user)
            ->andThrow(new \RuntimeException('You do not have access to this client'));

        $next = function () {
            $this->fail('Next middleware should not be called when access is denied');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Forbidden', $content['error']);
        $this->assertArrayHasKey('message', $content);
    }

    /** @test */
    public function it_returns_422_when_admin_missing_client_id(): void
    {
        // Arrange
        $user = User::factory()->admin()->create();

        $request = Request::create('/test', 'GET'); // No client_id
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with(null, $user)
            ->andThrow(new \InvalidArgumentException('client_id parameter required for admin/agency users'));

        $next = function () {
            $this->fail('Next middleware should not be called when client_id is missing');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('client_id', $content['message']);
    }

    /** @test */
    public function it_merges_resolved_client_into_request(): void
    {
        // Arrange
        $client = Client::factory()->create(['name' => 'Acme Corp']);
        $user = User::factory()->client()->make();
        $user->setRelation('client', $client);

        $request = Request::create('/test', 'GET');
        $request->merge(['authenticated_user' => $user]);

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->andReturn($client);

        $capturedRequest = null;
        $next = function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new JsonResponse(['success' => true]);
        };

        // Act
        $this->middleware->handle($request, $next);

        // Assert
        $this->assertNotNull($capturedRequest);
        $this->assertTrue($capturedRequest->has('resolved_client'));

        $resolvedClient = $capturedRequest->get('resolved_client');
        $this->assertEquals($client->id, $resolvedClient->id);
        $this->assertEquals('Acme Corp', $resolvedClient->name);
    }

    /** @test */
    public function it_handles_client_id_from_route_parameter(): void
    {
        // Arrange
        $user = User::factory()->admin()->make();
        $client = Client::factory()->create();

        $request = Request::create('/test', 'GET');
        $request->merge(['authenticated_user' => $user]);
        $request->setRouteResolver(function () use ($client) {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('parameter')
                ->with('client', null)
                ->andReturn($client->id);
            return $route;
        });

        $this->clientResolver
            ->shouldReceive('resolveClient')
            ->once()
            ->with($client->id, $user)
            ->andReturn($client);

        $next = function () {
            return new JsonResponse(['success' => true]);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }
}
