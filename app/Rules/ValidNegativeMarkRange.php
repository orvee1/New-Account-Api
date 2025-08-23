<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidNegativeMarkRange implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return empty( $value ) || preg_match( '/^([0-9]+)|([0-9]+\-[0-9]+)$\,/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Invalid Range. Range must be like 1-3 or 1,2,3 or 1-3,10,12';
    }
}
