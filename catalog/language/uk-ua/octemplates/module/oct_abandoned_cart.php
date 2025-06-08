<?php

// text
$_['text_invalid_cron_pass']                = 'Помилка: Невірний пароль для cron!';
$_['text_cron_done']                        = 'Cron виконано успішно!';
$_['text_module_disabled']                  = 'Модуль відключений!';
$_['text_error_invalid_api_key']            = 'Помилка: Невірний ключ API!';
$_['text_manual_send_nothing']              = 'Помилка відправлення, або кошик вже було сконвертовано!';
$_['text_manual_send_ok']                   = 'Повідомлення успішно відправлено!';
$_['text_error_invalid_restore_token']      = 'Помилка: Невірний токен відновлення!';
$_['text_success_restore_cart']             = 'Кошик успішно відновлено!';
$_['text_error_no_cart']                    = 'Помилка: Кошик не знайдено!';

// Email
$_['email_subject']                         = 'Зберегли для Вас товари, якими ви цікавилися';
$_['email_message']                         = '<div style="font-weight: bold; font-size: 16px; margin: 4px 24px;">Доброго дня, [customer_name]!<br>[promo_code]<br> Товари в кошику очікують на ваше рішення:</div> <br>[products]<br> Ви можете швидко придбати їх, перейшовши за посиланням: <br><br><a style="font-size: 20px; font-weight: bold;" href="[restore_link]">Завершити покупку</a><br> <br> <br> З повагою, [store_name]';
$_['email_promo_code']                      = 'Ваш персональний промокод на знижку:';   

// SMS
$_['default_sms_template']                  = 'Доброго дня![br]Товари в кошику очікують на ваше рішення: [restore_link][br]З повагою, [store_name]';
$_['default_sms_template_vs_promocode']     = 'Доброго дня![br]Ваш промокод: [promo_code][br]Товари в кошику очікують на ваше рішення: [restore_link][br]З повагою, [store_name]';