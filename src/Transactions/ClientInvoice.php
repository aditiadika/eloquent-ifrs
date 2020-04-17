<?php
/**
 * Laravel IFRS Accounting
 *
 * @author Edward Mungai
 * @copyright Edward Mungai, 2020, Germany
 * @license MIT
 */
namespace App\Transactions;

use Carbon\Carbon;


use App\Models\Account;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Transaction;

use App\Interfaces\Sells;
use App\Interfaces\Fetchable;

use App\Traits\Selling;
use App\Traits\Fetching;
use App\Interfaces\Clearable;
use App\Traits\Clearing;

class ClientInvoice extends AbstractTransaction implements Sells, Fetchable, Clearable
{
    use Selling;
    use Fetching;
    use Clearing;

    /**
     * Transaction Number prefix
     *
     * @var string
     */

    const PREFIX = Transaction::IN;

    /**
     * Transaction Main Account Credit Entry
     *
     * @var bool
     */

    const CREDITED = false;

    /**
     * Construct new ClientInvoice
     *
     * @param Account $account
     * @param Carbon $date
     * @param string $narration
     * @param Currency $currency
     * @param ExchangeRate $exchangeRate
     * @param string $reference
     *
     * @return AbstractTransaction
     */
    public static function new(
        Account $account,
        Carbon $date,
        string $narration,
        Currency $currency = null,
        ExchangeRate $exchangeRate = null,
        string $reference = null
    ) : AbstractTransaction {

        $clientInvoice = parent::instantiate(self::PREFIX);

        $clientInvoice->newTransaction(
            self::PREFIX,
            self::CREDITED,
            $account,
            $date,
            $narration,
            $currency,
            $exchangeRate,
            $reference
        );

        return $clientInvoice;
    }

    /**
     * Set ClientInvoice Date
     *
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->transaction->date = $date;
        $this->transaction->transaction_no  = Transaction::transactionNo(self::PREFIX, $date);
    }

    /**
     * ClientInvoice Amount that has been Cleared
     *
     * @return float
     */
    public function clearedAmount(): float
    {
        return $this->transaction->clearedAmount();
    }
}