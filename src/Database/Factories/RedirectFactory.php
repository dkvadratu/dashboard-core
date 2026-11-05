<?php

namespace Dkvadratu\DashboardCore\Database\Factories;

use Dkvadratu\DashboardCore\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Redirect> */
class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition(): array
    {
        return [
            'source' => '/old-'.fake()->unique()->uuid(),
            'destination' => '/new-'.fake()->uuid(),
            'status_code' => 301,
            'enabled' => true,
            'redirect_valid_until' => null,
        ];
    }
}
