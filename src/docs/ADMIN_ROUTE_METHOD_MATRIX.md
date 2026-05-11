# Admin Route -> Method Matrix (Legacy Trace)

Source of truth used:
- old_project/routes/admin.php
- old_project/app/Http/Controllers/Admin/*Controller.php

Scope rule:
- Migrate only methods that are bound to Admin routes.
- Do NOT migrate extra public methods that are not route-bound (dead path).
- Legacy Blade-only GET edit/show pages are not migrated; replace with shared detail API endpoint per resource.

## Route-bound methods by controller

### DashboardController
- GET / -> index

### UserController
- GET /user/list -> index
- GET /user/create -> create
- POST /user/create -> store
- GET /user/update/{id} -> edit (legacy blade page, skip; use detail API)
- POST /user/update/{id} -> update
- GET /user/delete/{id} -> destroy
- POST /user/delete_multi_ajax -> multiDeleteAjax

### CountryController
- GET /master-data/countries -> index
- GET /master-data/countries/create -> create
- GET /master-data/countries/edit/{country} -> show (legacy blade page, skip; use detail API)
- POST /master-data/countries/store -> store
- POST /master-data/countries/update/{country} -> update
- GET /master-data/countries/delete/{country} -> destroy

### CrawlerErrorLogController
- GET /crawler-log/list-error -> index

### HotelController
- GET /hotel/list -> index
- GET /hotel/create -> create
- POST /hotel/create -> store
- GET /hotel/update/{id} -> edit (legacy blade page, skip; use detail API)
- POST /hotel/update/{id} -> update
- GET /hotel/delete/{id} -> destroy
- POST /hotel/delete_multi_ajax -> multiDeleteAjax
- GET /hotel/login-as-manager/{id} -> loginAsManager
- GET /hotel/duplicate/{id} -> duplicate
- POST /hotel/create-hotel-user -> createHotelUser
- GET /hotel/crawl-setting/{id} -> crawlSetting
- POST /hotel/crawl-setting/{id}/update -> crawlSettingUpdate
- GET /hotel/analytics -> analytics
- GET /hotel/analytics/export -> export
- GET /hotel/analytics/download-pdf -> downloadPdf

### ChainController
- GET /chain/list -> index
- GET /chain/create -> create
- POST /chain/create -> store
- GET /chain/update/{id} -> edit (legacy blade page, skip; use detail API)
- POST /chain/update/{id} -> store
- GET /chain/delete/{id} -> destroy
- POST /chain/delete_multi_ajax -> multiDeleteAjax
- GET /chain/login-as-chain/{id} -> loginAsChain

### OtaController
- GET /ota/list -> index
- GET /ota/create -> create
- POST /ota/create -> store
- GET /ota/update/{id} -> edit (legacy blade page, skip; use detail API)
- POST /ota/update/{id} -> store
- GET /ota/delete/{id} -> destroy
- GET /ota/hotel-code-tag/create -> createHotelCodeTagCSV
- POST /ota/hotel-code-tag/store -> storeHotelCodeTagCSV
- GET /ota/hotel-code-tag/download-sample-csv -> downloadSampleHotelCodeTagCSV

### OtaGroupController
- GET /ota-group/list -> index
- POST /ota-group/create -> store
- GET /ota-group/delete/{id} -> delete

## OTA cluster wiring map (route -> trait/custom)

### OtaController
- GET /ota/list -> OtaController::index -> EntrySearchTrait (query + search request)
- GET /ota/create -> OtaController::create -> custom method (render/form style flow)
- POST /ota/create -> OtaController::store -> EntryCreateTrait (action + create request)
- GET /ota/update/{id} -> OtaController::edit -> EntryDetailTrait (or custom detail view handler)
- POST /ota/update/{id} -> OtaController::update (legacy uses store) -> EntryUpdateTrait
- GET /ota/delete/{id} -> OtaController::destroy -> EntryDeleteTrait
- GET /ota/hotel-code-tag/create -> OtaController::createHotelCodeTagCSV -> custom method
- POST /ota/hotel-code-tag/store -> OtaController::storeHotelCodeTagCSV -> custom method
- GET /ota/hotel-code-tag/download-sample-csv -> OtaController::downloadSampleHotelCodeTagCSV -> custom method

### OtaGroupController
- GET /ota-group/list -> OtaGroupController::index -> EntrySearchTrait
- POST /ota-group/create -> OtaGroupController::store -> EntryCreateTrait
- GET /ota-group/delete/{id} -> OtaGroupController::delete/destroy -> EntryDeleteTrait

Notes for strict migration:
- Only wire trait-backed endpoints for route-bound methods above.
- Do not auto-generate select-items for OTA cluster unless route trace explicitly requires it.

## Full wiring map for all Admin controllers

### DashboardController
- GET / -> DashboardController::index -> custom method

### UserController
- GET /user/list -> UserController::index -> EntrySearchTrait (index wrapper)
- GET /user/create -> UserController::create -> custom method
- POST /user/create -> UserController::store -> EntryCreateTrait
- GET /user/detail/{id} -> UserController::getDetail -> EntryDetailTrait (replacement for legacy GET /user/update/{id})
- POST /user/update/{id} -> UserController::update -> EntryUpdateTrait
- GET /user/delete/{id} -> UserController::destroy -> EntryDeleteTrait
- POST /user/delete_multi_ajax -> UserController::multiDeleteAjax -> custom bulk-delete method

### CountryController
- GET /master-data/countries -> CountryController::index -> EntrySearchTrait (index wrapper)
- GET /master-data/countries/create -> CountryController::create -> custom method
- GET /master-data/countries/detail/{country} -> CountryController::getDetail -> EntryDetailTrait (replacement for legacy GET /master-data/countries/edit/{country})
- POST /master-data/countries/store -> CountryController::store -> EntryCreateTrait
- POST /master-data/countries/update/{country} -> CountryController::update -> EntryUpdateTrait
- GET /master-data/countries/delete/{country} -> CountryController::destroy -> EntryDeleteTrait

### CrawlerErrorLogController
- GET /crawler-log/list-error -> CrawlerErrorLogController::index -> EntrySearchTrait (index wrapper)

### HotelController
- GET /hotel/list -> HotelController::index -> EntrySearchTrait (index wrapper)
- GET /hotel/create -> HotelController::create -> custom method
- POST /hotel/create -> HotelController::store -> EntryCreateTrait
- GET /hotel/detail/{id} -> HotelController::getDetail -> EntryDetailTrait (replacement for legacy GET /hotel/update/{id})
- POST /hotel/update/{id} -> HotelController::update -> EntryUpdateTrait
- GET /hotel/delete/{id} -> HotelController::destroy -> EntryDeleteTrait
- POST /hotel/delete_multi_ajax -> HotelController::multiDeleteAjax -> custom bulk-delete method
- GET /hotel/login-as-manager/{id} -> HotelController::loginAsManager -> custom method
- GET /hotel/duplicate/{id} -> HotelController::duplicate -> custom method
- POST /hotel/create-hotel-user -> HotelController::createHotelUser -> custom method
- GET /hotel/crawl-setting/{id} -> HotelController::crawlSetting -> custom method
- POST /hotel/crawl-setting/{id}/update -> HotelController::crawlSettingUpdate -> custom method
- GET /hotel/analytics -> HotelController::analytics -> custom method
- GET /hotel/analytics/export -> HotelController::export -> custom method
- GET /hotel/analytics/download-pdf -> HotelController::downloadPdf -> custom method

### ChainController
- GET /chain/list -> ChainController::index -> EntrySearchTrait (index wrapper)
- GET /chain/create -> ChainController::create -> custom method
- POST /chain/create -> ChainController::store -> EntryCreateTrait
- GET /chain/detail/{id} -> ChainController::getDetail -> EntryDetailTrait (replacement for legacy GET /chain/update/{id})
- POST /chain/update/{id} -> ChainController::update (legacy route points to store) -> EntryUpdateTrait
- GET /chain/delete/{id} -> ChainController::destroy -> EntryDeleteTrait
- POST /chain/delete_multi_ajax -> ChainController::multiDeleteAjax -> custom bulk-delete method
- GET /chain/login-as-chain/{id} -> ChainController::loginAsChain -> custom method

### OtaController
- GET /ota/list -> OtaController::index -> EntrySearchTrait (index wrapper)
- GET /ota/create -> OtaController::create -> custom method
- POST /ota/create -> OtaController::store -> EntryCreateTrait
- GET /ota/detail/{id} -> OtaController::getDetail -> EntryDetailTrait (replacement for legacy GET /ota/update/{id})
- POST /ota/update/{id} -> OtaController::update (legacy route points to store) -> EntryUpdateTrait
- GET /ota/delete/{id} -> OtaController::destroy -> EntryDeleteTrait
- GET /ota/hotel-code-tag/create -> OtaController::createHotelCodeTagCSV -> custom method
- POST /ota/hotel-code-tag/store -> OtaController::storeHotelCodeTagCSV -> custom method
- GET /ota/hotel-code-tag/download-sample-csv -> OtaController::downloadSampleHotelCodeTagCSV -> custom method

### OtaGroupController
- GET /ota-group/list -> OtaGroupController::index -> EntrySearchTrait (index wrapper)
- POST /ota-group/create -> OtaGroupController::store -> EntryCreateTrait
- GET /ota-group/delete/{id} -> OtaGroupController::delete/destroy -> EntryDeleteTrait

### ApiSystemController
- GET /api_system/list -> ApiSystemController::index -> EntrySearchTrait (index wrapper)
- GET /api_system/create -> ApiSystemController::create -> custom method
- POST /api_system/create -> ApiSystemController::store -> EntryCreateTrait
- GET /api_system/detail/{id} -> ApiSystemController::getDetail -> EntryDetailTrait (replacement for legacy GET /api_system/update/{id})
- POST /api_system/update/{id} -> ApiSystemController::update (legacy route points to store) -> EntryUpdateTrait
- GET /api_system/delete/{id} -> ApiSystemController::destroy -> EntryDeleteTrait

### NotificationController
- GET /notification/create -> NotificationController::create -> custom method
- POST /notification/create -> NotificationController::store -> EntryCreateTrait (if payload maps), otherwise custom
- GET /notification/list -> NotificationController::history -> custom method (non-standard listing method name)
- POST /notification/detail -> NotificationController::detail -> custom method
- GET /notification/view/{id} -> NotificationController::view -> custom method
- GET /notification/delete/{id} -> NotificationController::destroy -> EntryDeleteTrait

### SettingController
- GET /setting -> SettingController::index -> custom method
- POST /setting -> SettingController::update -> EntryUpdateTrait or custom (single-record settings update)
- GET /setting/ignore-characters -> SettingController::ignoreCharacters -> custom method
- POST /setting/ignore-characters -> SettingController::updateIgnoreCharacters -> custom method

### RestaurantApiKeyController
- GET /restaurant_api_key/delete/{id} -> RestaurantApiKeyController::destroy -> EntryDeleteTrait

### ReviewPerformanceController
- GET /review-performance -> ReviewPerformanceController::index -> EntrySearchTrait or custom list view
- GET /review-performance/import -> ReviewPerformanceController::import -> custom method
- POST /review-performance/import/validate -> ReviewPerformanceController::validateImport -> custom method
- GET /review-performance/import/preview/{uuid} -> ReviewPerformanceController::previewImport -> custom method
- GET /review-performance/import/store/{uuid} -> ReviewPerformanceController::importStore -> custom method
- GET /review-performance/download-sample-csv -> ReviewPerformanceController::downloadSampleCSV -> custom method

### AccountController
- GET /account/create -> AccountController::create -> custom method
- POST /account/store -> AccountController::store -> EntryCreateTrait (if payload maps), otherwise custom
- GET /account/download-sample-csv -> AccountController::downloadCsvData -> custom method

### TemplateSurveyController
- GET /template/survey -> TemplateSurveyController::index -> EntrySearchTrait (index wrapper)
- GET /template/survey/create -> TemplateSurveyController::create -> custom method
- POST /template/survey/store -> TemplateSurveyController::store -> EntryCreateTrait
- POST /template/survey/preview -> TemplateSurveyController::preview -> custom method
- GET /template/survey/copy/{id} -> TemplateSurveyController::copy -> custom method
- GET /template/survey/delete/{id} -> TemplateSurveyController::destroy -> EntryDeleteTrait
- GET /template/survey/detail/{id} -> TemplateSurveyController::getDetail -> EntryDetailTrait (replacement for legacy GET /template/survey/edit/{id})
- POST /template/survey/update -> TemplateSurveyController::update -> EntryUpdateTrait or custom (depends on identifier source)

### ReportSendController
- GET /report-send -> ReportSendController::index -> custom method
- POST /report-send/update-selection -> ReportSendController::updateSelection -> custom method
- GET /report-send/send-list -> ReportSendController::sendList -> custom method
- POST /report-send/send-list -> ReportSendController::sendList -> custom method
- GET /report-send/send-list-view -> ReportSendController::sendListView -> custom method
- POST /report-send/send-reports -> ReportSendController::sendReports -> custom method
- GET /report-send/review-performance-data -> ReportSendController::getReviewPerformanceData -> custom method
- GET /report-send/error-list -> ReportSendController::errorList -> custom method

### MessageExclusionRulesController
- GET /excluding-recipient/list -> MessageExclusionRulesController::index -> EntrySearchTrait (index wrapper)
- GET /excluding-recipient/delete/{group_id} -> MessageExclusionRulesController::destroy -> EntryDeleteTrait
- GET /excluding-recipient/create -> MessageExclusionRulesController::create -> custom method
- POST /excluding-recipient/store -> MessageExclusionRulesController::store -> EntryCreateTrait (if payload maps), otherwise custom
- GET /excluding-recipient/detail/{group_id} -> MessageExclusionRulesController::getDetail -> EntryDetailTrait (replacement for legacy GET /excluding-recipient/edit/{group_id})
- POST /excluding-recipient/update/{group_id} -> MessageExclusionRulesController::update -> EntryUpdateTrait

### ApiSystemController
- GET /api_system/list -> index
- GET /api_system/create -> create
- POST /api_system/create -> store
- GET /api_system/update/{id} -> edit (legacy blade page, skip; use detail API)
- POST /api_system/update/{id} -> store
- GET /api_system/delete/{id} -> destroy

### NotificationController
- GET /notification/create -> create
- POST /notification/create -> store
- GET /notification/list -> history
- POST /notification/detail -> detail
- GET /notification/view/{id} -> view
- GET /notification/delete/{id} -> destroy

### SettingController
- GET /setting -> index
- POST /setting -> update
- GET /setting/ignore-characters -> ignoreCharacters
- POST /setting/ignore-characters -> updateIgnoreCharacters

### RestaurantApiKeyController
- GET /restaurant_api_key/delete/{id} -> destroy

### ReviewPerformanceController
- GET /review-performance -> index
- GET /review-performance/import -> import
- POST /review-performance/import/validate -> validateImport
- GET /review-performance/import/preview/{uuid} -> previewImport
- GET /review-performance/import/store/{uuid} -> importStore
- GET /review-performance/download-sample-csv -> downloadSampleCSV

### AccountController
- GET /account/create -> create
- POST /account/store -> store
- GET /account/download-sample-csv -> downloadCsvData

### TemplateSurveyController
- GET /template/survey -> index
- GET /template/survey/create -> create
- POST /template/survey/store -> store
- POST /template/survey/preview -> preview
- GET /template/survey/copy/{id} -> copy
- GET /template/survey/delete/{id} -> destroy
- GET /template/survey/edit/{id} -> edit (legacy blade page, skip; use detail API)
- POST /template/survey/update -> update

### ReportSendController
- GET /report-send -> index
- POST /report-send/update-selection -> updateSelection
- GET /report-send/send-list -> sendList
- POST /report-send/send-list -> sendList
- GET /report-send/send-list-view -> sendListView
- POST /report-send/send-reports -> sendReports
- GET /report-send/review-performance-data -> getReviewPerformanceData
- GET /report-send/error-list -> errorList

### MessageExclusionRulesController
- GET /excluding-recipient/list -> index
- GET /excluding-recipient/delete/{group_id} -> destroy
- GET /excluding-recipient/create -> create
- POST /excluding-recipient/store -> store
- GET /excluding-recipient/edit/{group_id} -> edit (legacy blade page, skip; use detail API)
- POST /excluding-recipient/update/{group_id} -> update

## Legacy methods explicitly excluded (not route-bound)

- HotelController::multiArchiveAjax
- HotelController::archive
- ApiSystemController::multiDeleteAjax
- TestController::*

## Shared model reuse check (app/Models)

Models required by Admin routes already exist in app/Models and should be reused (no module model duplication):
- User, Country, Hotel, Chain, Ota, OtaGroup, ApiSystem, Notification
- AdminSetting/Setting, ReviewPerformance, RestaurantApiKey
- TemplateSurvey, Report, MessageExclusionRule, MessageExclusionRuleDetail
- CrawlerErrorLog, Account

Migration instruction for implementation phase:
- Generate controller/action/query/request/resource via m:* commands only.
- Wire route-used CRUD/search/detail/select by add:action and add:select-item.
- Do not introduce scaffold-only endpoints/methods.

## Generator behavior note (important)

- `m:controller` with `--skip-questions` auto-selects wiring defaults from existing Action/Query classes.
- This means `--wire-*=no` can still be bypassed in non-interactive mode when Action/Query exists.
- Safe workflow for strict route-scope migration:
	- create bare controller first with no optional class generation, or
	- generate Request/Action/Query/Resource separately via `m:request`, `m:action`, `m:query`, `m:resource`,
	- then wire only required endpoints explicitly with `add:action` per route-mapped method.
