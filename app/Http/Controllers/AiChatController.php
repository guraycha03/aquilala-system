<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function index()
    {
        return view('farmer.ai-chat.index');
    }
}
