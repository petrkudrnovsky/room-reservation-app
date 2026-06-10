<?php

namespace App\Repository;

use App\Entity\AccessLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessLog>
 *
 * @method AccessLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccessLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccessLog[]    findAll()
 * @method AccessLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessLog::class);
    }
}
