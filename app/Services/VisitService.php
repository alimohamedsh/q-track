<?php

namespace App\Services;

use App\Exceptions\GeofencingException;
use App\Exceptions\VisitException;
use App\Models\Visit;
use App\Models\VisitTaskResult;
use App\Models\Ticket;
use App\Notifications\VisitCompletedNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class VisitService
{
    /**
     * نصف قطر الأرض بالمتر (constant للأداء)
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * المسافة المسموح بها للـ Check-in (بالأمتار)
     */
    private const ALLOWED_DISTANCE_METERS = 200;

    public function recordCheckIn(int $ticketId, float $lat, float $lng)
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new VisitException('يجب تسجيل الدخول أولاً.');
        }

        // 1. نجيب بيانات التذكرة عشان نعرف موقع العميل
        $ticket = Ticket::findOrFail($ticketId);

        // 2. منع Check-in لو الفني عنده زيارة مفتوحة لنفس التذكرة
        $hasOpenVisit = Visit::where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->where('status', 'incomplete')
            ->whereNull('check_out_at')
            ->exists();

        if ($hasOpenVisit) {
            throw new VisitException('لديك زيارة مفتوحة لهذه التذكرة. يجب إتمام Check-out أولاً.');
        }

        // 3. التحقق من وجود إحداثيات موقع العميل
        if (is_null($ticket->lat) || is_null($ticket->lng)) {
            throw new GeofencingException('موقع العميل غير محدد في التذكرة.');
        }

        // 4. نحسب المسافة (بالأمتار)
        $distance = $this->calculateDistance($lat, $lng, $ticket->lat, $ticket->lng);

        // 5. التحقق من المسافة المسموح بها
        if ($distance > self::ALLOWED_DISTANCE_METERS) {
            throw new GeofencingException(
                "أنت بعيد جداً عن موقع العميل. المسافة الحالية: " . round($distance) . " متر."
            );
        }

        // 6. نسجل الزيارة
        return Visit::create([
            'ticket_id'   => $ticketId,
            'user_id'     => $userId,
            'check_in_at' => Carbon::now(),
            'start_lat'   => $lat,
            'start_lng'   => $lng,
            'status'      => 'incomplete',
        ]);
    }

    /**
     * معادلة Haversine لحساب المسافة بالمتر
     * محسّنة للأداء باستخدام constant و early calculations
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Early return لو الإحداثيات متطابقة
        if ($lat1 === $lat2 && $lon1 === $lon2) {
            return 0.0;
        }

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $a = sin($dLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
    /**
     * تسجيل نهاية الزيارة (Check-out) مع إمكانية رفع صور
     */
    public function recordCheckOut(int $visitId, array $data, array $images = [])
    {
        $userId = Auth::id();
        if (!$userId) {
            throw new VisitException('يجب تسجيل الدخول أولاً.');
        }

        $visit = Visit::with('ticket')->findOrFail($visitId);

        // التأكد إن الزيارة تخص الفني المسجل
        if ($visit->user_id !== $userId) {
            throw new VisitException('هذه الزيارة لا تخصك. لا يمكنك إجراء Check-out لها.');
        }

        // التأكد إن الزيارة لسه مفتوحة (incomplete)
        if ($visit->status === 'completed' || $visit->check_out_at !== null) {
            throw new VisitException('هذه الزيارة مقفولة مسبقاً.');
        }

        $status = $data['status'] ?? 'completed';

        // تحديث بيانات الزيارة
        $visit->update([
            'check_out_at'     => Carbon::now(),
            'end_lat'          => $data['lat'],
            'end_lng'          => $data['lng'],
            'status'           => $status,
            'technician_notes' => $data['notes'] ?? '',
            'failure_reason'   => $data['failure_reason'] ?? null,
        ]);

        // تحديث حالة التذكرة: Completed → closed، Incomplete → تبقى open
        if ($status === 'completed') {
            $visit->ticket->update(['status' => 'closed']);
        }

        // حفظ نتائج المهام (visit_task_results)
        foreach ($data['task_results'] ?? [] as $item) {
            VisitTaskResult::updateOrCreate(
                [
                    'visit_id'        => $visit->id,
                    'ticket_task_id'  => $item['ticket_task_id'],
                ],
                [
                    'status'  => $item['status'],
                    'comment' => $item['comment'] ?? null,
                ]
            );
        }

        // حفظ الصور المرفوعة (phase = check_out)
        if (!empty($images)) {
            foreach ($images as $image) {
                if ($image instanceof UploadedFile && $image->isValid()) {
                    $filePath = $image->store('visits/photos', 'public');
                    $visit->attachments()->create([
                        'file_path' => $filePath,
                        'file_type' => 'image',
                        'phase'     => 'check_out',
                    ]);
                }
            }
        }

        // إشعار المدير عند انتهاء الزيارة
        $manager = $visit->ticket->assignedManager;
        if ($manager) {
            $manager->notify(new VisitCompletedNotification($visit));
        }

        return $visit->load(['attachments', 'taskResults', 'ticket']);
    }
}