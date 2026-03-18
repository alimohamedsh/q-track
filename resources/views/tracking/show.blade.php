<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تتبع المشروع - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen p-4 md:p-6 font-sans">
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">تتبع المشروع</h1>

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="mb-4 p-4 bg-blue-100 text-blue-800 rounded-lg">{{ session('info') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="mb-4">
                <span class="text-sm font-medium text-gray-500">رقم المشروع</span>
                <p class="text-lg font-bold text-gray-900">{{ $ticket->ticket_number }}</p>
            </div>
            <div class="mb-4">
                <span class="text-sm font-medium text-gray-500">الحالة</span>
                <p class="text-gray-800">
                    @php
                        $statusLabels = [
                            'open'        => 'مفتوحة',
                            'in_progress' => 'قيد التنفيذ',
                            'closed'      => 'منتهية',
                        ];
                        $visit = $ticket->visits->first();
                    @endphp
                    @if($visit && $visit->check_out_at)
                        <span class="text-green-600 font-medium">✓ انتهت الزيارة</span>
                    @elseif($visit && !$visit->check_out_at)
                        <span class="text-amber-600 font-medium">الفني بدأ العمل</span>
                    @elseif($ticket->assignedTechnician)
                        <span class="text-blue-600 font-medium">الفني في الطريق</span>
                    @else
                        {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                    @endif
                </p>
            </div>
            @if($visit)
                <div class="mb-4">
                    <span class="text-sm font-medium text-gray-500">الفني</span>
                    <p class="text-gray-800">{{ $visit->technician?->name ?? '—' }}</p>
                </div>
            @endif
        </div>

        @if($visitEnded && !$hasEvaluation)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">قيّم الخدمة</h2>
                <form action="{{ route('tracking.evaluation') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="tracking_token" value="{{ $ticket->tracking_token }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تقييم الفني (1–5)</label>
                        <select name="technician_rating" required class="w-full rounded-lg border-gray-300">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ $i === 5 ? 'ممتاز' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تقييم الشركة (1–5)</label>
                        <select name="company_rating" required class="w-full rounded-lg border-gray-300">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ $i === 5 ? 'ممتاز' : '' }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تعليق (اختياري)</label>
                        <textarea name="comment" rows="3" class="w-full rounded-lg border-gray-300" maxlength="1000"></textarea>
                        @error('comment')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full px-6 py-3 rounded-lg font-semibold text-white bg-amber-500 hover:bg-amber-600 transition">
                        إرسال التقييم
                    </button>
                </form>
            </div>
        @elseif($hasEvaluation)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-center text-green-600">
                تم إرسال التقييم. شكراً لك!
            </div>
        @endif
    </div>
</body>
</html>
