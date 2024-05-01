<?php

namespace App\Http\Controllers\Api;

use App\Events\NewMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Caregiver;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    // get chat message

    public function index(GetMessageRequest $request)
    {
        $data = $request->validated();
        $chatId = $data['chat_id'];
        $currentPage = $data['page'];
        $pageSize = $data['page_size'] ?? 15;

        $messages = Message::where('chat_id', $chatId)
            ->with('user', 'caregiver')
            ->latest('created_at')
            ->simplePaginate(
                $pageSize,
                ['*'],
                'page',
                $currentPage
            );
        return response()->json([
            'data' => $messages->getCollection(),
            'status' => 200,
            'message' => "success"
        ]);
    }
    // create a chat message
    public function store(StoreMessageRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->guard('api')->user()->id;
        $chatMessage = Message::create($data);
        $chatMessage->load('user','caregiver');

         /// TODO send broadcast event to pusher and send notification to onesignal services
         $this->sendNotificationToOther($chatMessage);

        return response()->json([
            'data' => $chatMessage,
            'status' => 200,
            'message' => "Message has send successfully"
        ]);
    }
    // send notification to caregiver
    private function sendNotificationToOther(Message $chatMessage): void
    {

        broadcast(new NewMessageSent($chatMessage))->toOthers();

        $user = auth()->user();
        $userId = $user->id;

        $chat = Chat::where('id',$chatMessage->chat_id)
            ->with(['participants'=>function($query) use ($userId){
                $query->where('user_id','!=',$userId);
            }])
            ->first();
        if(count($chat->participants) > 0){
            $caregiverId = $chat->participants[0]->caregiver_id;

            $caregiver = Caregiver::where('id',$caregiverId)->first();
            $caregiver->sendNewMessageNotification([
                'messageData'=>[
                    'senderName'=>$user->username,
                    'message'=>$chatMessage->message,
                    'chatId'=>$chatMessage->chat_id
                ]
            ]);
        }
    }
}
