# Настройки уведомлений

Единый источник категорий и значений по умолчанию — `app/Support/NotificationPreferences.php`.
Отсутствующие настройки: все четыре канала сайта включены, три email-канала выключены.
Явно сохранённые значения имеют приоритет. Частичное сохранение формы не сбрасывает остальные значения.
Устаревшее сохранённое `email_security` игнорируется; форма его не отправляет.

Отключение действует только на новые события. Уведомления отключённого канала не создаются;
email и сайт проверяются независимо. Старые записи остаются в истории и в счётчике
непрочитанных до прочтения. Фильтрация истории по новым настройкам не производится.
Доступ к заказам, сообщениям и обращениям не зависит от настроек.

## Соответствие отправителей и событий

| Отправитель | Типы событий | Категория |
| --- | --- | --- |
| CheckoutController | order_created | Заказы |
| OrderPickupWorkflow | order_status_updated, order_payment_recorded, order_delivered, order_cancellation_requested, order_cancellation_rejected, pickup_confirmation_requested | Заказы |
| ChatController, обычный чат | chat_message | Сообщения |
| ChatController, чат поддержки; ChatMessageService → SupportReplyNotificationService, ответ администратора | support_message | Поддержка |
| Admin/ReviewController | review_approved, review_rejected | Отзывы |
| OrderDisputeController | order_dispute_opened | Поддержка |
| Admin/OrderDisputeController | order_dispute_resolved | Поддержка |
| ProductReportController | product_report | Поддержка |
| Admin/ProductReportController | product_report_resolved, product_report_product_hidden, product_report_dismissed, product_hidden_by_report, product_unblocked_by_admin | Поддержка |
| Admin/ProductController | product_unblocked_by_admin | Поддержка |

Поддержка имеет только канал сайта: `email_orders` и `email_messages` не управляют ею.
Новые типы требуют явного добавления в EVENTS; неизвестный тип вызывает исключение.
Внутренние заметки и служебные сообщения создания диалога не являются ответами поддержки.

## Заказ и ошибки

Запись сайта создаётся в транзакции заказа. Отключённый канал возвращает null без ошибки;
доступность продавца проверяется отдельно. Настоящая ошибка записи откатывает весь checkout.
Самовывоз также не требует наличия записи уведомления при отключённом канале.

Email отправляется после окончательного commit. Ошибка очереди/доставки не подавляется:
запрос завершается ошибкой, но заказ и ключ идемпотентности уже сохранены. Повтор с тем же
ключом возвращает существующий заказ. Менеджер транзакций выполняет остальные callbacks
и затем пробрасывает ошибку. Автоматическая повторная отправка email при ошибке постановки
в очередь не добавлена; успешная постановка использует существующий механизм очереди.

## Поддержка и безопасность

Ответ администратора обрабатывается после commit сообщения. В отдельной транзакции
блокируются получатель и сообщение, создаётся уведомление и сохраняется
`messages.support_notified_at`. Повторная обработка того же сообщения безопасна, включая
отключённый канал и последующее включение. Ошибка записи не устанавливает отметку;
её можно повторить через SupportReplyNotificationService. Автоматический фоновый повтор
этого callback не реализован. Разные отправленные сообщения являются разными событиями.

User.updated отправляет обязательные письма только при фактическом изменении пароля,
email или телефона. Создание пользователя, сохранение без изменений и анонимизация
аккаунта не отправляют такие письма. Сброс пароля использует тот же обработчик, без
второго письма из контроллера. Отправка откладывается до commit, если есть транзакция.
Изменения email направляются на прежний адрес; при одновременном изменении телефона или
пароля используется тот же прежний адрес. Адрес для письма о пароле фиксируется в событии,
а не определяется заново при выполнении очереди. Если прежнего email нет, оповещение о
смене контакта не отправляется на новый неподтверждённый адрес.
Подтверждение email и восстановление пароля сохранены. Уведомления о входах не заявлены.
Прямые SQL/bulk updates без событий Eloquent не создают писем безопасности.

## Проверка и применение

Требуется миграция `2026_10_24_000001_add_support_notified_at_to_messages.php`.
Она не запускалась на рабочей базе.

Добавлен NotificationPreferencesTest; обновлены CheckoutNotificationTest,
CheckoutNotificationAtomicityTest, CheckoutIdempotencyTest, ReleaseExperienceTest и
общая фикстура CheckoutTestCase. Проверяются независимые каналы для всех типов,
история, форма/defaults, обязательные письма, поддержка и повторный checkout.

PHP-тесты не запускались: предварительная проверка подключения к webv3_testing
под webvitrina_test на 127.127.126.9:3306 вернула:
`SQLSTATE[HY000] [1045] Access denied for user 'webvitrina_test'@'localhost' (using password: NO)`.
База не подменялась. После восстановления тестового доступа нужно запустить указанные
тесты, существующие CheckoutIdempotencyConcurrencyTest, CheckoutPickupWorkflowTest,
PickupOrderWorkflowTest и тесты Auth/Profile. Работоспособность интеграции пока не подтверждена.

Статические проверки: `php -l` для всех 19 изменённых/добавленных PHP-файлов и
`git diff --check` прошли. Это не заменяет запуск тестов.

## Файлы этой задачи

- app/Support/NotificationPreferences.php
- app/Services/UserNotificationService.php
- app/Services/SupportReplyNotificationService.php
- app/Services/ChatMessageService.php
- app/Services/OrderPickupWorkflow.php
- app/Http/Controllers/CheckoutController.php
- app/Http/Controllers/ChatController.php
- app/Http/Controllers/UserSettingsController.php
- app/Http/Controllers/Auth/NewPasswordController.php
- app/Models/User.php
- app/Notifications/PasswordChangedNotification.php
- app/Notifications/ContactChangedNotification.php
- resources/views/buyer/notifications/settings.blade.php
- database/migrations/2026_10_24_000001_add_support_notified_at_to_messages.php
- tests/Feature/NotificationPreferencesTest.php
- tests/Feature/CheckoutNotificationTest.php
- tests/Feature/CheckoutNotificationAtomicityTest.php
- tests/Feature/CheckoutIdempotencyTest.php
- tests/Feature/ReleaseExperienceTest.php
- tests/Support/CheckoutTestCase.php
- docs/notification-preferences.md

Существовавшие до этой задачи изменения в JS, странице заказа и PickupOrderWorkflowTest
не редактировались. Коммит не создавался.
