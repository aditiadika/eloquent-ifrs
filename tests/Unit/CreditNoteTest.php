<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Auth;

use Carbon\Carbon;

use IFRS\Tests\TestCase;

use IFRS\Models\Account;
use IFRS\Models\Balance;
use IFRS\Models\Currency;
use IFRS\Models\Ledger;
use IFRS\Models\LineItem;

use IFRS\Transactions\CreditNote;

use IFRS\Exceptions\LineItemAccount;
use IFRS\Exceptions\MainAccount;

class CreditNoteTest extends TestCase
{
    /**
     * Test Creating CreditNote Transaction
     *
     * @return void
     */
    public function testCreateCreditNoteTransaction()
    {
        $clientAccount = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );

        $creditNote = new CreditNote(
            [
            "account_id" => $clientAccount->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $creditNote->save();

        $this->assertEquals($creditNote->account->name, $clientAccount->name);
        $this->assertEquals($creditNote->account->description, $clientAccount->description);
        $this->assertEquals($creditNote->transaction_no, "CN0".$this->period->period_count."/0001");
    }

    /**
     * Test Posting CreditNote Transaction
     *
     * @return void
     */
    public function testPostCreditNoteTransaction()
    {
        $creditNote = new CreditNote(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            ]
        );
        $creditNote->addLineItem($lineItem);

        $creditNote->post();

        $debit = Ledger::where("entry_type", Balance::DEBIT)->get()[0];
        $credit = Ledger::where("entry_type", Balance::CREDIT)->get()[0];

        $this->assertEquals($debit->folio_account, $creditNote->account->id);
        $this->assertEquals($debit->post_account, $lineItem->account_id);
        $this->assertEquals($credit->post_account, $creditNote->account->id);
        $this->assertEquals($credit->folio_account, $lineItem->account_id);
        $this->assertEquals($debit->amount, 100);
        $this->assertEquals($credit->amount, 100);

        $vat_debit = Ledger::where("entry_type", Balance::DEBIT)->get()[1];
        $vat_credit = Ledger::where("entry_type", Balance::CREDIT)->get()[1];

        $this->assertEquals($vat_debit->folio_account, $creditNote->account->id);
        $this->assertEquals($vat_debit->post_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_credit->post_account, $creditNote->account->id);
        $this->assertEquals($vat_credit->folio_account, $lineItem->vat_account_id);
        $this->assertEquals($vat_debit->amount, 16);
        $this->assertEquals($vat_credit->amount, 16);

        $this->assertEquals($creditNote->getAmount(), 116);
    }

    /**
     * Test Credit Note Line Item Account.
     *
     * @return void
     */
    public function testCreditNoteLineItemAccount()
    {
        $creditNote = new CreditNote(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::RECEIVABLE,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $this->expectException(LineItemAccount::class);
        $this->expectExceptionMessage('Credit Note LineItem Account must be of type Operating Revenue');

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::RECONCILIATION
                ]
            )->id,
            ]
        );
        $creditNote->addLineItem($lineItem);

        $creditNote->post();
    }

    /**
     * Test Credit Note Main Account.
     *
     * @return void
     */
    public function testCreditNoteMainAccount()
    {
        $creditNote = new CreditNote(
            [
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                'account_type' => Account::RECONCILIATION,
                ]
            )->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $this->expectException(MainAccount::class);
        $this->expectExceptionMessage('Credit Note Main Account must be of type Receivable');

        $lineItem = factory(LineItem::class)->create(
            [
            "amount" => 100,
            "vat_id" => factory('IFRS\Models\Vat')->create(
                [
                "rate" => 16
                ]
            )->id,
            "account_id" => factory('IFRS\Models\Account')->create(
                [
                "account_type" => Account::OPERATING_REVENUE
                ]
            )->id,
            ]
        );
        $creditNote->addLineItem($lineItem);

        $creditNote->post();
    }

    /**
     * Test Credit Note Find.
     *
     * @return void
     */
    public function testCreditNoteFind()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );
        $transaction = new CreditNote(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $transaction->save();

        $found = CreditNote::find($transaction->id);
        $this->assertEquals($found->transaction_no, $transaction->transaction_no);
    }

    /**
     * Test Credit Note Fetch.
     *
     * @return void
     */
    public function testCreditNoteFetch()
    {
        $account = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );
        $transaction = new CreditNote(
            [
            "account_id" => $account->id,
            "date" => Carbon::now(),
            "narration" => $this->faker->word,
            ]
        );
        $transaction->save();

        $account2 = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );
        $transaction2 = new CreditNote(
            [
            "account_id" => $account2->id,
            "date" => Carbon::now()->addWeeks(2),
            "narration" => $this->faker->word,
            ]
        );
        $transaction2->save();

        // startTime Filter
        $this->assertEquals(count(CreditNote::fetch()), 2);
        $this->assertEquals(count(CreditNote::fetch(Carbon::now()->addWeek())), 1);
        $this->assertEquals(count(CreditNote::fetch(Carbon::now()->addWeeks(3))), 0);

        // endTime Filter
        $this->assertEquals(count(CreditNote::fetch(null, Carbon::now())), 1);
        $this->assertEquals(count(CreditNote::fetch(null, Carbon::now()->subDay())), 0);

        // Account Filter
        $account3 = factory(Account::class)->create(
            [
            'account_type' => Account::RECEIVABLE,
            ]
        );
        $this->assertEquals(count(CreditNote::fetch(null, null, $account)), 1);
        $this->assertEquals(count(CreditNote::fetch(null, null, $account2)), 1);
        $this->assertEquals(count(CreditNote::fetch(null, null, $account3)), 0);

        // Currency Filter
        $currency = factory(Currency::class)->create();
        $this->assertEquals(count(CreditNote::fetch(null, null, null, Auth::user()->entity->currency)), 2);
        $this->assertEquals(count(CreditNote::fetch(null, null, null, $currency)), 0);
    }
}
