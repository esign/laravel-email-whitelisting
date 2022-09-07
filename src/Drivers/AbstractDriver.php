<?php

namespace Esign\EmailWhitelisting\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractDriver
{
    protected function filterMatchingEmailAddressCollections(Collection $emailAddresses, Collection $whitelistedEmailAddresses): Collection
    {
        return $emailAddresses->filter(function (string $emailAddress) use ($whitelistedEmailAddresses) {
            return $whitelistedEmailAddresses->contains(function (string $whiteListedEmailAddress) use ($emailAddress) {
                return Str::is($whiteListedEmailAddress, $emailAddress);
            });
        });
    }
}
