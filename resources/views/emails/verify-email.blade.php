@php
    $userName = $user->name ?? 'друг';
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение email — WebVitrina</title>
</head>

<body style="margin:0;padding:0;background:#eef0f7;
             font-family:'Inter',Arial,Helvetica,sans-serif;">

<!-- Внешний фон с премиум-градиентами -->
<table width="100%" cellpadding="0" cellspacing="0" style="
        background:#eef0f7;
        background-image:
            radial-gradient(circle at 15% 25%, rgba(79,70,229,0.25), transparent 55%),
            radial-gradient(circle at 85% 18%, rgba(236,72,153,0.25), transparent 60%),
            radial-gradient(circle at 50% 85%, rgba(139,92,246,0.18), transparent 65%);
        background-repeat:no-repeat;">
    <tr>
        <td align="center" style="padding:70px 12px;">

            <!-- Основная карточка 820px -->
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="max-width:820px;background:#ffffff;border-radius:32px;overflow:hidden;
                          box-shadow:0 32px 70px rgba(0,0,0,0.10);">

                <!-- Верхняя полоса — фирменный WebVitrina -->
                <tr>
                    <td style="height:12px;background:linear-gradient(90deg,
                        #4f46e5,
                        #8b5cf6,
                        #6366f1,
                        #ec4899,
                        #4f46e5);"></td>
                </tr>

                <!-- Шапка -->
                <tr>
                    <td align="center" style="padding:52px 60px 18px;
                        background:linear-gradient(135deg,#fafaff 0%,#f3f0ff 100%);">

                        <img src="{{ asset('images/icon.png') }}"
                             alt="WebVitrina"
                             style="height:88px;border-radius:20px;
                                    box-shadow:0 6px 14px rgba(0,0,0,0.12);" />

                        <h1 style="
                            margin:26px 0 10px;
                            font-size:38px;
                            font-weight:800;
                            color:#111827;
                            letter-spacing:-0.6px;">
                            Подтвердите ваш email
                        </h1>

                        <p style="
                            margin:0;
                            color:#6b7280;
                            font-size:18px;
                            line-height:1.45;">
                            Завершите активацию аккаунта и получите полный доступ
                            <br>ко всем возможностям WebVitrina
                        </p>
                    </td>
                </tr>

                <!-- Основной текст -->
                <tr>
                    <td style="padding:40px 70px 20px;
                               color:#111827;
                               font-size:19px;
                               line-height:1.75;">

                        <p style="margin:0 0 20px;">
                            Привет, <strong>{{ $userName }}</strong> 👋
                        </p>

                        <p style="margin:0 0 20px;">
                            Мы рады видеть вас на <strong>WebVitrina</strong>!
                            Для безопасности вашей учётной записи необходимо подтвердить email.
                        </p>

                        <p style="margin:0 0 28px;">
                            После подтверждения вы сможете пользоваться всеми функциями:
                        </p>

                        <ul style="margin:0 0 32px 30px;padding:0;color:#374151;font-size:18px;line-height:1.6;">
                            <li>Покупать товары и оформлять быстрые заказы;</li>
                            <li>Сохранять избранное, историю просмотра и рекомендации;</li>
                            <li>Оставлять отзывы и задавать вопросы продавцам;</li>
                            <li>Использовать личный кабинет продавца (если вы продавец);</li>
                            <li>Получать уведомления о скидках, акциях и обновлениях.</li>
                        </ul>

                    </td>
                </tr>

                <!-- Кнопка -->
                <tr>
                    <td align="center" style="padding:10px 70px 48px;">

                        <a href="{{ $url }}"
                           style="
                                display:inline-block;
                                padding:22px 60px;
                                border-radius:20px;
                                background:linear-gradient(135deg,#4f46e5,#6366f1,#8b5cf6);
                                color:#ffffff;
                                text-decoration:none;
                                font-size:22px;
                                font-weight:700;
                                letter-spacing:0.5px;
                                box-shadow:0 8px 25px rgba(79,70,229,0.35);">
                            Подтвердить email
                        </a>

                    </td>
                </tr>

                <!-- Подсказка -->
                <tr>
                    <td style="padding:0 70px 40px;color:#6b7280;font-size:15px;line-height:1.6;">
                        Если кнопка не работает, откройте ссылку вручную:
                        <br><br>
                        <span style="word-break:break-all;color:#4b5563;font-size:13px;">
                            {{ $url }}
                        </span>
                    </td>
                </tr>

                <!-- Футер -->
                <tr>
                    <td align="center"
                        style="padding:26px 70px;
                               background:#f9fafb;
                               color:#9ca3af;
                               font-size:14px;
                               line-height:1.45;">
                        © {{ date('Y') }} WebVitrina  
                        <br>Вы получили это письмо, потому что создали аккаунт или вошли через Google.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
