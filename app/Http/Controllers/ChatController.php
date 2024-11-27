<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\ChatMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChatController extends Controller
{

    public function index($receiverId)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $messages = ChatMessages::with(['sender:id,first_name,last_name,avatar', 'receiver:id,first_name,last_name,avatar'])
            ->where(function ($query) use ($user, $receiverId) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($query) use ($user, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $user->id);
            })
            ->select('id', 'sender_id', 'receiver_id', 'message', 'created_at')
            ->get();
        return response()->json($messages);
    }

    public function store($receiverId, Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!$receiverId) {
            return formatResponse(STATUS_FAIL, '', '', 'Miss parameter receiverId', CODE_NOT_FOUND);
        }
        $validator = Validator::make(request()->all(), [
            'message' => 'required|string|max:50'
        ]);
        if ($validator->fails()) {
            return formatResponse(STATUS_FAIL, '', $validator->errors(), __('messages.validation_error'));
        }
        $message = ChatMessages::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $request->input('message'),
        ]);
        broadcast(new MessageSent($message))->toOthers();
        return formatResponse(STATUS_OK, $message, '', 'Creat message successfully');
    }

    public function getUsers()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $users = User::where('id', '!=', $user->id)
            ->whereIn('id', function ($query) use ($user) {
                $query->select('sender_id')
                    ->from('chat_messages')
                    ->where('receiver_id', $user->id);
            })
            ->orWhereIn('id', function ($query) use ($user) {
                $query->select('receiver_id')
                    ->from('chat_messages')
                    ->where('sender_id', $user->id);
            })
            ->get(['id', 'first_name', 'last_name', 'avatar', 'email']);

        // Attach latest message for each user
        $users->each(function ($otherUser) use ($user) {
            $latestMessage = ChatMessages::where(function ($query) use ($user, $otherUser) {
                $query->where('sender_id', $user->id)
                    ->where('receiver_id', $otherUser->id);
            })
                ->orWhere(function ($query) use ($user, $otherUser) {
                    $query->where('sender_id', $otherUser->id)
                        ->where('receiver_id', $user->id);
                })
                ->latest('created_at')
                ->first();

            $otherUser->first_message = $latestMessage ? $latestMessage->message : null;
        });

        return formatResponse(STATUS_OK, $users, '', 'Get user successfully');
    }
}
