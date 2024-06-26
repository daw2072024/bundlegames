<?php
namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserBoardgame;
use App\Models\Boardgame;
use App\Models\Friend;

use Exception;

class DatabaseController extends Controller
{
    public function getBoardgameIdsByUserId(Request $request)
    {
        $user = auth()->user();
        $boardgameIds = $user->boardgames()->pluck('id');
        return response()->json(['boardgame_ids' => $boardgameIds]);
    }

    public function addBoardgame($boardgameId)
    {
        $user = auth()->user();

        $boardgame = Boardgame::find($boardgameId);
        if (!$boardgame) {
            $boardgame = Boardgame::create(['id' => $boardgameId]);
        }

        if (!$user->boardgames()->where('id', $boardgameId)->exists()) {
            $user->boardgames()->attach($boardgameId);
            return response()->json(['message' => 'Board game added successfully']);
        }

        return response()->json(['message' => 'The board game is already associated with the user'], 422);
    }


    public function deleteBoardgame($boardgameId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        $friends = $this->getPlayerFriends($boardgameId)->original['friends'];

        $boardgame = Boardgame::findOrFail($boardgameId);
        $boardgame->friends()->detach($friends->pluck('id'));

        $user->boardgames()->detach($boardgameId);

        return response()->json(['message' => 'The board game and its friends have been successfully removed']);
    }


    public function addFriend($friendName)
    {
        $user = auth()->user();

        $existingFriend = $user->friends()->where('name', $friendName)->exists();

        if ($existingFriend) {
            return response()->json(['error' => 'The friend already exists for this user'], 422);
        }

        $friend = $user->friends()->create([
            'name' => $friendName,
        ]);

        return response()->json(['message' => 'Friend added successfully', 'friend' => $friend]);
    }
    public function getFriendsByUser()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        $friends = $user->friends()->get();

        return response()->json(['friends' => $friends]);
    }

    public function deleteFriend($friendId)
    {
        $user = auth()->user();

        $friend = Friend::where('id', $friendId)
            ->where('user_id', $user->id)
            ->first();

        if (!$friend) {
            return response()->json(['error' => 'The friend does not belong to the current user'], 404);
        }

        $deleted = Friend::destroy($friendId);

        if ($deleted) {
            return response()->json(['message' => 'Friend deleted successfully']);
        } else {
            return response()->json(['error' => 'Could not delete friend'], 404);
        }
    }
    public function addPlayerFriend($boardgameId, $friendId)
    {
        $boardgame = Boardgame::findOrFail($boardgameId);
        $friend = Friend::findOrFail($friendId);

        if ($boardgame->friends()->where('friend_id', $friendId)->exists()) {
            return response()->json(['error' => 'The friend is already associated with this game.'], 422);
        }

        $boardgame->friends()->attach($friend);

        return response()->json(['message' => 'Friend added to the game successfully.']);
    }

    public function getPlayerFriends($boardgameId)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        $boardgame = Boardgame::findOrFail($boardgameId);

        $friends = $boardgame->friends()->where('user_id', $user->id)->get();

        return response()->json(['friends' => $friends]);

    }
    public function deletePlayerFriend($boardgameId, $friendId)
    {
        $boardgame = Boardgame::findOrFail($boardgameId);

        if (!$boardgame->friends()->where('friend_id', $friendId)->exists()) {
            return response()->json(['error' => 'The friend is not associated with this game.'], 422);
        }

        $boardgame->friends()->detach($friendId);

        return response()->json(['message' => 'Friend removed from the game successfully.']);
    }


}






