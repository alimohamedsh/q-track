@php
    try {
        $record = $getRecord();
    } catch (\Throwable) {
        $record = null;
    }
    $results = $record?->taskResults ?? collect();
@endphp
@if($results->isEmpty())
    <p class="text-gray-500 text-sm">لا توجد نتائج مهام مسجلة</p>
@else
    <div class="space-y-3">
        @foreach($results as $result)
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="font-medium text-gray-800">{{ $result->ticketTask?->description ?? 'مهمة #'.$result->ticket_task_id }}</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $result->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $result->status === 'completed' ? 'تمت' : 'لم تتم' }}
                    </span>
                </div>
                @if($result->comment)
                    <p class="text-sm text-gray-600 mt-2">{{ $result->comment }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
