<?php

namespace App\Http\Controllers;

use App\Exceptions\GeofencingException;
use App\Exceptions\VisitException;
use App\Models\Ticket;
use App\Models\Visit;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * لوحة تحكم الفني (Technician Dashboard)
 *
 * يعرض التذاكر المكلف بها الفني، تسجيل الدخول للمهمة (Check-in)، وإنهاء المهمة (Check-out).
 * كل المنطق الخاص بالزيارات موجود في VisitService؛ الكونترولر يستقبل الطلبات ويوجّه النتائج.
 */
class TechnicianDashboardController extends Controller
{
    public function __construct(
        protected VisitService $visitService
    ) {}

    /**
     * عرض التذاكر المفتوحة المكلف بها الفني والتي ما زالت تحتاج منه إجراء (زيارة مفتوحة أو لم يبدأ بعد).
     * بعد إنهاء الزيارة (Check-out) التذكرة تختفي من القائمة.
     */
    public function index(): Response
    {
        $userId = Auth::id();
        $tickets = Ticket::where('assigned_to', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->where(function ($q) use ($userId) {
                // إما لا توجد أي زيارة للفني على التذكرة (لم يضغط «في الطريق» بعد)، أو توجد زيارة مفتوحة (بدون check_out_at)
                $q->whereDoesntHave('visits', fn ($v) => $v->where('user_id', $userId))
                    ->orWhereHas('visits', fn ($v) => $v->where('user_id', $userId)->whereNull('check_out_at'));
            })
            ->with([
                'tasks',
                'requiredItems',
                'visits' => fn ($q) => $q->where('user_id', $userId)->whereNull('check_out_at')->where('status', 'incomplete'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()
            ->view('technician.dashboard', compact('tickets'))
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'        => 'no-cache',
                'Expires'       => '0',
            ]);
    }

    /**
     * «في الطريق»: الفني أعلن أنه متجه للموقع (بدون GPS). العميل يرى "في الطريق".
     */
    public function onTheWay(Request $request): RedirectResponse
    {
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);

        try {
            $this->visitService->recordOnTheWay((int) $request->ticket_id);
            return redirect()
                ->to(route('technician.index', [], false) . '?_=' . time())
                ->with('success', 'تم تسجيل «في الطريق» بنجاح.')
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        } catch (VisitException $e) {
            return redirect()
                ->to(route('technician.index', [], false) . '?_=' . time())
                ->with('error', $e->getMessage())
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        }
    }

    /**
     * «وصلت وبدء العمل»: تسجيل الوصول الفعلي مع التحقق من GPS. العميل يرى "جاري العمل".
     */
    public function arrive(Request $request): RedirectResponse
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'lat'      => 'required|numeric|between:-90,90',
            'lng'      => 'required|numeric|between:-180,180',
        ]);

        try {
            $this->visitService->recordArrived(
                (int) $request->visit_id,
                (float) $request->lat,
                (float) $request->lng
            );
            return redirect()
                ->to(route('technician.index', [], false) . '?_=' . time())
                ->with('success', 'تم تسجيل الوصول وبدء العمل.')
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        } catch (GeofencingException|VisitException $e) {
            return redirect()
                ->to(route('technician.index', [], false) . '?_=' . time())
                ->with('error', $e->getMessage())
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        }
    }

    /**
     * إنهاء المهمة (Check-out): يستقبل visit_id، إحداثيات GPS، حالة الزيارة، نتائج المهام، وملف صور (اختياري)
     */
    public function checkOut(Request $request): RedirectResponse
    {
        $request->validate([
            'visit_id'       => 'required|exists:visits,id',
            'lat'            => 'required|numeric|between:-90,90',
            'lng'            => 'required|numeric|between:-180,180',
            'status'         => 'required|in:completed,incomplete',
            'notes'          => 'nullable|string|max:1000',
            'failure_reason_id' => 'nullable|exists:visit_failure_reasons,id|required_if:status,incomplete',
            'failure_reason'   => 'nullable|string|max:500',
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
        ], [
            'failure_reason_id.required_if' => 'يجب اختيار سبب الفشل عند اختيار حالة غير مكتملة',
        ]);

        $images = $this->collectValidImages($request);

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
                    'failure_reason_id' => $request->failure_reason_id,
                    'failure_reason'   => $request->failure_reason,
                    'task_results'   => $taskResults,
                ],
                $images
            );

            return redirect()
                ->to(route('technician.index', [], false))
                ->with('success', 'تم إنهاء الزيارة بنجاح.')
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        } catch (GeofencingException|VisitException $e) {
            return redirect()
                ->to(route('technician.index', [], false))
                ->with('error', $e->getMessage())
                ->withHeaders(['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache']);
        }
    }

    /**
     * عرض صفحة إنهاء الزيارة. مسموح فقط بعد تسجيل «وصلت وبدء العمل» (arrived_at غير فارغ).
     */
    public function showCheckOutForm(Visit $visit): View|RedirectResponse
    {
        if ($visit->user_id !== Auth::id()) {
            return redirect()->to(route('technician.index', [], false))->with('error', 'هذه الزيارة لا تخصك.');
        }
        if ($visit->check_out_at) {
            return redirect()->to(route('technician.index', [], false))->with('error', 'تم إنهاء هذه الزيارة مسبقاً.');
        }
        if (!$visit->arrived_at) {
            return redirect()->to(route('technician.index', [], false))->with('error', 'يجب تسجيل «وصلت وبدء العمل» أولاً قبل إنهاء المهمة.');
        }

        $visit->load('ticket.tasks');
        $failureReasons = \App\Models\VisitFailureReason::orderBy('sort_order')->get();
        return view('technician.checkout', compact('visit', 'failureReasons'));
    }

    /**
     * يجمع فقط الملفات الصالحة (صور) من الطلب مع حد أقصى 10 ملفات لتفادي المشاكل
     */
    private function collectValidImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }
        $files = $request->file('images');
        $files = is_array($files) ? $files : [$files];
        $valid = [];
        $maxFiles = 10;
        foreach ($files as $file) {
            if (count($valid) >= $maxFiles) {
                break;
            }
            if ($file instanceof UploadedFile && $file->isValid()) {
                $valid[] = $file;
            }
        }
        return $valid;
    }
}
