<?php
namespace App\Core;

use DateTimeImmutable;

final class CronogramaTrafficLight
{
    private const FILTER_VALUES = ['todos', 'finalizado', 'pendente', 'realizado'];

    private const LEGACY_STATUS_MAP = [
        'Realizado' => 'Finalizado',
        'Não Realizado' => 'Pendente',
    ];

    public static function normalizeFilter(?string $filter): string
    {
        $filter = strtolower(trim((string)$filter));
        if (!in_array($filter, self::FILTER_VALUES, true)) {
            return 'todos';
        }
        return $filter === 'realizado' ? 'finalizado' : $filter;
    }

    public static function occurrence(array $event, ?DateTimeImmutable $today = null): array
    {
        $today = $today ?: new DateTimeImmutable('today');
        $status = (string)($event['status'] ?? 'Planejado');
        $status = self::LEGACY_STATUS_MAP[$status] ?? $status;
        $date = new DateTimeImmutable((string)($event['data'] ?? $today->format('Y-m-d')));

        if ($status === 'Finalizado') {
            return [
                'key' => 'finalizado',
                'filter_key' => 'finalizado',
                'label' => 'Finalizado',
                'badge_class' => 'bg-green-100 text-green-700',
                'cell_class' => 'bg-green-100 text-green-800',
                'row_class' => 'border-l-4 border-green-500',
                'toggle_checked' => true,
            ];
        }

        if ($status === 'Andamento') {
            return [
                'key' => 'andamento',
                'filter_key' => 'pendente',
                'label' => 'Andamento',
                'badge_class' => 'bg-blue-100 text-blue-700',
                'cell_class' => 'bg-blue-100 text-blue-800',
                'row_class' => 'border-l-4 border-blue-500',
                'toggle_checked' => false,
            ];
        }

        if ($status === 'Adiado') {
            return [
                'key' => 'adiado',
                'filter_key' => 'pendente',
                'label' => 'Adiado',
                'badge_class' => 'bg-sky-100 text-sky-700',
                'cell_class' => 'bg-sky-100 text-sky-800',
                'row_class' => 'border-l-4 border-sky-500',
                'toggle_checked' => false,
            ];
        }

        if ($date < $today) {
            return [
                'key' => 'atrasado',
                'filter_key' => 'pendente',
                'label' => 'Atrasado',
                'badge_class' => 'bg-red-100 text-red-700',
                'cell_class' => 'bg-red-100 text-red-800',
                'row_class' => 'border-l-4 border-red-500',
                'toggle_checked' => false,
            ];
        }

        return [
            'key' => 'pendente',
            'filter_key' => 'pendente',
            'label' => $status === 'Planejado' ? 'Planejado' : 'Pendente',
            'badge_class' => 'bg-amber-100 text-amber-700',
            'cell_class' => 'bg-amber-100 text-amber-800',
            'row_class' => 'border-l-4 border-amber-500',
            'toggle_checked' => false,
        ];
    }

    public static function monthCell(array $events, ?DateTimeImmutable $today = null): array
    {
        $today = $today ?: new DateTimeImmutable('today');
        if (empty($events)) {
            return [
                'key' => 'vazio',
                'label' => 'Sem evento',
                'cell_class' => 'text-gray-300',
                'marked' => false,
            ];
        }

        $metas = array_map(static fn(array $event): array => self::occurrence($event, $today), $events);
        $keys = array_column($metas, 'key');

        if (count(array_unique($keys)) === 1 && $keys[0] === 'finalizado') {
            return [
                'key' => 'finalizado',
                'label' => 'Finalizado',
                'cell_class' => 'bg-green-100 text-green-800',
                'marked' => true,
            ];
        }
        if (in_array('atrasado', $keys, true)) {
            return [
                'key' => 'atrasado',
                'label' => 'Atrasado',
                'cell_class' => 'bg-red-100 text-red-800',
                'marked' => true,
            ];
        }
        return [
            'key' => 'pendente',
            'label' => 'Pendente',
            'cell_class' => 'bg-amber-100 text-amber-800',
            'marked' => true,
        ];
    }

    public static function series(array $months, ?DateTimeImmutable $today = null): array
    {
        $today = $today ?: new DateTimeImmutable('today');
        $allEvents = [];
        foreach ($months as $month) {
            foreach (($month['events'] ?? []) as $event) {
                $allEvents[] = $event;
            }
        }

        if (empty($allEvents)) {
            return [
                'key' => 'pendente',
                'filter_key' => 'pendente',
                'label' => 'Pendente',
                'badge_class' => 'bg-amber-100 text-amber-700',
            ];
        }

        $metas = array_map(static fn(array $event): array => self::occurrence($event, $today), $allEvents);
        $keys = array_column($metas, 'key');

        if (count(array_unique($keys)) === 1 && $keys[0] === 'finalizado') {
            return [
                'key' => 'finalizado',
                'filter_key' => 'finalizado',
                'label' => 'Finalizado',
                'badge_class' => 'bg-green-100 text-green-700',
            ];
        }
        if (in_array('atrasado', $keys, true)) {
            return [
                'key' => 'atrasado',
                'filter_key' => 'pendente',
                'label' => 'Atrasado',
                'badge_class' => 'bg-red-100 text-red-700',
            ];
        }
        return [
            'key' => 'pendente',
            'filter_key' => 'pendente',
            'label' => 'Pendente',
            'badge_class' => 'bg-amber-100 text-amber-700',
        ];
    }
}
