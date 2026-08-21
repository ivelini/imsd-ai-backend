# Origin в публичных листингах /api/catalog/tires и /api/catalog/wheels

> Source: Код (ProductOriginResource, TireListItemResource, WheelListItemResource, GetTireList, GetWheelList)
> Collected: 2026-08-21
> Published: 2026-08-21

В элемент листинга шин и дисков добавлен объект `origin` — происхождение товара из product_origins:

```json
"origin": {
  "vendor": {"badge": "Shandong Haohua Tire", "description": "<p>…</p>"} | null,
  "manufacture_country": {"badge": "100% Китай", "description": null} | null,
  "manufacture_year": {"badge": "2024-2025", "description": null} | null
} | null
```

- `origin` — вложенный компактный ProductOriginResource (по правилу: relation + whenLoaded, DTO OriginInfo сериализуется сам, как euro_label); null, если у товара нет origin_id.
- `GetTireList`/`GetWheelList` — 'origin' в with() (батч-загрузка).
- Версии кеш-ключей листингов подняты (смена формата payload): tire-list v5 → v6, wheel-list v1 → v2.
- Scramble-типы #[Response] и контракты public-api.json/admin-api.json обновлены.
