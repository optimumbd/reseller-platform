<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Domain;

final class DomainsController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $pagination = Domain::paginate('1=1', [], $page, 25, 'id DESC', '/admin/domains');
        return $this->view('admin/domains/index', ['pagination' => $pagination]);
    }
}
