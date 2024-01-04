<?php

namespace App\Repository;

use App\Entity\AppUser;
use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findReservationsByRoomId(int $roomId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.room = :roomId')
            ->setParameter('roomId', $roomId)
            ->orderBy('r.startDatetime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findReservationsByUser(AppUser $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.reservedFor = :user')
            ->setParameter('user', $user)
            ->orderBy('r.startDatetime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findVisitingReservationsByUser(AppUser $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere(':user MEMBER OF r.visitors')
            ->setParameter('user', $user)
            ->orderBy('r.startDatetime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOverlappingReservations(int $roomId, ?\DateTime $start, ?\DateTime $end, ?int $reservationId): array
    {
        // if start or end is null, there is no overlap (null values should be handled by validation (e.g. NotBlank))
        if(!$start || !$end) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.room = :roomId')
            ->andWhere('r.status = :status')
            ->andWhere('r.startDatetime < :endDatetime')
            ->andWhere('r.endDatetime > :startDatetime')
            ->setParameter('roomId', $roomId)
            ->setParameter('status', Reservation::STATUS_APPROVED)
            ->setParameter('startDatetime', $start)
            ->setParameter('endDatetime', $end);

        if($reservationId) {
            $qb->andWhere('r.id != :reservationId')
                ->setParameter('reservationId', $reservationId);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
