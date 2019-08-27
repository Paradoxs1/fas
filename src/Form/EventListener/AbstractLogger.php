<?php

namespace App\Form\EventListener;

use App\Repository\LoggerRepositoryInterface;
use App\Service\GelfLogger;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLogger implements LoggerListenerInterface
{
    //For search in the repository
    const ACCOUNT_FIELD = 'login';
    const FACILITY_OR_TENANT_FIELD = 'name';

    //Entity name
    const FACILITY = 'facility';
    const TENANT = 'tenant';
    const ACCOUNT = 'account';
    const FACILITY_LAYOUT = 'facilityLayout';

    //CRUD events for translate
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';

    //For search in the name of the router
    const ROUTE_NEW = ['new', 'add'];
    const ROUTE_EDIT = ['edit', 'profile'];

    //Name route configuration
    const ROUTE_FACILITY_CONFIGURATION = 'facility_configuration';
    const ROUTE_TENANT_CONFIGURATION = 'tenant_configuration';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected $fieldForRequest;

    /**
     * @var array
     */
    protected $reason;

    /**
     * @var string
     */
    protected $translateMessage;

    /**
     * @var Request
     */
    protected $request;

    /**
     * LogoutListener constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param GelfLogger $gelfLogger
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     */
    public function __construct(TokenStorageInterface $tokenStorage, GelfLogger $gelfLogger, TranslatorInterface $translator, ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->tokenStorage = $tokenStorage;
        $this->logger = $gelfLogger;
        $this->translator = $translator;
        $this->container = $container;
        $this->em = $em;
        $this->request = $container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @param string $event
     * @param string $username
     * @param string $entity
     * @param string $name
     * @param int $id
     * @return string
     */
    protected function getTranslateMessage(string $event, string $username, string $entity, string $name, int $id): string
    {
        return $this->translator->trans('gelf_logger.' . $event, [
            '%username%' => $username,
            '%entity%' => $entity,
            '%name%' => $name,
            '%id%' => $id
        ]);
    }

    /**
     * @param array $keys
     * @param array $array
     * @return array
     */
    protected function deleteKeyArray(array $keys, array $array): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }

        foreach ($array as $keyArray => $item) {
            foreach ($keys as $key) {
                if (is_array($item) && array_key_exists($key, $item)) {
                    unset($item[$key]);
                    $array[$keyArray] = $item;
                }
            }

        }

        return $array;
    }

    /**
     * @return void
     */
    public function onPreSubmit(): void
    {
        $this->data = $this->deleteKeyArray(['save', 'passwordHash', '_token'], $this->getRequestData());
    }

    /**
     * @param array $array
     * @param string $route
     * @return bool
     */
    protected function checkRouteName(array $array, string $route): bool
    {
        $result = false;
        foreach ($array as $item) {
             if (strpos($route, $item)) {
                 $result = true;
             };
        }

        return $result;
    }

    /**
     * @return array|null
     */
    protected function getRequestData(): ?array
    {;
        return isset($this->request) ? $this->request->request->all() : null;
    }

    /**
     * @param FormErrorIterator $errors
     * @return void
     */
    protected function getErrorMessages(FormErrorIterator $errors): void
    {
        $this->reason = [];

        foreach ($errors as $error) {
            $name = $error->getOrigin()->getName();
            $errorMessage = $error->getMessage();

            if (isset($this->reason[$name]) && $this->reason[$name] != $errorMessage) {
                $this->reason[$name] = $errorMessage;
            } else {
                $this->reason[$name] = $errorMessage;
            }
        }
    }

    /**
     * @param LoggerRepositoryInterface $repository
     * @param string $field
     */
    protected function getEditDateForTranslateMessage(LoggerRepositoryInterface $repository, string $field): void
    {
        $object = $repository->findOneBy([$field => $this->fieldForRequest]);
        $this->data = ['id' => $object->getId()] + $this->data;
    }

    /**
     * @return void
     */
    protected function cleanVariablesForTranslate(): void
    {
        $this->translateMessage = '';
        $this->reason = $this->data = null;
    }

    /**
     * @param string $field
     * @return string|null
     */
    protected function findFieldInArray(string $field): ?string
    {
        foreach ($this->data as $item) {
            if (array_key_exists($field, $item)) {
                return $item[$field];
            }
        }

        return null;
    }

    /**
     * @param FormEvent $event
     * @param string $field
     * @param string $entity
     * @return void
     */
    protected function generalPostSubmit(FormEvent $event, string $field, string $entity): void
    {
        $username = $this->tokenStorage->getToken()->getUsername();
        $repository = $this->em->getRepository('App\Entity\\' . ucfirst($entity));

        if (isset($this->request)) {
            $route = $this->request->get('_route');
            $name = $this->findFieldInArray($field);

            if ($event->getForm()->isValid()) {
                if ($this->checkRouteName(self::ROUTE_NEW, $route)) {
                    $object = $repository->findOneBy([], ['id' => 'desc']);
                    //$this->translateMessage = $this->getTranslateMessage(self::CREATE, $username, $entity, $name, $object->getId() + 1);
                    $this->data = null;
                } elseif ($this->checkRouteName(self::ROUTE_EDIT, $route)) {
                    $this->getEditDateForTranslateMessage($repository, $field);
                    $this->translateMessage = $this->getTranslateMessage(self::EDIT, $username, $entity, $name, $this->data['id']);
                }

                $this->logger->info($this->translateMessage, $this->reason, $this->data);
            } else {
                $this->getErrorMessages($event->getForm()->getErrors(true));

                if ($this->checkRouteName(self::ROUTE_NEW, $route)) {
                    $this->translateMessage = $this->translator->trans('gelf_logger.failed_create', ['%username%' => $username, '%entity%' => $entity]);
                } elseif ($this->checkRouteName(self::ROUTE_EDIT, $route)) {
                    $this->getEditDateForTranslateMessage($repository, $field);
                    $this->translateMessage = $this->getTranslateMessage('failed_edit', $username, $entity, $name, $this->data['id']);
                }

                $this->logger->error($this->translateMessage, $this->reason, $this->data);
            }
        }

        $this->cleanVariablesForTranslate();
    }
}
