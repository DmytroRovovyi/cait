<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramUser;
use Telegram\Bot\Api;

class TelegramController extends Controller
{
    /**
     * Initializes Telegram API with token.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function handle(Request $request)
    {
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $update = $telegram->getWebhookUpdate();

        $message = $update->getMessage();
        if (!$message) {
            return response('ok', 200);
        }

        $chatId = $message->getChat()->getId();
        $username = $message->getFrom()->getUsername();
        $firstName = $message->getFrom()->getFirstName();
        $lastName = $message->getFrom()->getLastName();
        $text = $message->getText();

        if ($text === '/start') {
            TelegramUser::updateOrCreate(
                ['telegram_id' => $chatId],
                [
                    'username' => $username,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]
            );
            $name = $firstName ?: $username ?: 'користувач';
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Вітаю, {$name}! Ви зареєстровані.",
            ]);
        } elseif ($text === '/help') {
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Доступні команди:\n\n/start - Запуск бота та реєстрація користувача.\n/help - Вивід довідки по командам бота.",
            ]);
        }

        return response('ok', 200);
    }
}
