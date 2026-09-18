<?php

namespace Tests\Unit\Support\Storage;

use App\Models\Storage\StorageContract;
use App\Models\Storage\StorageItem;
use App\Models\User;
use App\Support\Storage\ContractDocumentValues;
use Carbon\Carbon;
use Tests\TestCase;

/** Значения для шаблона договора хранения: русские даты, суммы без рубля, строки акта. */
class ContractDocumentValuesTest extends TestCase
{
    /**
     * Договор без обращения к БД: аксессоры и касты работают на несозданной модели.
     *
     * @param  int  $priceKopecks  стоимость за весь срок, копейки
     */
    private function contract(int $priceKopecks = 600000): StorageContract
    {
        $contract = new StorageContract([
            'user_id' => 1,
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-04-30',
            'price' => $priceKopecks,
        ]);

        $contract->id = 100;
        $contract->created_at = Carbon::parse('2026-09-18 10:00:00');
        $contract->setRelation('user', new User([
            'surname' => 'Петров',
            'name' => 'Иван',
            'patronymic' => 'Иванович',
            'phone' => '79001234567',
        ]));

        return $contract;
    }

    /** Дата печатается длинной русской формой: англоязычная локаль дала бы «October» */
    public function test_formats_date_in_russian_long_form(): void
    {
        $values = ContractDocumentValues::forContract($this->contract());

        $this->assertSame('1 октября 2026', $values['starts_on']);
        $this->assertSame('30 апреля 2027', $values['ends_on']);
    }

    /** Сумма — цифрами с разделителем разрядов и без знака рубля (шаблон дописывает «рублей» сам) */
    public function test_formats_money_whole_rubles(): void
    {
        $values = ContractDocumentValues::forContract($this->contract());

        $this->assertSame('6 000', $values['price']);
    }

    /** Копейки не теряются: округление до рубля этот тест завалит */
    public function test_keeps_kopecks_when_present(): void
    {
        $values = ContractDocumentValues::forContract($this->contract(600050));

        $this->assertSame('6 000,50', $values['price']);
    }

    /** Набор ключей совпадает с шаблоном — расхождение ключа и шаблона всплыло бы только в готовом документе */
    public function test_provides_all_placeholders(): void
    {
        $values = ContractDocumentValues::forContract($this->contract());

        $this->assertSame([
            'contract_num' => '00100',
            'created_at' => '18 сентября 2026',
            'user_fio' => 'Петров Иван Иванович',
            'user_phone' => '79001234567',
            'starts_on' => '1 октября 2026',
            'ends_on' => '30 апреля 2027',
            'price' => '6 000',
        ], $values);
    }

    /** Позиция отдаёт номер строки, наименование и особенности; пустое описание — пустая строка, а не пропуск */
    public function test_item_values_with_empty_description(): void
    {
        $rows = ContractDocumentValues::items([
            new StorageItem(['name' => 'Колпаки', 'description' => null]),
        ]);

        $this->assertSame([
            ['item_id' => '1', 'item_name' => 'Колпаки', 'item_desc' => ''],
        ], $rows);
    }

    /** Номера строк идут от 1 по порядку списка, а не по id позиций: колонка «№ п/п» заполнена */
    public function test_numbers_rows_from_one(): void
    {
        $first = new StorageItem(['name' => 'Литые диски R17', 'description' => 'скол']);
        $first->id = 7;
        $second = new StorageItem(['name' => 'Колпаки', 'description' => null]);
        $second->id = 9;

        $rows = ContractDocumentValues::items([$first, $second]);

        $this->assertSame('1', $rows[0]['item_id']);
        $this->assertSame('2', $rows[1]['item_id']);
    }
}
