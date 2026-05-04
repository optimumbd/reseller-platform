<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Service;

final class ServicesController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $pagination = Service::paginate('1=1', [], $page, 25, 'id DESC', '/admin/services');
        return $this->view('admin/services/index', ['pagination' => $pagination]);
    }
}
