<?php

namespace App\Security\Voter;

use App\Entity\Permission;
use App\Entity\RoleHasPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PermissionVoter extends Voter
{

    private $entity_manager;
    private $permission_repo;
    private $role_has_permission_repo;
    private $permissions;

    public function __construct(EntityManagerInterface $entity_manager)
    {
        $this->entity_manager = $entity_manager;
        $this->permission_repo = $entity_manager->getRepository(Permission::class);
        $this->role_has_permission_repo = $entity_manager->getRepository(RoleHasPermission::class);
    }

    protected function supports($attribute, $subject = null): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html

        $permissions = $this->permission_repo->getAllPermissions();
        $this->permissions = $permissions;

        return in_array($attribute, $permissions) & $subject == 'permission';
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        $permission = $this->permission_repo->findOneBySlug($attribute);

        $has_permission = $this->role_has_permission_repo->findOnePermission($user->getRole() , $permission->getId() , $user->getSubRole());



        return !is_null($has_permission);
    }
}
