<?php

namespace App\DataFixtures;

use App\Entity\Permission;
use App\Entity\RoleHasPermission;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RolePermissionFixture extends Fixture
{
    protected $container;

    protected function container(ContainerInterface $container) 
    {
        $this->container = $container;
    }

    protected function permissionList()
    {
        return [
            [
                'role_slug' => 'ROLE_USER_SELLER',
                'subrole_slug' => null,
                'permission_slug' => 'order.approve_negotiation',
                'permission_name' => 'Approve Negotation',
            ],
            [
                'role_slug' => 'ROLE_USER_SELLER',
                'subrole_slug' => null,
                'permission_slug' => 'order.cancel_order',
                'permission_name' => 'Cancel Order',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PP',
                'permission_slug' => 'order.approve_negotiation',
                'permission_name' => 'Approve Negotation',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PP',
                'permission_slug' => 'order.cancel_order',
                'permission_name' => 'Cancel Order',
            ],

            
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PPK',
                'permission_slug' => 'order.reject_received',
                'permission_name' => 'Reject Pengiriman Barang',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PPK',
                'permission_slug' => 'order.approve_received',
                'permission_name' => 'Approve Pengiriman Barang',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PPK',
                'permission_slug' => 'order.cancel_order',
                'permission_name' => 'Cancel Order',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PPK',
                'permission_slug' => 'order.order_confirmation',
                'permission_name' => 'Approve Order',
            ],
            [
                'role_slug' => 'ROLE_USER_GOVERNMENT',
                'subrole_slug' => 'PPK',
                'permission_slug' => 'order.order_spp',
                'permission_name' => 'Order Parsial',
            ],
        ];
    }

    protected function createPermission(ObjectManager $manager , $permission)
    {
        $modelPermission = new Permission();
        $modelPermission->setName($permission['permission_name']);
        $modelPermission->setSlug($permission['permission_slug']);
        $modelPermission->setCreatedAt();
        $modelPermission->setUpdatedAt();

        $manager->persist($modelPermission);
        $manager->flush();

        $permission_repo = $manager->getRepository(Permission::class);
        $data_permission = $permission_repo->findOneBySlug($permission['permission_slug']);

        return $data_permission;
    }

    protected function createHasRolePermission(ObjectManager $manager , $data)
    {
        $rolePermission = new RoleHasPermission();
        $rolePermission->setRoleSlug($data['role_slug']);
        $rolePermission->setSubroleSlug($data['subrole_slug']);
        $rolePermission->setPermissionId($data['permission_id']);

        $manager->persist($rolePermission);
        $manager->flush();

        $role_has_permission_repo = $manager->getRepository(RoleHasPermission::class);
        $data_permission = $role_has_permission_repo->findOnePermission($data['role_slug'], $data['permission_id'], $data['subrole_slug'] );

        return $data_permission;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $permission_repo = $manager->getRepository(Permission::class);
        $role_has_permission_repo = $manager->getRepository(RoleHasPermission::class);

        $permission_repo->removeAll();
        $role_has_permission_repo->removeAll();

        $permissions = $this->permissionList();


        foreach ($permissions as $permission) {
            
            $data_permission = $permission_repo->findOneBySlug($permission['permission_slug']);

            if(is_null($data_permission)) { 
               $data_permission = $this->createPermission($manager , $permission);
            }

            $has_permission = $role_has_permission_repo->findOnePermission($permission['role_slug'], $data_permission->getId(), $permission['subrole_slug'] );

            if(is_null($has_permission)) {
                $has_permission = $this->createHasRolePermission($manager, [
                    'role_slug' => $permission['role_slug'],
                    'subrole_slug' => $permission['subrole_slug'],
                    'permission_id' => $data_permission->getId(),
                ]);
            }

        }

        

        $manager->flush();
    }
}
