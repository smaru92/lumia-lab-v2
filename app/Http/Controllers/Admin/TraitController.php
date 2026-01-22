<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameTrait;

class TraitController extends Controller
{
    public function index()
    {
        $traits = GameTrait::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $traits->map(fn ($t) => ['value' => $t->id, 'label' => $t->name]),
        ]);
    }
}
