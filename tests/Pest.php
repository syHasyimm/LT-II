<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Tes Feature berbasis Pest memakai TestCase aplikasi dan database bersih.
| Tes kelas PHPUnit bawaan starter kit tetap berjalan apa adanya.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/Domain');
