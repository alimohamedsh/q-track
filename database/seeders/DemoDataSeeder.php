<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    /**
     * إنشاء مستخدمين تجريبيين بمعرف (إيميل) وكلمة مرور
     *
     * الإيميلات وكلمات السر:
     * ┌──────────────────────┬──────────────┐
     * │ admin@qtrack.com     │ password     │
     * │ manager@qtrack.com   │ password     │
     * │ tech@qtrack.com      │ password     │
     * └──────────────────────┴──────────────┘
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. الأدوار
        $adminRole   = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $techRole    = Role::firstOrCreate(['name' => 'technician']);

        // 2. إنشاء المستخدمين
        $admin = User::updateOrCreate(
            ['email' => 'admin@qtrack.com'],
            ['name' => 'المسؤول', 'password' => $password]
        );
        $admin->syncRoles([$adminRole]);

        $manager = User::updateOrCreate(
            ['email' => 'manager@qtrack.com'],
            ['name' => 'مدير الفنيين', 'password' => $password]
        );
        $manager->syncRoles([$managerRole]);

        $tech = User::updateOrCreate(
            ['email' => 'tech@qtrack.com'],
            ['name' => 'فني واحد', 'password' => $password]
        );
        $tech->syncRoles([$techRole]);

        // 3. تذكرة تجريبية
        Ticket::updateOrCreate(
            ['ticket_number' => 'Q-1001'],
            [
                'type'            => 'installation',
                'client_name'     => 'Client X',
                'client_address'  => 'Cairo, Egypt',
                'lat'             => 30.0444,
                'lng'             => 31.2357,
                'assigned_to'     => $tech->id,
                'assigned_manager_id' => $manager->id,
                'status'          => 'open',
                'priority'        => 'high',
            ]
        );
    }
}