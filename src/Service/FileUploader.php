<?php

namespace App\Service;

use App\Entity\Attach;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{

    /**
     *
     * @var string|null
     */
    private ?string $target;

    /**
     *
     * @param string $targetDirectory
     * @param SluggerInterface $slugger
     * @param EntityManagerInterface $em
     */
    public function __construct(
        private string                 $targetDirectory,
        private SluggerInterface       $slugger,
        private EntityManagerInterface $em,
    )
    {

    }

    /**
     *
     * @return int|null
     */
    private function getSize(): ?int
    {
        return filesize($this->target);
    }

    /**
     *
     * @return string|null
     */
    private function getMimeType(): ?string
    {
        return mime_content_type($this->target);
    }

    /**
     *
     * @param UploadedFile $file
     * @return self
     * @throws \Exception
     */
    public function upload(UploadedFile $file): self
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $this->fileName = sprintf("%s-%s.%s", $safeFilename, uniqid(), $file->guessExtension());

        try {
            $target = $file->move($this->getTargetDirectory(), $this->fileName);
        } catch (FileException $e) {
            throw new \Exception($e->getMessage());
        }

        $this->target = $target;
        return $this;
    }

    /**
     *
     * @param object|null $object
     * @return Attach|null
     */
    public function handle(?object $object): ?Attach
    {
        $attach = new Attach();
        $attach->setUserDetails($object)
            ->setName($this->fileName)
            ->setSize($this->getSize())
            ->setMime($this->getMimeType());

        $this->em->persist($attach);
        $this->em->flush();
        return $attach;
    }

    /**
     *
     * @return string
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
