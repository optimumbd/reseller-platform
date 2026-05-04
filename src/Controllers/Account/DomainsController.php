<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Domain;
use App\Models\User;
use App\Services\Registrar\RegistrarFactory;

final class DomainsController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $domains = Domain::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->view('account/domains/index', ['domains' => $domains]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $domain = Domain::find($id);
        if (!$domain || (int) $domain->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        return $this->view('account/domains/show', ['domain' => $domain]);
    }

    public function nameservers(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $domain = Domain::find($id);
        if (!$domain || (int) $domain->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        if ($request->method() === 'POST') {
            $hosts = array_filter(array_map('trim', (array) $request->input('nameservers', [])));
            RegistrarFactory::default()->setNameservers((string) $domain->domain, $hosts);
            flash('success', __('domain.nameservers_updated'));
            return $this->back();
        }
        $hosts = RegistrarFactory::default()->getNameservers((string) $domain->domain);
        return $this->view('account/domains/nameservers', ['domain' => $domain, 'hosts' => $hosts]);
    }

    public function lock(Request $request, int $id): Response
    {
        return $this->toggleFlag($id, 'is_locked', __('domain.lock_updated'));
    }

    public function privacy(Request $request, int $id): Response
    {
        return $this->toggleFlag($id, 'privacy_enabled', __('domain.privacy_updated'));
    }

    public function autoRenew(Request $request, int $id): Response
    {
        return $this->toggleFlag($id, 'auto_renew', __('domain.autorenew_updated'));
    }

    private function toggleFlag(int $id, string $field, string $message): Response
    {
        $user = User::currentModel();
        $domain = Domain::find($id);
        if (!$domain || (int) $domain->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $domain->$field = empty($domain->$field) ? 1 : 0;
        $domain->save();
        flash('success', $message);
        return $this->back();
    }
}
