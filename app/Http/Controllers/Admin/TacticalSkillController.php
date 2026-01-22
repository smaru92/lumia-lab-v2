<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TacticalSkill;

class TacticalSkillController extends Controller
{
    public function index()
    {
        $skills = TacticalSkill::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $skills->map(fn ($s) => ['value' => $s->id, 'label' => $s->name]),
        ]);
    }
}
