<?php

namespace Esign\EmailWhitelisting\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

abstract class AbstractDriver
{
    protected function filterMatchingEmailAddressCollections(Collection $emailAddresses, Collection $whitelistedEmailAddresses): Collection
    {
        $filteredAddresses =  $emailAddresses->filter(function (string $emailAddress) use ($whitelistedEmailAddresses) {
            return $whitelistedEmailAddresses->contains(function (string $whiteListedEmailAddress) use ($emailAddress) {
                return Str::is($whiteListedEmailAddress, $emailAddress);
            });
        });

        if (config('email-whitelisting.warn')) {
            $diff = $emailAddresses->diff($filteredAddresses);
            if ($diff->isNotEmpty()) {
                Log::warning("Skipping email addresses: {$diff->join(', ')}.");
            }
        }

        return $filteredAddresses;
    }
}
