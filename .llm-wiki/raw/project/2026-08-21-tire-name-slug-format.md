# Формат name/slug товаров при импорте: SEO-формулы

> Source: План .claude/plans/import-name-slug-format.md + код (ProductSlugBuilder, TireNameBuilder, ReferenceResolver, UpsertTireProduct)
> Collected: 2026-08-21
> Published: 2026-08-21

Согласованный формат (заказчик, 2026-08-21):

1. `product_models.slug` = только из названия модели (`Str::slug(name)`), без префикса бренда. Уникальность — пара (brand_id, slug); коллизии между брендами безопасны.

2. `tire_products.name` = «Шина {сезон} {бренд} {модель} {width}/{profile} R{diameter} {load}{speed}»:
   - сезон в нижнем регистре («зимняя», «летняя», «всесезон»);
   - индексы склеиваются из раздельных колонок load_index + speed_index («91» + «T» → «91T»);
   - части пропускаются при отсутствии данных. Пример: «Шина зимняя Gislaved Soft Frost 200 195/55 R16 91T».
   - Признаки «шипованная»/runflat в name НЕ выводятся — только в slug.

3. `tire_products.slug` = `{brand-slug}-{model-slug}-{width}-{profile}-r{diameter}-{load}{speed}[-studded][-runflat]`:
   - всё в lowercase («91T» → «91t»), единый разделитель дефис;
   - части только при наличии/true; коллизия → суффикс -2 (ProductSlugService::unique).
   - Пример: `gislaved-soft-frost-200-195-55-r16-91t-studded`.
   - Смена подхода: раньше slug был только из характеристик (стабилен при смене названий), теперь SEO-адрес с брендом/моделью.

4. `wheel_products.slug` = прежняя формула `{brand-slug}-{name}-{width}-{diameter}-{et}-{pcd}-{hub_diameter}`, но точки → дефисы: «7.5» → «7-5», «5*114.3» → «5x114-3», «58.6» → «58-6».

Сфера: единый билдер ProductSlugBuilder/ProductSlugService — импорт и админка (store/update) дают одинаковый slug. Сборка имени шины — чистый TireNameBuilder (ADR 0001). Старые записи переписываются следующим импортом (upsert по EAN).
