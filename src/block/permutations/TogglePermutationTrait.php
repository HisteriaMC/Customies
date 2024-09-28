<?php

namespace customiesdevs\customies\block\permutations;

use customiesdevs\customies\block\Material;
use pocketmine\data\bedrock\block\convert\BlockStateReader;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\nbt\tag\CompoundTag;

trait TogglePermutationTrait
{
    private bool $isToggled = false;

    public function getBlockProperties(): array {
        return [
            new BlockProperty("histeria:toggled", [false, true]),
        ];
    }

    public function getPermutations(): array
    {
        $permutations = [];
        foreach ([false, true] as $enabled) {
            $texture = $this->getBaseTexture() . ($enabled ? "_on" : "_off");
            $materials = $this->getTargetMaterial($texture);

            $materialsNbt = CompoundTag::create();
            foreach ($materials as $material) {
                $materialsNbt->setTag($material->getTarget(), $material->toNBT());
            }

            $permutation = (new Permutation("q.block_property('histeria:toggled') == $enabled"))
                ->withComponent("minecraft:material_instances", CompoundTag::create()
                    ->setTag("mappings", CompoundTag::create()) // What is this? The client will crash if it is not sent.
                    ->setTag("materials", $materialsNbt));
            $this->getAdditionalComponents($permutation, $enabled);
            $permutations[] = $permutation;
        }

        return $permutations;
    }

    public function getCurrentBlockProperties(): array
    {
        return [$this->isToggled];
    }

    public function serializeState(BlockStateWriter $blockStateOut): void
    {
        $blockStateOut->writeBool("histeria:toggled", $this->isToggled);
    }

    public function deserializeState(BlockStateReader $blockStateIn): void
    {
        $this->isToggled = $blockStateIn->readBool("histeria:toggled");
    }

    protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void
    {
        $w->bool($this->isToggled);
    }

    public function setToggled(bool $toggled): void
    {
        $this->isToggled = $toggled;
        $this->position->getWorld()->setBlock($this->getPosition(), $this);
    }

    public function isToggled(): bool
    {
        return $this->isToggled;
    }

    public function getTargetMaterial($texture): array
    {
        return [new Material(Material::TARGET_ALL, $texture, Material::RENDER_METHOD_ALPHA_TEST, false, false)];
    }

    /**
     * This is made to be override to add permutations
     * @return void
     */
    public function getAdditionalComponents(Permutation $permutation, bool $toggled): void
    {

    }

    abstract public function getBaseTexture(): string;
}