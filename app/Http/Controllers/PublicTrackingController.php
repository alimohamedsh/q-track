<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketEvaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    /**
     * عرض حالة التذكرة للعميل بناءً على uuid (لضمان الخصوصية)
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $ticket = Ticket::where('uuid', $uuid)->with([
            'visits' => fn ($q) => $q->orderBy('check_in_at', 'desc'),
            'visits.technician',
            'evaluation',
        ])->firstOrFail();

        $latestVisit = $ticket->visits->first();
        $visitEnded = $latestVisit && $latestVisit->check_out_at !== null;
        $hasEvaluation = $ticket->evaluation !== null;

        return view('tracking.show', compact('ticket', 'visitEnded', 'hasEvaluation'));
    }

    /**
     * حفظ تقييم العميل (يظهر فقط بعد انتهاء الزيارة)
     */
    public function storeEvaluation(Request $request): RedirectResponse
    {
        $request->validate([
            'uuid'                => 'required|uuid',
            'technician_rating'   => 'required|integer|min:1|max:5',
            'company_rating'      => 'required|integer|min:1|max:5',
            'comment'             => 'nullable|string|max:1000',
        ]);

        $ticket = Ticket::where('uuid', $request->uuid)->firstOrFail();
        $latestVisit = $ticket->visits()->whereNotNull('check_out_at')->orderBy('check_out_at', 'desc')->first();

        if (!$latestVisit) {
            return redirect()->back()->with('error', 'لم تنتهِ الزيارة بعد.');
        }

        if ($ticket->evaluation) {
            return redirect()->back()->with('info', 'تم إرسال التقييم مسبقاً.');
        }

        TicketEvaluation::create([
            'ticket_id'          => $ticket->id,
            'visit_id'           => $latestVisit->id,
            'technician_rating'  => $request->technician_rating,
            'company_rating'     => $request->company_rating,
            'comment'            => $request->comment,
        ]);

        return redirect()
            ->route('tracking.show', $request->uuid)
            ->with('success', 'شكراً لتقييمك.');
    }
}
