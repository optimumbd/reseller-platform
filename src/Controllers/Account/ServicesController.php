<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Service;
use App\Models\User;

final class ServicesController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $services = Service::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->view('account/services/index', ['services' => $services]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $service = Service::find($id);
        if (!$service || (int) $service->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('account/services/show', ['service' => $service]);
    }
}
