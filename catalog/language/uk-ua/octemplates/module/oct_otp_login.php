<?php
// Heading
$_['heading_title']             = 'Авторизація';

// Entry
$_['entry_telephone']           = 'Номер телефону';
$_['entry_otp']                 = 'Одноразовий пароль (OTP)';

// Button
$_['button_send_otp']           = 'Відправити';
$_['button_verify_otp']         = 'Підтвердити';
$_['button_resend_otp']         = 'Відправити новий код';

// Text
$_['text_otp_sent']             = 'Код пітвердження відправлено на ваш номер телефону.';
$_['text_otp_resent']           = 'Новий код підтвердження відправлено на ваш номер телефону.';
$_['text_success_login']        = 'Ви успішно увійшли і через секунду будете перенаправлені.';
$_['default_sms_template']      = 'Ваш код підтвердження: [code]';
$_['text_entry_telephone']      = 'Будь ласка, введіть ваш номер телефону для отримання коду підтвердження.';
$_['text_choose_login']         = 'Спосіб авторізації:';
$_['text_otp_tab']              = 'По телефону';
$_['text_email_tab']            = 'По E-mail';
$_['text_otp_not_sent']         = 'Якщо протягом 60 секунд ви не отримали код, виконайте наступні дії:';
$_['text_otp_not_sent_list']    = '<ul class="pl-3"><li>перевірте правильність введення номера телефону;</li><li>переконайтеся, що є зʼєднання з Інтернетом;</li><li>перевірте пам’ять у телефоні, спробуйте видалити кілька діалогів, щоб переконатися у наявності вільного місця;</li><li>перезавантажте телефон.</li></ul>';
$_['text_otp_not_received']     = 'Не отримали код?';

// Error
$_['error_module_disabled']     = 'Модуль авторизації по телефону вимкнено.';
$_['error_telephone']           = 'Введіть коректний номер телефону.';
$_['error_not_found']           = 'Користувача з таким номером телефону не знайдено.';
$_['error_otp']                 = 'Невірний код OTP. Будь ласка, спробуйте ще раз.';
$_['error_otp_expired']         = 'OTP код прострочений. Будь ласка, запросіть новий.';
$_['error_otp_not_found']       = 'OTP код не знайдено. Будь ласка, запросіть новий.';
$_['error_max_attempts']        = 'Ви перевищили максимальну кількість спроб. Будь ласка, спробуйте через %s хвилин(и).';
$_['error_throttle']            = 'Наступна спроба запрошення OTP-паролю можлива через %s секунд(и).';
$_['error_session_expired']     = 'Сесія закінчилася. Будь ласка, почніть процес спочатку.';
$_['error_empty_telephone']     = 'Введіть номер телефону.';
$_['error_csrf']                = 'Невірний токен CSRF.';