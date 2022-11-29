<?php

namespace App\Traits;

use Carbon\Carbon;

trait ModelLicenseTrait
{
    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes(array_merge($this->attributes, [
            'purchase_at' => Carbon::now(),
            'expiration_date' => Carbon::now()->add(1, 'year')
        ]), true);

        parent::__construct($attributes);
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }
}
