<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetRequest
{
    /**
     * @var string|null
     * @Assert\NotBlank(
     *     message="security.account.username_not_blank"
     * )
     * @Assert\Regex
     * (
     *     pattern="/^[a-zA-Z0-9\S]{2,}$/",
     *     message="security.account.username_min_length"
     * )
     */
    private $username;

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }
}
