<?php

use App\Models\AuditTrail;
use App\Models\PurchaseRequest;
use App\Models\Sppg;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('procurement:backfill-pr-requester {--dry-run} {--fallback-sppg-user}', function () {
    $isDryRun = (bool) $this->option('dry-run');
    $useSppgFallback = (bool) $this->option('fallback-sppg-user');

    $missingRequesterPrs = PurchaseRequest::query()
        ->whereNull('requested_by')
        ->get(['id', 'number']);

    if ($missingRequesterPrs->isEmpty()) {
        $this->info('Tidak ada purchase request dengan requester kosong.');

        return;
    }

    $updated = 0;
    $skipped = 0;
    $fallbackMapped = 0;

    foreach ($missingRequesterPrs as $purchaseRequest) {
        $trail = AuditTrail::query()
            ->where('event', 'purchase_request.created')
            ->where('auditable_type', PurchaseRequest::class)
            ->where('auditable_id', $purchaseRequest->id)
            ->whereNotNull('user_id')
            ->latest('created_at')
            ->first();

        $resolvedUserId = (int) ($trail?->user_id ?? 0);

        if ($resolvedUserId <= 0 && $useSppgFallback) {
            $resolvedUserId = (int) (\App\Models\User::query()
                ->where('role', \App\Enums\UserRole::SPPG_USER->value)
                ->where('sppg_id', $purchaseRequest->sppg_id)
                ->orderBy('id')
                ->value('id') ?? 0);

            if ($resolvedUserId > 0) {
                $fallbackMapped++;
            }
        }

        if ($resolvedUserId <= 0) {
            $skipped++;
            continue;
        }

        if (! $isDryRun) {
            $purchaseRequest->update([
                'requested_by' => $resolvedUserId,
            ]);
        }

        $updated++;
    }

    $prefix = $isDryRun ? '[DRY RUN] ' : '';
    $this->info($prefix.'Backfill selesai.');
    $this->line('Total PR requester kosong : '.$missingRequesterPrs->count());
    $this->line('Berhasil dipetakan       : '.$updated);
    if ($useSppgFallback) {
        $this->line('Mapping fallback SPPG    : '.$fallbackMapped);
    }
    $this->line('Tidak ditemukan jejak    : '.$skipped);
})->purpose('Backfill requested_by for purchase requests from audit trails');

Artisan::command('procurement:backfill-sppg-signers {--dry-run} {--force}', function () {
    $isDryRun = (bool) $this->option('dry-run');
    $isForce = (bool) $this->option('force');

    $sppgs = Sppg::query()
        ->orderBy('id')
        ->get(['id', 'code', 'name', 'ka_sppg_name', 'accounting_name']);

    if ($sppgs->isEmpty()) {
        $this->info('Tidak ada data SPPG untuk diproses.');

        return;
    }

    $updated = 0;
    $skipped = 0;
    $noCandidate = 0;

    foreach ($sppgs as $sppg) {
        $currentKa = trim((string) ($sppg->ka_sppg_name ?? ''));
        $currentAccounting = trim((string) ($sppg->accounting_name ?? ''));

        if (! $isForce && $currentKa !== '' && $currentAccounting !== '') {
            $skipped++;
            continue;
        }

        $sppgUsers = User::query()
            ->where('role', \App\Enums\UserRole::SPPG_USER->value)
            ->where('sppg_id', $sppg->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($sppgUsers->isEmpty()) {
            $noCandidate++;
            continue;
        }

        $preferredKa = $sppgUsers->first(function ($user) {
            return preg_match('/\b(ka\.?|kepala|head|pimpinan)\b/i', (string) $user->name) === 1;
        });

        $preferredAccounting = $sppgUsers->first(function ($user) {
            return preg_match('/(akunt|account|finance|keuangan)/i', (string) $user->name) === 1;
        });

        $fallbackPrimary = $sppgUsers->first();
        $fallbackSecondary = $sppgUsers->skip(1)->first();

        $resolvedKa = trim((string) ($preferredKa?->name ?? $fallbackPrimary?->name ?? ''));
        $resolvedAccounting = trim((string) ($preferredAccounting?->name ?? $fallbackSecondary?->name ?? $fallbackPrimary?->name ?? ''));

        if ($resolvedKa === '' && $resolvedAccounting === '') {
            $noCandidate++;
            continue;
        }

        $newKa = $isForce ? $resolvedKa : ($currentKa !== '' ? $currentKa : $resolvedKa);
        $newAccounting = $isForce ? $resolvedAccounting : ($currentAccounting !== '' ? $currentAccounting : $resolvedAccounting);

        if ($newKa === $currentKa && $newAccounting === $currentAccounting) {
            $skipped++;
            continue;
        }

        if (! $isDryRun) {
            $sppg->update([
                'ka_sppg_name' => $newKa !== '' ? $newKa : null,
                'accounting_name' => $newAccounting !== '' ? $newAccounting : null,
            ]);
        }

        $updated++;
        $this->line(sprintf(
            '%s %s (%s): Ka. SPPG="%s", Akuntansi="%s"',
            $isDryRun ? '[DRY RUN]' : '[UPDATED]',
            $sppg->code,
            $sppg->name,
            $newKa !== '' ? $newKa : '-',
            $newAccounting !== '' ? $newAccounting : '-'
        ));
    }

    $this->newLine();
    $this->info(($isDryRun ? '[DRY RUN] ' : '').'Backfill signatory SPPG selesai.');
    $this->line('Total SPPG diproses : '.$sppgs->count());
    $this->line('Berhasil diisi       : '.$updated);
    $this->line('Dilewati             : '.$skipped);
    $this->line('Tanpa kandidat user  : '.$noCandidate);
})->purpose('Backfill ka_sppg_name and accounting_name for SPPG from existing users');
