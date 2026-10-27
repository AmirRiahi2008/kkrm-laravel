<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ContactMessage;
use Illuminate\Console\Command;

final class PrunePrivateData extends Command
{
    protected $signature = 'app:prune-private-data {--dry-run}';

    protected $description = 'Prune expired closed contact messages and audit logs';

    public function handle(): int
    {
        $messages = ContactMessage::where('status', 'closed')->where('updated_at', '<', now()->subDays(config('kkrm.contact_retention_days')));
        $logs = AuditLog::where('created_at', '<', now()->subDays(config('kkrm.audit_retention_days')));
        $this->info('Expired contacts: '.$messages->count().'; expired audits: '.$logs->count());
        if (! $this->option('dry-run')) {
            $messages->delete();
            $logs->delete();
        }

        return self::SUCCESS;
    }
}
