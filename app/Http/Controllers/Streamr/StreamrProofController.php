<?php

namespace App\Http\Controllers\Streamr;

use App\Models\StreamrPaymentProof;
use Illuminate\Http\Request;

/**
 * A Streamr receipt, streamed to signed-in admins only:
 * /streamr-proofs?order=STR-...&n=1. This is the address /ecom-orders opens
 * from Payments > View (the payment's screenshot is this relative URL).
 * Receipts carry names, numbers and balances: never cached, never indexed.
 */
class StreamrProofController extends StreamrController
{
    public function show(Request $request, $order = null, $n = null)
    {
        $order = trim((string) ($order ?? $request->query('order', '')));
        $n = (int) ($n ?? $request->query('n', 1));

        try {
            $proof = StreamrPaymentProof::where('orderNumber', $order)->where('n', $n)->where('delete_status', 'active')
                ->whereIn('kind', ['image', 'pdf'])->first(['id', 'orderNumber', 'n', 'mime', 'bytes']);
        } catch (\Throwable $e) {
            $proof = null;
        }
        abort_unless($proof && $proof->bytes !== null && $proof->bytes !== '', 404);

        $bytes = is_resource($proof->bytes) ? (string) stream_get_contents($proof->bytes) : (string) $proof->bytes;
        $mime = in_array($proof->mime, ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'], true)
            ? $proof->mime : 'application/octet-stream';
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $proof->orderNumber) . '-' . (int) $proof->n . '.' . $proof->extension();

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($bytes),
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
