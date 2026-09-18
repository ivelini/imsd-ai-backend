<?php

namespace Tests\Feature\Services\StorageContract;

use App\Models\Storage\StorageContract;
use App\Models\Storage\StorageItem;
use App\Models\User;
use App\Services\StorageContract\StorageContractDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/** Печать договора хранения по настоящему шаблону: поля договора, строки акта, границы. */
class StorageContractDocumentTest extends TestCase
{
    use RefreshDatabase;

    /** Договор с позициями: клиент, срок и стоимость — как в форме панели. */
    private function contract(array $items): StorageContract
    {
        $client = User::factory()->bookingClient()->create([
            'surname' => 'Петров',
            'name' => 'Иван',
            'patronymic' => 'Иванович',
            'phone' => '79001234567',
        ]);

        $contract = StorageContract::factory()->create([
            'user_id' => $client->id,
            'personal_document' => '75 18 074294',
            'starts_on' => '2026-10-01',
            'ends_on' => '2027-04-30',
            'price' => 600000,
        ]);

        foreach ($items as $item) {
            StorageItem::create([
                'storage_contract_id' => $contract->id,
                'name' => $item['name'],
                'description' => $item['description'],
            ]);
        }

        return $contract->load('user', 'items');
    }

    /** Байты готового .docx */
    private function render(StorageContract $contract): string
    {
        return app(StorageContractDocumentService::class)->render($contract);
    }

    /** XML основного документа внутри .docx */
    private function documentXml(string $bytes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($path, $bytes);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        return $xml;
    }

    /** Текст документа без разметки: склейка всех <w:t> */
    private function text(string $xml): string
    {
        preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#s', $xml, $matches);

        return html_entity_decode(implode('', $matches[1]), ENT_QUOTES | ENT_XML1);
    }

    /** Договор печатается целиком: все поля подставлены, плейсхолдеров не осталось */
    public function test_render_fills_real_template(): void
    {
        $contract = $this->contract([
            ['name' => 'Литые диски R17', 'description' => 'царапина на диске 2'],
            ['name' => 'Колпаки', 'description' => null],
        ]);

        $bytes = $this->render($contract);

        $this->assertStringStartsWith('PK', $bytes);

        $text = $this->text($this->documentXml($bytes));
        $this->assertStringContainsString($contract->number, $text);
        $this->assertStringContainsString('Петров Иван Иванович', $text);
        $this->assertStringContainsString('79001234567', $text);
        $this->assertStringContainsString('75 18 074294', $text);
        $this->assertStringContainsString('1 октября 2026', $text);
        $this->assertStringContainsString('30 апреля 2027', $text);
        $this->assertStringContainsString('6 000', $text);
        $this->assertStringNotContainsString('${', $text);
    }

    /** Строк акта ровно по числу позиций: шаблонная строка размножается, номера идут по порядку */
    public function test_render_lists_all_items(): void
    {
        $contract = $this->contract([
            ['name' => 'Литые диски R17', 'description' => 'царапина на диске 2'],
            ['name' => 'Колпаки', 'description' => 'скол'],
            ['name' => 'Секретки', 'description' => 'комплект'],
        ]);

        $xml = $this->documentXml($this->render($contract));

        $this->assertSame(4, preg_match_all('#<w:tr[ >]#', $xml)); // шапка акта + три строки

        $text = $this->text($xml);
        foreach (['Литые диски R17', 'царапина на диске 2', 'Колпаки', 'скол', 'Секретки', 'комплект'] as $value) {
            $this->assertStringContainsString($value, $text);
        }
        foreach (['1', '2', '3'] as $number) {
            $this->assertMatchesRegularExpression("#<w:t[^>]*>{$number}</w:t>#", $xml);
        }
    }

    /** Одна позиция — одна строка акта (нижняя граница размножения) */
    public function test_single_item_gives_single_row(): void
    {
        $contract = $this->contract([
            ['name' => 'Литые диски R17', 'description' => null],
        ]);

        $xml = $this->documentXml($this->render($contract));

        $this->assertSame(2, preg_match_all('#<w:tr[ >]#', $xml)); // шапка акта + одна строка
        $this->assertStringContainsString('Литые диски R17', $this->text($xml));
    }
}
