<?php

use App\Models\Subscriber;
use App\Notifications\VerifySubscription;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('user can subscribe with valid email', function () {
    Notification::fake();

    $response = $this->postJson('/subscribe', [
        'email' => 'test@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Please check your email to verify your subscription.']);

    $this->assertDatabaseHas('subscribers', [
        'email' => 'test@example.com',
        'verified_at' => null,
    ]);

    Notification::assertSentTo(
        Subscriber::where('email', 'test@example.com')->first(),
        VerifySubscription::class
    );
});

test('subscription requires email', function () {
    $response = $this->postJson('/subscribe', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('subscription requires valid email format', function () {
    $response = $this->postJson('/subscribe', [
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('subscription rejects duplicate email', function () {
    Subscriber::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/subscribe', [
        'email' => 'existing@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('subscriber can verify email with valid signed url', function () {
    $subscriber = Subscriber::factory()->create([
        'email' => 'unverified@example.com',
        'verified_at' => null,
    ]);

    $verifyUrl = URL::temporarySignedRoute(
        'subscribe.verify',
        now()->addHours(24),
        ['subscriber' => $subscriber->id]
    );

    $response = $this->get($verifyUrl);

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Subscribe/Verified')
            ->where('alreadyVerified', false)
        );

    $this->assertNotNull($subscriber->fresh()->verified_at);
});

test('already verified subscriber sees already verified message', function () {
    $subscriber = Subscriber::factory()->verified()->create();

    $verifyUrl = URL::temporarySignedRoute(
        'subscribe.verify',
        now()->addHours(24),
        ['subscriber' => $subscriber->id]
    );

    $response = $this->get($verifyUrl);

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Subscribe/Verified')
            ->where('alreadyVerified', true)
        );
});

test('verification fails with invalid signature', function () {
    $subscriber = Subscriber::factory()->create();

    $response = $this->get("/subscribe/verify/{$subscriber->id}");

    $response->assertForbidden();
});

test('verification fails with expired signature', function () {
    $subscriber = Subscriber::factory()->create();

    $expiredUrl = URL::temporarySignedRoute(
        'subscribe.verify',
        now()->subHour(),
        ['subscriber' => $subscriber->id]
    );

    $response = $this->get($expiredUrl);

    $response->assertForbidden();
});

test('subscriber can unsubscribe with valid signed url', function () {
    $subscriber = Subscriber::factory()->verified()->create();

    $unsubscribeUrl = URL::signedRoute(
        'subscribe.unsubscribe',
        ['subscriber' => $subscriber->id]
    );

    $response = $this->get($unsubscribeUrl);

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Subscribe/Unsubscribed'));

    $this->assertDatabaseMissing('subscribers', [
        'id' => $subscriber->id,
    ]);
});

test('unsubscribe fails with invalid signature', function () {
    $subscriber = Subscriber::factory()->verified()->create();

    $response = $this->get("/subscribe/unsubscribe/{$subscriber->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('subscribers', [
        'id' => $subscriber->id,
    ]);
});

test('subscriber model has verified state', function () {
    $unverified = Subscriber::factory()->create();
    $verified = Subscriber::factory()->verified()->create();

    expect($unverified->isVerified())->toBeFalse()
        ->and($verified->isVerified())->toBeTrue();
});

test('subscriber can be marked as verified', function () {
    $subscriber = Subscriber::factory()->create();

    expect($subscriber->isVerified())->toBeFalse();

    $subscriber->markAsVerified();

    expect($subscriber->isVerified())->toBeTrue()
        ->and($subscriber->verified_at)->not->toBeNull();
});
