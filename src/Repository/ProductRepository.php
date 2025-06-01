<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products with optional filters and pagination, excluding deleted products.
     */
    public function search(
        string $searchTerm = '',
        string $category = '',
        int $page = 1,
        PaginatorInterface $paginator
    ) {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.isDeleted = false');

        if ($searchTerm) {
            $queryBuilder->andWhere('p.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        if ($category) {
            $queryBuilder->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        return $paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            10,
            [
                'defaultSortFieldName' => 'p.id',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Get all categories from non-deleted products.
     */
    public function getAvailableCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->where('p.isDeleted = false')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
