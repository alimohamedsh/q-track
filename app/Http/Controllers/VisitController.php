<?php

namespace App\Http\Controllers;

use App\Exceptions\GeofencingException;
use App\Exceptions\VisitException;
use App\Services\VisitService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VisitController extends Controller
{
    protected $visitService;

    public function __construct(VisitService $visitService)
    {
        $this->visitService = $visitService;
    }

    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'lat'       => 'required|numeric|between:-90,90',
            'lng'       => 'required|numeric|between:-180,180',
        ]);

        try {
            if (!Auth::check()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            $visit = $this->visitService->recordCheckIn(
                $request->ticket_id,
                $request->lat,
                $request->lng
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'تم تسجيل بداية الزيارة بنجاح',
                'data'    => $visit
            ], 201);

        } catch (GeofencingException|VisitException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'التذكرة المطلوبة غير موجودة'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }

    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'visit_id'       => 'required|exists:visits,id',
            'lat'            => 'required|numeric|between:-90,90',
            'lng'            => 'required|numeric|between:-180,180',
            'status'         => 'required|in:completed,incomplete',
            'notes'          => 'nullable|string|max:1000',
            'failure_reason' => 'nullable|string|max:500|required_if:status,incomplete',
            'images'         => 'nullable|array',
            'images.*'       => 'image|mimes:jpeg,jpg,png,webp|max:2048', // max 2MB per image
        ], [
            'images.*.image'  => 'يجب أن يكون الملف المرفوع صورة',
            'images.*.mimes' => 'نوع الصورة المدعوم: JPEG, JPG, PNG, WEBP',
            'images.*.max'    => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'failure_reason.required_if' => 'يجب تحديد سبب الفشل عند اختيار حالة غير مكتملة',
        ]);

        try {
            if (!Auth::check()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'يجب تسجيل الدخول أولاً'
                ], 401);
            }

            $images = [];
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                $images = is_array($files) ? $files : [$files];
            }

            $visit = $this->visitService->recordCheckOut(
                $request->visit_id,
                [
                    'lat'            => $request->lat,
                    'lng'            => $request->lng,
                    'status'         => $request->status,
                    'notes'          => $request->notes,
                    'failure_reason' => $request->failure_reason,
                ],
                $images
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'تم تسجيل نهاية الزيارة بنجاح',
                'data'    => $visit
            ], 200);

        } catch (VisitException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الزيارة المطلوبة غير موجودة'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }
}
