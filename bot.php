<?php
require_once "Telegram.php";

$token = "6837549941:AAE-v9V7sppqQiLa9nrkDFTny0wEQDASYdI";

// Created at Samandar Sariboyev - samandarsariboyev69@gmail.com - +998 97 567 20 09
$username = "";
$host = "";
$password = "";
$db = "";

$telegram = new Telegram($token);
$data = $telegram->getData();
$message = $data['message'];
$message_id = $message['message_id'];
$text = $message['text'];
$chat_id = $message['chat']['id'];
$user_id = $message['from']['id'];
$callback_query = $telegram->Callback_Query();
$chatID = $telegram->Callback_ChatID();
$adminlar = [987888787,6461454179];

$con = mysqli_connect($host, $username, $password, $db);
if(isset($con)){
    echo "Yes DB";
}
if(in_array($chat_id, $adminlar)){
    if ($text == '/start') {
        AdminHome();
    }
    else{
        $r = mysqli_query($con, "Select * from `users` where `chat_id` = {$chat_id}");
        $p = mysqli_fetch_assoc($r);
        $page = $p['page'];
        switch ($page) {
            case 'home':
                switch ($text) {
                    case 'Kanallar🔗':
                        $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
                        $p = mysqli_fetch_assoc($r);
                        $tekst = "Kanallar ro'yhati:\n\n";
                        $arr = json_decode($p['channels_list']);
                        foreach ($arr as $key => $value) {
                            $tekst = $tekst.$value."\n";
                        }
                        $tekst = $tekst."\nEslatma: Botni kanallarda admin qiling. Bo'lmasa foydalanuvchi a'zo bo'lganini tekshirib bo'lmaydi";
                        sendMessage($tekst);
                        AdminHome();
                        break;
                    case 'Sovga matni🎁':
                        $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
                        $p = mysqli_fetch_assoc($r);
                        $tekst = "Sovga matni🎁:\n\n{$p['gift_text']}";
                        sendMessage($tekst);
                        AdminHome();
                        break;
                    case 'Statistika📊':
                        $r = mysqli_query($con, "Select COUNT(id) as c from `users`;");
                        $r2 = mysqli_query($con, "Select COUNT(id) as c from `users` where subscribe_status = 1;");
                        $p = mysqli_fetch_assoc($r);
                        $p2 = mysqli_fetch_assoc($r2);
                        $tekst = "Foydalanuvchilar soni: {$p['c']} ta\nKanallarga azo bolganlar: {$p2['c']} ta";
                        sendMessage($tekst);
                        AdminHome();
                        break;
                    case 'Yangi konkurs':
                        sendTextWithKeyboard(["Bosh menu"],"Kanallar ro'yhatini quidagi ko'rinishda yuboring: \n\n[\"@channel1\", \"@channel2\"] \n\nBotni ushbu kanallarda admin qilishni unutmang");
                        SetPage('get_channel');
                        break;
                    default:
                        AdminHome();
                        break;
                }
                break;
            case 'get_channel':
                if($text == "Bosh menu"){
                    AdminHome();
                }
                else{
                    $sql = "UPDATE `configs` SET `channels_list` = '{$text}' WHERE `configs`.`id` = 1;";
                    mysqli_query($con,$sql);
                    sendMessage("Kanallar ro'yhati yangilandi. Sovga matnini yuboring");
                    SetPage('get_gift_text');
                }
                break;
            case 'get_gift_text':
                if($text == "Bosh menu"){
                    AdminHome();
                }
                else{
                    $sql = "UPDATE `configs` SET `gift_text` = '{$text}' WHERE `configs`.`id` = 1;";
                    mysqli_query($con,$sql);
                    $sql = "UPDATE `users` SET `referral_users`=NULL,`status`=0,`subscribe_status`=0,`page`='new_konkurs';";
                    mysqli_query($con,$sql);
                    sendMessage("Konkurs yangilandi");
                    AdminHome();
                }
                break;
            default:
                # code...
                break;
        }
    }
}
elseif ($callback_query != null && $callback_query != '') {
    $callback_data = $telegram->Callback_Data();
    $r = "Select * from `users` where `chat_id` = {$chatID}";
    $res = mysqli_query($con, $r);
    $p = mysqli_fetch_assoc($res);
    $page = $p['page'];
    switch ($page) {
        case 'subscribe_channels':
            $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
            $pe = mysqli_fetch_assoc($r);
            $arr = json_decode($pe['channels_list']);
            $subscribe_count = 0;
            $counter = 1;
            foreach ($arr as $key => $value) {
                $check = $telegram->getChatMember(['chat_id' => "{$value}", 'user_id' => $chatID]);
                if($check['result']['status'] == "member"){
                    $subscribe_count++;
                }
                else{
                    $telegramLink = "https://t.me/" . ltrim($value, '@');
                    $option[] = array($telegram->buildInlineKeyboardButton("{$counter} - KANAL","{$telegramLink}","{$value}"));
                    $counter++;
                }
            }
            if ($subscribe_count == count($arr)) {
                if($p['referraled_by'] != NULL){
                    $sql = "Select * from `users` where `chat_id` = {$p['referraled_by']}";
                    $result = mysqli_query($con, $sql);
                    $from_referralled = mysqli_fetch_assoc($res);
                    if($from_referralled['referral_users'] != NULL){
                        $idies = json_decode($from_referralled['referral_users']);
                        $idies[] = $chatID;
                        $str_array = json_encode($idies);
                        $sql = "UPDATE `users` SET `referral_users`='{$str_array}' WHERE chat_id = {$p['referraled_by']}";
                    }
                    else{
                        $idies = array($chatID);
                        $str_array = json_encode($idies);
                        $sql = "UPDATE `users` SET `referral_users`='{$str_array}' WHERE chat_id = {$p['referraled_by']}";
                    }
                    mysqli_query($con, $sql);
                }
                $sql = "UPDATE `users` SET `subscribe_status`=1 WHERE chat_id = {$chatID}";
                mysqli_query($con, $sql);
                $telegram->deleteMessage(['chat_id' => $chatID, 'message_id' => $data['callback_query']['message']['message_id']]);
                HomeInline();
            }
            else{
                $option[] = array($telegram->buildInlineKeyboardButton("TEKSHIRISH ✅","","check"));
                $keyb = $telegram->buildInlineKeyBoard($option);
                $content = ['chat_id' => $chatID, 'reply_markup' => $keyb, "text" => "Siz barcha kanallarga a'zo bo'lamgansiz. Konkursda qatnashish hamkor kanallarga a'zo bo'ling. A'zo bo'lganingizdan so'ng <b>TEKSHIRISH ✅</b> tugamasini bosing", 'parse_mode' => "HTML"];
                $telegram->sendMessage($content);
                $telegram->deleteMessage(['chat_id' => $chatID, 'message_id' => $data['callback_query']['message']['message_id']]);
                SetPage('subscribe_channels');
            }
            break;
        default:
            # code...
            break;
    }
}
elseif ($text == '/start') {
    Start();
    // sendMessage(4);
}
elseif (strpos($text, '/start') !== false) {
    $referal_chat_id = substr($text, 7);
    StartByReferal($referal_chat_id);
}
else{
    $r = mysqli_query($con, "Select * from `users` where `chat_id` = {$chat_id}");
    $p = mysqli_fetch_assoc($r);
    $page = $p['page'];
    switch ($page) {
        case 'home':
            switch ($text) {
                case 'Taklif havolasi 🔗':
                    $getMe = $telegram->getMe();
                    $str = "Sizning referral havolangiz:\n\nhttps://t.me/".$getMe['result']['username']."?start=".$chat_id."\n\nKonkurs sovg'asini qo'lga kiritish uchun ushbu havolani 5 kishiga ulashing\n\n👆 Yuqoridagi sizning referal link/havolangiz. Uni koʼproq tanishlaringizga ulashing va sovgʻaga ega bo'ling.";
                    sendTextWithKeyboard(["Bosh menu 🏠"],$str);
                    break;
                case 'Sovg\'ani olish 🎁':
                    $count_referallas = 0;
                    if($p['referral_users'] != NULL){
                        $referrals = json_decode($p['referral_users']);
                        $count_referallas = count($referrals);
                    }
                    if($count_referallas >= 5){
                        $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
                        $pe = mysqli_fetch_assoc($r);
                        sendTextWithKeyboard(["Bosh menu 🏠"],$pe['gift_text']);
                    }
                    else{
                        sendTextWithKeyboard(["Bosh menu 🏠"],"Siz taklif qilgan a'zolar soni sovg'ani olish uchun yetarli emas! \n\nA'zolar soni: {$count_referallas}");
                    }
                    break;
                default:
                    Home();
                    break;
            }
            break;

        default:
            subscribe_channels();
            break;
    }
}


function AdminHome() {
    global $chat_id, $message,$con, $data;
    sendTextWithKeyboard(['Kanallar🔗', "Sovga matni🎁","Yangi konkurs", "Statistika📊"], "Tanlang ⤵️");
    SetPage('home');
}




function Home() {
    global $chat_id, $message,$con, $data, $telegram;
    $getMe = $telegram->getMe();
    $str = "https://t.me/".$getMe['result']['username']."?start=".$chat_id;
    sendTextWithKeyboard(['Taklif havolasi 🔗', "Sovg'ani olish 🎁"], "Assalomu alaykum !
Konkurs Masterbotga xush kelibsiz!
G'olib bo'lish va sovg'alarga ega bo'lishni xohlasangiz, maxsus taklif havolasini boshqa foydalanuvchilarga yuboring.
Sizning referal havolangiz:\n
{$str}\n
Kamida 5 ta odam qo’shgan barcha ishtirokchilarga sovgʻa avtomatik tarzda beriladi. 🎁 🎁");
    SetPage('home');
}

function HomeInline() {
    global $message,$con, $data, $telegram, $chatID;
    $getMe = $telegram->getMe();
    $str = "https://t.me/".$getMe['result']['username']."?start=".$chatID;
    sendTextWithKeyboardCall(['Taklif havolasi 🔗', "Sovg'ani olish 🎁"], "Assalomu alaykum !
Konkurs Masterbotga xush kelibsiz!
G'olib bo'lish va sovg'alarga ega bo'lishni xohlasangiz, maxsus taklif havolasini boshqa foydalanuvchilarga yuboring.
Sizning referal havolangiz:\n
{$str}\n
Kamida 5 ta odam qo’shgan barcha ishtirokchilarga sovgʻa avtomatik tarzda beriladi. 🎁 🎁");
    SetPageCall('home');
}

function Start(){
    global $chat_id, $message,$con, $data, $user_id, $telegram;
    $user = mysqli_query($con, "SELECT * FROM  `users` where `chat_id` =  {$chat_id}");
    $dat = json_encode($data);
    if(mysqli_num_rows($user)<1){
        $sql = "INSERT INTO `users`(`chat_id`, `name`, `page`, `data`, `user_id`) VALUES ($chat_id, '{$message['from']['first_name']}','start', '{$dat}', {$user_id})";
        $r = mysqli_query($con,$sql);
        subscribe_channels();
        // SetPage('start');
        // $b = ["Havolani olish 🔗"];
        // sendTextWithKeyboard($b, "<b>Assalomu aleykum!</b>\nKonkurs botiga xush kelibsiz! G'olib bo'lish va sovg'alarga ega bo'lishni xohlasangiz, maxsus taklif havolasini boshqalarga yuboring.");
    }
    else{
        $user = mysqli_fetch_assoc($user);
        if($user['subscribe_status'] == 0){
            subscribe_channels();
        }
        else{
            $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
            $pe = mysqli_fetch_assoc($r);
            $arr = json_decode($pe['channels_list']);
            $subscribe_count = 0;
            $counter = 1;
            foreach ($arr as $key => $value) {
                $check = $telegram->getChatMember(['chat_id' => "{$value}", 'user_id' => $chat_id]);
                if($check['result']['status'] == "member"){
                    $subscribe_count++;
                }
                else{
                    $telegramLink = "https://t.me/" . ltrim($value, '@');
                    $option[] = array($telegram->buildInlineKeyboardButton("{$counter} - KANAL","{$telegramLink}","{$value}"));
                    $counter++;
                }
            }
            if ($subscribe_count == count($arr)) {
                Home();
            }
            else{
                mysqli_query($con, "UPDATE `users` SET `subscribe_status` = '0' WHERE `users`.`chat_id` = {$chat_id};");
                $option[] = array($telegram->buildInlineKeyboardButton("TEKSHIRISH ✅","","check"));
                $keyb = $telegram->buildInlineKeyBoard($option);
                $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb,"text" => "Siz barcha kanallarga a'zo bo'lamgansiz. Konkursda qatnashish hamkor kanallarga a'zo bo'ling. A'zo bo'lganingizdan so'ng <b>TEKSHIRISH ✅</b> tugamasini bosing", 'parse_mode' => "HTML"];
                $telegram->sendMessage($content);
                $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $data['callback_query']['message']['message_id']]);
                SetPage('subscribe_channels');
            }
        }
    }
}

function StartByReferal($referral){
    global $chat_id, $message,$con, $data, $telegram;
    $user = mysqli_query($con, "SELECT * FROM  `users` where `chat_id` =  {$chat_id}");
    $dat = json_encode($data);
    if(mysqli_num_rows($user)<1){
        $sql = "INSERT INTO `users`(`chat_id`, `name`, `page`, `data`, `referraled_by`) VALUES ($chat_id, '{$message['from']['first_name']}','start', '{$dat}', {$referral})";
        $r = mysqli_query($con,$sql);
        subscribe_channels();
    }
    else{
        $user = mysqli_fetch_assoc($user);
        if($user['subscribe_status'] == 0){
            subscribe_channels();
        }
        else{
            $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
            $pe = mysqli_fetch_assoc($r);
            $arr = json_decode($pe['channels_list']);
            $subscribe_count = 0;
            $counter = 1;
            foreach ($arr as $key => $value) {
                $check = $telegram->getChatMember(['chat_id' => "{$value}", 'user_id' => $chat_id]);
                if($check['result']['status'] == "member"){
                    $subscribe_count++;
                }
                else{
                    $telegramLink = "https://t.me/" . ltrim($value, '@');
                    $option[] = array($telegram->buildInlineKeyboardButton("{$counter} - KANAL","{$telegramLink}","{$value}"));
                    $counter++;
                }
            }
            if ($subscribe_count == count($arr)) {
                Home();
            }
            else{
                mysqli_query($con, "UPDATE `users` SET `subscribe_status` = '0' WHERE `users`.`chat_id` = {$chat_id};");
                $option[] = array($telegram->buildInlineKeyboardButton("TEKSHIRISH ✅","","check"));
                $keyb = $telegram->buildInlineKeyBoard($option);
                $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb,"text" => "Siz hali barcha kanallarga a'zo bo'lmadingiz. Konkursda qatnashish va sovgʻalarga ega boʻlish uchun hamkor kanallarga a'zo bo'ling. A'zo bo'lganingizdan so'ng: \n\nTEKSHIRISH ✅ tugmasini bosing.", 'parse_mode' => "HTML"];
                $telegram->sendMessage($content);
                $telegram->deleteMessage(['chat_id' => $chat_id, 'message_id' => $data['callback_query']['message']['message_id']]);
                SetPage('subscribe_channels');
            }
        }
    }

}


function subscribe_channels() {
    global $chat_id, $message,$con, $data, $telegram;
    $r = mysqli_query($con, "Select * from `configs` where `id` = 1");
    $p = mysqli_fetch_assoc($r);
    $tekst = "Kanallar ro'yhati:\n\n";
    $arr = json_decode($p['channels_list']);
    $counter = 1;
    foreach ($arr as $key => $value) {
        $telegramLink = "https://t.me/" . ltrim($value, '@');
        $option[] = array($telegram->buildInlineKeyboardButton("{$counter} - KANAL","{$telegramLink}","{$value}"));
        $counter++;
    }
    $option[] = array($telegram->buildInlineKeyboardButton("TEKSHIRISH ✅","","check"));
    $keyb = $telegram->buildInlineKeyBoard($option);
    $content = ['chat_id' => $chat_id, 'reply_markup' => $keyb, "text" => "Konkursda ishtirok etish va botdan mutlaqo bepul foydalanish uchun quyidagi kanallarga a'zo bo'lishingiz kerak.  A'zo bo'lganingizdan so'ng: \nTEKSHIRISH ✅  tugmasini bosing", 'parse_mode' => "HTML"];
    $telegram->sendMessage($content);
    SetPage('subscribe_channels');
}


function sendTextWithKeyboard($buttons, $text, $backBtn = false)
{
    global $telegram, $chat_id, $texts;
    $option = [];
    if (count($buttons) % 2 == 0) {
        for ($i = 0; $i < count($buttons); $i += 2) {
            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));
        }
    } else {
        for ($i = 0; $i < count($buttons) - 1; $i += 2) {
            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));
        }
        $option[] = array($telegram->buildKeyboardButton(end($buttons)));
    }
    if ($backBtn) {
        $option[] = [$telegram->buildKeyboardButton($texts->getText("back_btn"))];
    }
    $keyboard = $telegram->buildKeyBoard($option, $onetime = false, $resize = true);
    $content = array('chat_id' => $chat_id, 'reply_markup' => $keyboard, 'text' => $text, 'parse_mode' => "HTML");
    $telegram->sendMessage($content);
}

function sendTextWithKeyboardCall($buttons, $text, $backBtn = false)
{
    global $telegram, $chatID, $texts;
    $option = [];
    if (count($buttons) % 2 == 0) {
        for ($i = 0; $i < count($buttons); $i += 2) {
            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));
        }
    } else {
        for ($i = 0; $i < count($buttons) - 1; $i += 2) {
            $option[] = array($telegram->buildKeyboardButton($buttons[$i]), $telegram->buildKeyboardButton($buttons[$i + 1]));
        }
        $option[] = array($telegram->buildKeyboardButton(end($buttons)));
    }
    if ($backBtn) {
        $option[] = [$telegram->buildKeyboardButton($texts->getText("back_btn"))];
    }
    $keyboard = $telegram->buildKeyBoard($option, $onetime = false, $resize = true);
    $content = array('chat_id' => $chatID, 'reply_markup' => $keyboard, 'text' => $text, 'parse_mode' => "HTML");
    $telegram->sendMessage($content);
}


function SetPage($name)
{
    global $chat_id, $con;
    $r = mysqli_query($con, "UPDATE `users` SET `page`='{$name}' WHERE `chat_id` = {$chat_id}");
}

function SetPageCall($name)
{
    global $con, $chatID;
    $r = mysqli_query($con, "UPDATE `users` SET `page`='{$name}' WHERE `chat_id` = {$chatID}");
}


function sendMessage($text)
{
    global $telegram, $chat_id;
    $telegram->sendMessage(['chat_id' => $chat_id, 'reply_markup' => json_encode(['remove_keyboard' => true], true), 'text' => $text, 'parse_mode' => "HTML"]);
}

function sendMessageCall($text)
{
    global $telegram, $chatID;
    $telegram->sendMessage(['chat_id' => $chatID, 'reply_markup' => json_encode(['remove_keyboard' => true]),'text' => $text, 'parse_mode' => "HTML"]);
}