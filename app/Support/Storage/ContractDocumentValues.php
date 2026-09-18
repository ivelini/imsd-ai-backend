<?php

namespace App\Support\Storage;

use App\Models\Storage\StorageContract;
use App\Models\Storage\StorageItem;
use App\Support\RussianDate;
use Carbon\Carbon;

/**
 * Значения для шаблона договора хранения: поля договора и строки акта приёма-передачи.
 * Ключи совпадают с плейсхолдерами шаблона; подстановка — в StorageContractDocumentService.
 */
final class ContractDocumentValues
{
    private function __construct() {}

    /**
     * Поля договора: номер, даты, клиент, стоимость.
     *
     * @return array<string, string>
     */
    public static function forContract(StorageContract $contract): array
    {
        /** @var Carbon $createdAt договор создаётся через Eloquent — дата создания всегда есть */
        $createdAt = $contract->created_at;

        return [
            'contract_num' => $contract->number,
            'created_at' => RussianDate::dayWithYear($createdAt),
            'user_fio' => $contract->user->full_name,
            'user_phone' => (string) $contract->user->phone,
            'starts_on' => RussianDate::dayWithYear($contract->starts_on),
            'ends_on' => RussianDate::dayWithYear($contract->ends_on),
            'price' => $contract->price->amount(),
        ];
    }

    /**
     * Строки акта: номер по порядку списка (не по id позиции), наименование и особенности.
     *
     * @param  iterable<StorageItem>  $items
     * @return list<array<string, string>>
     */
    public static function items(iterable $items): array
    {
        $rows = [];

        foreach ($items as $item) {
            $rows[] = [
                'item_id' => (string) (count($rows) + 1),
                'item_name' => $item->name,
                'item_desc' => (string) $item->description,
            ];
        }

        return $rows;
    }
}
