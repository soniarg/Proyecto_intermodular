<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameTest;

class GameTestController extends Controller
{
    public function index(){
        return GameTest::all();
    }
}
