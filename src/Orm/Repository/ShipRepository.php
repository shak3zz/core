<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use PhpTal\PHPTAL;
use Stu\Orm\Entity\Ship;
use Stu\Orm\Entity\ShipInterface;
use Stu\Orm\Entity\ShipRumpSpecial;
use Stu\Orm\Entity\ShipStorage;
use Stu\Orm\Entity\StarSystemInterface;
use Stu\Orm\Entity\UserInterface;

final class ShipRepository extends EntityRepository implements ShipRepositoryInterface
{

    public function prototype(): ShipInterface
    {
        return new Ship();
    }

    public function save(ShipInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->persist($post);
        $em->flush();
    }

    public function delete(ShipInterface $post): void
    {
        $em = $this->getEntityManager();

        $em->remove($post);
        $em->flush();
    }

    public function getAmountByUserAndSpecialAbility(
        int $userId,
        int $specialAbilityId
    ): int {
        return (int)$this->getEntityManager()->createQuery(
            sprintf(
                'SELECT COUNT(s) FROM %s s WHERE s.user_id = :userId AND s.rumps_id IN (
                    SELECT rs.rumps_id FROM %s rs WHERE rs.special = :specialAbilityId
                )',
                Ship::class,
                ShipRumpSpecial::class
            )
        )->setParameters([
            'userId' => $userId,
            'specialAbilityId' => $specialAbilityId,
        ])->getSingleScalarResult();
    }

    public function getAmountByUserAndRump(int $userId, int $shipRumpId): int
    {
        return $this->count([
            'user_id' => $userId,
            'rumps_id' => $shipRumpId,
        ]);
    }

    public function getByUser(UserInterface $user): iterable
    {
        return $this->findBy([
            'user_id' => $user,
        ]);
    }

    public function getByInnerSystemLocation(
        int $starStstemId,
        int $sx,
        int $sy
    ): iterable {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.systems_id = :starSystemId AND s.sx = :sx AND s.sy = :sy AND s.cloak = :cloakState
                ORDER BY s.is_destroyed ASC, s.fleets_id DESC, s.id ASC',
                Ship::class
            )
        )->setParameters([
            'starSystemId' => $starStstemId,
            'sx' => $sx,
            'sy' => $sy,
            'cloakState' => 0
        ])->getResult();
    }

    public function getTradePostsWithoutDatabaseEntry(): iterable
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.database_id is null AND s.trade_post_id > 0',
                Ship::class
            )
        )->getResult();
    }

    public function getByUserAndFleetAndBase(int $userId, ?int $fleetId, bool $isBase): iterable
    {
        return $this->findBy(
            [
                'user_id' => $userId,
                'fleets_id' => $fleetId,
                'is_base' => $isBase,
            ],
            ['id' => 'asc']
        );
    }

    public function getWithTradeLicensePayment(
        int $userId,
        int $tradePostShipId,
        int $commodityId,
        int $amount
    ): iterable {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.user_id = :userId AND s.dock = :tradePostShipId AND s.id IN (
                    SELECT ss.ships_id FROM %s ss WHERE ss.goods_id = :commodityId AND ss.count >= :amount
                )',
                Ship::class,
                ShipStorage::class
            )
        )->setParameters([
            'userId' => $userId,
            'tradePostShipId' => $tradePostShipId,
            'commodityId' => $commodityId,
            'amount' => $amount,
        ])->getResult();
    }

    public function getSuitableForShildRegeneration(int $regenerationThreshold): iterable
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.is_destroyed = :destroyedState AND s.schilde<s.max_schilde AND s.shield_regeneration_timer <= :regenerationThreshold',
                Ship::class
            )
        )->setParameters([
            'regenerationThreshold' => $regenerationThreshold,
            'destroyedState' => 0,
        ])->getResult();
    }

    public function getDebrisFields(): iterable
    {
        return $this->findBy([
            'is_destroyed' => true,
        ]);
    }

    public function getPlayerShipsForTick(): iterable
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.user_id > 100 AND s.plans_id > 0',
                Ship::class
            )
        )->getResult();
    }

    public function getNpcShipsForTick(): iterable
    {
        return $this->getEntityManager()->createQuery(
            sprintf(
                'SELECT s FROM %s s WHERE s.user_id BETWEEN 2 AND 100',
                Ship::class
            )
        )->getResult();
    }

    public function getSensorResultInnerSystem(int $systemId, int $sx, int $sy, int $sensorRange): iterable
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('posx', 'posx', 'integer');
        $rsm->addScalarResult('posy', 'posy', 'integer');
        $rsm->addScalarResult('sysid', 'sysid', 'integer');
        $rsm->addScalarResult('shipcount', 'shipcount', 'integer');
        $rsm->addScalarResult('cloakcount', 'cloakcount', 'integer');
        $rsm->addScalarResult('type', 'type', 'integer');
        $rsm->addScalarResult('field_id', 'field_id', 'integer');
        return $this->getEntityManager()->createNativeQuery(
            'SELECT a.sx as posx,a.sy as posy,a.systems_id as sysid, count(b.id) as shipcount, count(c.id) as cloakcount, d.type, a.field_id
            FROM stu_sys_map a LEFT JOIN stu_ships b ON b.systems_id = a.systems_id AND b.sx = a.sx AND b.sy = a.sy AND b.cloak = :stateOff LEFT JOIN
            stu_ships c ON c.systems_id = a.systems_id AND c.sx = a.sx AND c.sy = a.sy AND c.cloak = :stateOn LEFT JOIN
            stu_map_ftypes d ON d.id = a.field_id WHERE
			a.systems_id = :starSystemId AND a.sx BETWEEN :sxStart AND :sxEnd AND a.sy BETWEEN :syStart AND :syEnd
            GROUP BY a.sy, a.sx, a.systems_id, d.type, a.field_id ORDER BY a.sy,a.sx',
            $rsm
        )->setParameters([
            'starSystemId' => $systemId,
            'sxStart' => $sx - $sensorRange,
            'sxEnd' => $sx + $sensorRange,
            'syStart' => $sy - $sensorRange,
            'syEnd' => $sy + $sensorRange,
            'stateOff' => 0,
            'stateOn' => 1
        ])->getResult();
    }

    public function getSensorResultOuterSystem(int $cx, int $cy, int $sensorRange): iterable
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('posx', 'posx', 'integer');
        $rsm->addScalarResult('posy', 'posy', 'integer');
        $rsm->addScalarResult('shipcount', 'shipcount', 'integer');
        $rsm->addScalarResult('cloakcount', 'cloakcount', 'integer');
        $rsm->addScalarResult('type', 'type', 'integer');
        $rsm->addScalarResult('field_id', 'field_id', 'integer');
        return $this->getEntityManager()->createNativeQuery(
            'SELECT a.cx as posx,a.cy as posy, count(b.id) as shipcount, count(c.id) as cloakcount, d.type, a.field_id
            FROM stu_map a LEFT JOIN stu_ships b ON b.cx=a.cx AND b.cy=a.cy AND b.cloak = :stateOff LEFT JOIN stu_ships c ON c.cx = a.cx AND
            c.cy=a.cy AND c.cloak = :stateOn LEFT JOIN stu_map_ftypes d ON d.id = a.field_id
			WHERE a.cx BETWEEN :sxStart AND :sxEnd AND a.cy BETWEEN :syStart AND :syEnd GROUP BY a.cy, a.cx, d.type, a.field_id ORDER BY a.cy,a.cx',
            $rsm
        )->setParameters([
            'sxStart' => $cx - $sensorRange,
            'sxEnd' => $cx + $sensorRange,
            'syStart' => $cy - $sensorRange,
            'syEnd' => $cy + $sensorRange,
            'stateOff' => 0,
            'stateOn' => 1
        ])->getResult();
    }


    public function getSingleShipScannerResults(
        ?StarSystemInterface $starSystem,
        int $sx,
        int $sy,
        int $cx,
        int $cy,
        int $ignoreId,
        bool $isBase
    ): iterable {
        if ($starSystem === null) {
            $query = $this->getEntityManager()->createQuery(
                sprintf(
                    'SELECT s FROM %s s WHERE s.systems_id is null AND s.cx = :cx AND s.cy = :cy AND
                         s.sx = :sx AND s.sy = :sy AND s.fleets_id IS NULL AND s.cloak = :cloakState AND
                         s.is_base = :isBase AND s.id != :ignoreId',
                    Ship::class
                )
            )->setParameters([
                'sx' => $sx,
                'sy' => $sy,
                'cx' => $cx,
                'cy' => $cy,
                'ignoreId' => $ignoreId,
                'isBase' => $isBase,
                'cloakState' => 0
            ]);
        } else {
            $query = $this->getEntityManager()->createQuery(
                sprintf(
                    'SELECT s FROM %s s WHERE s.systems_id = :starSystem AND s.cx = :cx AND s.cy = :cy AND
                         s.sx = :sx AND s.sy = :sy AND s.fleets_id IS NULL AND s.cloak = :cloakState AND
                         s.is_base = :isBase AND s.id != :ignoreId',
                    Ship::class
                )
            )->setParameters([
                'starSystem' => $starSystem,
                'sx' => $sx,
                'sy' => $sy,
                'cx' => $cx,
                'cy' => $cy,
                'ignoreId' => $ignoreId,
                'isBase' => $isBase,
                'cloakState' => 0
            ]);
        }
        return $query->getResult();
    }
}
