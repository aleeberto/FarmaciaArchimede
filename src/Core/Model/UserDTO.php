<?php
declare(strict_types=1);

namespace App\Core\Model;

class UserDTO
{
    private int $id;
    private string $email;
    private string $firstName;
    private string $lastName;
    private string $taxCode;
    private bool $isAdmin;

    public function __construct(
        int $id,
        string $email,
        string $firstName,
        string $lastName,
        string $taxCode,
        bool $isAdmin
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->taxCode = $taxCode;
        $this->isAdmin = $isAdmin;
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getTaxCode(): string { return $this->taxCode; }
    public function isAdmin(): bool { return $this->isAdmin; }
}
