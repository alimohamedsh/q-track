<?php

namespace App\Http\Controllers;

use App\Exceptions\GeofencingException;
use App\Exceptions\VisitException;
use App\Models\Ticket;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TechnicianDashboardController extends Controller
{
    public function __construct(
        protected VisitService $visitService
    ) {}

    /**
     * عرض التذاكر المفتوحة المكلف بها الفني
     */
    public function index(): View
    {
        $tickets = Ticket::where('assigned_to', Auth::id())
            ->whereIn('status', ['open', 'in_progress'])
            ->with([
                'tasks',
                'visits' => fn ($q) => $q->where('user_id', Auth::id())->whereNull('check_out_at')->where('status', 'incomplete'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('technician.dashboard', compact('tickets'));
    }

    /**
     * تسجيل دخول (Check-in) - يستقبل lat, lng من JavaScript
     */
    public function checkIn(Request $request): RedirectResponse
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'lat'       => 'required|numeric|between:-90,90',
            'lng'       => 'required|numeric|between:-180,180',
        ]);

        try {
            $this->visitService->recordCheckIn(
                (int) $request->ticket_id,
                (float) $request->lat,
                (float) $request->lng
            );

            return redirect()
                ->route('technician.index')
                ->with('success', 'تم تسجيل بداية الزيارة بنجاح.');
        } catch (GeofencingException|VisitException $e) {
            return redirect()
                ->route('technician.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * إنهاء المهمة (Check-out) - يستقبل visit_id وبيانات إضافية
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $request->validate([
            'visit_id'       => 'required|exists:visits,id',
            'lat'            => 'required|numeric|between:-90,90',
            'lng'            => 'required|numeric|between:-180,180',
            'status'         => 'required|in:completed,incomplete',
            'notes'          => 'nullable|string|max:1000',
            'failure_reason' => 'nullable|string|max:500|required_if:status,incomplete',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,jpg,png,webp|max:2048',
        ], [
            'failure_reason.required_if' => 'يجب تحديد سبب الفشل عند اختيار حالة غير مكتملة',
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            $images = is_array($files) ? $files : [$files];
        }
        if (empty($images)) {
            return redirect()->back()->withInput()->withErrors(['images' => 'يجب رفع صورة واحدة على الأقل عند إنهاء الزيارة']);
        }

        $taskResults = [];
        foreach ($request->input('tasks', []) as $taskId => $data) {
            if (is_array($data) && isset($data['status'])) {
                $taskResults[] = [
                    'ticket_task_id' => (int) $taskId,
                    'status'         => $data['status'],
                    'comment'        => $data['comment'] ?? null,
                ];
            }
        }

        try {
            $this->visitService->recordCheckOut(
                (int) $request->visit_id,
                [
                    'lat'            => (float) $request->lat,
                    'lng'            => (float) $request->lng,
                    'status'         => $request->status,
                    'notes'          => $request->notes,
                    'failure_reason' => $request->failure_reason,
                    'task_results'   => $taskResults,
                ],
                $images
            );

            return redirect()
                ->route('technician.index')
                ->with('success', 'تم إنهاء الزيارة بنجاح.');
        } catch (VisitException $e) {
            return redirect()
                ->route('technician.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * نموذج إنهاء الزيارة
     */
    public function showCheckOutForm(Visit $visit): View|RedirectResponse
    {
        if ($visit->user_id !== Auth::id()) {
            return redirect()->route('technician.index')->with('error', 'هذه الزيارة لا تخصك.');
        }

        if ($visit->check_out_at) {
            return redirect()->route('technician.index')->with('error', 'تم إنهاء هذه الزيارة مسبقاً.');
        }

        $visit->load('ticket.tasks');
        return view('technician.checkout', compact('visit'));
    }
}
