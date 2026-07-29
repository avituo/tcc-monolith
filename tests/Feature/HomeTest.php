<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_redirects_to_the_product_catalog(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('products.index'));
    }
}
