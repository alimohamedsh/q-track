<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">أداء الفنيين</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 text-start font-medium">الفني</th>
                        <th class="pb-2 text-start font-medium">عدد الزيارات</th>
                        <th class="pb-2 text-start font-medium">مكتملة</th>
                        <th class="pb-2 text-start font-medium">نسبة النجاح %</th>
                        <th class="pb-2 text-start font-medium">متوسط مدة الزيارة (د)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($technicians as $row)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2">{{ $row['name'] }}</td>
                            <td class="py-2">{{ $row['total_visits'] }}</td>
                            <td class="py-2">{{ $row['completed'] }}</td>
                            <td class="py-2">{{ $row['completion_pct'] }}%</td>
                            <td class="py-2">{{ $row['avg_minutes'] !== null ? $row['avg_minutes'] : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">لا توجد زيارات منتهية بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
