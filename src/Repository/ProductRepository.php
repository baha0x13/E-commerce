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
     * Search products with optional filters and pagination
     *
     * @param string $searchTerm
     * @param string $category
     * @param int $page
     * @param PaginatorInterface $paginator
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function search(
        string $searchTerm = '', 
        string $category = '', 
        int $page = 1, 
        PaginatorInterface $paginator
    ) {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p');

        if ($searchTerm) {
            $queryBuilder->andWhere('p.name LIKE :searchTerm')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
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
     * Get all available categories from products
     * @return array
     */
    public function getAvailableCategories(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}