<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;

class FixStaffPermissions extends Command
{
    protected $signature = 'staff:fix-permissions {--role=staff : The role to fix permissions for}';
    protected $description = 'Fix permissions for existing staff members by applying default permissions';

    public function handle()
    {
        $role = $this->option('role');
        $defaultPermissions = config("permissions.defaults.{$role}", []);

        if (empty($defaultPermissions)) {
            $this->error("No default permissions found for role: {$role}");
            return 1;
        }

        $this->info("Default permissions for {$role}: " . implode(', ', $defaultPermissions));

        $staffMembers = Staff::where('role', $role)->get();
        $count = 0;

        foreach ($staffMembers as $staff) {
            $currentPermissions = $staff->permissions ?? [];
            
            // Merge current permissions with defaults (avoiding duplicates)
            $newPermissions = array_unique(array_merge($currentPermissions, $defaultPermissions));
            
            if ($newPermissions !== $currentPermissions) {
                $staff->permissions = $newPermissions;
                $staff->save();
                $this->info("Updated: {$staff->full_name} ({$staff->username})");
                $count++;
            } else {
                $this->line("Skipped: {$staff->full_name} ({$staff->username}) - already has all permissions");
            }
        }

        $this->info("\nDone! Updated {$count} staff member(s).");
        return 0;
    }
}
