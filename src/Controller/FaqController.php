<?php

namespace App\Controller;

use App\Entity\Faq;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaqController extends AbstractController
{

    /**
     * 
     * @param EntityManagerInterface $em
     * @return Response
     */
    #[Route('/faq', name: 'faq')]
    public function index(EntityManagerInterface $em): Response
    {
        $entries = $em->getRepository(Faq::class)->findAll();

        return $this->render('faq/index.html.twig', [
                    'entries' => $entries,
        ]);
    }
}
