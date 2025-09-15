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
    private string $username; // NEW

    public function __construct(
        int $id,
        string $email,
        string $firstName,
        string $lastName,
        string $taxCode,
        bool $isAdmin,
        string $username = '' // NEW default per retro-compatibilità
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->taxCode = $taxCode;
        $this->isAdmin = $isAdmin;
        $this->username = $username;
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getTaxCode(): string { return $this->taxCode; }
    public function isAdmin(): bool { return $this->isAdmin; }
    public function getUsername(): string { return $this->username; }
}