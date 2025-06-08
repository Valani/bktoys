<?php
// Heading
$_['heading_title']             = 'Octemplates - Авторизації по SMS (OTP)';

// Text
$_['text_extension']            = 'Розширення';
$_['text_success']              = 'Налаштування модуля успішно змінено!';
$_['text_edit']                 = 'Редагування модуля Авторизації по телефону';
$_['text_enabled']              = 'Увімкнено';
$_['text_disabled']             = 'Вимкнено';
$_['text_install_success']      = 'Модуль успішно встановлено!';
$_['text_uninstall_success']    = 'Модуль успішно видалено!';
$_['text_logs']                 = 'Логи OTP';
$_['text_list']                 = 'Список логів';
$_['text_pagination']           = 'Показано від %d до %d з %d (%d Сторінок)';
$_['text_success_logs_deleted'] = 'Логи успішно видалені!';
$_['text_loading']              = 'Завантаження...';
$_['text_no_results']          = 'Немає даних для відображення.';
$_['text_confirm_delete']      = 'Ви впевнені, що хочете видалити всі логи?';
$_['text_success_logs_deleted'] = 'Логи успішно видалені!';
$_['text_success_ip_logs_deleted'] = 'Логи IP успішно видалені!';
$_['help_ip_logs']              = 'Цей список містить усі IP-адреси, які намагалися авторизуватися та ввели невірний номер телефону або код OTP.';

// Entry
$_['entry_status']              = 'Статус';
$_['entry_otp_length']          = 'Довжина OTP';
$_['entry_otp_expiry']          = 'Термін дії OTP (хвилини)';
$_['entry_max_attempts']        = 'Максимальна кількість спроб';
$_['entry_lockout_time']        = 'Час блокування (хвилини)';
$_['entry_throttle_time']       = 'Час між запитами (секунди)';
$_['entry_phone_mask']          = 'Маска телефону';
$_['entry_logging']             = 'Увімкнути логування';
$_['entry_customer_id']         = 'ID Клієнта';
$_['entry_telephone']           = 'Телефон';

// Help
$_['help_otp_length']           = 'Кількість цифр у OTP коді (від 4 до 10).';
$_['help_otp_expiry']           = 'Час у хвилинах, протягом якого OTP є дійсним.';
$_['help_max_attempts']         = 'Максимальна кількість неправильних вводів OTP або номеру телефону перед блокуванням.';
$_['help_lockout_time']         = 'Час у хвилинах, протягом якого користувач буде заблокований після перевищення спроб.';
$_['help_throttle_time']        = 'Мінімальний час у секундах між запитами OTP для запобігання спаму.';
$_['help_logging']              = 'Увімкніть для запису спроб OTP у лог.';
$_['help_logs']                 = 'Цей список містить усі спроби введення OTP користувачами.';

// Error
$_['error_permission']          = 'Увага: У вас немає прав для зміни модуля Авторизації по телефону!';
$_['error_status']              = 'Статус модуля є обов\'язковим!';
$_['error_otp_length']          = 'Довжина OTP повинна бути числом від 4 до 10!';
$_['error_otp_expiry']          = 'Термін дії OTP повинен бути більше 0!';
$_['error_max_attempts']        = 'Максимальна кількість спроб повинна бути більше 0!';
$_['error_lockout_time']        = 'Час блокування повинен бути більше 0!';
$_['error_throttle_time']       = 'Час між запитами повинен бути більше 0!';
$_['error_no_settings']         = 'Налаштування модуля не знайдено!';
$_['warning_sms_template']      = 'Увага: Шаблон SMS повідомлення не встановлено або відключено! Активуйте шаблон "Авторизація по SMS" у модулі "Налаштування SMS сповіщень".';

// Tabs
$_['tab_settings']              = 'Налаштування';
$_['tab_logs']                  = 'Логи OTP';
$_['tab_ip_logs']               = 'Логи IP';

// Buttons
$_['button_save']               = 'Зберегти';
$_['button_cancel']             = 'Скасувати';
$_['button_delete_logs']        = 'Видалити логи';
$_['button_close']              = 'Закрити';

// Columns
$_['column_log_id']             = 'ID Логу';
$_['column_customer_id']        = 'ID Клієнта';
$_['column_telephone']          = 'Телефон';
$_['column_status']             = 'Статус';
$_['column_date_added']         = 'Дата додавання';
$_['column_attempt_id']         = 'ID';
$_['column_ip_address']         = 'IP Адреса';
$_['column_attempt_count']      = 'Кількість спроб';
$_['column_last_attempt']       = 'Остання спроба';