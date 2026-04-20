<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request, User $author): RedirectResponse
    {
        $follower = $request->user();
        abort_if($follower->id === $author->id, 422, 'Tidak dapat follow akun sendiri.');

        $alreadyFollowing = $follower->follows()->where('author_id', $author->id)->exists();
        if (!$alreadyFollowing) {
            $follower->follows()->attach($author->id);

            UserNotification::create([
                'user_id' => $author->id,
                'type' => 'follow.created',
                'title' => 'Follower baru',
                'body' => "{$follower->name} mulai mengikuti Anda.",
                'data_json' => [
                    'follower_id' => $follower->id,
                    'follower_public_id' => $follower->public_id,
                ],
            ]);
        }

        return back()->with('success', 'Berhasil follow author.');
    }

    public function destroy(Request $request, User $author): RedirectResponse
    {
        $request->user()->follows()->detach($author->id);

        return back()->with('success', 'Berhasil unfollow author.');
    }
}
