# Routing

## Implicit route model binding для одиночных ресурсов

Если эндпоинт работает с конкретным экземпляром модели — используй implicit binding: `{model}` в маршруте, типизированный параметр в контроллере. Laravel сам резолвит модель по route key, 404 если не найдена.

```php
// ❌ Ручной ID + findOrFail
Route::get('/catalog/tires/{tireId}/warehouse-stock', WarehouseStockController::class);
public function __invoke(Request $request): Resource
{
    $productId = (int) $request->route('tireId');
    $product = TireProduct::findOrFail($productId);
}

// ✅ Implicit binding
Route::get('/catalog/tires/{tire}/warehouse-stock', TireWarehouseStockController::class);
public function __invoke(TireProduct $tire, Request $request): Resource
{
    // $tire уже разрешён, 404 если не найден
}
```

**Почему:** snake_case-имя в маршруте (конвенция Laravel), меньше кода в контроллере, 404 бесплатно, типизированный параметр = самодокументирование.

**Исключения:** эндпоинт получает ID для передачи в Action без загрузки модели (например, `destroy(int $id)` с `findOrFail` внутри — ок, потому что `Product $product` загрузит модель до вызова метода).
