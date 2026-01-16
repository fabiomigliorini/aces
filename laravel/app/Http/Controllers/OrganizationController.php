<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Se o usuário pertence a uma organization, retorna apenas ela
        // Caso contrário, retorna todas (para super admins)
        if ($user->organization_id) {
            $organizations = Organization::where('id', $user->organization_id)
                ->where('is_active', true)
                ->get();
        } else {
            $organizations = Organization::where('is_active', true)->get();
        }

        return response()->json([
            'data' => $organizations,
        ]);
    }

    public function show(Organization $organization)
    {
        return response()->json([
            'data' => $organization,
        ]);
    }
}
