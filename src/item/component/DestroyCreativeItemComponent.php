<?php
declare(strict_types=1);

namespace customiesdevs\customies\item\component;

final class DestroyCreativeItemComponent implements ItemComponent
{
    private bool $destroyCreative;

    public function __construct(bool $destroyCreative = true) {
        $this->destroyCreative = $destroyCreative;
    }

    public function getName(): string {
        return "can_destroy_in_creative";
    }

    public function getValue(): bool {
        return $this->destroyCreative;
    }

    public function isProperty(): bool {
        return true;
    }
}