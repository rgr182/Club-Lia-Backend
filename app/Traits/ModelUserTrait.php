<?php

namespace App\Traits;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;


trait ModelUserTrait
{
    public function __construct(array $attributes = [])
    {

        $this->setRawAttributes(array_merge($this->attributes, [
            'uuid' => Uuid::uuid4(),
            'member_since' => Carbon::now()
        ]), true);

        parent::__construct($attributes);
    }


}
