<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\index;
use App\Http\Controllers\Controller;

class PostFolloweeController extends Controller
{
    public function index(){
        $user = Auth()->user()->getAuthIdentifier();
        Post::where('users_id',$user.id);
    }
}
