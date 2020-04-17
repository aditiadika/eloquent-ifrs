<?php

namespace Tests\Unit;

use Tests\TestCase;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\RecycledObject;

class CurrencyTest extends TestCase
{
    /**
     * Currency Model relationships test.
     *
     * @return void
     */
    public function testCurrencyRelationships()
    {
        $currency = Currency::new($this->faker->word, $this->faker->currencyCode);
        $currency->save();

        $exchangeRate = factory(ExchangeRate::class)->create(
            [
            'currency_id'=> $currency->id,
            ]
        );

        $this->assertEquals(
            $currency->exchangeRates->first()->rate,
            $exchangeRate->rate
        );
    }

    /**
     * Test Currency Model recylcling
     *
     * @return void
     */
    public function testCurrencyRecycling()
    {
        $currency = Currency::new($this->faker->word, $this->faker->currencyCode);
        $currency->delete();

        $recycled = RecycledObject::all()->first();
        $this->assertEquals($currency->recycled->first(), $recycled);
    }
}