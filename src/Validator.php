<?php

namespace Fruit\CheckKit;

use Exception;

/**
 * Validator represents an object to check data with custom rules.
 */
interface Validator
{
    /**
     * Check $data according to $rule.
     *
     * It uses exception to indicate the result of validating, but not the PHP-way:
     * implementations MUST throw an Exceptions::InvalidRuleException
     * if user provides "semantically wrong" rules, and return an exception for
     * invalid data.
     *
     * Semantically wrong is something like passing string to numeric rules, string
     * length or number boundary for example.
     *
     * ### About non-standard exception usage
     *
     * 1. Throwing exception here does not provide more useful detail about the
     *    incorrectness of your data. Instead, you'll have to dig further in stack
     *    dump to find out source of your data. Throwing exception from checker is
     *    fairly enough for users to understand "Ah! The data is invalid".
     * 2. Validators might need to call other validators to do its work, array for
     *    example. You will fall into try-catch hell when implementing such
     *    validator if you throws exception for invalid data.
     *
     * ### Notes about implementing
     *
     * 1. Throw Exceptions::InvalidRuleException if $rule is not supported.
     * 2. Return `null` if data is valid, and exception instance otherwise.
     *    (Exceptions::InvalidFormatException for example)
     * 3. Use primitive types in rule if possible.
     * 4. Add detailed documentation about non-primitive type rules.
     * 5. Rule validating COULD be lazy. In other words, passing invalid rules to
     *    this method MIGHT NOT trigger Exceptions::InvalidRuleException if data
     *    failed validating before applying the rule.
     * 6. DO NOT create new validator instance dynamically. But it is good to create
     *    instances of other validator and cache them if you are reusing old ones
     *    to create "combined" validator, like using Validators::FloatValidator and
     *    Validators::IntValidator to create "integer or float validator".
     * 7. NEVER DEPENDS ON INTERNAL STATE. There should be no side-effects here.
     */
    public function validate(Repo $repo, $data, array $rule);
}
