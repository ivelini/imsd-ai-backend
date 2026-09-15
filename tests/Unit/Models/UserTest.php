<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

/** Клиент записи: ФИО хранится частями, для показа собирается в одну строку. */
class UserTest extends TestCase
{
    public function test_composes_full_name_from_parts(): void
    {
        $user = new User(['surname' => 'Петров', 'name' => 'Иван', 'patronymic' => 'Иванович']);

        $this->assertSame('Петров Иван Иванович', $user->full_name);
    }

    public function test_skips_empty_parts(): void
    {
        // Сайт не спрашивает фамилию и отчество — в карточке может быть только имя
        $onlyName = new User(['name' => 'Иван']);
        $this->assertSame('Иван', $onlyName->full_name);

        $withoutPatronymic = new User(['surname' => 'Петров', 'name' => 'Иван']);
        $this->assertSame('Петров Иван', $withoutPatronymic->full_name);
    }
}
