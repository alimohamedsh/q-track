<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequiredItemTemplate;
use App\Models\Ticket;
use App\Models\TicketRequiredItem;
use App\Models\User;
use App\Models\VisitFailureReason;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        // 0. أسباب فشل الزيارة (قائمة قياسية)
        $reasons = [
            ['code' => 'client_not_available', 'label' => 'العميل غير متواجد', 'sort_order' => 1],
            ['code' => 'wrong_address', 'label' => 'عنوان خاطئ أو غير واضح', 'sort_order' => 2],
            ['code' => 'spare_parts_missing', 'label' => 'قطع غيار غير متوفرة', 'sort_order' => 3],
            ['code' => 'equipment_defect', 'label' => 'عطل في المعدات', 'sort_order' => 4],
            ['code' => 'client_refused', 'label' => 'العميل رفض التنفيذ', 'sort_order' => 5],
            ['code' => 'other', 'label' => 'أخرى', 'sort_order' => 99],
        ];
        foreach ($reasons as $r) {
            VisitFailureReason::firstOrCreate(['code' => $r['code']], $r);
        }

        // قوالب متطلبات المشروع (للاختيار من القائمة في التذكرة)
        $templates = [
            ['name' => 'شاشة 24 بوصة', 'sort_order' => 1],
            ['name' => 'Case i3', 'sort_order' => 2],
            ['name' => 'كابل شبكة', 'sort_order' => 3],
            ['name' => 'راوتر', 'sort_order' => 4],
            ['name' => 'لوحة مفاتيح + ماوس', 'sort_order' => 5],
        ];
        foreach ($templates as $i => $t) {
            RequiredItemTemplate::firstOrCreate(
                ['name' => $t['name']],
                ['sort_order' => $t['sort_order'] ?? $i + 1]
            );
        }

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
        $ticket = Ticket::updateOrCreate(
            ['ticket_number' => 'Q-1001'],
            [
                'uuid'               => (string) Str::uuid(),
                'type'               => 'installation',
                'client_name'        => 'Client X',
                'client_address'     => 'Cairo, Egypt',
                'lat'                => 30.0444,
                'lng'                => 31.2357,
                'assigned_to'        => $tech->id,
                'assigned_manager_id'=> $manager->id,
                'status'             => 'open',
                'priority'           => 'high',
            ]
        );

        // 4. متطلبات تجريبية على التذكرة (للتجربة: لوحة الأدمن + لوحة الفني + صفحة التفاصيل)
        if ($ticket->requiredItems()->count() === 0) {
            $tpl = RequiredItemTemplate::orderBy('sort_order')->get();
            $demos = [
                [$tpl->firstWhere('name', 'شاشة 24 بوصة'), 1, null],
                [$tpl->firstWhere('name', 'كابل شبكة'), 2, '5 أمتار'],
                [$tpl->firstWhere('name', 'لوحة مفاتيح + ماوس'), 1, null],
            ];
            foreach ($demos as [$template, $qty, $notes]) {
                if ($template) {
                    TicketRequiredItem::create([
                        'ticket_id'                   => $ticket->id,
                        'required_item_template_id'   => $template->id,
                        'name'                        => $template->name,
                        'quantity'                    => $qty,
                        'notes'                      => $notes,
                    ]);
                }
            }
        }
    }
}