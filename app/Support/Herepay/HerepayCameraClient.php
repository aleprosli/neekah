<?php

namespace App\Support\Herepay;

use App\Models\CameraPurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Kamera Majlis purchases on Neekah's own Herepay account: the same account,
 * switch and keys as Neekah Pro (HerepayClient), with its own callback route
 * so the signed URL says what is being paid for.
 */
class HerepayCameraClient implements CameraPaymentGateway
{
    public function __construct(private HerepayClient $neekah, private HerepayTransport $transport) {}

    public function isConfigured(): bool
    {
        return $this->neekah->isConfigured();
    }

    public function createPaymentLink(CameraPurchase $purchase, User $payer): string
    {
        return $this->transport->createLink(HerepayCredentials::neekah(), [
            'title' => Str::limit(__('pages.camera.herepay_title', ['tier' => $purchase->tier->label(), 'wedding' => $purchase->wedding->title]), 250),
            'amount' => round((float) $purchase->amount, 2),
            'description' => e(__('pages.camera.herepay_description', ['reference' => $purchase->reference])),
            'usage_type' => 'single',
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'redirect_url' => route('camera.done', ['ref' => $purchase->reference]),
            'callback_url' => url(URL::signedRoute('webhooks.herepay.camera', ['ref' => $purchase->reference], absolute: false)),
            'payer_name' => Str::limit($payer->name, 250, ''),
            'payer_email' => $payer->email,
            'payer_phone' => $payer->phone ? Str::limit($payer->phone, 32, '') : null,
        ]);
    }

    public function parseCallback(Request $request): ?array
    {
        return $this->transport->readCallback($request, HerepayCredentials::neekah());
    }
}
