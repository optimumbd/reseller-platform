<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

final class WalletController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $wallet = Wallet::forUser((int) $user->id);
        $transactions = WalletTransaction::where('wallet_id = :w ORDER BY id DESC LIMIT 50', ['w' => (int) $wallet->id]);
        return $this->view('account/wallet', ['wallet' => $wallet, 'transactions' => $transactions]);
    }
}
