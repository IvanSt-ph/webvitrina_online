# PROD-10 — verification/review, 2026-09-26

Продолжение существующей реализации. PROD-11 не начат, предыдущие изменения сохранены.
Окончательный полный suite: **461 passed, 3343 assertions, 645.62 s**, exit 0.

1. **Три findings подтверждены.** DB/files race между consistent DB snapshot и архивами; уничтожение recovery copy при неудачном rollback; старый drill через `--force` заменял live storage.
2. **Root cause.** DB dump завершался до архивов без общей координации mutations. Restore не проверял rollback rename и удалял staging в finally. Отдельная БД не изолировала filesystem destination.
3. **Consistency model.** Cooperative local-filesystem barrier: shared flock для mutations, exclusive flock для backup/restore, стабильный общий inode. PROD-03 использует отдельный nonpersistent PDO, REPEATABLE READ, READ ONLY WITH CONSISTENT SNAPSHOT, metadata locks и проверку InnoDB.
4. **T0–T9.** T0: запрос exclusive lock, ожидание активных shared writers. T1: exclusive lock получен — предыдущие protected writes закончены, новые не входят. T2: consistent DB snapshot. T3: dump завершён, transaction commit/connection close, SQL gzip. T4: public archive/gzip. T5: private/chat-images archive/gzip. T6: manifest. T7: SHA256SUMS. T8: publication rename, retention. T9: освобождение в finally. Пока exclusive acquisition только ожидает, новые shared writers ещё могут войти; fairness не обещается, ожидание ограничено timeout. После T5 DB/files consistency уже зафиксирована: T6–T8 держатся под barrier консервативно, оптимизация не вводилась.
5. **Mutation paths.** Полный inventory и entry points: [backup-restore-safety.md](backup-restore-safety.md#reviewed-mutation-inventory). Проверены product main/gallery/derivatives; avatar; shop banner; category icon/original/thumbnail/admin image; review images и model hooks; chat attachments/MessageObserver/Conversation cascade; admin banner upload/crop/delete; account cleanup; purge; health probes; restore.
6. **HTTP/CLI/queue/after-commit.** Web mutations защищены на весь controller call. ProductService и User::delete используют transaction barrier для direct CLI и enclosing transactions. Scheduled purge вызывает ProductService. Queue jobs защищены JobProcessing → JobProcessed/JobExceptionOccurred. Product/account file callbacks выполняются до deferred release; account filesystem cleanup failures логируются для retry. Новым standalone callers низкоуровневых ImageService/ChatImageService/model hooks нужна общая защита DB-plus-files операции.
7. **Crash/lock semantics.** Нет TTL/lease/renewal, lock принадлежит процессу. PHP Throwable в run освобождает handle через finally. Отдельный Windows probe: PHP держал exclusive flock, был принудительно остановлен Stop-Process, тот же существующий файл сразу успешно залочен независимым PHP. Probe удалён. Стабильный main lock-файл сохраняется; его нельзя unlink/rotate. Два backup:run сериализуются exclusive lock; timestamp collision безопасно отклоняется. Непрерывные shared writers могут вызвать timeout ожидания backup.
8. **Maintenance mode.** Backup/restore/barrier не вызывают down/up. Failure tests проверяют сохранение обоих maintenance states. Обычные GET/HEAD чтения не берут HTTP barrier; writable health probe сам является mutation и берёт shared lock.
9. **Backup failure.** Ошибки DB/public/private/manifest/SHA256 не публикуют backup, чистится только owned staging. Pre-existing collision не удаляется. Ошибка retention после rename сохраняет опубликованную копию. HTTP acquisition timeout даёт 503 и Retry-After: 60 без запуска controller; CLI печатает понятную barrier error.
10. **Restore rollback.** Оба архива проверяются и извлекаются до перемещения старых файлов. Extraction failure сохраняет старые public/private files. Activation/permissions failure запускает проверяемые обратные rename по каждому root.
11. **Rollback failure recovery.** При rollback failure staging и .previous-* сохраняются, исходный exception включён в ошибку, recovery path указан. Cleanup после успешной установки отделён commit point и не инициирует rollback.
12. **Isolated drill.** --drill восстанавливает фиксированные public и private/chat-images в существующий пустой отдельный каталог. Тест подтверждает оба restored roots и сохранение live fixture files. Реальный production DB-plus-files restore drill не выполнялся.
13. **Unsafe destination protection.** Проверены кодом: /, drive root, project/live roots и ancestors, backup source/descendants, identical/overlapping roots, sibling constraint, traversal, canonical aliases и Windows case folding. SafeRestoreTest проверяет unsafe destinations, identical roots и symlink/junction alias. Linux semantics reviewed, runtime на Linux не выполнялся; не все перечисленные комбинации имеют отдельный executable test.
14. **Format compatibility.** Manifest v2, SQL gzip, два storage archives и SHA256SUMS сохранены. Complete v2 backups совместимы; incomplete legacy отклоняются. Старый backup не получает retroactive DB/files consistency.
15. **Изменённые файлы.** В этом продолжении изменены: app/Models/User.php; app/Services/AccountDeletionService.php; app/Http/Middleware/CoordinateBackupWrites.php; tests/Feature/BackupAtomicityTest.php; tests/Feature/BackupWriteBarrierTest.php; tests/Feature/OperationalHealthTest.php; tests/Feature/ReleaseExperienceTest.php; tests/Unit/ProductionHealthTest.php; docs/backup-restore-safety.md. Добавлен этот verification report. Полное текущее рабочее дерево, включая сохранённые предыдущие PROD changes, приведено ниже.
16. **Migrations.** PROD-10 не добавляет migrations. Ранее существовавшая незакоммиченная address_snapshot migration сохранена. Live schema не мигрировалась.
17. **Focused.** SafeRestoreTest: 12 passed, 62 assertions. Основной focused/regression batch: 53 passed, 688 assertions. После исправления health isolation: 25 passed, 139 assertions. Новый account outer-commit/private-cleanup-failure test прошёл. HTTP timeout и isolated checklist: 2 passed, 27 assertions. Race test отдельно: 1 passed, 16 assertions; повтор порядка AccountDeletionTest → race: 9 passed, 119 assertions.
18. **PROD-02/03/07/09 regressions.** PrivateBackupTest, BackupSnapshotTest, ProductImageLifecycleTest, OperationalHealthTest прошли в focused batch и окончательном полном suite. Backup health checks ReleaseExperienceTest тоже прошли.
19. **Full suite.** Окончательный php artisan test: 461 passed, 3343 assertions, 645.62 s, exit 0. Предыдущий complete suite также был green: 460 passed, 3339 assertions; затем добавлена проверка HTTP timeout и финальная test isolation.
20. **Syntax/Blade.** php -l всех 53 изменённых PHP/Blade файлов — успешно. php artisan view:cache — успешно; php -l 314 compiled Blade PHP files — успешно.
21. **git diff --check.** Успешно. Проверены текущие diff и status. Commit/push не выполнялись.
22. **Cleanup.** Тестовых PHP processes нет; остаются исходные artisan serve, его server и PHP language server. Main backup barrier успешно получен независимым handle и освобождён. jobs/failed_jobs/cache/cache_locks в webv3_testing: 0/0/0/0. Restore staging/recovery, test archives, test locks, .health-* и maintenance down отсутствуют. Fake public/local disks очищены; старые PROD-03/04 logs сохранены. .env/.env.testing hashes неизменны. Private/chat-images по-прежнему отсутствует. Live public inventory: 48 файлов; 46 совпадают с ранее зафиксированными path/length/SHA256. Два category files заменены в 15:07:41; новые paths указаны в live webv3.categories id=1 с тем же временем обновления (12:07:41 UTC). Это указывает на параллельное live обновление, а не guarded test DB. Live файлы не откатывались. До исправления test isolation health checks могли кратковременно создать/удалить unique probe в live public; постоянных изменений от probe не найдено.
23. **Remaining risks.** Нет гарантий durability при power loss/OS crash, filesystem corruption или атомарности всего DB-plus-files restore. Нет offsite backup и выполненного production restore drill. Backup duration/scale не проверены на больших объёмах. Linux runtime, NFS/distributed locks, multi-host/per-release lock inodes и Windows ACL guarantees не проверены. External scripts, raw SQL/model/path changes, schema changes, Tinker/seeders и future uncoordinated callers вне cooperative boundary. Один ранний повтор race test вернул backup exit 1 без достаточного диагностического output; причина не установлена, отказ не воспроизведён отдельно, в том же порядке классов и в последующих полных suites. Он не объявлен flaky; assertion теперь сохраняет backup output и worker stderr. Изменение live категории требует подтверждения источника и не скрывается за утверждением «live полностью неизменен».

## Полное текущее git status

```text
 M .env.example
 M app/Console/Commands/PurgeOldProducts.php
 M app/Console/Commands/QueueHealthCheck.php
 M app/Console/Commands/RestoreBackupFiles.php
 M app/Console/Commands/RunBackup.php
 M app/Http/Controllers/Admin/OrderController.php
 M app/Http/Controllers/CategoryController.php
 M app/Http/Controllers/OrderController.php
 M app/Http/Controllers/ProfileController.php
 M app/Http/Controllers/Seller/OrderController.php
 M app/Jobs/QueueHealthCheckJob.php
 M app/Models/Order.php
 M app/Models/User.php
 M app/Providers/AppServiceProvider.php
 M app/Repositories/ProductRepository.php
 M app/Services/AccountDeletionService.php
 M app/Services/BackupStorageService.php
 M app/Services/ImageService.php
 M app/Services/ProductService.php
 M app/Support/ProductionHealth.php
 M bootstrap/app.php
 M config/backup.php
 M config/logging.php
 M deploy/supervisor-webvitrina-worker.conf.example
 M docs/deployment.md
 M docs/local-backup.md
 M docs/release-runbook.md
 M resources/views/admin/orders/show.blade.php
 M resources/views/admin/production-checklist.blade.php
 M resources/views/seller/orders/show.blade.php
 M resources/views/shop/order-show.blade.php
 M routes/console.php
 M routes/web.php
 M tests/Feature/AccountDeletionTest.php
 M tests/Feature/ReleaseExperienceTest.php
 M tests/Unit/ProductionHealthTest.php
?? app/Console/Commands/ProductionHealthCheck.php
?? app/Http/Controllers/LivenessController.php
?? app/Http/Middleware/CoordinateBackupWrites.php
?? app/Http/Middleware/NormalizeCatalogInput.php
?? app/Services/BackupWriteBarrier.php
?? app/Services/ProductImageOperation.php
?? app/Services/RestoreDestination.php
?? app/Support/CatalogAttributeFilters.php
?? app/Support/DiskSpace.php
?? config/health.php
?? database/migrations/2026_09_25_000001_add_address_snapshot_to_orders.php
?? deploy/webvitrina-health.service.example
?? deploy/webvitrina-health.timer.example
?? docs/backup-restore-safety.md
?? docs/order-address-snapshot.md
?? docs/product-image-lifecycle.md
?? docs/production-monitoring.md
?? docs/public-catalog-validation.md
?? storage/framework/backup-writes.lock
?? tests/Feature/BackupAtomicityTest.php
?? tests/Feature/BackupWriteBarrierTest.php
?? tests/Feature/OperationalHealthTest.php
?? tests/Feature/OrderAddressSnapshotMigrationTest.php
?? tests/Feature/OrderAddressSnapshotTest.php
?? tests/Feature/ProductImageLifecycleTest.php
?? tests/Feature/PublicCatalogValidationTest.php
?? tests/Feature/SafeRestoreTest.php
?? tests/Support/backup-mutation-worker.php
?? tests/Support/health-process.php
?? tests/Support/product-image-worker.php
?? docs/prod-10-verification.md
```
