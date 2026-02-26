<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Models\User;
use Filament\Widgets\Widget;

class TechnicianPerformanceWidget extends Widget
{
    protected static string $view = 'filament.widgets.technician-performance-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $rows = Visit::query()
            ->whereNotNull('check_out_at')
            ->selectRaw("user_id, COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, AVG(TIMESTAMPDIFF(MINUTE, COALESCE(arrived_at, check_in_at), check_out_at)) as avg_min")
            ->groupBy('user_id')
            ->get();

        $userIds = $rows->pluck('user_id')->unique()->values()->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $technicians = $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);
            $total = (int) $row->total;
            $completed = (int) $row->completed;
            return [
                'name'           => $user ? $user->name : '—',
                'total_visits'   => $total,
                'completed'      => $completed,
                'completion_pct' => $total > 0 ? round($completed / $total * 100) : 0,
                'avg_minutes'    => $row->avg_min !== null ? (int) round($row->avg_min) : null,
            ];
        });

        return ['technicians' => $technicians];
    }
}
