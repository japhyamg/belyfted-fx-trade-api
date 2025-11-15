<?php
namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Repositories\AccountRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountRepository $accountRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $accounts = $this->accountRepository->getUserAccounts($request->user()->id);

        return response()->json([
            'accounts' => $accounts
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $account = $this->accountRepository->findByUserAndId(
            $request->user()->id,
            $id
        );

        if (!$account) {
            return response()->json([
                'message' => 'Account not found'
            ], 404);
        }

        return response()->json($account);
    }
}
