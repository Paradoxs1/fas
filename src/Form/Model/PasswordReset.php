<?php

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class PasswordReset
{
    /**
     * @var string|null
     * @Assert\NotBlank(
     *     message = "security.new_password_not_blank"
     * )
     * @Assert\Length(
     *      min = 6,
     *      minMessage = "security.new_password_min_length",
     * )
     */
    private $password;

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}
