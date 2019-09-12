<?php
class FirstCest
{
    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        //$I->see('Text. In. Frontpage.');
    }
}