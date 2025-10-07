<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\EnsureAuthenticated;
use App\Services\RoleDetectionService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Mockery;

class EnsureAuthenticatedTest extends TestCase
{
    private RoleDetectionService $roleDetectionService;
    private EnsureAuthenticated $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleDetectionService = Mockery::mock(RoleDetectionService::class);
        $this->middleware = new EnsureAuthenticated($this->roleDetectionService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_authenticated_user_to_pass(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1, 'name' => 'Test User']);
        $request = Request::create('/test', 'GET');
        $nextCalled = false;

        $this->roleDetectionService
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($user);

        $next = function ($req) use (&$nextCalled, $user) {
            $nextCalled = true;
            // Verify user was merged into request
            $this->assertEquals($user, $req->get('authenticated_user'));
            return new JsonResponse(['success' => true]);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertTrue($nextCalled, 'Next middleware should be called');
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_unauthenticated_user_with_401(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');

        $this->roleDetectionService
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn(null);

        $next = function () {
            $this->fail('Next middleware should not be called for unauthenticated user');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Unauthorized', $content['error']);
    }

    /** @test */
    public function it_merges_authenticated_user_into_request(): void
    {
        // Arrange
        $user = User::factory()->make([
            'id' => 42,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $request = Request::create('/test', 'GET');

        $this->roleDetectionService
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($user);

        $capturedRequest = null;
        $next = function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new JsonResponse(['success' => true]);
        };

        // Act
        $this->middleware->handle($request, $next);

        // Assert
        $this->assertNotNull($capturedRequest);
        $this->assertTrue($capturedRequest->has('authenticated_user'));

        $mergedUser = $capturedRequest->get('authenticated_user');
        $this->assertEquals($user->id, $mergedUser->id);
        $this->assertEquals($user->email, $mergedUser->email);
    }

    /** @test */
    public function it_does_not_modify_request_when_authentication_fails(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $originalRequestData = $request->all();

        $this->roleDetectionService
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn(null);

        $next = function () {
            // Should not be called
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertFalse($request->has('authenticated_user'));
    }
}
