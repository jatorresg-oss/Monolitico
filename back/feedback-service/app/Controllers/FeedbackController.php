<?php

namespace App\Controllers;

use App\Models\RetroItem;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;

class FeedbackController
{
    public const CATEGORIAS = ['accion', 'logro', 'impedimento', 'comentario', 'otro'];
    public const CATEGORIAS_FEEDBACK = ['logro', 'impedimento', 'comentario', 'otro'];

    public function get_feedback_by_sprint(int $sprintId, ?string $categoria = null): Collection
    {
        $query = RetroItem::with('sprint')
            ->where('sprint_id', $sprintId)
            ->orderBy('created_at');

        if ($categoria !== null) {
            $query->where('categoria', $categoria);
        } else {
            $query->whereIn('categoria', self::CATEGORIAS_FEEDBACK);
        }

        return $query->get();
    }

    public function get_all_feedback(): Collection
    {
        return RetroItem::with('sprint')
            ->whereIn('categoria', self::CATEGORIAS_FEEDBACK)
            ->orderByDesc('created_at')
            ->get();
    }

    public function get_feedback(int $id): RetroItem
    {
        return RetroItem::with('sprint')->findOrFail($id);
    }

    public function get_summary_by_sprint(int $sprintId): array
    {
        $sprint = Sprint::findOrFail($sprintId);

        $counts = RetroItem::where('sprint_id', $sprintId)
            ->selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();

        return $this->buildSummary(
            $counts,
            $this->actionStats(RetroItem::where('sprint_id', $sprintId)->where('categoria', 'accion')),
            [
                'sprint_id' => (int) $sprint->id,
                'sprint_nombre' => $sprint->nombre,
                'fecha_inicio' => $sprint->fecha_inicio?->format('Y-m-d'),
                'fecha_fin' => $sprint->fecha_fin?->format('Y-m-d'),
            ]
        );
    }

    public function get_global_summary(): array
    {
        $counts = RetroItem::selectRaw('categoria, COUNT(*) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();

        return $this->buildSummary(
            $counts,
            $this->actionStats(RetroItem::where('categoria', 'accion')),
            ['sprints' => Sprint::count()]
        );
    }

    private function buildSummary(array $counts, array $actions, array $extra = []): array
    {
        $porCategoria = [];
        foreach (self::CATEGORIAS as $categoria) {
            $porCategoria[$categoria] = (int) ($counts[$categoria] ?? 0);
        }

        return array_merge($extra, [
            'total_items' => array_sum($porCategoria),
            'por_categoria' => $porCategoria,
            'acciones' => $actions,
        ]);
    }

    private function actionStats($query): array
    {
        $total = (clone $query)->count();
        $cumplidas = (clone $query)->where('cumplida', 1)->count();
        $pendientes = (clone $query)
            ->where(function ($q) {
                $q->whereNull('cumplida')->orWhere('cumplida', 0);
            })
            ->count();

        return [
            'total' => $total,
            'cumplidas' => $cumplidas,
            'pendientes' => $pendientes,
            'tasa_cumplimiento' => $total > 0 ? round(($cumplidas / $total) * 100, 1) : 0,
        ];
    }
}
