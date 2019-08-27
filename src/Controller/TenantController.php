<?php

namespace App\Controller;

use App\Entity\FacilityLayout;
use App\Form\DefaultFacilityLayoutType;
use App\Service\Facility\ConfigurationParamsHandlerComposite;
use App\Service\Facility\TenantConfigurationProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TenantController
 * @package App\Controller
 */
class TenantController extends AbstractController
{
    /**
     * @Route("/tenant", name="tenant")
     * @return Response
     */
    public function tenant(): Response
    {
        return $this->render('tenant/tenant.html.twig');
    }

    /**
     * @Route("/tenant/configuration", name="tenant_configuration")
     * @param Request $request
     * @param TenantConfigurationProcessor $tenantConfigurationProcessor
     * @param ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Exception
     */
    public function tenantConfiguration(
        Request $request,
        TenantConfigurationProcessor $tenantConfigurationProcessor,
        ConfigurationParamsHandlerComposite $configurationParamsHandlerComposite,
        EntityManagerInterface $em
    ): Response {
        $tenant = $this->getUser()->getTenant();
        $data = [];
        $facilityLayout = $em->getRepository(FacilityLayout::class)->findOneBy(['tenant' => $tenant, 'facility' => null]) ?: new FacilityLayout();

        $form = $this->createForm(DefaultFacilityLayoutType::class, $facilityLayout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all();
            $tenantConfigurationProcessor->process($requestData, $facilityLayout, $tenant);
        }

        return $this->render('tenant/configuration.html.twig', [
            'data'                   => $data = $configurationParamsHandlerComposite->getPositions($data, $facilityLayout),
            'form'                   => $form->createView(),
            'tenant'                 => $tenant,
            'facilityLayout'         => $facilityLayout,
        ]);
    }
}
