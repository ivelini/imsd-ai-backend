<?php

return [
    /*
    | Диск, на котором лежит .docx-шаблон договора хранения.
    */
    'disk' => 'local',

    /*
    | Путь к шаблону внутри диска (правится в Word, в git не попадает — каталог storage/app/private в игноре).
    */
    'template_path' => 'template/storage_contract.docx',
];
