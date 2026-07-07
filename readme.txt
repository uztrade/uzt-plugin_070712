=== Uztrading Plugin (Top Lists & Broker Cards) ===
Contributors: uztrading
Tags: brokers, top list, ranking, forex, uzbekistan, shortcodes, schema, faq
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Серверные шорткоды для топ-листа брокеров (вариант A задачи 12.1 uztrading.net).

== Description ==

Плагин добавляет три серверных шорткода:

* `[uzt_top_list region="uz" type="forex" limit="10" show_table="true"]` — полный топ-лист: сравнительная таблица + карточки + JSON-LD ItemList.
* `[uzt_broker_card slug="exness" variant="extended"]` — одна карточка брокера (extended / compact).
* `[uzt_faq preset="uz-forex"]` — FAQ + FAQPage JSON-LD.

Данные брокеров берутся из CPT `broker` + ACF-полей, которые вы уже настроили в Задаче 4/7. Порядок, награды и buzz-фразы хранятся в отдельной таблице ранжирования и правятся в админке «Узтрейдинг → Топ-листы».

При активации автоматически создаётся таблица `wp_uzt_rankings` и заливается пилотный топ-10 форекс-брокеров UZ (Exness, IC Markets, FxPro, FP Markets, AMarkets, RoboForex, Admirals, MultiBank, XM Group, AvaTrade).

== Installation ==

1. WP-админка → Плагины → Добавить новый → Загрузить плагин → выбрать `uzt-plugin.zip`.
2. Активируйте плагин.
3. Зайдите в «Узтрейдинг → Топ-листы» — увидите автозалитый топ-10.
4. Вставьте шорткод на нужную страницу:
   [uzt_top_list region="uz" type="forex" limit="10"]

== Changelog ==

= 1.0.0 =
* Первая версия. Шорткоды [uzt_top_list], [uzt_broker_card], [uzt_faq]. JSON-LD ItemList + FAQPage. Мини-админка для правки порядка и наград.
