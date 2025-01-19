<?php declare(strict_types=1);

namespace Inno\Controller\Dashboard;

use Doctrine\ORM\EntityManagerInterface;
use Inno\Entity\Attach;
use Inno\Entity\Entry;
use Inno\Entity\EntryAttachment;
use Inno\Entity\Enum\EnumAttachment;
use Inno\Entity\FileManager;
use Inno\Entity\MarketPlace\StoreProduct;
use Inno\Entity\MarketPlace\StoreProductAttach;
use Inno\Service\FileUploader;
use Knp\Component\Pager\PaginatorInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/image-manager')]
class ImageManagerController extends AbstractController
{
    final const int LIMIT = 24;

    /**
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    #[Route('', name: 'dashboard.image-manager', methods: ['GET'])]
    public function index(
        Request                $request,
        EntityManagerInterface $manager,
        PaginatorInterface     $paginator,
    ): JsonResponse
    {
        $images = $manager->getRepository(FileManager::class)->fetch($this->getUser(), EnumAttachment::Image);
        $pagination = $paginator->paginate($images, $request->query->getInt('page', 1), self::LIMIT);

        return $this->json([
            'success' => true,
            'total' => $pagination->getTotalItemCount(),
            'nextPage' => $pagination->getCurrentPageNumber() + 1,
            'pages' => ceil($pagination->getTotalItemCount() / (self::LIMIT - 1)),
            'limit' => self::LIMIT,
            'url' => $this->generateUrl('dashboard.image-manager'),
            'html' => $this->renderView('dashboard/content/image-manager/index.html.twig', ['images' => $pagination]),
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param ParameterBagInterface $params
     * @param SluggerInterface $slugger
     * @return JsonResponse
     */
    #[Route('', name: 'dashboard.image-manager.attach', methods: ['POST'])]
    public function attach(
        Request                $request,
        EntityManagerInterface $manager,
        ParameterBagInterface  $params,
        SluggerInterface       $slugger,
    ): JsonResponse
    {
        $targetDir = $params->get('image_storage_dir');
        $files = $request->files->get('images');

        foreach ($files as $file) {
            $fileUploader = new FileUploader($targetDir . '/' . $this->getUser()->getId(), $slugger, $manager);
            try {
                $attach = $fileUploader->upload($file)->handle();
            } catch (\Exception $ex) {
                return $this->json([
                    'success' => false,
                    'message' => $ex->getMessage(),
                    'picture' => null,
                ]);
            }

            $fileManager = new FileManager();
            $fileManager->setFile($attach)->setOwner($this->getUser())->setType(EnumAttachment::Image);
            $manager->persist($fileManager);
        }
        $manager->flush();

        return $this->json([
            'success' => true,
            'url' => $this->generateUrl('dashboard.image-manager'),
        ]);
    }

    #[Route('/inject', name: 'dashboard.image-manager.inject', methods: ['POST'])]
    public function inject(
        Request $request,
        EntityManagerInterface $manager,
        CacheManager           $cacheManager,
    ): JsonResponse
    {
        $payload = $request->getPayload()->all();
        $product = $entry = null;
        $pictures = [];

        if (isset($payload['ids']) && count($payload['ids'])) {
            if ($payload['target'] == 'entry') {
                $entry = $manager->getRepository(Entry::class)->find($payload['id']);
            }

            if ($payload['target'] == 'product') {
                $product = $manager->getRepository(StoreProduct::class)->find($payload['id']);
            }

            foreach ($payload['ids'] as $id) {
                $attach = $manager->getRepository(Attach::class)->find($id);
                if ($payload['target'] == 'entry') {
                    $attachment = new EntryAttachment();
                    $attachment->setAttach($attach)->setEntry($entry)->setInUse(0);
                    $manager->persist($attachment);
                }

                if ($payload['target'] == 'product') {
                    $attachment = new StoreProductAttach();
                    $attachment->setAttach($attach)->setProduct($product);
                    $manager->persist($attachment);
                }

                $path = "{$attach->getPath()}/{$attach->getName()}";
                $pictures[$id] = $cacheManager->getBrowserPath(parse_url($path, PHP_URL_PATH), 'image_thumb');

            }

            $manager->flush();
        }

        return $this->json([
            'pictures' => $pictures,
            'success' => true,
        ]);
    }
}
