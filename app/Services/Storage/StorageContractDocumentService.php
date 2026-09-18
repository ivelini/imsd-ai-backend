<?php

namespace App\Services\Storage;

use App\Models\Storage\StorageContract;
use App\Support\Storage\ContractDocumentValues;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Печать договора хранения: заполняет .docx-шаблон значениями договора и отдаёт байты готового файла.
 */
final readonly class StorageContractDocumentService
{
    /** Байты готового .docx: договор и акт приёма-передачи по позициям. */
    public function render(StorageContract $contract): string
    {
        $template = new TemplateProcessor(
            Storage::disk(config('storage_document.disk'))->path(config('storage_document.template_path'))
        );

        foreach (ContractDocumentValues::forContract($contract) as $key => $value) {
            $template->setValue($key, $value);
        }

        $this->fillItems($template, $contract);

        $path = $template->save();
        $bytes = (string) file_get_contents($path);
        unlink($path);

        return $bytes;
    }

    /** Строк акта — по числу позиций: строка-образец размножается, плейсхолдеры получают номер строки. */
    private function fillItems(TemplateProcessor $template, StorageContract $contract): void
    {
        $rows = ContractDocumentValues::items($contract->items);

        if ($rows === []) {
            // Договор без позиций — акт без строк: образец удаляем, иначе в бумаге останутся плейсхолдеры
            $template->deleteRow('item_name');

            return;
        }

        $template->cloneRow('item_name', count($rows));

        foreach ($rows as $index => $row) {
            $number = $index + 1;

            foreach ($row as $key => $value) {
                $template->setValue("{$key}#{$number}", $value);
            }
        }
    }
}
