<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetChatRequest;
use App\Http\Requests\StoreChatRequest;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // get all chats
    public function index(GetChatRequest $request): JsonResponse
    {
        $data = $request->validated();
        $is_private = 1;
        if ($request->has('is_private')) {
            $isPrivate = (int)$data['is_private'];
        }
        if (isset(auth()->guard('api')->user()->id)) { // patient send the message
            $id = auth()->guard('api')->user()->id;
            $chats = Chat::where('is_private', $is_private)
                ->whereHas('participants', function ($q) use ($id) {
                    $q->where('user_id', $id);
                })
                // ->whereHas('messages')
                ->with('lastMessage.user', 'participants.user', 'participants.caregiver')
                ->latest('updated_at')
                ->get();
        } else if (isset(auth()->guard('caregiver')->user()->id)) { // caregiver send the message
            $id = auth()->guard('caregiver')->user()->id;
            $chats = Chat::where('is_private', $is_private)->whereHas('participants', function ($q) use ($id) {
                    $q->where('caregiver_id', $id);
                })
                // ->whereHas('messages')
                ->with('lastMessage.user', 'participants.user', 'participants.caregiver')
                ->latest('updated_at')
                ->get();
        } else {
            return response()->json([
                "message" => "un authorized",
            ], 401);
        }
        return response()->json([
            'data' => $chats,
            'status' => 200,
            'message' => "get all chats data"
        ]);
    }
    // get one chat
    public function show(Chat $chat): JsonResponse
    {
        $chat->load('lastMessage.user', 'participants.user', 'participants.caregiver');
        return response()->json([
            'data' => $chat->load('lastMessage.user', 'participants.user', 'participants.caregiver'),
            'status' => 200,
            'message' => "get one chat data"
        ]);
    }
    // store a new chat
    public function store(StoreChatRequest $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory
    {
        $data = $this->prepareStoreData($request);

        $previousChat = $this->getPreviousChat($data['caregiverId']);
        if ($previousChat === null) {
            $chat = Chat::create($data['data']);
            $chat->participants()->createMany([
                [
                    'user_id' => $data['userId'],
                    'caregiver_id' => $data['caregiverId']
                ]
            ]);
            $chat->refresh()->load('lastMessage.user', 'participants.user');
            return response($chat, 200, ["success"]);
        }
        return response()->json([
            'data' => $previousChat->load('lastMessage.user', 'participants.user', 'participants.caregiver'),
            'status' => 200,
            'message' => "chat successfully created"
        ]);
    }


    private function getPreviousChat(int $caregiverId): mixed
    {
        $userId = auth()->guard('api')->user()->id;
        return Chat::where('is_private', 1)
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function ($query) use ($caregiverId) {
                $query->where('caregiver_id', $caregiverId);
            })
            ->first();
    }
    // prepare data for store chat
    private function prepareStoreData(StoreChatRequest $request): array
    {
        $data = $request->validated();
        $caregiverId = (int)$data['caregiver_id'];
        unset($data['caregiver_id']);
        $data['created_by'] = auth()->guard('api')->user()->id;
        $data['name'] = auth()->guard('api')->user()->name;
        return [
            'caregiverId' => $caregiverId,
            'userId' => auth()->guard('api')->user()->id,
            'data' => $data,
        ];
    }
}
