<?php
/**
 * @copyright    OCTemplates
 * @support      https://octemplates.net/
 * @license      LICENSE.txt
 */

class ModelOctemplatesModuleOctSmsNotify extends Model {
    private $sender;
    private $settings;
    private $moduleToken;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->settings = $this->config->get('oct_sms_settings');
        $this->sender = $this->settings['sender_name'] ?? '';
        $this->moduleToken = $this->settings['oct_sms_token'] ?? '';
    }

    public function sendNotification(array $data = []) {
        $access_token = isset($data['access_token']) ? trim($data['access_token']) : '';
        
        if ($access_token !== $this->moduleToken || empty($this->sender)) {
            return false;
        }
    
        if (!empty($data['phone']) && !empty($data['message']) && !empty($data['template_code'])) {
            $phone = $this->sanitizePhone($data['phone']);
            $message = $this->sanitizeMessage($data['message']);
            $template_code = $this->sanitizeTemplateCode($data['template_code']);
    
            if ($this->validatePhone($phone) && $this->validateTemplateCode($template_code)) {
                return $this->sendSms($phone, $message, $template_code);
            } 
        }
    
        return false;
    }
    
    public function addSmsLog($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "oct_sms_notify_log` SET phone = '" . $this->db->escape($data['phone']) . "', message = '" . $this->db->escape($data['message']) . "', provider = '" . $this->db->escape($data['provider']) . "', response = '" . $this->db->escape($data['response']) . "', template_code = '" . $this->db->escape($data['template_code']) . "', date_added = NOW()");
    }

    private function sendSms($phone, $message, $template_code) {
        if (!$this->isSmsEnabled($template_code)) {
            return false;
        }

        $providerName = $this->settings['provider'] ?? '';
        $response = $this->sendSmsViaProvider($providerName, $phone, $message);

        if ($response === false) {
            return false;
        }

        if ($this->isLogEnabled()) {
            $this->logSms($phone, $message, $providerName, $response, $template_code);
        }

        return $response;
    }

    private function isSmsEnabled($template_code) {
        return !empty($this->settings['status']) && !empty($this->settings['templates'][$template_code]['status']);
    }

    private function isLogEnabled() {
        return !empty($this->settings['logging']);
    }

    private function sendSmsViaProvider($providerName, $phone, $message) {
        switch ($providerName) {
            case 'turbosms':
                return $this->sendSmsViaTurboSMS($phone, $message);
            case 'alphasms':
                return $this->sendSmsViaAlphaSMS($phone, $message);
            default:
                return false;
        }
    }

    private function sanitizePhone($phone) {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    private function sanitizeMessage($message) {
        $message = htmlspecialchars_decode($message, ENT_QUOTES);
    
        if (!empty($this->settings['translit'])) {
            $message = $this->translit($message);
        }
    
        $message = preg_replace('/[^\p{L}\p{N}\p{P}\p{S}\p{Zs}\n]+/u', '', $message);
        $message = str_replace('[br]', "\n", $message);
    
        return htmlspecialchars(trim($message), ENT_NOQUOTES, 'UTF-8');
    }

    private function sanitizeTemplateCode($template_code) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', trim($template_code));
    }

    private function validatePhone($phone) {
        return preg_match('/^\+?[0-9]{9,15}$/', $phone);
    }

    private function validateTemplateCode($template_code) {
        return isset($this->settings['templates'][$template_code]);
    }

    private function sendSmsViaTurboSMS($phoneNumber, $message) {
        $turbosms_settings = $this->settings['turbosms'] ?? [];
        $hybridSending = $turbosms_settings['hybrid_sending'] ?? false;
        $token = $turbosms_settings['token'] ?? '';
        $viber_sender = $turbosms_settings['viber_sender'] ?? '';

        if (empty($token)) {
            return 'TurboSMS token is missing';
        }

        $url = "https://api.turbosms.ua/message/send.json";
        $data = [
            'recipients' => [$phoneNumber],
            'sms' => [
                'sender' => $this->sender,
                'text' => $message
            ]
        ];

        if ($hybridSending) {
            $data['viber'] = [
                'sender' => $viber_sender,
                'text' => $message,
                'image_url' => null,
                'button' => null
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return $error;
        }

        curl_close($ch);
        return $response;
    }

    private function sendSmsViaAlphaSMS($phoneNumber, $message) {
        $alphasms_settings = $this->settings['alphasms'] ?? [];
        $hybridSending = $alphasms_settings['hybrid_sending'] ?? false;
        $apiKey = $alphasms_settings['api_key'] ?? '';
        $viber_sender = $alphasms_settings['viber_sender'] ?? '';

        if (empty($apiKey)) {
            return 'AlphaSMS credentials are missing';
        }

        $url = "https://alphasms.ua/api/http.php";
        $data = [
            'version' => 'http',
            'from' => $this->sender,
            'to' => $phoneNumber,
            'key' => $apiKey,
            'message' => $message,
            'command' => 'send'
        ];

        if ($hybridSending) {
            $data['viber'] = 1;
            $data['viber_type'] = 'text';
            $data['viber_message'] = $message;
            $data['viber_from'] = $viber_sender;
            $data['viber_lifetime'] = 60;
            $data['viber_sms'] = 1;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return $error;
        }

        curl_close($ch);
        return $response;
    }

    private function logSms($phone, $message, $providerName, $response, $template_code) {
        $this->model_octemplates_module_oct_sms_notify->addSmsLog([
            'phone' => $phone,
            'message' => $message,
            'provider' => $providerName,
            'response' => $response,
            'template_code' => $template_code
        ]);
    }

    private function translit($text) {
        $translit = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd',
            'е' => 'e', 'є' => 'ye', 'ж' => 'zh', 'з' => 'z', 'и' => 'y', 'і' => 'i',
            'ї' => 'yi', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ь' => '', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'H', 'Ґ' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Є' => 'Ye', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'Y', 'І' => 'I',
            'Ї' => 'Yi', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Shch',
            'Ь' => '', 'Ю' => 'Yu', 'Я' => 'Ya'
        ];

        return strtr($text, $translit);
    }
}
