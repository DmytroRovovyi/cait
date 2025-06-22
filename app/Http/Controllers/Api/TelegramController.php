<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;
use App\Models\UserState;

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

        if (!$text) {
            return response('ok', 200);
        }
        $userState = UserState::firstOrCreate(['telegram_id' => $chatId]);

        switch (true) {
            case ($text === '/start'):
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
                break;
            case ($text === '/help'):
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Доступні команди:
                    /start - Запуск бота та реєстрація користувача.
                    /help - Вивід довідки по командам бота.
                    /list - Cписок задач.
                    /add - Додати нову задачу.",
                ]);
                break;
            case ($text === '/list'):
                $response = Http::get(config('app.url') . '/api/tasks');
                $tasks = $response->json();
                if (empty($tasks)) {
                    $msg = "У вас немає задач.";
                } else {
                    $msg = "Ваші задачі:\n";
                    foreach ($tasks as $task) {
                        $msg .= "-------------------------\n";
                        $msg .= "{$task['id']}: {$task['title']}\n";
                        $msg .= "Опис: {$task['description']}\n";
                        $msg .= "Статус: [" . ($task['completed'] ? 'done' : 'not done') . "]\n\n";
                    }
                }
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $msg,
                ]);
                break;
            case ($text === '/add' || ($userState && in_array($userState->step, ['add_title', 'add_description']))):

                if ($text === '/add') {
                    $userState->step = 'add_title';
                    $userState->save();
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Введіть заголовок задачі:",
                    ]);
                    break;
                }

                if ($userState->step === 'add_title') {
                    $userState->title = $text;
                    $userState->step = 'add_description';
                    $userState->save();
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Введіть опис задачі:",
                    ]);
                    break;
                }

                if ($userState->step === 'add_description') {
                    Http::post(config('app.url').'/api/tasks',[
                        'title' => $userState->title,
                        'description' => $text,
                        'completed' => false,
                    ]);
                    $userState->delete();
                    $telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Задачу створено!",
                    ]);
                    break;
                }
                break;
            default:
                $telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Невідома команда. Введіть /help для списку команд.",
                    ]);
        }

        return response('ok', 200);
    }
}
