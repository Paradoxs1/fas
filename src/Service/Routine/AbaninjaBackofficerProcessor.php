<?php

namespace App\Service\Routine;

use App\Entity\Facility;
use Symfony\Component\Translation\TranslatorInterface;


class AbaninjaBackofficerProcessor implements RoutineProcessorInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * AbaninjaCashierProcessor constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getSuccessMessage(): string
    {
        return $this->translator->trans('report_api_abaninja.success');
    }

    /**
     * @param Facility $facility
     * @param string $date
     * @param array $requestData
     * @param bool $approved
     * @return array
     */
    public function getOverlayData(Facility $facility, string $date = '', array $requestData = [], bool $approved = false): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getErrorMessages()
    {
        return [];
    }
}
