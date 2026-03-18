<x-filament-panels::page
    @class([
        'fi-resource-view-record-page',
        'fi-resource-tickets',
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @php
        $ticket = $record;
        $statusLabels = [
            'open' => 'مفتوحة',
            'in_progress' => 'قيد التنفيذ',
            'on_hold' => 'معلّقة',
            'revisit_required' => 'إعادة زيارة',
            'closed' => 'مغلقة',
            'canceled' => 'ملغاة',
        ];
        $priorityLabels = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];
        $typeLabels = ['installation' => 'تركيب', 'maintenance' => 'صيانة'];
        $visitStatusLabels = ['completed' => 'مكتملة', 'incomplete' => 'غير مكتملة', 'failed' => 'فاشلة'];
        $visits = $ticket->visits;
        $hasAnyCompletedVisit = $visits->contains(fn ($v) => $v->check_out_at !== null);
    @endphp

    {{-- قسم 1: بيانات عامة --}}
    <x-filament::section>
        <x-slot name="heading">بيانات عامة</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div><span class="text-gray-500">رقم المشروع:</span> <strong>{{ $ticket->ticket_number }}</strong></div>
            <div><span class="text-gray-500">اسم المشروع:</span> {{ $ticket->project_name ?? '—' }}</div>
            <div><span class="text-gray-500">الحالة:</span> {{ $statusLabels[$ticket->status] ?? $ticket->status }}</div>
            <div><span class="text-gray-500">الأولوية:</span> {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</div>
            <div><span class="text-gray-500">نوع المشروع:</span> {{ $typeLabels[$ticket->type] ?? $ticket->type }}</div>
            <div class="md:col-span-2"><span class="text-gray-500">العميل:</span> {{ $ticket->client_name }}</div>
            <div><span class="text-gray-500">موبايل العميل:</span> {{ $ticket->client_phone ?? '—' }}</div>
            <div><span class="text-gray-500">العنوان:</span> {{ $ticket->address ?? '—' }}</div>
            <div><span class="text-gray-500">من أنشأ المشروع:</span> {{ $ticket->creator?->name ?? '—' }}</div>
            <div><span class="text-gray-500">مدير الفنيين:</span> {{ $ticket->assignedManager?->name ?? '—' }}</div>
            <div><span class="text-gray-500">الفني المكلف:</span> {{ $ticket->assignedTechnician?->name ?? '—' }}</div>
            <div><span class="text-gray-500">موعد الجدولة:</span> {{ $ticket->scheduled_at?->format('d/m/Y H:i') ?? '—' }}</div>
            <div><span class="text-gray-500">تاريخ الاستحقاق:</span> {{ $ticket->due_date?->format('d/m/Y') ?? '—' }}</div>
            <div><span class="text-gray-500">تاريخ ووقت الإنشاء:</span> {{ $ticket->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
        </div>
    </x-filament::section>

    {{-- قسم المتطلبات --}}
    @if($ticket->requiredItems && $ticket->requiredItems->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">المتطلبات التي يجب تجهيزها</x-slot>
            <ul class="list-disc list-inside text-gray-700 space-y-1">
                @foreach($ticket->requiredItems as $item)
                    <li>
                        {{ $item->name }}
                        @if($item->quantity > 1) (×{{ $item->quantity }}) @endif
                        @if($item->notes) <span class="text-gray-500 text-xs">– {{ $item->notes }}</span> @endif
                    </li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif

    {{-- قسم 2: ماذا حدث؟ (Timeline الزيارات) --}}
    <x-filament::section>
        <x-slot name="heading">ماذا حدث؟ (الزيارات)</x-slot>
        @if($visits->isEmpty())
            <p class="text-gray-500 text-sm">لا توجد زيارات مسجّلة بعد.</p>
        @else
            <div class="space-y-6">
                @foreach($visits as $visit)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50/50 dark:bg-gray-800/30">
                        <div class="font-medium text-gray-900 dark:text-white mb-2">فني الزيارة: {{ $visit->technician?->name ?? '—' }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm mb-2">
                            <div><span class="text-gray-500">في الطريق (check-in):</span> {{ $visit->check_in_at?->format('d/m/Y H:i') ?? '—' }}</div>
                            <div><span class="text-gray-500">وصل وبدء العمل:</span> {{ $visit->arrived_at?->format('d/m/Y H:i') ?? '—' }}</div>
                            <div><span class="text-gray-500">إنهاء الزيارة:</span> {{ $visit->check_out_at?->format('d/m/Y H:i') ?? '—' }}</div>
                        </div>
                        <div class="text-sm mb-2"><span class="text-gray-500">حالة الزيارة:</span> {{ $visitStatusLabels[$visit->status] ?? $visit->status }}</div>
                        @if(in_array($visit->status, ['incomplete', 'failed']) && ($visit->failureReason || $visit->failure_reason))
                            <div class="text-sm mb-2 p-2 bg-amber-50 dark:bg-amber-900/20 rounded">
                                <span class="text-gray-500">سبب الفشل:</span>
                                {{ $visit->failureReason?->label ?? $visit->failure_reason ?? '—' }}
                                @if($visit->failure_reason && $visit->failureReason) <span class="text-gray-600">({{ $visit->failure_reason }})</span> @endif
                            </div>
                        @endif
                        @if($visit->technician_notes)
                            <div class="text-sm mb-2"><span class="text-gray-500">ملاحظات الفني:</span> {{ $visit->technician_notes }}</div>
                        @endif
                        @if($visit->taskResults && $visit->taskResults->isNotEmpty())
                            <div class="text-sm mt-2">
                                <span class="text-gray-500 font-medium">المهام:</span>
                                <ul class="list-disc list-inside mt-1 space-y-0.5">
                                    @foreach($visit->taskResults as $tr)
                                        <li>
                                            {{ $tr->ticketTask?->description ?? 'مهمة' }}:
                                            {{ $tr->status === 'completed' ? 'تم' : 'لم يتم' }}
                                            @if($tr->comment) – {{ $tr->comment }} @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    {{-- قسم 3: الصور (فقط إن وُجدت زيارة منتهية) --}}
    @if($hasAnyCompletedVisit)
        <x-filament::section>
            <x-slot name="heading">الصور (بعد انتهاء الزيارات)</x-slot>
            @php
                $visitsWithPhotos = $visits->filter(fn ($v) => $v->check_out_at && $v->attachments->isNotEmpty());
            @endphp
            @if($visitsWithPhotos->isEmpty())
                <p class="text-gray-500 text-sm">لا توجد صور مرفوعة.</p>
            @else
                <div class="space-y-6">
                    @foreach($visitsWithPhotos as $v)
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">زيارة بتاريخ {{ $v->check_out_at?->format('d/m/Y H:i') }}</h4>
                            <div class="flex flex-wrap gap-4">
                                @foreach($v->attachments as $att)
                                    @php $url = '/storage/' . ltrim($att->file_path ?? '', '/'); @endphp
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="block">
                                        <img src="{{ $url }}" alt="صورة الزيارة" class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-600 hover:opacity-90 transition" loading="lazy">
                                    </a>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-1">اضغط على الصورة لفتحها بحجم كامل في تبويب جديد.</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    @endif

    {{-- قسم 4: تقييم العميل --}}
    <x-filament::section>
        <x-slot name="heading">تقييم العميل</x-slot>
        @if($ticket->evaluation)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">تقييم الفني (1–5):</span> {{ $ticket->evaluation->technician_rating }}</div>
                <div><span class="text-gray-500">تقييم الشركة (1–5):</span> {{ $ticket->evaluation->company_rating }}</div>
                @if($ticket->evaluation->comment)
                    <div class="sm:col-span-2"><span class="text-gray-500">تعليق العميل:</span> {{ $ticket->evaluation->comment }}</div>
                @endif
                <div><span class="text-gray-500">تاريخ ووقت التقييم:</span> {{ $ticket->evaluation->created_at?->format('d/m/Y H:i') ?? '—' }}</div>
            </div>
        @else
            <p class="text-gray-500 text-sm">لم يتم تسجيل تقييم من العميل بعد.</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
