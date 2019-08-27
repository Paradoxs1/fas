<?php

namespace App\Service;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TranslationService
 * @package App\Service
 */
class TranslationService
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TranslationService constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator
    )
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getTranslationsForReportJS()
    {
        return addslashes(
            json_encode(
                [
                    'report.unsaved.data.getting.lost' => $this->translator->trans('report.unsaved.data.getting.lost'),
                    'report.load.new.report'           => $this->translator->trans('report.load.new.report'),
                    'report.load.new.report'           => $this->translator->trans('report.load.new.report'),
                    'report.button.yes'                => $this->translator->trans('report.button.yes'),
                    'report.button.cancel'             => $this->translator->trans('report.button.cancel'),
                    'report.terminal'                  => $this->translator->trans('report.terminal'),
                    'report.delete.terminal'           => $this->translator->trans('report.delete.terminal'),
                    'report.total'                     => $this->translator->trans('report.total'),
                    'report.terminal'                  => $this->translator->trans('report.terminal'),
                ]
            )
        );
    }
}