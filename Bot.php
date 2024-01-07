<?php
use danog\Decoder\FileId;

class Bot
{
    /**
     * Bot token from @BotFather
     */
    public static $token = '';

    /**
     * Bot name from @BotFather
     */
    public static $name = '';

    /**
     * Telegram Bot API URL / endpoint
     */
    public static $url = "https://api.telegram.org/bot";

    /**
     * for debugging (in CLI mode)
     */
    private static $dbg = '';

    /**
     * parsed-JSON from Telegram server
     */
    public static $getUpdates = [];

    /**
     * Input object
     */
    public static $inputObject = '';

    /**
     * array of commands and the responses
     */
    public static $_command = [];

    /**
     * array of events (types) and the responses
     */
    public static $_onMessage = [];

    /**
     * to be switched ON / OFF on CLI mode
     */
    private static $debug = true;

    /**
     * version of this code
     */
    public static $version = '0.0.1';

    /**
     * message id
     */
    public static $message_id = '';

    /**
     * message text
     */
    public static $message_text = '';

    /**
     * user name (first and last)
     */
    public static $user = '';

    /**
     * user id
     */
    public static $from_id;

    /**
     * chat id
     */
    public static $chat_id;

    /**
     * @var int admin ID
     */
    public static $admin_id;

    /**
     * Set bot token and bot name
     */
    public static function setToken(string $token, string $nama)
    {
        self::$token = $token;
        self::$url .= $token;
        self::$name = $nama;
    }

    /**
     * set Admin ID
     */
    public static function setAdmin(int $id)
    {
        self::$admin_id = $id;
    }

    /**
     * Check if user is Admin
     */
    public static function isAdmin()
    {
        return self::from_id() == self::$admin_id ? true : false;
    }

    /**
     * get bot name
     */
    public static function name()
    {
        if (!empty(self::$name)) return self::$name;
        $url = self::$url . '/getMe';
        if (function_exists('curl_version')) {
            $ch = curl_init($url);
            $json = curl_exec($ch);
            curl_close($ch);
        } else {
            $json = file_get_contents($url);
        }
        $res = json_decode($json);
        return $res->result->username ?? false;
    }

    /**
     * alias of chat()
     */
    public static function cmd(string $command, $answer)
    {
        return self::chat($command, $answer);
    }

    /**
     * Command.
     *
     * @param string          $command
     * @param callable|string $answer
     */
    public static function chat(string $command, $answer)
    {
        if ($command == '*') {
            self::$_onMessage['text'] = $answer;
        } else {
            self::$_command[$command] = $answer;
        }
    }

    /**
     * array of chat
     */
    public static function chat_array(array $array)
    {
        foreach ($array as $key => $value) {
            self::chat($key, $value);
        }
    }

    /**
     * to build keyboard from string
     */
    public static function keyboard(
        string $pattern,
        $input_field_placeholder = 'type here..',
        $resize_keyboard = true,
        $one_time_keyboard = true
    ) {
        /**
         * for example: Bot::keyboard('[text]')
         */
        if (preg_match_all('/\[[^\]]+\]([^\n]+)?([\n]+|$)/', $pattern, $match)) {
            $keyboard = [];
            foreach ($match[0] as $list) {
                preg_match_all('/\[([^\]]+)\]/', $list, $new);
                $array = $new[1];
                foreach ($array as $key => $value) {
                    $array[$key] = ['text' => $value];
                }
                $keyboard[] = $array;
            }
            return json_encode([
                "keyboard" => $keyboard,
                'resize_keyboard' => $resize_keyboard,
                'one_time_keyboard' => $one_time_keyboard,
                'input_field_placeholder' => $input_field_placeholder
            ]);
        }
    }

    /**
     * to build inline_keyboard from string
     */
    public static function inline_keyboard(string $pattern)
    {
        /**
         * Bot::inline_keyboard('[text|text] [url|http://url]')
         */
        if (preg_match_all('/\[[^\|\]]+\|?[^\|\]]+\]([^\n]+)?([\n]+|$)/', $pattern, $match)) {
            $arr = $match[0]; #array
            $inline_keyboard = [];
            foreach ($arr as $list) {
                preg_match_all('/\[[^\|\]]+\|?[^\|\]]+\]/', $list, $new);
                $array = $new[0];
                $arrange = [];
                foreach ($array as $a) {
                    $b = explode('|', $a);
                    $x = [];
                    foreach ($b as $c) {
                        $x[] = $c;
                    }
                    $b0 = trim(str_replace(['[', ']'], '', $x[0]));
                    $b1 = isset($x[1]) ? trim(str_replace(']', '', $x[1])) : '';
                    if (filter_var($b1, FILTER_VALIDATE_URL) !== false) {
                        $arrange[] = [
                            "text" => $b0,
                            "url" => $b1
                        ];
                    } else {
                        if ($b1 == '*' or empty($b1)) {
                            $b1 = $b0;
                        }
                        $arrange[] = [
                            "text" => $b0,
                            "callback_data" => $b1
                        ];
                    }
                }
                $inline_keyboard[] = $arrange;
            }
            return json_encode(["inline_keyboard" => $inline_keyboard]);
        }
    }

    /**
     * to get message ID
     */
    public static function message_id()
    {
        return self::$message_id;
    }

    /**
     * to get message text
     */
    public static function message_text()
    {
        return self::$message_text;
    }

    /**
     * to get first (and last) name of user
     */
    public static function user()
    {
        return self::$user;
    }

    /**
     * get user id
     */
    public static function from_id()
    {
        return self::$from_id;
    }

    /**
     * get chat id
     */
    public static function chat_id()
    {
        return self::$chat_id;
    }

    /**
     * Events.
     *
     * @param string          $types
     * @param callable|string $answer
     */
    public static function on($types, $answer)
    {
        $types = explode('|', $types);
        foreach ($types as $type) {
            self::$_onMessage[$type] = $answer;
        }
    }

    /**
     * Custom regex for command.
     *
     * @param string          $regex
     * @param callable|string $answer
     */
    public static function regex($regex, $answer)
    {
        self::$_command['customRegex:' . $regex] = $answer;
    }

    /**
     * Run telebot.
     *
     * @return bool
     */
    public static function run()
    {
        try {
            if (php_sapi_name() == 'cli') {
                echo 'PHPTelebot version ' . self::$version;
                echo "\nMode\t: Long Polling\n";
                $options = getopt('q', ['quiet']);
                if (isset($options['q']) || isset($options['quiet'])) {
                    self::$debug = false;
                }
                echo "Debug\t: " . (self::$debug ? 'ON' : 'OFF') . "\n";
                self::longPoll();
            } else {
                self::webhook();
            }

            return true;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";

            return false;
        }
    }

    /**
     * Webhook Mode.
     */
    private static function webhook()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
            $input = file_get_contents('php://input');
            self::$inputObject = json_decode($input);
            self::$getUpdates = json_decode($input, true);
            echo self::process();
        } else {
            http_response_code(400);
            throw new Exception('Access not allowed!');
        }
    }

    /**
     * Long Poll Mode.
     *
     * @throws Exception
     */
    private static function longPoll()
    {
        $offset = 0;
        while (true) {
            $req = json_decode(self::send('getUpdates', ['offset' => $offset, 'timeout' => 30]), true);

            // Check error.
            if (isset($req['error_code'])) {
                if ($req['error_code'] == 404) {
                    $req['description'] = 'Incorrect bot token';
                }
                throw new Exception($req['description']);
            }

            if (!empty($req['result'])) {
                foreach ($req['result'] as $update) {
                    self::$getUpdates = $update;
                    $process = self::process();

                    if (self::$debug) {
                        $line = "\n--------------------\n";
                        $outputFormat = "$line %s $update[update_id] $line%s";
                        echo sprintf($outputFormat, 'Query ID :', json_encode($update));
                        echo sprintf($outputFormat, 'Response for :', self::$dbg ?: $process ?: '--NO RESPONSE--');
                        // reset debug
                        self::$dbg = '';
                    }
                    $offset = $update['update_id'] + 1;
                }
            }

            // Delay 1 second
            sleep(1);
        }
    }

    /**
     * Process the message.
     *
     * @return string
     */
    private static function process()
    {
        $get = self::$getUpdates;
        $run = false;

        $events = [
            'message',
            'my_chat_member',
            'callback_query',
        ];

        foreach ($events as $key => $event) {
            if (isset($get[$event])) {
                self::$user = isset($get[$event]['from']['last_name']) ? $get[$event]['from']['first_name'] . ' ' . $get[$event]['from']['last_name'] : $get[$event]['from']['first_name'];
                self::$from_id = $get[$event]['from']['id'];
                self::$chat_id = $get[$event]['chat']['id'] ?? self::$admin_id;
                self::$message_text = $get[$event]['text'] ?? '';
                self::$message_id = $event == 'callback_query' 
                ? $get[$event]['message']['message_id'] 
                : $get[$event]['message_id'];
            }
        }

        if (isset($get['message']['date']) && $get['message']['date'] < (time() - 120)) {
            return '-- Pass --';
        }

        if (self::type() == 'text') {
            self::$message_text = $get['message']['text'];
            $customRegex = false;
            foreach (self::$_command as $cmd => $call) {
                $cr = 'customRegex:';
                $crpos = strpos($cmd, $cr);
                if (false === $crpos) {
                    $regex = '/^(?:' . addcslashes($cmd, '/\+*?[^]$(){}=!<>:-') . ')' . (self::$name ? '(?:@' . self::$name . ')?' : '') . '(?:\s(.*))?$/';
                } elseif (0 === $crpos) {
                    $regex = substr($cmd, strlen($cr));
                    // Remove bot name from command
                    if (self::$name != '') {
                        $get['message']['text'] = preg_replace('/^\/(.*)@' . self::$name . '(.*)/', '/$1$2', $get['message']['text']);
                    }
                    $customRegex = true;
                }
                if ($get['message']['text'] != '*' && preg_match($regex, $get['message']['text'], $matches)) {
                    $run = true;
                    if ($customRegex) {
                        $param = [$matches];
                    } else {
                        $param = isset($matches[1]) ? $matches[1] : '';
                    }
                    break;
                }
            }
        }

        if (isset(self::$_onMessage) && $run === false) {
            if (in_array(self::type(), array_keys(self::$_onMessage))) {
                $run = true;
                $call = self::$_onMessage[self::type()];
            } elseif (isset(self::$_onMessage['*'])) {
                $run = true;
                $call = self::$_onMessage['*'];
            }

            if ($run) {
                switch (self::type()) {
                    case 'callback_query':
                        $param = $get['callback_query']['data'];
                        break;
                    case 'inline_query':
                        $param = $get['inline_query']['query'];
                        break;
                    case 'location':
                        $param = [$get['message']['location']['longitude'], $get['message']['location']['latitude']];
                        break;
                    case 'text':
                        $param = $get['message']['text'];
                        break;
                    default:
                        $param = $get;
                        break;
                }
            }
        }

        if ($run) {
            if (is_callable($call)) {
                if (!is_array($param)) {
                    $count = count((new ReflectionFunction($call))->getParameters());
                    if ($count > 1) {
                        $param = array_pad(explode(' ', $param, $count), $count, '');
                    } else {
                        $param = [$param];
                    }
                }
                return call_user_func_array($call, $param);
            } else {
                if (!isset($get['inline_query'])) {
                    return self::send('sendMessage', ['text' => $call]);
                }
            }
        }
    }

    public static function send(string $action, array $data)
    {
        $upload = false;
        $actionUpload = [
            'sendPhoto',
            'sendAudio',
            'sendDocument',
            'sendSticker',
            'sendVideo',
            'sendVoice',
        ];
        
        $needChatId = [
            'sendMessage',
            'forwardMessage',
            'sendPhoto',
            'sendAudio',
            'sendDocument',
            'sendSticker',
            'sendVideo',
            'sendVoice',
            'sendLocation',
            'sendVenue',
            'sendContact',
            'sendChatAction',
            'editMessageText',
            'editMessageCaption',
            'editMessageReplyMarkup',
            'sendGame',
            'deleteMessage',
            'editMessageMedia',
        ];

        $needMessageId = [
            'editMessageText',
            'deleteMessage',
            'editMessageReplyMarkup',
            'editMessageCaption',
            'editMessageMedia',
        ];

        $needMediaId = [
            'editMessageMedia',
        ];

        $needCallbackQueryId = [
            'answerCallbackQuery',
        ];

        if (in_array($action, $actionUpload)) {
            $field = str_replace('send', '', strtolower($action));

            if (is_file($data[$field])) {
                $upload = true;
                $data[$field] = self::curlFile($data[$field]);
            }
        }

        if (isset(self::$getUpdates['callback_query'])) {
            self::$getUpdates = self::$getUpdates['callback_query'];
        }

        // automate callback query id
        if (in_array($action, $needCallbackQueryId) && !isset($data['callback_query_id']) && isset(self::$getUpdates['id'])) {
            $data['callback_query_id'] = self::$getUpdates['id'];
        }

        // automate media
        if (in_array($action, $needMediaId) && isset($data['media']) && is_string($data['media'])){
            $fileId = FileId::fromBotAPI($data['media']);
            $data['media'] = json_encode(['type' => $fileId->getTypeName(), 'media' => $data['media']]);
        }

        //automate message_id
        if (in_array($action, $needMessageId)) {
            if (isset($data['message_id']) and is_string($data['message_id']) and isset(json_decode($data['message_id'])->result->message_id)) {
                $data['message_id'] = (json_decode($data['message_id']))->result->message_id;
            } elseif (isset(self::$getUpdates['message']['message_id'])){
                $data['message_id'] = self::$getUpdates['message']['message_id'];
            } elseif (isset(self::$getUpdates['message']['reply_to_message'])) {
                $data['message_id'] = self::$getUpdates['message']['reply_to_message']['message_id'];
            }
        }
        //automate chat id
        if (in_array($action, $needChatId) && !isset($data['chat_id'])) {
            if (isset(self::$getUpdates['message']['chat']['id'])) {
                $data['chat_id'] = self::$getUpdates['message']['chat']['id'];
            } elseif (isset(self::$getUpdates['channel_post'])) {
                $data['chat_id'] = self::$getUpdates['channel_post']['chat']['id'];
            } elseif (isset(self::$getUpdates['my_chat_member'])) {
                $data['chat_id'] = self::$getUpdates['my_chat_member']['chat']['id'];
            } elseif (isset(self::$admin_id)) {
                $data['chat_id'] = self::$admin_id;
            }
        }
        
        // automate reply message
        if (isset(self::$getUpdates['message']['message_id']) && !isset($data['reply_to_message_id'])) {
            $data['reply_to_message_id'] = self::$getUpdates['message']['message_id'];
        }

        // no reply
        if (isset($data['reply']) && $data['reply'] === false) unset($data['reply_to_message_id']);

        if (isset($data['reply_markup']) && is_array($data['reply_markup'])) {
            $data['reply_markup'] = json_encode($data['reply_markup']);
        }

        if (function_exists('curl_version')) {
            $ch = curl_init();
            $options = [
                CURLOPT_URL => self::$url . '/' . $action,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false
            ];

            if (is_array($data)) {
                $options[CURLOPT_POSTFIELDS] = $data;
            }

            if ($upload) {
                $options[CURLOPT_HTTPHEADER] = ['Content-Type: multipart/form-data'];
            }

            curl_setopt_array($ch, $options);

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                echo curl_error($ch) . "\n";
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {

            if ($upload) return self::send('sendMessage', ['text' => 'Maaf, layanan ini tidak tersedia karena versi PHP yang digunakan saat ini tidak mendukung fungsi curl. Silahkan instal terlebih dahulu']);

            $opts = [
                'http' => [
                    'method' => "POST",
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data)
                ]
            ];

            $result = file_get_contents(self::$url . '/' . $action, false, stream_context_create($opts));
            if (!$result) return false;

            $httpcode = null; //perlu review lagi
        }

        if (self::$debug && $action != 'getUpdates') {
            self::$dbg .= 'Method: ' . $action . "\n";
            self::$dbg .= 'Data: ' . print_r($data, true) . "\n";
            self::$dbg .= 'Response: ' . $result . "\n";
        }

        if ($httpcode == 401) {
            throw new Exception('Incorrect bot token');
            return false;
        } else {
            return $result;
        }
    }

    public static function answerInlineQuery($results, $options = [])
    {
        if (!empty($options)) {
            $data = $options;
        }

        if (!isset($options['inline_query_id'])) {
            $get = self::$getUpdates;
            $data['inline_query_id'] = $get['inline_query']['id'];
        }

        $data['results'] = json_encode($results);

        return self::send('answerInlineQuery', $data);
    }

    private static function curlFile($path)
    {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) {
            return curl_file_create($path);
        } else {
            // Use the old style if using an older version of PHP
            return "@$path";
        }
    }

    public static function message()
    {
        $get = self::$getUpdates;
        if (isset($get['message'])) {
            return $get['message'];
        } elseif (isset($get['callback_query'])) {
            return $get['callback_query'];
        } elseif (isset($get['inline_query'])) {
            return $get['inline_query'];
        } elseif (isset($get['edited_message'])) {
            return $get['edited_message'];
        } elseif (isset($get['channel_post'])) {
            return $get['channel_post'];
        } elseif (isset($get['edited_channel_post'])) {
            return $get['edited_channel_post'];
        } else {
            return [];
        }
    }

    public static function type()
    {
        $events = [
            'text',
            'animation',
            'photo',
            'video',
            'video_note',
            'audio',
            'contact',
            'dice',
            'game',
            'poll',
            'voice',
            'document',
            'sticker',
            'venue',
            'location',
            'new_chat_members',
            'new_chat_member',
            'left_chat_member',
            'new_chat_title',
            'new_chat_photo',
            'delete_chat_photo',
            'group_chat_created',
            'supergroup_chat_created',
            'channel_chat_created',
            'message_auto_delete_timer_changed',
            'migrate_to_chat_id',
            'migrate_from_chat_id',
            'pinned_message',
            'invoice',
            'successful_payment',
            'user_shared',
            'chat_shared',
            'connected_website',
            'write_access_allowed',
        ];
        foreach ($events as $event) {
            if (isset(self::$getUpdates['message'][$event])) return $event;
        }
        return isset(array_keys(self::$getUpdates)[1]) ? array_keys(self::$getUpdates)[1] : 'unknown';
    }

    public static function __callStatic(string $action, array $args)
    {
        /**
         * list of events (types)
         * see: https://core.telegram.org/bots/api#message
         * except `start` and `all`
         */
        $types = [
            'start',
            'all',
            'text',
            'animation',
            'audio',
            'document',
            'photo',
            'sticker',
            'video',
            'video_note',
            'voice',
            'contact',
            'dice',
            'game',
            'poll',
            'venue',
            'location',
            'new_chat_members',
            'left_chat_members',
            'new_chat_title',
            'new_chat_photo',
            'delete_chat_photo',
            'group_chat_created',
            'supergroup_chat_created',
            'channel_chat_created',
            'my_chat_member',
            'message_auto_delete_timer_changed',
            'migrate_to_chat_id',
            'migrate_from_chat_id',
            'pinned_message',
            'invoice',
            'successful_payment',
            'user_shared',
            'chat_shared',
            'write_access_allowed',
            'connected_website',
            'passport_data',
            'proximity_alert_triggered',
            'forum_topic_created',
            'forum_topic_edited',
            'forum_topic_closed',
            'forum_topic_reopened',
            'general_forum_topic_hidden',
            'general_forum_topic_unhidden',
            'voice_chat_scheduled',
            'voice_chat_started',
            'voice_chat_ended',
            'voice_chat_participants_invited',
            'web_app_data',
            'reply_markup',
            'inline_query',
            'callback_query',
            'edited_message',
            'channel_post',
            'edited_channel_post',
        ];
        /**
         * $action is __callStatic
         * for example: 
         * action of Bot::start() is `start`
         * action of Bot::all() is `all`
         * action of Bot::text() is `text`
         * action of Bot::photo() is `photo`
         * etc.
         */
        if (in_array($action, $types)) {
            if ($action == 'all') {
                self::$_onMessage['*'] = $args[0];
                return;
            }
            if ($action == 'start') {
                if (isset($args[1])) {
                    return self::chat('/start', function () use ($args) {
                        return self::send('sendMessage', array_merge(['text' => $args[0]], $args[1]));
                    });
                } else {
                    return self::$_command['/start'] = $args[0];
                }
            }
            return self::on($action, $args[0]);
        }

        $param = [];
        $firstParam = [
            'sendMessage' => 'text',
            'sendPhoto' => 'photo',
            'sendVideo' => 'video',
            'sendAudio' => 'audio',
            'sendVoice' => 'voice',
            'sendDocument' => 'document',
            'sendSticker' => 'sticker',
            'sendVenue' => 'venue',
            'sendChatAction' => 'action',
            'setWebhook' => 'url',
            'deleteWebhook' => 'drop_pending_updates',
            'getUserProfilePhotos' => 'user_id',
            'getFile' => 'file_id',
            'getChat' => 'chat_id',
            'leaveChat' => 'chat_id',
            'getChatAdministrators' => 'chat_id',
            'getChatMembersCount' => 'chat_id',
            'sendGame' => 'game_short_name',
            'getGameHighScores' => 'user_id',
            'editMessageText' => 'text',
            'editMessageReplyMarkup' => 'reply_markup',
            'editMessageCaption' => 'caption',
            'editMessageMedia' => 'media',
            'deleteMessage' => 'message_id',
            'answerCallbackQuery' => 'text',
        ];
        if (isset($args[0])) {
            if (is_array($args[0])){
                $param = $args[0];
                if (isset($args[1]) && is_array($args[1])) {
                    $param = array_merge($param, $args[1]);
                }
            } else {
                $param[$firstParam[$action]] = $args[0];
                if (isset($args[1]) && is_array($args[1])) {
                    $param = array_merge($param, $args[1]);
                }
            }
        }
        return call_user_func_array(__CLASS__ . '::send', [$action, $param]);
    }

    /**
     * Proses pesan sebelum dikirim
     * 
     * @var string	$teks
     * @var array	$data
     */
    public static function prosesPesan(string $teks, array $data = null)
    {

        // jika pesan teks TIDAK melebihi 4096 karakter, langsung kirim
        if (strlen($teks) <= 4096) return self::sendMessage($teks, $data);

        // jika pesan teks melebihi 4096 karakter
        $pecahan = self::potong($teks, 4096);
        foreach ($pecahan as $no => $pesan) {
            $pesan = self::cekTag($pesan);
            $pilihan = $data;
            if ($no === 0) {
                // pesan pertama tanpa markup saja
                unset($pilihan['reply_markup']);
                self::sendMessage($pesan, $pilihan);
            } elseif ($no < (count($pecahan) - 1)) {
                // pesan di tengah tanpa markup dan tanpa reply
                unset($pilihan['reply']);
                unset($pilihan['reply_markup']);
                self::sendMessage($pesan, $pilihan);
            } else {
                // pesan terakhir tanpa reply saja
                unset($data['reply']);
                self::sendMessage($pesan, $data);
            }
        }
    }
    /**
     * potong teks
     * 
     * @param string    $text
     * @param int       $jml_kar
     * @return  array 
     */
    private static function potong(string $text, int $jml_kar)
    {
        $panjang = strlen($text);
        $ke = 0;
        $pecahan = [];
        while ($panjang > $jml_kar) {
            $no = $jml_kar;
            $karakter = $text[$no];
            while ($karakter != ' ' and $karakter != "\n" and $karakter != "\r" and $karakter != "\r\n") {
                $karakter = $text[--$no];
            }
            $pecahan[] = substr($text, 0, $no);
            $panjang = strlen($pecahan[$ke]);
            $text = trim(substr($text, $panjang));
            $panjang = strlen($text);
            $ke++;
        }
        return array_merge($pecahan, array($text));
    }

    /**
     * cek tag HTML
     * 
     * @param string    $html
     * @return string
     */
    private static function cekTag(string $html)
    {
        // buang semua tag kecuali <a><b><i>
        $html = strip_tags($html, '<a><b><i>');
        // tangkap semua tag yang terbuka
        preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
        $openedtags = $result[1];
        $first_opened_tag_position = strpos($html, $openedtags[0]);
        //tangkap semua tag yang tertutup
        preg_match_all('#</([a-z]+)>#iU', $html, $result);
        $closedtags = $result[1];
        $first_closed_tag = $closedtags[0];
        $first_closed_tag_position = strpos($html, $first_closed_tag);
        // hitung jumlah tag terbuka
        $len_opened = count($openedtags);
        // jika jumlah tag tertutup sama dengan jumlah tag terbuka
        if (count($closedtags) == $len_opened) {
            // langsung kembalikan
            return $html;
        }
        // balik urutan tag terbuka
        $openedtags = array_reverse($openedtags);
        for ($i = 0; $i < $len_opened; $i++) {
            // jika tag terbuka belum tertutup
            if (!in_array($openedtags[$i], $closedtags)) {
                // tambah tag tutupnya
                $html .= '</' . $openedtags[$i] . '>';
            } else {
                // jika tag terbuka sudah ada tutupnya
                // buang dari array
                unset($closedtags[array_search($openedtags[$i], $closedtags)]);
            }
        }
        // jika ada tag penutup yang tidak tidak diawali dengan tag pembuka
        if ($first_closed_tag_position < $first_opened_tag_position) {
            $html = str_replace('<', '</', $first_closed_tag) . $html;
        }
        return $html;
    }
    /**
     * For backgroud process
     * example: bg_exec('Class::method', [$param1, $param2], 'require "functions.php"; require "config.php"; ', 1000);
     */
    public static function bg_exec(string $function_name, array $params = [], string $str_requires = '', int $timeout = 1000, $debug = false)
    {
        $cmd = "(php -r \"chdir('" . dirname($_SERVER['SCRIPT_FILENAME']) . "'); " . strtr($str_requires, array('"' => '\"', '$' => '\$', '`' => '\`', '\\' => '\\\\', '!' => '\!')) . " \\\$params=json_decode(file_get_contents('php://stdin'), true); \\\$res = call_user_func_array('{$function_name}', \\\$params); \" <&3 &) 3<&0"; //php by default use "sh", and "sh" don't support "<&0"
        $stdin = json_encode($params);
        $start = time();
        $stdout = '';
        $stderr = '';
        if ($debug) file_put_contents('debug.txt', time() . ':cmd:' . $cmd . "\n", FILE_APPEND);
        if ($debug) file_put_contents('debug.txt', time() . ':stdin:' . $stdin . "\n", FILE_APPEND);

        $process = proc_open($cmd, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return array('return' => '1', 'stdout' => $stdout, 'stderr' => $stderr);
        }
        $status = proc_get_status($process);
        posix_setpgid($status['pid'], $status['pid']);    //seperate pgid(process group id) from parent's pgid

        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        while (1) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            if (time() - $start > $timeout) {
                //proc_terminate($process, 9);    //only terminate subprocess, won't terminate sub-subprocess
                posix_kill(-$status['pid'], 9);    //sends SIGKILL to all processes inside group(negative means GPID, all subprocesses share the top process group, except nested my_timeout_exec)
                if ($debug) file_put_contents('debug.txt', time() . ":kill group {$status['pid']}\n", FILE_APPEND);
                return array('return' => '1', 'stdout' => $stdout, 'stderr' => $stderr);
            }

            $status = proc_get_status($process);
            if ($debug) file_put_contents('debug.txt', time() . ':status:' . var_export($status, true) . "\n");
            if (!$status['running']) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                return $status['exitcode'];
            }

            usleep(100000);
        }
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add sub-directory.
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     * Bot::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    public static function zipDir(string $sourcePath, string $outZipPath)
    {
        $pathInfo = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName = $pathInfo['basename'];
        $z = new ZipArchive();
        $z->open($outZipPath, ZIPARCHIVE::CREATE);
        $z->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
        $z->close();
    }
    
    /**
     * For debugging via Bot message
     */
    public static function debug($result = null){
        if (is_null($result)) return Bot::sendMessage(json_encode(Bot::message(), JSON_PRETTY_PRINT));
        $object = json_decode($result);
        if (!$object || !$object->ok) return Bot::sendMessage(json_encode($result, JSON_PRETTY_PRINT));
    }
}
