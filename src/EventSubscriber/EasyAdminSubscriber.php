<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['setIllustration'],
           // BeforeEntityUpdatedEvent::class => ['updateIllustration']
        ];
    }

    public function setIllustration(BeforeEntityPersistedEvent $event)
    {
        if(!($event->getEntityInstance() instanceof Product)) {
            return;
        }

        $this->uploadIllustration($event);
    }

    public function updateIllustration(BeforeEntityUpdatedEvent $event)
    {
        if(!($event->getEntityInstance() instanceof Product)) {
            return;
        }

        if($_FILES['Product']['name']['illustration'] != '') {
            $this->uploadIllustration($event);
        }
    }

    public function uploadIllustration($event)
    {
        $entity = $event->getEntityInstance();

        $tmpName = $_FILES['Product']['tmp_name']['illustration']['file'];

        //dd($_FILES['Product']);

        $fileName = uniqid();

        $extension = pathinfo($_FILES['Product']['name']['illustration']['file'], PATHINFO_EXTENSION);

        $projectDir = $this->appKernel->getProjectDir();

        move_uploaded_file($tmpName, $projectDir.'/public/uploads/'.$fileName.'.'.$extension);

        $entity->setIllustration($fileName.'.'.$extension);
    }
}
