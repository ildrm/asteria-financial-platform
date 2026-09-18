<?php

declare(strict_types=1);

namespace Asteria\FinancialPlatform\Security;

final class CapabilityRegistrar
{
    public function install(): void
    {
        $administrator = get_role('administrator');
        if ($administrator === null) {
            return;
        }
        foreach (Capabilities::administratorDefaults() as $capability) {
            $administrator->add_cap($capability);
        }
    }
}
