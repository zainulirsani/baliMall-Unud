<?php

namespace App\Entity;

use App\Repository\RoleHasPermissionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoleHasPermissionRepository::class)
 * @ORM\Table(name="role_has_permissions")
 */
class RoleHasPermission
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $role_slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subrole_slug;

    /**
     * @ORM\Column(type="integer")
     */
    private $permission_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleSlug(): ?string
    {
        return $this->role_slug;
    }

    public function setRoleSlug(?string $role_slug): self
    {
        $this->role_slug = $role_slug;

        return $this;
    }

    public function getSubroleSlug(): ?string
    {
        return $this->subrole_slug;
    }

    public function setSubroleSlug(?string $subrole_slug): self
    {
        $this->subrole_slug = $subrole_slug;

        return $this;
    }

    public function getPermissionId(): ?int
    {
        return $this->permission_id;
    }

    public function setPermissionId(int $permission_id): self
    {
        $this->permission_id = $permission_id;

        return $this;
    }
}
