<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Stores the subscription with explicit consent (double opt-in token generated).
     * The response is identical for new and existing e-mails to avoid leaking who is subscribed.
     */
    public function subscribe(SubscribeNewsletterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $email = Str::lower(trim($data['email']));

        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);

        if (! $subscriber->exists || $subscriber->unsubscribed_at !== null) {
            $subscriber->fill([
                'name' => $data['name'] ?? $subscriber->name,
                'consented_at' => now(),
                'unsubscribed_at' => null,
                'confirmed_at' => null,
                'confirmation_token' => Str::random(48),
            ])->save();
            // TODO(mail): send confirmation e-mail containing $subscriber->confirmation_token.
        }

        return response()->json([
            'message' => 'Inscrição recebida! Confirme seu e-mail para começar a receber a newsletter.',
        ], 202);
    }

    public function confirm(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('confirmation_token', $token)
            ->whereNull('unsubscribed_at')
            ->firstOrFail();

        $subscriber->forceFill(['confirmed_at' => now(), 'confirmation_token' => null])->save();

        return response()->json(['message' => 'Inscrição confirmada. Obrigado!']);
    }
}
