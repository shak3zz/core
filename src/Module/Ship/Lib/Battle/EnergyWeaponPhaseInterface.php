<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Lib\Battle;

use Stu\Orm\Entity\ShipInterface;

interface EnergyWeaponPhaseInterface
{
    public function fire(ShipInterface $attacker, array $targetPool): array;

    public function getEnergyWeaponEnergyCosts(): int;
}
