<?php

namespace App\Support;

use Exception;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Inertia\Ssr\HttpGateway;
use Inertia\Ssr\Response;

/**
 * SSR hanya untuk halaman publik (beranda dan Public/*), yang perlu terbaca mesin
 * pencari dan crawler AI. Dashboard tetap dirender di browser: tidak perlu
 * diindeks, dan editor teksnya tidak bisa berjalan di server.
 *
 * Bila server SSR mati atau lambat, halaman tetap tampil lewat render di browser.
 */
class PublicPagesSsrGateway extends HttpGateway
{
    private const TIMEOUT_SECONDS = 3;

    /**
     * @param  array<string, mixed>  $page
     */
    public function dispatch(array $page): ?Response
    {
        if (! self::isPublicComponent((string) ($page['component'] ?? '')) || ! $this->shouldDispatch()) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->post($this->getUrl('/render'), $page)
                ->throw()
                ->json();
        } catch (Exception $e) {
            if ($e instanceof StrayRequestException) {
                throw $e;
            }

            // Tidak di-report agar log tidak penuh saat server SSR mati; statusnya
            // tampil di dashboard admin (isHealthy()).
            return null;
        }

        if (! is_array($response)) {
            return null;
        }

        return new Response(implode("\n", $response['head'] ?? []), $response['body'] ?? '');
    }

    /**
     * Dipakai dashboard admin; batas waktu pendek agar dashboard tidak ikut lambat.
     */
    public function isHealthy(): bool
    {
        try {
            return Http::timeout(2)->get($this->getUrl('/health'))->successful();
        } catch (Exception $e) {
            if ($e instanceof StrayRequestException) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Status untuk dashboard admin: bundle sudah di-build dan server SSR menjawab.
     *
     * @return array{enabled:bool,bundle:bool,healthy:bool}
     */
    public function status(): array
    {
        $enabled = (bool) config('inertia.ssr.enabled', true);
        $bundle = $this->bundleExists();

        return [
            'enabled' => $enabled,
            'bundle' => $bundle,
            'healthy' => $enabled && $bundle && $this->isHealthy(),
        ];
    }

    public static function isPublicComponent(string $component): bool
    {
        return $component === 'Welcome' || str_starts_with($component, 'Public/');
    }
}
