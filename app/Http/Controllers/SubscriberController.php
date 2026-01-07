<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriberRequest;
use App\Models\Subscriber;
use App\Notifications\VerifySubscription;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function store(StoreSubscriberRequest $request): JsonResponse
    {
        $subscriber = Subscriber::create([
            'email' => $request->validated('email'),
        ]);

        $subscriber->notify(new VerifySubscription);

        return response()->json([
            'message' => 'Please check your email to verify your subscription.',
        ]);
    }

    public function verify(Subscriber $subscriber): Response
    {
        if ($subscriber->isVerified()) {
            return Inertia::render('Subscribe/Verified', [
                'alreadyVerified' => true,
            ]);
        }

        $subscriber->markAsVerified();

        return Inertia::render('Subscribe/Verified', [
            'alreadyVerified' => false,
        ]);
    }

    public function unsubscribe(Subscriber $subscriber): Response
    {
        $subscriber->delete();

        return Inertia::render('Subscribe/Unsubscribed');
    }
}
