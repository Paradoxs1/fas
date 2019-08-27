<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CommonExtension extends AbstractExtension
{
    /**
     * @var string
     */
    private $env;

    /**
     * CommonExtension constructor.
     * @param string $env
     */
    public function  __construct(string $env)
    {
        $this->env = $env;
    }

    /**
     * @return array|\Twig_Function[]
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('display_environment_ribbon', [$this, 'displayEnvironmentRibbon']),
        ];
    }

    /**
     * @return array
     */
    public function displayEnvironmentRibbon(): array
    {
        $data = [];

        if (in_array($this->env, ['staging', 'dev'])) {
            $hash = exec("git log -1 --pretty=format:'%H' --abbrev-commit");
            $data = [
                'label' => 'develop',
                'shortHash' => substr(trim($hash), 0, 6),
                'fullHash' => trim($hash)
            ];
        }

        switch ($this->env) {
            case 'dev':
                return $data;
                break;
            case 'staging':
                $data['label'] = 'staging';
                return $data;
                break;
            case 'demo':
                $data['label'] = 'DEMO';
                return $data;
                break;
            default:
                return [];
        }
    }

    public function getFilters()
    {
        return [
            new TwigFilter('normalCase', [$this, 'camelCaseIntoNormalCase']),
        ];
    }

    public function camelCaseIntoNormalCase($string): string
    {
        preg_match_all('/((?:^|[A-Z])[a-z]+)/',$string,$matches);
        if ($matches[0]) {
            $matches[0][0] = ucfirst($matches[0][0]);
            $result = implode(" ", $matches[0]);
            return $result;
        } else {
            return $string;
        }
    }
}
