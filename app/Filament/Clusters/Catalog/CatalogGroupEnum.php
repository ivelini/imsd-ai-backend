<?php

namespace App\Filament\Clusters\Catalog;

enum CatalogGroupEnum: string
{
    case Warehouse = 'Склады';
    case DeliveryPoint = 'Точки выдачи';

    case Product = 'Продукция';
}
