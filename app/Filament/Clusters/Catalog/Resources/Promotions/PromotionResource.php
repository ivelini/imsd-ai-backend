<?php

namespace App\Filament\Clusters\Catalog\Resources\Promotions;

use App\Filament\Clusters\Catalog\CatalogCluster;
use App\Filament\Clusters\Catalog\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Clusters\Catalog\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Clusters\Catalog\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Clusters\Catalog\Resources\Promotions\Schemas\PromotionForm;
use App\Filament\Clusters\Catalog\Resources\Promotions\Tables\PromotionsTable;
use App\Models\Catalog\Promotion\Promotion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    protected static ?string $cluster = CatalogCluster::class;

    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Акции';

    protected static ?string $modelLabel = 'акция';

    protected static ?string $pluralModelLabel = 'акции';

    public static function form(Schema $schema): Schema
    {
        return PromotionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromotionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromotions::route('/'),
            'create' => CreatePromotion::route('/create'),
            'edit' => EditPromotion::route('/{record}/edit'),
        ];
    }
}
