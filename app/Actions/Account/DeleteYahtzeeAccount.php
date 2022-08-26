<?php
declare(strict_types=1);

namespace App\Actions\Account;

/**
 * @author Dean Blackborough <dean@g3d-development.com>
 * @copyright Dean Blackborough (Costs to Expect) 2018-2022
 * https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE
 */
class DeleteYahtzeeAccount
{
    public function __invoke(
        string $bearer_token,
        string $resource_type_id,
        string $resource_id,
        string $user_id
    ): bool
    {
        \App\Jobs\DeleteYahtzeeAccount::dispatch([
            'bearer_token' => $bearer_token,
            'resource_type_id' => $resource_type_id,
            'resource_id' => $resource_id,
            'user_id' => $user_id
        ])->delay(now()->addMinute());

        return true;
    }
}
