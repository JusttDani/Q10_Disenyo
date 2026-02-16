<?php

namespace App\Repository;

use App\Entity\Producto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Producto>
 */
class ProductoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Producto::class);
    }

    /**
     * Busca productos cuyo nombre o descripción contengan la cadena indicada.
     *
     * @return Producto[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nombre LIKE :q')
            ->orWhere('p.descripcion LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('p.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
