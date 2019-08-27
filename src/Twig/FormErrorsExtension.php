<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;

class FormErrorsExtension extends AbstractExtension
{
    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_unique_errors', [$this, 'getUniqueErrors']),
        ];
    }

    /**
     * @param string $errors
     * @return string
     */
    public function getUniqueErrors(string $errors): string
    {
        if (!empty($errors)) {
            $errors = strip_tags(implode(' ', array_unique(explode('><',$errors))));
        }

        return $errors;
    }
}