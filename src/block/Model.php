<?php
declare(strict_types=1);

namespace customiesdevs\customies\block;

use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;

final class Model {

	/** @var Material[] */
	private array $materials;
	private ?string $geometry;
	private Vector3 $origin;
	private Vector3 $size;
    private bool $collidable;

	/**
	 * @param Material[] $materials
	 */
	public function __construct(array $materials, ?string $geometry, ?Vector3 $origin = null, ?Vector3 $size = null, bool $collidable = true) {
		$this->materials = $materials;
		$this->geometry = $geometry;
		$this->origin = $origin ?? new Vector3(-8, 0, -8); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
		$this->size = $size ?? new Vector3(16, 16, 16); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
        $this->collidable = $collidable;
	}

	/**
	 * Returns the model in the correct NBT format supported by the client.
	 * @return CompoundTag[]
	 */
	public function toNBT(): array {
		$materials = CompoundTag::create();
		foreach($this->materials as $material){
			$materials->setTag($material->getTarget(), $material->toNBT());
		}

		$material = [
			"minecraft:material_instances" => CompoundTag::create()
				->setTag("mappings", CompoundTag::create()) // What is this? The client will crash if it is not sent.
				->setTag("materials", $materials),
		];
		if($this->geometry === null) {
			$material["minecraft:unit_cube"] = CompoundTag::create();
		} else {
            $material["minecraft:geometry"] = CompoundTag::create()
                ->setString("value", $this->geometry);
            $material["minecraft:collision_box"] = $this->collidable ? CompoundTag::create()
                ->setByte("enabled", 1)
                ->setTag("origin", new ListTag([
                    new FloatTag($this->origin->getX()),
                    new FloatTag($this->origin->getY()),
                    new FloatTag($this->origin->getZ())
                ]))
                ->setTag("size", new ListTag([
                    new FloatTag($this->size->getX()),
                    new FloatTag($this->size->getY()),
                    new FloatTag($this->size->getZ())
                ])) : new ByteTag(0); //0 = false, no collissions
            $material["minecraft:selection_box"] = $this->collidable ? CompoundTag::create()
                ->setByte("enabled", 1)
                ->setTag("origin", new ListTag([
                    new FloatTag($this->origin->getX()),
                    new FloatTag($this->origin->getY()),
                    new FloatTag($this->origin->getZ())
                ]))
                ->setTag("size", new ListTag([
                    new FloatTag($this->size->getX()),
                    new FloatTag($this->size->getY()),
                    new FloatTag($this->size->getZ())
                ])) : new ByteTag(0);
		}
		return $material;
	}
}
