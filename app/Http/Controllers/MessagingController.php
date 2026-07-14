<?php

namespace App\Http\Controllers;

class MessagingController extends Controller
{
    public function inbox()
    {
        return view('messaging.inbox');
    }

    public function chatRooms()
    {
        return view('messaging.chat-rooms');
    }
}