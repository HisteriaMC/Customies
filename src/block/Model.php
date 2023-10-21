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
	private Vector3 $originCollision;
	private Vector3 $sizeCollision;
    private Vector3 $originSelection;
    private Vector3 $sizeSelection;
    private bool $collidable;

	/**
	 * @param Material[] $materials
	 */
	public function __construct(array $materials, ?string $geometry, ?Vector3 $originCollision = null, ?Vector3 $sizeCollision = null,
                                ?Vector3 $originSelection = null, ?Vector3 $sizeSelection = null, bool $collidable = true) {
		$this->materials = $materials;
		$this->geometry = $geometry;
		$this->originCollision = $originCollision ?? new Vector3(-8, 0, -8); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
		$this->sizeCollision = $sizeCollision ?? new Vector3(16, 16, 16); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
        $this->originSelection = $originSelection ?? $originCollision ?? new Vector3(-8, 0, -8); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
        $this->sizeSelection = $sizeSelection ?? $sizeCollision ?? new Vector3(16, 16, 16); // must be in the range (-8, 0, -8) to (8, 16, 8), inclusive.
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
                ->setString("identifier", $this->geometry);
            $material["minecraft:collision_box"] = CompoundTag::create()
                ->setByte("enabled", $this->collidable ? 1 : 0)
                ->setTag("origin", new ListTag([
                    new FloatTag($this->originCollision->getX()),
                    new FloatTag($this->originCollision->getY()),
                    new FloatTag($this->originCollision->getZ())
                ]))
                ->setTag("size", new ListTag([
                    new FloatTag($this->sizeCollision->getX()),
                    new FloatTag($this->sizeCollision->getY()),
                    new FloatTag($this->sizeCollision->getZ())
                ]));
            $material["minecraft:selection_box"] = CompoundTag::create()
                ->setByte("enabled", 1)
                ->setTag("origin", new ListTag([
                    new FloatTag($this->originSelection->getX()),
                    new FloatTag($this->originSelection->getY()),
                    new FloatTag($this->originSelection->getZ())
                ]))
                ->setTag("size", new ListTag([
                    new FloatTag($this->sizeSelection->getX()),
                    new FloatTag($this->sizeSelection->getY()),
                    new FloatTag($this->sizeSelection->getZ())
                ]));
		}

		return $material;
	}
}
