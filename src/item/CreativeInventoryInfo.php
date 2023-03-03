<?php

namespace customiesdevs\customies\item;

use customiesdevs\customies\item\enum\{Category, Group};

final class CreativeInventoryInfo {

    /**
     * Returns a default type which puts the item in to the all category and no sub group.
     *
     * @return static
     */
	public static function DEFAULT(): self {
		return new self(Category::ALL, Group::NONE);
	}

	public function __construct(private readonly Category $category = Category::NONE, private readonly Group $group = Group::NONE) {}

    /**
     * Returns the category the item is part of.
     *
     * @return Category
     */
	public function getCategory(): Category {
		return $this->category;
	}

    /**
     * Returns the numeric representation of the category the item is part of.
     *
     * @return int
     */
	public function getNumericCategory(): int {
		return match ($this->getCategory()) {
			Category::CONSTRUCTION => 1,
            Category::NATURE => 2,
            Category::EQUIPMENT => 3,
            Category::ITEMS => 4,
			default => 0
		};
	}

    /**
     * Returns the group the item is part of, if any.
     *
     * @return Group
     */
	public function getGroup(): Group {
		return $this->group;
	}
}
