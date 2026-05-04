<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

final class CustomersController extends BaseController
{
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));
        $q = trim((string) $request->input('q', ''));
        $where = '1=1';
        $bindings = [];
        if ($q !== '') {
            $where .= ' AND (name LIKE :q OR email LIKE :q)';
            $bindings['q'] = '%' . $q . '%';
        }
        $pagination = User::paginate($where, $bindings, $page, 25, 'id DESC', '/admin/customers');
        return $this->view('admin/customers/index', ['pagination' => $pagination, 'q' => $q]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::find($id);
        if (!$user) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('admin/customers/show', ['user' => $user]);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::find($id);
        if (!$user) {
            return $this->view('errors/404', [], 404);
        }
        foreach (['name', 'email', 'role', 'status'] as $f) {
            $val = $request->input($f);
            if ($val !== null) {
                $user->$f = $val;
            }
        }
        $user->save();
        flash('success', __('admin.customer_updated'));
        return $this->back();
    }
}
